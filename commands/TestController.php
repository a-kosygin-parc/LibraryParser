<?php
namespace app\commands;

use app\library\SplitterFabric;
use yii\console\Controller;

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
		//$splitter = Splitter::create('D:\apps\test\Adam D. Vosprijatie, soznanie, pamyat#. Razmyshlenija biologa (Mir, 1983)(ru)(L)(T)(75s).djvu');
		$splitter = SplitterFabric::create('L:\app\test\Design Patterns by D.pdf', ['from' => -1, 'to' => 2, 'destination' => 'L:\app\test']);
		if ($splitter)
		var_dump(
			$splitter->getAttributes(),
			$splitter->validate(),
			$splitter->getErrors(),
			$splitter->className()
			, array_map(function ($a){return $a->getAttributes();}, $splitter->split('L:\app\test\Design Patterns by D.pdf')['pages'])
		);
	}
}