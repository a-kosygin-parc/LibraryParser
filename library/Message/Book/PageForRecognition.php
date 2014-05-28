<?php
namespace app\library\Message\Book;
use app\core\Model;
use app\core\Validator\FileExists;

/**
 * Страница для распознавания
 */
class PageForRecognition extends Model
{
	/**
	 * @var integer Номер страницы
	 */
	public $page;

	/**
	 * @var string полное имя файла
	 */
	public $filename;

	/**
	 * Создаёт объект и присваивает ему сразу же свойства
	 * @param array $attributes
	 */
	public function __construct($attributes = array())
	{
		$this->setAttributes($attributes);
	}

	public function rules()
	{
		return [
			['page', 'integer', 'min' => 1],
			['filename', 'string'],
			['filename', FileExists::className()],
		];
	}
}