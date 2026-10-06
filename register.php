<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Jika sudah login, redirect langsung ke dashboard
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error_msg = '';
$form_data = [
    'nama'         => '',
    'email'        => '',
    'blok_no'      => '',
    'nik'          => '',
    'no_hp'        => '',
    'status_warga' => 'Tetap'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['nama']         = trim($_POST['nama'] ?? '');
    $form_data['email']        = trim($_POST['email'] ?? '');
    $form_data['blok_no']      = trim($_POST['blok_no'] ?? '');
    $form_data['nik']          = trim($_POST['nik'] ?? '');
    $form_data['no_hp']        = trim($_POST['no_hp'] ?? '');
    $form_data['status_warga'] = $_POST['status_warga'] ?? 'Tetap';

    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi input
    if (empty($form_data['nama']) || empty($form_data['email']) || empty($form_data['blok_no']) || empty($password)) {
        $error_msg = "Harap lengkapi semua kolom yang wajib diisi (Nama, Email, Blok/Rumah, dan Password)!";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Format email tidak valid!";
    } elseif (strlen($password) < 6) {
        $error_msg = "Password minimal terdiri dari 6 karakter!";
    } elseif ($password !== $confirm_password) {
        $error_msg = "Konfirmasi kata sandi tidak cocok!";
    } else {
        // Cek apakah email sudah terdaftar
        $stmtCek = $pdo->prepare("SELECT id FROM warga WHERE email = ? LIMIT 1");
        $stmtCek->execute([$form_data['email']]);
        if ($stmtCek->fetch()) {
            $error_msg = "Email " . htmlspecialchars($form_data['email']) . " sudah terdaftar! Silakan gunakan email lain atau login.";
        } else {
            // Hash password
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);

            // Simpan ke database dengan level 'user'
            $stmt = $pdo->prepare("INSERT INTO warga (nama, email, password, level, blok_no, nik, no_hp, status_warga) VALUES (?, ?, ?, 'user', ?, ?, ?, ?)");
            $stmt->execute([
                $form_data['nama'],
                $form_data['email'],
                $hashedPass,
                $form_data['blok_no'],
                $form_data['nik'],
                $form_data['no_hp'],
                $form_data['status_warga']
            ]);

            $_SESSION['flash_success'] = "Pendaftaran berhasil! Akun warga Anda telah dibuat. Silakan masuk menggunakan email dan password Anda.";
            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pendaftaran Warga - Smart RT / RW System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            blue: '#0E3A78',
                            dark: '#0A2A57',
                            accent: '#2563EB',
                            light: '#EFF6FF',
                            gold: '#EAB308',
                            goldHover: '#CA8A04'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-brand-dark to-slate-900 text-slate-800 font-sans min-h-screen flex items-center justify-center p-4 antialiased relative overflow-x-hidden py-10">

    <!-- Ambient Decorative Lights -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-lg relative z-10">
        <!-- Brand Logo Header -->
        <div class="text-center mb-6">
            <a href="login.php" class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-brand-accent to-blue-400 text-white text-2xl shadow-xl shadow-blue-900/40 mb-3 hover:scale-105 transition duration-300">
                <i class="fa-solid fa-house-chimney-window"></i>
            </a>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Registrasi Warga Baru</h1>
            <p class="text-xs sm:text-sm text-blue-200/80 mt-1">Daftar untuk mengakses pengajuan surat, cek iuran, dan informasi RT</p>
        </div>

        <!-- Register Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-6 sm:p-8">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-slate-800">Form Pendaftaran</h2>
                    <p class="text-xs text-slate-500">Lengkapi data kependudukan Anda</p>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 bg-blue-100 text-blue-700 rounded-full border border-blue-200">
                    Role: Warga
                </span>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($error_msg)): ?>
                <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs flex items-start gap-2.5 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base mt-0.5 flex-shrink-0"></i>
                    <div>
                        <span class="font-bold">Gagal Registrasi:</span> <?= htmlspecialchars($error_msg) ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="space-y-4">
                <!-- Nama Lengkap -->
                <div>
                    <label for="nama" class="block text-xs font-bold text-slate-700 mb-1">
                        <i class="fa-solid fa-user text-brand-accent mr-1"></i> Nama Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="nama" name="nama" required placeholder="Contoh: Joko Susilo" value="<?= htmlspecialchars($form_data['nama']) ?>"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                </div>

                <!-- Grid Email & No HP -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1">
                            <i class="fa-solid fa-envelope text-brand-accent mr-1"></i> Email (Untuk Login) <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" required placeholder="nama@email.com" value="<?= htmlspecialchars($form_data['email']) ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                    </div>
                    <div>
                        <label for="no_hp" class="block text-xs font-bold text-slate-700 mb-1">
                            <i class="fa-solid fa-phone text-brand-accent mr-1"></i> No. HP / WhatsApp
                        </label>
                        <input type="tel" id="no_hp" name="no_hp" placeholder="081234567890" value="<?= htmlspecialchars($form_data['no_hp']) ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                    </div>
                </div>

                <!-- Grid Blok & Status Warga -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="blok_no" class="block text-xs font-bold text-slate-700 mb-1">
                            <i class="fa-solid fa-location-dot text-brand-accent mr-1"></i> Blok / No. Rumah <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="blok_no" name="blok_no" required placeholder="Contoh: Blok B2 No. 10" value="<?= htmlspecialchars($form_data['blok_no']) ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                    </div>
                    <div>
                        <label for="status_warga" class="block text-xs font-bold text-slate-700 mb-1">
                            <i class="fa-solid fa-id-card-clip text-brand-accent mr-1"></i> Status Kependudukan
                        </label>
                        <select id="status_warga" name="status_warga" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                            <option value="Tetap" <?= ($form_data['status_warga'] === 'Tetap') ? 'selected' : '' ?>>Warga Tetap</option>
                            <option value="Kontrak" <?= ($form_data['status_warga'] === 'Kontrak') ? 'selected' : '' ?>>Warga Kontrak / Kost</option>
                        </select>
                    </div>
                </div>

                <!-- NIK (Opsional / KTP) -->
                <div>
                    <label for="nik" class="block text-xs font-bold text-slate-700 mb-1">
                        <i class="fa-solid fa-address-card text-brand-accent mr-1"></i> NIK (Nomor Induk Kependudukan)
                    </label>
                    <input type="text" id="nik" name="nik" placeholder="16 digit NIK di KTP" value="<?= htmlspecialchars($form_data['nik']) ?>"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                </div>

                <!-- Password & Konfirmasi Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-700 mb-1">
                            <i class="fa-solid fa-lock text-brand-accent mr-1"></i> Kata Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="password" name="password" required minlength="6" placeholder="Min. 6 karakter"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-xs font-bold text-slate-700 mb-1">
                            <i class="fa-solid fa-shield-halved text-brand-accent mr-1"></i> Ulangi Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Ulangi kata sandi"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-brand-blue to-brand-accent hover:from-blue-800 hover:to-blue-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-600/30 transition transform active:scale-[0.98] flex items-center justify-center gap-2 mt-2">
                    <i class="fa-solid fa-user-plus"></i> Daftar Sebagai Warga
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-5 text-center text-xs text-slate-600 border-t border-slate-100 pt-4">
                Sudah memiliki akun?
                <a href="login.php" class="font-bold text-brand-accent hover:underline ml-1">
                    Masuk Sekarang <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <!-- Footer Notice -->
        <p class="text-center text-[11px] text-slate-400 mt-5">
            &copy; <?= date('Y') ?> Smart RT 01 / RW 05. Kerukunan & Ketertiban Bersama.
        </p>
    </div>

</body>
</html>
