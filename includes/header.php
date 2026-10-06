<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Wajibkan login untuk mengakses halaman
require_login();

$user = current_user();
$isAdmin = is_admin();
$userRoleName = $isAdmin ? 'Admin RT' : 'Warga';

// Determine current page for active nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Smart RT - Mobile Native Responsive System</title>
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
    <style>
        /* Smooth scrolling & touch highlights */
        html {
            -webkit-tap-highlight-color: transparent;
            scroll-behavior: smooth;
        }
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        /* Mobile safe area padding for modern iPhones/Androids */
        .pb-safe {
            padding-bottom: env(safe-area-inset-bottom, 1rem);
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased min-h-screen flex flex-col md:flex-row">

    <!-- DESKTOP SIDEBAR (Visible on md and larger) -->
    <aside class="hidden md:flex w-64 bg-brand-dark text-white flex-shrink-0 flex-col justify-between shadow-xl min-h-screen sticky top-0 h-screen overflow-y-auto">
        <div>
            <!-- Logo Section -->
            <a href="index.php" class="p-5 flex items-center gap-3 border-b border-blue-900/50 hover:bg-blue-900/30 transition">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white text-xl font-bold shadow-md">
                    <i class="fa-solid fa-house-chimney-window"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg leading-tight tracking-wide">Smart RT</h1>
                    <p class="text-[10px] text-blue-200 opacity-80">RT Lebih Modern, Warga Sejahtera</p>
                </div>
            </a>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1 text-sm font-medium">
                <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'index.php') ? 'bg-brand-gold text-slate-900 font-semibold shadow-md' : 'text-slate-300 hover:bg-blue-900/50 hover:text-white' ?>">
                    <i class="fa-solid fa-gauge-high w-5 text-center"></i> Dashboard
                </a>
                <a href="warga.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'warga.php') ? 'bg-brand-gold text-slate-900 font-semibold shadow-md' : 'text-slate-300 hover:bg-blue-900/50 hover:text-white' ?>">
                    <i class="fa-solid fa-users w-5 text-center"></i> <?= $isAdmin ? 'Data Warga' : 'Daftar Warga' ?>
                </a>
                <a href="iuran.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'iuran.php') ? 'bg-brand-gold text-slate-900 font-semibold shadow-md' : 'text-slate-300 hover:bg-blue-900/50 hover:text-white' ?>">
                    <i class="fa-solid fa-wallet w-5 text-center"></i> <?= $isAdmin ? 'Iuran & Pembayaran' : 'Informasi Kas & Iuran' ?>
                </a>
                <a href="surat.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'surat.php') ? 'bg-brand-gold text-slate-900 font-semibold shadow-md' : 'text-slate-300 hover:bg-blue-900/50 hover:text-white' ?>">
                    <i class="fa-solid fa-file-lines w-5 text-center"></i> <?= $isAdmin ? 'Verifikasi Surat' : 'Permohonan Surat' ?>
                </a>
                <a href="kegiatan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'kegiatan.php') ? 'bg-brand-gold text-slate-900 font-semibold shadow-md' : 'text-slate-300 hover:bg-blue-900/50 hover:text-white' ?>">
                    <i class="fa-solid fa-calendar-days w-5 text-center"></i> Kegiatan RT
                </a>
                <a href="inventaris.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'inventaris.php') ? 'bg-brand-gold text-slate-900 font-semibold shadow-md' : 'text-slate-300 hover:bg-blue-900/50 hover:text-white' ?>">
                    <i class="fa-solid fa-boxes-stacked w-5 text-center"></i> Inventaris RT
                </a>
                <a href="pengaduan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition <?= ($current_page == 'pengaduan.php') ? 'bg-brand-gold text-slate-900 font-semibold shadow-md' : 'text-slate-300 hover:bg-blue-900/50 hover:text-white' ?>">
                    <i class="fa-solid fa-bullhorn w-5 text-center"></i> Pengaduan Warga
                </a>
            </nav>
        </div>

        <!-- Sidebar User Card & Logout -->
        <div class="p-3 m-3 bg-gradient-to-br from-blue-900 to-blue-950 rounded-xl border border-blue-800/60 relative overflow-hidden">
            <div class="relative z-10 space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full <?= $isAdmin ? 'bg-amber-400 text-slate-900' : 'bg-blue-500 text-white' ?> flex items-center justify-center font-bold text-sm shadow">
                        <i class="fa-solid <?= $isAdmin ? 'fa-user-shield' : 'fa-user' ?>"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="font-bold text-xs text-white block truncate"><?= htmlspecialchars($user['nama'] ?? 'Pengguna') ?></span>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-[10px] px-1.5 py-0.2 rounded font-semibold <?= $isAdmin ? 'bg-amber-400/20 text-amber-300' : 'bg-emerald-400/20 text-emerald-300' ?>">
                                <?= $userRoleName ?>
                            </span>
                            <?php if (!empty($user['blok_no'])): ?>
                                <span class="text-[10px] text-blue-200 truncate"><?= htmlspecialchars($user['blok_no']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="w-full flex items-center justify-center gap-2 py-1.5 px-3 rounded-lg bg-red-600/20 hover:bg-red-600/30 text-red-200 border border-red-500/30 text-xs font-semibold transition">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar (Logout)
                </a>
            </div>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-blue-500/10 rounded-full blur-xl pointer-events-none"></div>
        </div>
    </aside>

    <!-- MOBILE STICKY TOP APP BAR (Only visible on small screens < md) -->
    <div class="flex md:hidden bg-brand-dark text-white items-center justify-between px-4 py-3 sticky top-0 z-30 shadow-md">
        <a href="index.php" class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white text-base font-bold shadow-sm">
                <i class="fa-solid fa-house-chimney-window"></i>
            </div>
            <span class="font-bold text-base tracking-wide">Smart RT</span>
        </a>
        <div class="flex items-center gap-2">
            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $isAdmin ? 'bg-amber-400 text-slate-900' : 'bg-emerald-500 text-white' ?>">
                <?= $userRoleName ?>
            </span>
            <button onclick="toggleMobileDrawer()" class="p-2 rounded-lg bg-blue-900/60 text-white hover:bg-blue-800 focus:outline-none" title="Menu Navigasi">
                <i class="fa-solid fa-bars-staggered text-lg"></i>
            </button>
        </div>
    </div>

    <!-- MOBILE SLIDE-OUT DRAWER MENU OVERLAY -->
    <div id="mobileDrawer" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 transform -translate-x-full transition-transform duration-300 md:hidden flex">
        <div class="w-72 bg-brand-dark text-white h-full flex flex-col justify-between p-4 shadow-2xl overflow-y-auto">
            <div>
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-blue-900/60">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white text-lg font-bold">
                            <i class="fa-solid fa-house-chimney-window"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-base leading-none">Smart RT</h2>
                            <span class="text-[10px] text-blue-200">RT 01 / RW 05</span>
                        </div>
                    </div>
                    <button onclick="toggleMobileDrawer()" class="text-slate-400 hover:text-white p-2">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                <nav class="space-y-1 text-sm font-medium">
                    <a href="index.php" class="flex items-center gap-3 px-3 py-3 rounded-xl transition <?= ($current_page == 'index.php') ? 'bg-brand-gold text-slate-900 font-bold' : 'text-slate-200 hover:bg-blue-900/50' ?>">
                        <i class="fa-solid fa-gauge-high w-5 text-center text-brand-gold"></i> Dashboard
                    </a>
                    <a href="warga.php" class="flex items-center gap-3 px-3 py-3 rounded-xl transition <?= ($current_page == 'warga.php') ? 'bg-brand-gold text-slate-900 font-bold' : 'text-slate-200 hover:bg-blue-900/50' ?>">
                        <i class="fa-solid fa-users w-5 text-center text-blue-400"></i> <?= $isAdmin ? 'Data Warga' : 'Daftar Warga' ?>
                    </a>
                    <a href="iuran.php" class="flex items-center gap-3 px-3 py-3 rounded-xl transition <?= ($current_page == 'iuran.php') ? 'bg-brand-gold text-slate-900 font-bold' : 'text-slate-200 hover:bg-blue-900/50' ?>">
                        <i class="fa-solid fa-wallet w-5 text-center text-amber-400"></i> <?= $isAdmin ? 'Iuran & Pembayaran' : 'Informasi Kas' ?>
                    </a>
                    <a href="surat.php" class="flex items-center gap-3 px-3 py-3 rounded-xl transition <?= ($current_page == 'surat.php') ? 'bg-brand-gold text-slate-900 font-bold' : 'text-slate-200 hover:bg-blue-900/50' ?>">
                        <i class="fa-solid fa-file-lines w-5 text-center text-emerald-400"></i> <?= $isAdmin ? 'Verifikasi Surat' : 'Permohonan Surat' ?>
                    </a>
                    <a href="kegiatan.php" class="flex items-center gap-3 px-3 py-3 rounded-xl transition <?= ($current_page == 'kegiatan.php') ? 'bg-brand-gold text-slate-900 font-bold' : 'text-slate-200 hover:bg-blue-900/50' ?>">
                        <i class="fa-solid fa-calendar-days w-5 text-center text-purple-400"></i> Kegiatan RT
                    </a>
                    <a href="inventaris.php" class="flex items-center gap-3 px-3 py-3 rounded-xl transition <?= ($current_page == 'inventaris.php') ? 'bg-brand-gold text-slate-900 font-bold' : 'text-slate-200 hover:bg-blue-900/50' ?>">
                        <i class="fa-solid fa-boxes-stacked w-5 text-center text-orange-400"></i> Inventaris RT
                    </a>
                    <a href="pengaduan.php" class="flex items-center gap-3 px-3 py-3 rounded-xl transition <?= ($current_page == 'pengaduan.php') ? 'bg-brand-gold text-slate-900 font-bold' : 'text-slate-200 hover:bg-blue-900/50' ?>">
                        <i class="fa-solid fa-bullhorn w-5 text-center text-rose-400"></i> Pengaduan Warga
                    </a>
                </nav>
            </div>
            
            <!-- Mobile Drawer User Profile & Logout -->
            <div class="pt-4 border-t border-blue-900/60 space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full <?= $isAdmin ? 'bg-amber-400 text-slate-900' : 'bg-blue-500 text-white' ?> flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid <?= $isAdmin ? 'fa-user-shield' : 'fa-user' ?>"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="block font-semibold text-white text-xs truncate"><?= htmlspecialchars($user['nama'] ?? 'Pengguna') ?></span>
                        <span class="text-[10px] text-blue-200"><?= $userRoleName ?> &bull; <?= htmlspecialchars($user['blok_no'] ?? '') ?></span>
                    </div>
                </div>
                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="w-full flex items-center justify-center gap-2 py-2 rounded-xl bg-red-600/20 hover:bg-red-600/30 text-red-200 border border-red-500/30 text-xs font-semibold transition">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Keluar (Logout)
                </a>
            </div>
        </div>
        <div class="flex-1" onclick="toggleMobileDrawer()"></div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Top Header (Desktop & Mobile Header) -->
        <header class="bg-white border-b border-slate-200 px-4 sm:px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 sticky top-0 z-20 shadow-xs">
            <div>
                <h2 class="text-lg sm:text-xl font-bold text-slate-800 flex items-center gap-2">
                    Halo, <?= htmlspecialchars($user['nama'] ?? 'Warga') ?> <span class="text-lg">👋</span>
                </h2>
                <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">Sistem Kas, Warga & Surat RT 01 / RW 05</p>
            </div>

            <!-- Top Actions / Indicators -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- RT Selection Dropdown -->
                <div class="bg-slate-100 rounded-lg px-2.5 sm:px-3 py-1 flex items-center gap-1.5 text-xs font-semibold text-slate-700 border border-slate-200">
                    <i class="fa-solid fa-location-dot text-brand-blue text-xs"></i>
                    <span class="text-[11px] sm:text-xs">RT 01/RW 05</span>
                </div>

                <!-- User Profile Badge & Logout -->
                <div class="flex items-center gap-2.5 pl-2 border-l border-slate-200">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full <?= $isAdmin ? 'bg-brand-blue text-white ring-amber-400' : 'bg-emerald-600 text-white ring-emerald-300' ?> flex items-center justify-center font-bold text-xs sm:text-sm ring-2">
                        <i class="fa-solid <?= $isAdmin ? 'fa-user-shield' : 'fa-user' ?>"></i>
                    </div>
                    <div class="hidden sm:block text-left">
                        <span class="block text-xs font-bold text-slate-800 leading-tight"><?= htmlspecialchars($user['nama'] ?? 'Pengguna') ?></span>
                        <span class="text-[10px] font-semibold <?= $isAdmin ? 'text-amber-600' : 'text-emerald-600' ?>">
                            <?= $userRoleName ?> (<?= htmlspecialchars($user['blok_no'] ?? '-') ?>)
                        </span>
                    </div>
                    <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="hidden sm:flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 transition" title="Keluar / Logout">
                        <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Body Starts Here (pb-24 ensures mobile bottom nav doesn't cover content) -->
        <div class="p-3 sm:p-6 pb-24 md:pb-6 space-y-4 sm:space-y-6">
