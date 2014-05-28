<?php
namespace app\library\Splitter;

class Pdf extends BaseSplitter
{
	/**
	 * @var string Путь к распознавалке
	 */
	private $exec;

	public function init()
	{
		$this->exec = \Yii::$app->params['EXEC_PDF_DECODE'];
	}

	public function exec($source_filename)
	{
		$cmd = $this->exec . ' -r300x300 -sDEVICE=jpeg -o ' . $this->destination . '\p%d.jpg';
		$cmd .= $this->from ? (' -dFirstPage=' . $this->from) : '';
		$cmd .= $this->to ? (' -dLastPage=' . $this->to) : '';
		$cmd .= ' "' . $source_filename . '"';

		return system($cmd);
	}
}
