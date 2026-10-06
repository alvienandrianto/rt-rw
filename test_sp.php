<?php
require_once 'config/database.php';

echo "--- PROCEDURES ---\n";
$procs = $pdo->query("SHOW PROCEDURE STATUS WHERE Db = 'rtrw_db'")->fetchAll();
foreach ($procs as $p) {
    echo "Procedure: " . $p['Name'] . "\n";
}

echo "\n--- TRIGGERS ---\n";
$trigs = $pdo->query("SHOW TRIGGERS")->fetchAll();
foreach ($trigs as $t) {
    echo "Trigger: " . $t['Trigger'] . " ON " . $t['Table'] . " (" . $t['Event'] . ")\n";
}

echo "\n--- TESTING CALL sp_get_dashboard_stats() ---\n";
$stmt = $pdo->query("CALL sp_get_dashboard_stats()");
$stats = $stmt->fetch();
print_r($stats);
