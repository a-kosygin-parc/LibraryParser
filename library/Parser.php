<?php
namespace app\library;

use app\core\FileHelper;
use app\core\ValidateException;
use app\library\Recognite\Processor;
use app\models\Book;
use app\models\Page;
use app\models\Worker;
use yii\log\Logger;
use yii\mongodb\Query;

/**
 * Пока сюда всё. Потом раскидаем
 */
class Parser
{
	Const LOG_CATEGORY = 'application.parser';

	private $_prefix = '';

	/**
	 * @var Worker
	 */
	private $_worker = null;

	/**
	 * Уже распознанные файлы
	 * @var array
	 */
	private $_skip_files = [];

	public function setPrefix($str)
	{
		$this->_prefix = $str;
		return $this;
	}

	private function fillSkipList()
	{
		if (empty($this->_skip_list)) {
			$query = new Query();

			$cursor = $query
				->select(['filename'])
				->from(\app\models\Book::collectionName())
				->where(array('IN', 'parse_status', [\app\models\Book::STATUS_RECOGNITED, \app\models\Book::STATUS_RECOGNITED_PARTIAL]))
				->all();

			foreach ($cursor as $item) {
				$this->_skip_files[] = $item['filename'];
			}
		}
		return $this;
	}

	public function start($library_path = 'L:/biblioteka/kolhoz')
	{
		set_time_limit(0);
		ini_set('memory_limit', '2048M');

		// Инициируем воркер
		$this->_worker = new Worker();
		$this->_worker->insert() || die(print_r($this->_worker->errors, true));

		// Инициируем временную директорию
		$tmpdir = $this
			->setPrefix($this->_worker->pid)
			->getTempDir();

		if (!file_exists($tmpdir)) {
			mkdir($tmpdir, 0777, true) or die('Permission deny for mkdir');
		}
		else {
			//$this->cleanTempDir();
		}

		// Бутстрапим файлы, которые уже обработаны
		$this->fillSkipList();


		// Обрабатываем рекурсивно все книжки
		FileHelper::map($library_path . '/', array($this, 'splitBookToImages'));

		// Подчищаем за собой
		FileHelper::removeDirectory($this->getTempDir());

		// Освобождаем воркер
		$this->_worker->delete();
	}

	public function splitBookToImages($filename)
	{
		// Пропускаем уже распознанные файлы
		if (($pos = array_search($filename, $this->_skip_files)) !== false) {
			unset($this->_skip_files[$pos]);
			\Yii::getLogger()->log(sprintf('File %s in skip list', $filename), Logger::LEVEL_INFO, self::LOG_CATEGORY);
			return false;
		}

		// И те, что были удалены в процессе пропускаем
		if (!file_exists($filename)) {
			\Yii::getLogger()->log(sprintf('File %s not exists', $filename), Logger::LEVEL_INFO, self::LOG_CATEGORY);
			return false;
		}

		// Подбираем правильный сплиттер
		$splitter = SplitterFabric::create($filename);

		if (!$splitter) {
			\Yii::getLogger()->log(sprintf('File %s extension not supported', $filename), Logger::LEVEL_INFO, self::LOG_CATEGORY);
			return false;
		}

		if (!$this->_worker->lock($filename)) {
			\Yii::getLogger()->log(sprintf('File %s is locked by other worker', $filename), Logger::LEVEL_INFO, self::LOG_CATEGORY);
			return false;
		}

		// Подсчитаем MD5 файл, чтобы убедиться потом что он не изменился
		$content = file_get_contents($filename);
		$hash = md5($content);
		unset($content);

		$book = Book::findOne([
			'filename' => $filename,
		]);

		if (!$this->_worker->isLocked($filename)) {
			\Yii::getLogger()->log(sprintf('File %s is bad lock', $filename), Logger::LEVEL_INFO, self::LOG_CATEGORY);
			return false;
		}

		if (!$book) {
			$book = new Book();
			$book->filename = $filename;
			$book->hash = $hash;
			$book->create_dt = new \MongoDate();
			$book->parse_status = Book::STATUS_NONE;
			//$book->extension = strtolower(substr($filename, strrpos($filename, '.') + 1));
			$book->insert();
			echo '#';
		}
		else {
			echo '@';
		}

		echo $filename;

		if ($hash != $book->hash) {
			\Yii::getLogger()->log('Hash "' . $filename . '" is changed.', Logger::LEVEL_WARNING, self::LOG_CATEGORY);
		}

		if ($book->parse_status == Book::STATUS_RECOGNITED) {
			echo $book->filename . ' ALREADY RECOGNITED' . PHP_EOL;
			return;
		}

		if ($book->parse_status == Book::STATUS_RECOGNITED_PARTIAL) {
			echo $book->filename . ' ALREADY RECOGNITED PARTIAL' . PHP_EOL;
			return;
		}

		$final_status = Book::STATUS_RECOGNITED;

		if ($book->parse_status != Book::STATUS_PROCESS || $this->_worker->processing == $book->filename) {
			$this->cleanTempDir();

			$queue_pages = $splitter
				->setAttributes([
					'destination' => $this->getTempDir(),
					'from' => 1,
					'to' => 10,
				])
				->split($filename);

			$final_status = Book::STATUS_RECOGNITED_PARTIAL;

			$book->parse_status = Book::STATUS_PROCESS;
			if (!$book->save()) {
				throw new ValidateException($book->errors);
			}

			// Перегоняем распознанные страницы в базу данных
			foreach ($queue_pages['pages'] as $item) {
				$this->recognite(
					$item['filename'],
					[
						'page_number' => $item['page'],
						'book' => $book,
					]
				);
			}

			$book->parse_status = $final_status;
			if (!$book->save()) {
				throw new ValidateException($book->errors);
			}
		}

		$this->_worker->unLock();
		echo PHP_EOL;
	}

