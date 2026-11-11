<?php
/**
 * config.php — konfigurasi aplikasi
 * Materi: konstanta (define / const), session, predefined variable.
 * File ini di-include oleh semua halaman.
 */

// ---- Konstanta (nilainya tidak dapat diubah setelah dideklarasikan) ----
define('NAMA_TOKO', 'Toko Buku Kampus');
define('BASE_DIR', dirname(__DIR__));
define('DATA_DIR', BASE_DIR . '/data');
define('UPLOAD_DIR', BASE_DIR . '/uploads');
define('COVER_DEFAULT', 'assets/img/covers/cover-default.png');
define('MAKS_UPLOAD', 2 * 1024 * 1024); // 2 MB

// Ongkos kirim berdasarkan jenis paket
const ONGKIR = ['Reguler' => 15000, 'Express' => 30000, 'Same Day' => 50000];

// Tahapan pengiriman (dipakai progress bar tracking)
const TAHAP = ['Diproses', 'Dikemas', 'Dikirim', 'Dalam Perjalanan', 'Terkirim'];

const EKSPEDISI = ['JNE', 'J&T Express', 'SiCepat', 'Pos Indonesia'];
const METODE_BAYAR = ['Transfer Bank', 'E-Wallet', 'Kartu Kredit', 'COD'];
const JENIS_BUKU = ['Buku Ajar', 'Modul', 'Buku Referensi', 'Lainnya'];

date_default_timezone_set('Asia/Jakarta');

// ---- Session (dengan cookie yang lebih aman) ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/data.php';
require_once __DIR__ . '/functions.php';
