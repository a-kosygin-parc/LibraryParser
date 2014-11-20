<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [
		'js/app/lib/angular.min.js',
		'js/app/lib/ui-bootstrap-0.11.0.min.js',
		'js/app/lib/ui-bootstrap-tpls-0.11.0.min.js',
		'js/site.js',
    ];
    public $depends = [
//		'yii\web\JqueryAsset',
//        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
