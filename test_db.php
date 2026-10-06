<?php
require_once 'config/database.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "SUCCESS: Connected to rtrw_db! Tables found: " . implode(", ", $tables) . "\n";
