<?php
namespace app\library;

/**
 * Фабрика сплиттеров
 *
 * Использование:
 * <code>
 * use \app\library\SplitterFabric;
 *
 * // Исходные данные
 * $filename = 'C:\books\Design Patterns by D.pdf';
 * $extension = 'pdf'; // Не обязательно выделять расширение. Можно прямо файл скормить
 * $params = [
 * 		'from' => 1,
 * 		'to' => 2,
 * 		'destination' => 'L:\app\test'
 * 	];
 *
 * $results = SplitterFabric::create($extension, $params)
 * 	->split($filename);
 *
 * // result = ['result' => cmd_response, 'pages' => [...]]
 * </code>
 */
class SplitterFabric
{
	/**
	 * @var array
	 */
	private static $_aliases = [
		'djv' => 'djvu',
	];

	/**
	 * @var array - Инстансы сплиттеров
	 */
	private static $_instances = [];

	/**
	 * По расширению отдаёт инстанс сплиттера
	 *
	 * @param string $extension - расширение или имя файла
	 * @param array - аттрибуты, прокидываемые в конструктор сплиттера (from, to, destination)
	 * @return \app\library\Splitter\BaseSplitter
	 */
	public static function create($extension, $attributes = array())
	{
		$pos = strrpos($extension, '.');

		if ($pos !== false) {
			$extension = substr($extension, $pos + 1);
		}

		$class_name = '\\' . __NAMESPACE__ . '\\Splitter\\' . ucfirst($extension);

		if (isset(self::$_aliases[$extension])) {
			$extension = self::$_aliases[$extension];
		}

		if (!isset(self::$_instances[$extension])) {
			if (!class_exists($class_name)) {
				return null;
			}

			self::$_instances[$extension] = new $class_name();
		}

		return self::$_instances[$extension]
			->setAttributes($attributes);
	}
}