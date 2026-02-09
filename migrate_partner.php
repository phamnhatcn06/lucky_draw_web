<?php
// migrate.php
$config = require_once('protected/config/main.php');
require_once('d:/1.CODE/1. PHP/luckydraw/framework/yii.php');
Yii::createWebApplication($config);

try {
    echo "Adding is_partner column...\n";
    Yii::app()->db->createCommand("
        ALTER TABLE participants ADD COLUMN is_partner TINYINT(1) DEFAULT 0
    ")->execute();
    echo "Column added successfully.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
