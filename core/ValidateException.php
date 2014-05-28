<?php
namespace app\core;

/**
 * Ошибка валидации модели
 */
class ValidateException extends \Exception
{
	public function __construct($message = "", $code = 0, Exception $previous = null)
	{
		if (is_array($message)) {
			$text = [];
			foreach ($message as $key => $errors) {
				if (is_array($errors)) {
					$errors = implode(PHP_EOL . ' ', $errors);
				}
				$text[] = $key . PHP_EOL . ' ' . $errors;
			}
			$this->message = 'Model validation Error:' . PHP_EOL . implode(PHP_EOL, $text);
		}
	}
}