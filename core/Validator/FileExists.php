<?php
namespace app\core\Validator;

use yii\validators\Validator;

/**
 * Валидатор проверяет что файл существует
 *
 * @package app\core\Validator
 */
class FileExists extends Validator
{

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = \Yii::t('yii', '{attribute} file not exists.');
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		if (file_exists($value)) {
			return null;
		}

		return [$this->message, []];
	}
}