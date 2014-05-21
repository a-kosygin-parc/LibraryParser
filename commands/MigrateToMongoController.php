<?php
/**
 * Migration from MySQL to MongoDB
 */

namespace app\commands;

use yii\console\Controller;
use app\models\Book;
use app\models\Page;

class MigrateToMongoController extends Controller
{
	private $_library_path = 'L:/biblioteka/kolhoz';

    public function actionIndex()
    {
		set_time_limit(0);
        $books = \Yii::$app
			->db
			->createCommand('SELECT * FROM files ORDER BY id_file')
			->queryAll();

		$count = count($books);

		foreach ($books as $i => $row) {
			echo $i . '/' . $count . ' (' . memory_get_usage() . ') ';

			$file_id = $row['id_file'];
			unset($row['id_file']);
			$row['create_dt'] = new \MongoDate(strtotime($row['create_dt']));
			$row['parse_status'] = $row['parse_status'] == 'recognited'
				? Book::STATUS_RECOGNITED
				: (
					$row['parse_status'] == 'process'
					? BOOK::STATUS_PROCESS
					: BOOK::STATUS_NONE
				);

			$book = Book::findOne([
				'filename' => $row['filename'],
			]);

			if (!$book) {
				$book = new Book();
				$book->setAttributes($row);
				if (!$book->insert()) {
					var_dump($book->getErrors());
					die();
				};
				echo '#';
			}
			else {
				echo '@';
			}

			$pages = \Yii::$app
				->db
				->createCommand(
					'SELECT page, text_rus, text_eng FROM recognited WHERE id_file = :id ORDER BY page',
					array(
						':id' => $file_id,
					)
				)
				->queryAll();

			foreach ($pages as $p) {
				$p['book_id'] = $book->getPrimaryKey();
				$p['page'] = (int) preg_replace('#[^0-9]#', '', $p['page']);
				$page = Page::findOne([
					'book_id' => $p['book_id'],
					'page' => $p['page']
				]);

				if (!$page) {
					$page = new Page();
					$p['lang'] = 'rus';
					$p['text'] = $p['text_eng'];
					$page->setAttributes($p);
					if (!$page->insert()) {
						var_dump($page->getErrors());
						die();
					}

					$page = new Page();
					$p['lang'] = 'eng';
					$p['text'] = $p['text_rus'];
					$page->setAttributes($p);
					if (!$page->insert()) {
						var_dump($page->getErrors());
						die();
					}
					echo '.';
				}
				else {
					echo ':';
				}
			}

			echo PHP_EOL;
		}
    }
}
