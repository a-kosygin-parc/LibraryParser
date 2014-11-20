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

	public function init()
	{
		parent::init();
		//$this->getCollection()->createIndex('processing', ['unique' => true]);
	}

	public function generatePID()
	{
		return \Yii::$app->db->createCommand('SELECT UUID()')->queryScalar();
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

	/**
	 * Вернёт true, если удалось залочить файл
	 *
	 * @param $filename
	 * @return bool
	 */
	public function lock($filename)
	{
		try {
			$this->save();
		}
		catch (\yii\mongodb\Exception $e) {
			return false;
		}

		$this->processing = $filename;
		return true;
	}

	/**
	 * Вернёт true, если файл захвачен нашим процессом
	 *
	 * @param $filename
	 * @return bool
	 */
	public function isLocked($filename)
	{
		return $this->processing == $filename;
	}

	/**
	 * Разлочить файлы, захваченные воркером
	 *
	 * @return bool
	 */
	public function unLock()
	{
		$this->processing = $this->generatePID();
		return $this->save();
	}

	public function __destruct()
	{
		// Освободим залоченные нами файлы перед уничтожением воркера
		$this->unLock();
	}
}