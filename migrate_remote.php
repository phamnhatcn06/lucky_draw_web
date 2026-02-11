<?php
// migrate_remote.php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lucky_draw', 'root', '123456a@');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if setting exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE name = 'remote_command'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO settings (name, value) VALUES ('remote_command', '')");
        echo "Inserted remote_command setting.\n";
    } else {
        echo "remote_command setting already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
