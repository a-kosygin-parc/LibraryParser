<?php
/**
 * Компонент 'authManager'
 */

if (file_exists($filename = __DIR__ . '/dev_' . basename(__FILE__))) {
	// dev_ файл имеет больший приоритет
	return require($filename);
}

return [
	'class' => 'yii\rbac\PhpManager',
	'defaultRoles' => ['admin', 'user', 'guest'],
];