<?php
// includes/auth.php - Helper Autentikasi & Otorisasi Role RT/RW

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mendapatkan data user yang sedang login
 * @return array|null
 */
function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Cek apakah ada user yang sedang login
 * @return bool
 */
function is_logged_in() {
    return !empty($_SESSION['user']['id']);
}

/**
 * Cek apakah user yang login memiliki role admin
 * @return bool
 */
function is_admin() {
    return is_logged_in() && (strtolower($_SESSION['user']['level'] ?? '') === 'admin');
}

/**
 * Cek apakah user yang login memiliki role warga/user biasa
 * @return bool
 */
function is_warga() {
    return is_logged_in() && (strtolower($_SESSION['user']['level'] ?? '') === 'user');
}

/**
 * Wajibkan login. Jika belum login, redirect ke halaman login.php
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Silakan login terlebih dahulu untuk mengakses sistem RT/RW.";
        header("Location: login.php");
        exit;
    }
}

/**
 * Wajibkan role Admin. Jika user biasa (warga) mencoba akses, redirect ke dashboard dengan peringatan.
 */
function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['toast_msg'] = "Akses Ditolak! Tindakan atau halaman ini khusus untuk Admin RT.";
        $_SESSION['toast_type'] = "rose";
        header("Location: index.php");
        exit;
    }
}
