<?php

namespace app\models;

use Yii;
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

	public static $statuses = [
		self::STATUS_NONE,
		self::STATUS_PROCESS,
		self::STATUS_RECOGNITED,
		self::STATUS_RECOGNITED_PARTIAL,
	];

	public function rules()
	{
		return array(
			['filename', 'string', 'max' => 1024],
			['extension', 'string'],
			['create_dt', 'safe'],
			['parse_status', 'default', 'value' => self::STATUS_NONE],
			['parse_status', 'string'],
			['parse_status', 'in', 'range' => self::$statuses],
			['hash', 'string'],
		);
	}

	/**
	 * @return \yii\db\ActiveQueryInterface
	 */
	public function getPages()
	{
		return $this->hasMany(Page::className(), ['book_id' => '_id']);
	}

	public function beforeSave($insert)
	{
		if (empty($this->create_dt)) {
			$this->create_dt = new \MongoDate();
		}
		elseif (is_string($this->create_dt)) {
			$this->create_dt = new \MongoDate(strtotime($this->create_dt));
		}
		if (empty($this->extension)) {
			$this->extension = strtolower(substr($this->filename, strrpos($this->filename, '.') + 1));
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

	public function beforeDelete()
	{
		$pages = $this->getPages();
		if ($pages && $pages->count()) {
			foreach ($pages->all() as $page) {
				$page->delete();
			}
		}

		return parent::beforeDelete();
	}
}