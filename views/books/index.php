<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\BooksSearch $searchModel
 */

$this->title = 'Books';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="book-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Book', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
				'class' => 'yii\grid\Column',
				'content' => function ($a) {
					return Html::a('pages', ['pages/index', 'PageSearch[book_id]' => (string)$a->_id]);
				},
			],
            'filename',
            'extension',
            ['class' => 'yii\grid\Column', 'content' => function ($a) {return date('Y-m-d H:i:s', $a->create_dt->sec);}],
            'parse_status',
            // 'hash',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
