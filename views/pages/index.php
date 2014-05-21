<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\PageSearch $searchModel
 */

$this->title = 'Pages';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Create Page', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            '_id',
            'book_id',
            ['content' => function($model){return $model->page;}],
            ['format' => 'text', 'label' => 'label', 'attribute' => 'text', 'content' => function ($model){return mb_substr($model->text, 0, 81, 'utf-8');}],
            'lang',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