	public function recognite($img_file, $params = array())
	{
		static $recognite_processor = null;

		if ($recognite_processor === null) {
			$recognite_processor = new Processor();
			$recognite_processor->setLogFile($this->getTempDir() . '.log');
		}

		// Обновим сведения о работе воркера
		if (!$this->_worker->save()) {
			throw new ValidateException($this->_worker->errors);
		}

		if (!file_exists($img_file)) {
			\Yii::getLogger()->log(sprintf('File %s not found', $img_file), Logger::LEVEL_INFO, self::LOG_CATEGORY . '.recognite');
			return false;
		}

		$book = $params['book'];

		if (!$recognite_processor->checkFile($img_file)) {
			\Yii::getLogger()->log(sprintf('File %s not supported', $img_file), Logger::LEVEL_INFO, self::LOG_CATEGORY . '.recognite');
			return false;
		}

		$page_number = (int) $params['page_number'];

		foreach (array($recognite_processor::LANG_RUS, $recognite_processor::LANG_ENG) as $language) {

			$attributes = [
				'book_id' => $book->getPrimaryKey(),
				'page' => $page_number,
				'lang' => $language,
			];

			$page = Page::findOne($attributes);

			if ($page) {
				// Уже распознано. Игнорим
				echo '.';
				continue;
			}

			$result = $recognite_processor->recognite($img_file, $language, $img_file);

			if ($result && file_exists($result)) {
				$attributes['text'] = file_get_contents($result);

				$page = new Page();
				$page->setAttributes($attributes);

				if (!$page->insert()) {
					\Yii::getLogger()->log('Error insert ' . var_export($page->getErrors(), true), Logger::LEVEL_ERROR, self::LOG_CATEGORY . '.parser.mongo');
				}

				unlink($result);
			}
			else {
				\Yii::getLogger()->log('Not recognite (' . $book->filename . ' Page=' . $page->page . ')', Logger::LEVEL_WARNING, self::LOG_CATEGORY . '.parser');
			}
		}
		echo '=';

		if (file_exists($img_file . '.txt')) {
			unlink($img_file . '.txt');
		}

		if (file_exists($img_file)) {
			unlink($img_file);
		}

		return true;
	}


	/**
	 * Очистка каталога
	 *
	 * Если стоит $remove_self = true, то удалит и себя
	 * @param bool $remove_self
	 * @return $this
	 */
	private function cleanTempDir($remove_self = false)
	{
		FileHelper::map($this->getTempDir() . '/', 'unlink');
		return $this;
	}

	/**
	 * Вернёт путь к временной директории (создаст если нет)
	 *
	 * @return mixed|string
	 */
	private function getTempDir()
	{
		static $tmpdir = null;

		if (!$tmpdir) {
			$tmpdir = \Yii::$app->getRuntimePath() . '/tmp' . $this->_prefix;
			$tmpdir = str_replace(array('\/', '//'), array('/', '/'), $tmpdir);
		}

		return $tmpdir;
	}
}