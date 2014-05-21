<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\BooksSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="book-search js-search-form">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
		'options' => [
			'class' => 'g-hidden',
		],
    ]); ?>

    <?= $form->field($model, '_id') ?>

    <?= $form->field($model, 'filename') ?>

    <?= $form->field($model, 'extension') ?>

    <?= $form->field($model, 'create_dt') ?>

    <?= $form->field($model, 'parse_status') ?>

    <?php // echo $form->field($model, 'hash') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

	<button class="js-search-form-hide" data-related="<?=$form->id;?>">Поиск</button>
</div>
