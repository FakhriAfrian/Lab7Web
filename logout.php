<?php
/**
 * logout.php — mengakhiri session lalu kembali ke halaman login.
 */
require_once __DIR__ . '/includes/config.php';

// Hapus seluruh data session & cookie-nya
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

session_start();
setFlash('sukses', 'Anda sudah keluar. Sampai jumpa!');
redirect('index.php');
