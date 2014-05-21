<?php
namespace app\library;

use app\models\Book;
use app\models\Page;
use app\models\Worker;
use yii\mongodb\Query;

/**
 * Пока сюда всё. Потом раскидаем
 */
class Parser
{
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
				->where(array('parse_status' => \app\models\Book::STATUS_RECOGNITED))
				->all();

			foreach ($cursor as $item) {
				$this->_skip_files[] = $item['filename'];
			}
		}
		return true;
	}

	public function start($library_path = 'L:/biblioteka/kolhoz')
	{
		set_time_limit(0);
		ini_set('memory_limit', '2048M');

		$this->_worker = new Worker();
		$this->_worker->insert() || die(print_r($this->_worker->errors, true));

		$tmpdir = $this
			->setPrefix($this->_worker->pid)
			->getTempDir();

		if (!file_exists($tmpdir)) {
			mkdir($tmpdir, 0777, true) or die('Permission deny for mkdir');
		}
		else {
			//$this->cleanTempDir();
		}

		$this->fillSkipList();

		$this->mapReduce($library_path . '/', array($this, 'splitBookToImages'));

		$this->cleanTempDir();
		rmdir($this->getTempDir());
		$this->_worker->delete();
	}

	private function mapReduce($dir, $func, $params = null) {
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {

				while (($file = readdir($dh)) !== false) {
					if ($file == '.' || $file=='..') continue;
					if (is_dir($dir.$file)) {
						$this->mapReduce($dir . $file.'/', $func);
					} else {
						try {
							if ($params !== null) {
								$func($dir . $file, $params);
							}
							else {
								$func($dir . $file);
							}
						}
						catch (\Exception $e) {
							echo $e->getMessage() . "\n";
						}
					}
				}
				closedir($dh);
			}
		}
		return true;
	}

	/**
	 * Вернёт true, если удалось залочить файл
	 * @param $filename
	 * @return bool
	 */
	private function lock($filename)
	{
		$this->_worker->processing = $filename;
		return $this->_worker->save();
	}

	/**
	 * Вернёт true, если файл захвачен нашим процессом
	 * @param $filename
	 * @return bool
	 */
	private function isLocked($filename)
	{
		return $this->_worker->processing == $filename;
	}

	/**
	 * Разлочить
	 * @return bool
	 */
	private function unLock()
	{
		$this->_worker->processing = '';
		return $this->_worker->save();
	}

	private function splitBookToImages($filename)
	{
		if (($pos = array_search($filename, $this->_skip_files)) !== false) {
			unset($this->_skip_files[$pos]);
			echo 'skipped';
			return false;
		}

		if (!file_exists($filename)) {
			throw new \Exception('File ' . $filename . ' not found.');
		}

		// Нужно достать расширение файла, чтобы знать чем его обрабатывать

		$path_parts = pathinfo($filename);

		if (!in_array(strtolower($path_parts['extension']), array('djvu', 'djv'))) {
			throw new \Exception($filename . ' not is DJVU expected.');
		}

		if (!$this->lock($filename)) {
			throw new \Exception('Файл залочен другим процессом');
		}

		// Подсчитаем MD5 файл, чтобы убедиться потом что он не изменился
		$content = file_get_contents($filename);
		$hash = md5($content);
		unset($content);

		$book = Book::findOne([
			'filename' => $filename,
		]);

		if (!$this->isLocked($filename)) {
			throw new \Exception('Кто-то перелочил файл');
		}

		if (!$book) {
			$book = new Book();
			$book->filename = $filename;
			$book->hash = $hash;
			$book->create_dt = new \MongoDate();
			$book->parse_status = Book::STATUS_NONE;
			$book->extension = strtolower($path_parts['extension']);
			$book->insert();
			echo '#';
		}
		else {
			echo '@';
		}

		echo $filename;

		if ($hash != $book->hash) {
			\Yii::getLogger()->log('Hash "' . $filename . '" is changed.', Logger::LEVEL_WARNING, 'application.parser');
		}

		if ($book->parse_status == Book::STATUS_RECOGNITED) {
			echo $book->filename . ' ALREADY RECOGNITED' . PHP_EOL;
			return;
		}

		if ($book->parse_status != Book::STATUS_PROCESS || $this->_worker->processing == $book->filename) {
			$this->cleanTempDir();

			system(\Yii::$app->params['EXEC_DJVUDECODE'] . ' --output-format=tif --dpi=300 "' . $filename . '" ' . $this->getTempDir());

			$book->parse_status = Book::STATUS_PROCESS;
			$book->save();
		}

		echo PHP_EOL;

		// Перегоняем распознанные страницы в базу данных
		$this->mapReduce($this->getTempDir() . '/', array($this, 'recognite'), array('book' => $book));

		$book->parse_status = Book::STATUS_RECOGNITED;
		$book->save();

		$this->unLock();
		echo PHP_EOL;
	}

	private function recognite($img_file, $params = array())
	{
		// Обновим сведения о работе воркера
		$this->_worker->save();

		if (!file_exists($img_file)) {
			throw new \Exception('File ' . $img_file . ' not found.');
		}

		$book = $params['book'];

		$path_parts = pathinfo($img_file);

		if (!in_array(strtolower($path_parts['extension']), array('tif', 'tiff'))) {
			echo '?';
			return;
		}

		$page_number = (int) preg_replace('#[^0-9]#', '', $path_parts['filename']);

		foreach (array('rus', 'eng') as $language) {

			$attributes = [
				'book_id' => $book->getPrimaryKey(),
				'page' => $page_number,
				'lang' => $language,
			];

			$page = Page::findOne($attributes);

			if ($page) {
				if (file_exists($img_file)) {
					unlink($img_file);
				}
				if (file_exists($img_file . '.txt')) {
					unlink($img_file . '.txt');
				}
				echo '.';
				return;
			}

			$img_file = str_replace(['/', '\\\\'], ['\\', '\\'], $img_file); // windows style
			$cmd = \Yii::$app->params['EXEC_TESSERACT'] . ' ' . $img_file . ' ' . $img_file . ' -l ' . $language;
			$cmd .= ' 2>> ' . $this->getTempDir() . '.log';
			$res = '';

			exec($cmd, $res);

			if (file_exists($img_file . '.txt')) {
				$attributes['text'] = file_get_contents($img_file . '.txt');

				$page = new Page();
				$page->setAttributes($attributes);

				if (!$page->insert()) {
					\Yii::getLogger()->log('Error insert ' . var_export($page->getErrors(), true), Logger::LEVEL_ERROR, 'application.parser.mongo');
				}

				unlink($img_file . '.txt');
			}
			else {
				\Yii::getLogger()->log('Not recognite (' . $book->filename . ' Page=' . $page->page . ')', Logger::LEVEL_WARNING, 'application.parser');
			}
		}
		echo '=';

		unlink($img_file);
	}

	private function cleanTempDir()
	{
		$this->mapReduce($this->getTempDir() . '/', 'unlink');
		return $this;
	}

	private function getTempDir()
	{
		static $tmpdir = null;

		if (!$tmpdir) {
			$tmpdir = \Yii::$app->getRuntimePath() . '/djvu_tmp' . $this->_prefix;
			$tmpdir = str_replace(array('\/', '//'), array('/', '/'), $tmpdir);
		}

		return $tmpdir;
	}

	private static function getMicrotime()
	{
		static $t = null;
		if ($t === null) {
			$t = microtime(true);
		}
		return - $t + ($t = microtime(true));
	}
}