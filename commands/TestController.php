<?php
namespace app\commands;

use yii\console\Controller;
use yii\mongodb\Query;

class TestController extends Controller
{
	public function actionIndex($n = 3)
	{
		$descriptor_spec = array(
			0 => array("pipe", "r"), // stdin
			1 => array("pipe", "w"), // stdout
			2 => array('pipe', "r"), // stderr
		);

		$php_path = $_SERVER['_'] . ' -c D:\\server\\php5.5\\php.ini';
		$cmd_path = $_SERVER['argv'][0];

		if (stristr(PHP_OS, 'WIN')) {
			for ($i=0;$i<$n;$i++) {
				$proc = proc_open("start \"library parser worker\" $php_path $cmd_path test/start-worker", $descriptor_spec, $pipes);
			}
		}

		exit(0);
	}

	public function actionStartWorker()
	{
		$parser = new \app\library\Parser();
		$parser->start();
		echo 'End of work';
		sleep(1);
	}

	public function actionTest()
	{

	}
}