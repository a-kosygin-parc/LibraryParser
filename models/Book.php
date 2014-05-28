<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\mongodb\ActiveRecord;

/**
 * Class Book
 * @package app\models
 * class \app\models\Book
 * @property \MongoId $_id
 * @property string $filename
 * @property string $extension
 * @property \MongoDate|string $create_dt
 * @property string $parse_status
 * @property string $hash

 */
class Book extends ActiveRecord
{
	Const
		STATUS_NONE = '',
		STATUS_PROCESS = 'p',
		STATUS_RECOGNITED = 'r',
		STATUS_RECOGNITED_PARTIAL = 'f';

	public function rules()
	{
		return array(
			array('filename', 'string', 'max' => 1024),
			array('extension', 'string'),
			array('create_dt', 'safe'),
			array('parse_status', 'default', 'value' => self::STATUS_NONE),
			array('parse_status', 'string'),
			array('parse_status', 'in', 'range' => array(self::STATUS_NONE, self::STATUS_PROCESS, self::STATUS_RECOGNITED)),
			array('hash', 'string'),
		);
	}

	public function setFilename($value)
	{
		if (file_exists($value)) {
			$path_parts = pathinfo($value);
			$this->extension = strtolower($path_parts['extension']);
		}
	}

	public function getPages()
	{
		return $this->hasMany('Page', ['book_id' => '_id']);
	}

	public function beforeSave($insert)
	{
		if (empty($this->create_dt)) {
			$this->create_dt = new \MongoDate();
		}
		elseif (is_string($this->create_dt)) {
			$this->create_dt = new \MongoDate(strtotime($this->create_dt));
		}
		return parent::beforeSave($insert);
	}

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'book_files';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return ['_id', 'filename', 'extension', 'create_dt', 'parse_status', 'hash'];
    }
}