<?php
namespace app\core;

/**
 * Обёртка над стандартной моделью Yii
 */
class Model extends \yii\base\Model
{
	/**
	 * @see \yii\base\Model::setAttributes()
	 *
	 * @param array $values
	 * @param bool $safeOnly
	 * @return $this|void
	 */
	public function setAttributes($values, $safeOnly = true)
	{
		parent::setAttributes($values, $safeOnly);

		// Организуем Chainings
		return $this;
	}
}