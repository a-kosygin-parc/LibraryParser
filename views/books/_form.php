<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\Book $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="book-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'filename') ?>

    <?= $form->field($model, 'extension') ?>

    <?= $form->field($model, 'create_dt') ?>

    <?= $form->field($model, 'parse_status') ?>

    <?= $form->field($model, 'hash') ?>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
