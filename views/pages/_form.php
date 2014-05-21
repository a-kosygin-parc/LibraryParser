<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\Page $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="page-form js-search-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'book_id') ?>

    <?= $form->field($model, 'page') ?>

    <?= $form->field($model, 'lang') ?>

    <?= $form->field($model, 'text')->textarea(['rows' => 40]) ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

	<button value="Скрыть" class="js-search-form-hide" />
</div>
