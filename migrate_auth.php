<?php
require_once __DIR__ . '/config/database.php';

echo "Starting Auth Migration for RT/RW System...\n";

try {
    // 1. Check and add email column
    $stmt = $pdo->query("SHOW COLUMNS FROM warga LIKE 'email'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE warga ADD COLUMN email VARCHAR(100) NULL UNIQUE AFTER nama");
        echo "[+] Added column 'email' to warga\n";
    } else {
        echo "[-] Column 'email' already exists\n";
    }

    // 2. Check and add password column
    $stmt = $pdo->query("SHOW COLUMNS FROM warga LIKE 'password'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE warga ADD COLUMN password VARCHAR(255) NULL AFTER email");
        echo "[+] Added column 'password' to warga\n";
    } else {
        echo "[-] Column 'password' already exists\n";
    }

    // 3. Check and add level column
    $stmt = $pdo->query("SHOW COLUMNS FROM warga LIKE 'level'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE warga ADD COLUMN level VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password");
        echo "[+] Added column 'level' to warga\n";
    } else {
        echo "[-] Column 'level' already exists\n";
    }

    // 4. Seed/Ensure Admin Account (admin@gmail.com / admin123)
    $stmt = $pdo->prepare("SELECT id FROM warga WHERE email = ? LIMIT 1");
    $stmt->execute(['admin@gmail.com']);
    $admin = $stmt->fetch();

    $hashedPass = password_hash('admin123', PASSWORD_BCRYPT);

    if ($admin) {
        $stmtUpdate = $pdo->prepare("UPDATE warga SET password = ?, level = 'admin' WHERE id = ?");
        $stmtUpdate->execute([$hashedPass, $admin['id']]);
        echo "[*] Updated existing Admin account (admin@gmail.com)\n";
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO warga (nama, email, password, level, blok_no, nik, no_hp, status_warga) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtInsert->execute([
            'Administrator RT',
            'admin@gmail.com',
            $hashedPass,
            'admin',
            'Kantor RT 01',
            '3271010000000001',
            '081200000000',
            'Tetap'
        ]);
        echo "[+] Created new Admin account (admin@gmail.com / admin123)\n";
    }

    // 5. Optionally provide sample warga login for testing convenience (budi@gmail.com / warga123)
    $stmtBudi = $pdo->prepare("SELECT id FROM warga WHERE nama = 'Budi Santoso' LIMIT 1");
    $stmtBudi->execute();
    $budi = $stmtBudi->fetch();
    if ($budi) {
        $userPass = password_hash('warga123', PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE warga SET email = 'budi@gmail.com', password = ?, level = 'user' WHERE id = ? AND (email IS NULL OR email = '')")
            ->execute([$userPass, $budi['id']]);
        echo "[*] Set demo user account for Budi Santoso (budi@gmail.com / warga123)\n";
    }

    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
