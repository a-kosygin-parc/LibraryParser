<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\mongodb\ActiveRecord;

class Page extends ActiveRecord
{
	public function rules()
	{
		return array(
			array(array('book_id','page','lang'), 'required'),
			array('page', 'integer'),
			array('text', 'default', 'value' => ''),
			array('text', 'string'),
			array('lang', 'string'),
		);
	}

	public function getBook()
	{
		return $this->hasOne('Book', ['_id' => 'book_id']);
	}

    /**
     * @return string the name of the index associated with this ActiveRecord class.
     */
    public static function collectionName()
    {
        return 'book_pages';
    }

    /**
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return ['_id', 'book_id', 'page', 'text', 'lang'];
    }
}