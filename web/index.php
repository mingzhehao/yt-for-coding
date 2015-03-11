<?php

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
error_reporting(E_ALL & ~E_NOTICE);
ini_set('date.timezone', 'Asia/Shanghai');
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../common/config/alias.php');
require(__DIR__ . '/../config/functions.php');

$config = require(__DIR__ . '/../config/web.php');



(new yii\web\Application($config))->run();
