<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;

$bootstrap_models = json_encode([
	'user' => [
		'identity' => Yii::$app->user->identity,
		'isGuest' => Yii::$app->user->isGuest,
	],
]);

/**
 * @var \yii\web\View $this
 * @var string $content
 */

AppAsset::register($this);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" ng-app ng-controller="InitCtrl">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>

<?php $this->beginBody() ?>
    <div class="wrap">"{{user.identity}}"
        <div class="navbar-inverse navbar-fixed-top" ng-controller="NavigationCtrl">
            <div>
				<a href="<?=Yii::$app->homeUrl;?>">HOME</a>
            </div>
			<div class="navbar-nav navbar-right">
				<a ng-href="<?=Url::to(['books/index']);?>" class="btn btn-default">Книжки</a>
				<a ng-href="<?=Url::to(['pages/index']);?>" class="btn btn-default">Страницы</a>
				<a ng-click="login()" ng-show="user.isGuest" class="btn btn-default">Login</a>
				<a ng-click="logout" ng-hide="user.isGuest" class="btn btn-default">Logout ({user.identity.username}})</a>
			</div>
		</div>

        <div class="container">
            <?= Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]) ?>
            <?= $content ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p class="pull-left">&copy; My Company <?= date('Y') ?></p>
            <p class="pull-right"><?= Yii::powered() ?></p>
        </div>
    </footer>
	<script type="text/javascript">var bootstrap=<?=$bootstrap_models;?></script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
