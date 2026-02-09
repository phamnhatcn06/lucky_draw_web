<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lucky_draw', 'root', '123456a@');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Adding is_partner column...\n";
    $pdo->exec("ALTER TABLE participants ADD COLUMN is_partner TINYINT(1) DEFAULT 0");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
