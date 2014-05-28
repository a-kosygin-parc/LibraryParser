<?php
namespace app\library\Splitter;

class Djvu extends BaseSplitter
{
	/**
	 * @var string Путь к распознавалке
	 */
	private $exec;

	public function init()
	{
		$this->exec = \Yii::$app->params['EXEC_DJVU_DECODE'];
	}

	/**
	 * Раскидать документ по пути $source_filename на изображения
	 *
	 * @param string $source_filename
	 * @return array - смог ли
	 */
	protected function exec($source_filename)
	{
		$cmd = $this->exec . ' --output-format=tif --dpi=300';

		if ($this->from || $this->to) {
			$cmd .= ' --page-range=' . ($this->from ? :'') . '-' . ($this->to ? : '');
		}

		$cmd .= ' "' . $source_filename . '"';
		$cmd .=  ' ' . $this->destination;

		return system($cmd);
	}
}