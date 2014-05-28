<?php
namespace app\core;

/**
 * Хелпер с функциями для облегчающими работу с файловой системой
 */
class FileHelper extends \yii\helpers\FileHelper
{
	/**
	 * Рекурсивный обход файлов и папок $dir с применением callable $func с параметрами $params
	 *
	 * @param string $dir - исходная директория
	 * @param callable $func - колбек. Первым аргументом идёт полное имя файла
	 * @param null|array $params - дополнительные параметры, пробрасываемые в $func 2-м аргументом
	 * @param boolean $skip_throws - (optional) игнорировать исключения
	 */
	public static function map($dir, $func, $params = null, $skip_throws = true) {
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {

				while (($file = readdir($dh)) !== false) {
					if ($file == '.' || $file=='..') continue;
					if (is_dir($dir.$file)) {
						self::map($dir . $file.'/', $func);
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
							throw $e;
						}
					}
				}
				closedir($dh);
			}
		}
	}
}