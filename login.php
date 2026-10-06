<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Jika sudah login, redirect langsung ke dashboard
if (is_logged_in()) {
    header("Location: index.php");
    exit;
}

$error_msg = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$success_msg = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

// Proses form login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_msg = "Harap isi email dan password!";
    } else {
        // Cari akun berdasarkan email
        $stmt = $pdo->prepare("SELECT * FROM warga WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
            // Login sukses
            $_SESSION['user'] = [
                'id'           => $user['id'],
                'nama'         => $user['nama'],
                'email'        => $user['email'],
                'level'        => strtolower($user['level'] ?? 'user'),
                'blok_no'      => $user['blok_no'] ?? '',
                'nik'          => $user['nik'] ?? '',
                'no_hp'        => $user['no_hp'] ?? '',
                'status_warga' => $user['status_warga'] ?? 'Tetap'
            ];

            $_SESSION['toast_msg'] = "Selamat datang, " . htmlspecialchars($user['nama']) . "! Anda masuk sebagai " . ucfirst($_SESSION['user']['level']) . ".";
            header("Location: index.php");
            exit;
        } else {
            $error_msg = "Email atau password salah! Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Smart RT / RW System</title>
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
<body class="bg-gradient-to-br from-slate-900 via-brand-dark to-slate-900 text-slate-800 font-sans min-h-screen flex items-center justify-center p-4 antialiased relative overflow-x-hidden">

    <!-- Ambient Decorative Lights -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Brand Logo Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-brand-accent to-blue-400 text-white text-3xl shadow-xl shadow-blue-900/40 mb-3 transform hover:scale-105 transition duration-300">
                <i class="fa-solid fa-house-chimney-window"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Smart RT / RW</h1>
            <p class="text-xs sm:text-sm text-blue-200/80 mt-1">Sistem Administrasi, Kas Warga & Pelayanan Surat</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-white/20 p-6 sm:p-8">
            <div class="mb-5">
                <h2 class="text-xl font-bold text-slate-800">Masuk ke Akun</h2>
                <p class="text-xs text-slate-500 mt-1">Silakan masukkan email dan password terdaftar Anda</p>
            </div>

            <!-- Flash Error / Success Alert -->
            <?php if (!empty($error_msg)): ?>
                <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs flex items-start gap-2.5 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base mt-0.5 flex-shrink-0"></i>
                    <div>
                        <span class="font-bold">Gagal Masuk:</span> <?= htmlspecialchars($error_msg) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="mb-5 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs flex items-start gap-2.5 shadow-sm">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-base mt-0.5 flex-shrink-0"></i>
                    <div>
                        <?= htmlspecialchars($success_msg) ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-4">
                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                        <i class="fa-solid fa-envelope text-brand-accent mr-1"></i> Alamat Email
                    </label>
                    <div class="relative">
                        <input type="email" id="email" name="email" required placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                        <i class="fa-solid fa-lock text-brand-accent mr-1"></i> Kata Sandi
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required placeholder="Masukkan kata sandi..."
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-accent focus:bg-white transition text-slate-800 pr-10">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                            <i id="eyeIcon" class="fa-solid fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3 bg-gradient-to-r from-brand-blue to-brand-accent hover:from-blue-800 hover:to-blue-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-600/30 transition transform active:scale-[0.98] flex items-center justify-center gap-2 mt-2">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Masuk Sekarang
                </button>
            </form>

            <!-- Quick Demo Accounts Helper -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <span class="block text-[11px] font-semibold text-slate-500 mb-2 uppercase tracking-wider text-center">Akun Uji Coba Cepat</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="fillCredential('admin@gmail.com', 'admin123')" class="p-2.5 rounded-xl border border-blue-200 bg-blue-50/70 hover:bg-blue-100/80 text-blue-900 text-left transition flex flex-col">
                        <span class="font-bold flex items-center gap-1 text-[11px]">
                            <i class="fa-solid fa-user-shield text-brand-accent"></i> Admin RT
                        </span>
                        <span class="text-[10px] text-blue-700 mt-0.5 truncate">admin@gmail.com</span>
                        <span class="text-[9px] text-slate-500 font-mono">pass: admin123</span>
                    </button>
                    <button type="button" onclick="fillCredential('budi@gmail.com', 'warga123')" class="p-2.5 rounded-xl border border-amber-200 bg-amber-50/70 hover:bg-amber-100/80 text-amber-900 text-left transition flex flex-col">
                        <span class="font-bold flex items-center gap-1 text-[11px]">
                            <i class="fa-solid fa-user text-amber-600"></i> Warga (Budi)
                        </span>
                        <span class="text-[10px] text-amber-800 mt-0.5 truncate">budi@gmail.com</span>
                        <span class="text-[9px] text-slate-500 font-mono">pass: warga123</span>
                    </button>
                </div>
            </div>

            <!-- Register Link -->
            <div class="mt-5 text-center text-xs text-slate-600">
                Belum punya akun warga?
                <a href="register.php" class="font-bold text-brand-accent hover:underline ml-1">
                    Daftar di sini <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

        <!-- Footer Notice -->
        <p class="text-center text-[11px] text-slate-400 mt-5">
            &copy; <?= date('Y') ?> Smart RT 01 / RW 05. Aman, Cepat & Terpercaya.
        </p>
    </div>

    <script>
        function togglePassword() {
            const pass = document.getElementById('password');
            const eye = document.getElementById('eyeIcon');
            if (pass.type === 'password') {
                pass.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                pass.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }

        function fillCredential(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
