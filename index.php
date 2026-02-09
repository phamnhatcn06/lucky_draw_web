<?php
// index.php
$yii = dirname(__FILE__) . '/framework/yii.php';          // đường dẫn tới yii.php
$config = dirname(__FILE__) . '/protected/config/main.php';

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

// Suppress deprecation warnings (PHP 8.1+ compatibility)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 0); // Don't print errors to screen, log them instead if possible
ob_start(); // Buffer output to prevent headers already sent issues

require_once($yii);
Yii::createWebApplication($config)->run();
