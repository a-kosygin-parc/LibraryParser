<?php

namespace app\models;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * Class Worker
 * @package app\models
 *
 * @property \MongoId $_id
 * @property string $pid
 * @property string $path
 * @property \MongoDate|string $update_dt
 * @property string $status
 * @property string $processing
 */
class Worker extends ActiveRecord
{
	Const TTL = 3600; // 1 hour time to life

	public function rules()
	{
		return array(
			array('pid', 'safe'),
			array('pid', 'default', 'value' => $this->generatePID()),

			array('path', 'string'),
			array('path', 'default', 'value' => $_SERVER['PHP_SELF']),

			array('update_dt', 'safe'),
			array('update_dt', 'default', 'value' => new \MongoDate()),

			array('status', 'string'),

			array('processing', 'string'),
			array('processing', 'unique'),
		);
	}

	public function generatePID()
	{
		return str_replace([',', '.'], ['', ''], microtime(true)) . '-' . uniqid();
	}

	/**
	 * @return string the name of the index associated with this ActiveRecord class.
	 */
	public static function collectionName()
	{
		return 'workers';
	}

	public function beforeValidate()
	{
		$this->gc();
		return parent::beforeValidate();
	}

	/**
	 * @return array list of attribute names.
	 */
	public function attributes()
	{
		return ['_id', 'pid', 'path', 'update_dt', 'status', 'processing'];
	}

	/**
	 * Сборщик мусора
	 */
	private function gc()
	{
		$dt = new \MongoDate(time() - self::TTL);
		self::deleteAll(['update_dt' => ['$lt' => $dt]]);

	}
}