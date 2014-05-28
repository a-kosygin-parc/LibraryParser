<?php
namespace app\library\Splitter;

use app\core\ValidateException;
use app\core\Validator\FileExists;
use app\library\Message\Book\PageForRecognition;
use app\core\Model;

/**
 * Базовый класс для всех Splitters.
 *
 * Разбивает указанный файл на изображения. С возможностью указать страницы "от" и "до", и папку назначения.
 * @property integer $from
 * @property integer $to
 * @property string $destination
 * @property boolean $clean_before_split
 */
abstract class BaseSplitter extends Model
{
	/**
	 * (optional) С какой страницы начинать разбор
	 * @var integer
	 */
	public $from = null;

	/**
	 * (optional) До какой страницы разбирать
	 * @var integer
	 */
	public $to = null;

	/**
	 * (required) Куда складывать результаты разбора
	 * @var string
	 */
	public $destination = null;

	/**
	 * Зачистить выходной каталог, перед сплитом (default true)
	 * @var bool
	 */
	public $clean_before_split = true;

	/**
	 * Допустимые форматы на выходе
	 * @var array
	 */
	protected $extensions = array('tif', 'tiff', 'jpg', 'jpeg');

	public function __construct($attributes = array())
	{
		$this->setAttributes($attributes)
			->init();
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['from', 'integer', 'min' => 1, 'skipOnEmpty' => true],
			['to', 'integer', 'min' => 1, 'skipOnEmpty' => true],
			['destination', FileExists::className(), 'skipOnEmpty' => true],
		];
	}

	public function init()
	{
		//
	}

	/**
	 * Раскидать документ по пути $source_filename на изображения
	 *
	 * @param string $source_filename
	 * @return array - смог ли
	 */
	public function split($source_filename)
	{
		if (!$this->validate()) {
			throw new ValidateException($this->getErrors());
		}

		if ($this->clean_before_split) {
			$this->cleanDestinationDirectory();
		}

		$result = array(
			'result' => $this->exec($source_filename),
			'pages' => $this->scanPages(),
		);

		return $result;
	}

	/**
	 * Отсканирует директорию $this->destination, и отдаст список файлов с номарами страниц им соответствующими.
	 * Учитывает $this->from
	 *
	 * @return array
	 */
	protected function scanPages()
	{
		$files = scandir($this->destination);

		$list = array();

		$shift = $this->from ? ($this->from - 1) : 0;

		if ($files && count($files)) {
			foreach ($files as $filename) {
				if ($filename == '.' || $filename == '..') {
					continue;
				}

				$path_parts = pathinfo($filename);
				$page_number = (int) preg_replace('#[^0-9]#', '', $path_parts['filename']);

				if ($page_number && in_array(strtolower($path_parts['extension']), $this->extensions)) {
					$list[] = new PageForRecognition([
						'filename' => $this->destination . '/' . $filename,
						'page' => $shift + $page_number,
					]);
				}
			}
		}

		return $list;
	}

	/**
	 * Зачистить выходной каталог перед парсингом
	 * @return $this
	 */
	protected function cleanDestinationDirectory()
	{
		$files = scandir($this->destination);

		$regexp = '#\.(' . implode('|', $this->extensions) . ')$#';

		if ($files && count($files)) {
			foreach ($files as $filename) {
				if ($filename == '.' || $filename == '..') {
					continue;
				}

				if (preg_match($regexp, $filename)) {
					unlink($this->destination . '/' . $filename);
				}
			}
		}

		return $this;
	}

	/**
	 * Собственно реализация раскидывания документа на картинки.
	 * На входе полный путь к файлу, на выходе ответ от shell
	 *
	 * @param string $source_filename
	 * @return mixed
	 */
	abstract protected function exec($source_filename);
}