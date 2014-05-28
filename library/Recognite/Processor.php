<?php
namespace app\library\Recognite;

/**
 *
 */
class Processor
{
	Const
		LANG_RUS = 'rus',
		LANG_ENG = 'eng';

	/**
	 * @var array Поддерживаемые расширения
	 */
	private static $_extensions = ['tif', 'tiff', 'jpg', 'jpeg'];

	private $log_file = null;

	/**
	 * Вернёт true, если файл возможно распознать с помощью этого процессора
	 *
	 * @param $ext - расширение или имя файла
	 * @return bool
	 */
	public static function checkFile($ext)
	{
		$pos = strrpos($ext, '.');

		if ($pos !== false) {
			$ext = substr($ext, $pos + 1);
		}

		return (in_array(strtolower($ext), self::$_extensions));
	}

	/**
	 * Setter. Путь к инкрементируемому логу вывода распознавалки
	 *
	 * @param $value
	 * @return $this
	 */
	public function setLogFile($value)
	{
		$this->log_file = $value;
		return $this;
	}

	/**
	 * Распознать файл $filename и, если не указано $saveAs, положить рядом с файлом текст, добавив .txt
	 * Вернёт путь к файлу с распознанным текстом. Или null в случае не удачи
	 *
	 * @param string $img_file
	 * @param string $lang - язык распознавания (self::LANG_RUS, self::LANG_ENG)
	 * @param string $saveAs (optional) куда сохранить результат (в конце будет добавлено .txt)
	 * @return string|null
	 */
	public function recognite($img_file, $lang = 'ru', $saveAs = null)
	{
		$img_file = str_replace(['/', '\\\\'], ['\\', '\\'], $img_file); // windows style
		$dest_file = $saveAs ? $saveAs : $img_file;
		$cmd = \Yii::$app->params['EXEC_TESSERACT'] . ' ' . $img_file . ' ' . $dest_file . ' -l ' . $lang;
		if ($this->log_file) {
			$cmd .= ' 2>> ' . $this->log_file;
		}
		$res = '';

		exec($cmd, $res);
		return file_exists($dest_file . '.txt') ? ($dest_file . '.txt') : null;
	}
}