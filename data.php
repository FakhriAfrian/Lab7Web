<?php
/**
 * data.php — data awal (seed) aplikasi
 * Materi: variabel, tipe data (string, integer, array asosiatif), array multidimensi.
 *
 * Tiga tier dalam materi "Server Side Scripting":
 *   Presentation tier = file halaman (*.php) + includes/header.php & footer.php
 *   Application tier  = includes/functions.php (logika bisnis)
 *   Data tier         = file ini + folder data/ (penyimpanan JSON)
 *
 * Password disimpan dalam bentuk HASH (password_hash), bukan teks biasa.
 * Akun demo: rina@gmail.com / rina123  |  agus@gmail.com / agus123  |  siti@gmail.com / siti123 (Admin)
 */

$dataPengguna = [
    [
        'id'       => 1,
        'nama'     => 'Rina Wulandari',
        'email'    => 'rina@gmail.com',
        'password' => '$2y$10$cK.WCqe40p70s1UsRCNdE.WEUmSgPO.GcPQrZKoGNJAnGcUnKY6z6',
        'role'     => 'User',
    ],
    [
        'id'       => 2,
        'nama'     => 'Agus Pranoto',
        'email'    => 'agus@gmail.com',
        'password' => '$2y$10$XD.dZC658aHJ8fVeJTIiF.Hgun27lH7FAaH6IlHzc8848VfJ2G/QG',
        'role'     => 'User',
    ],
    [
        'id'       => 3,
        'nama'     => 'Siti Marlina',
        'email'    => 'siti@gmail.com',
        'password' => '$2y$10$tFliP6dzgGHNq91geDLUWOMb/CiABFe/w4XUh4XBG6YbZTzLqfDWG',
        'role'     => 'Admin',
    ],
];

$dataKatalogBuku = [
    [
        'kodeBarang' => 'ASIP4301',
        'namaBarang' => 'Pengantar Ilmu Komunikasi',
        'jenisBarang' => 'Buku Ajar',
        'edisi'      => 2,
        'stok'       => 548,
        'harga'      => 180000,
        'cover'      => 'assets/img/covers/pengantar_komunikasi.jpg',
    ],
    [
        'kodeBarang' => 'EKMA4002',
        'namaBarang' => 'Manajemen Keuangan',
        'jenisBarang' => 'Buku Ajar',
        'edisi'      => 3,
        'stok'       => 392,
        'harga'      => 220000,
        'cover'      => 'assets/img/covers/manajemen_keuangan.jpg',
    ],
    [
        'kodeBarang' => 'EKMA4310',
        'namaBarang' => 'Kepemimpinan',
        'jenisBarang' => 'Buku Ajar',
        'edisi'      => 1,
        'stok'       => 278,
        'harga'      => 150000,
        'cover'      => 'assets/img/covers/kepemimpinan.jpg',
    ],
    [
        'kodeBarang' => 'BIOL4211',
        'namaBarang' => 'Mikrobiologi Dasar',
        'jenisBarang' => 'Buku Ajar',
        'edisi'      => 2,
        'stok'       => 165,
        'harga'      => 200000,
        'cover'      => 'assets/img/covers/mikrobiologi.jpg',
    ],
    [
        'kodeBarang' => 'PAUD4401',
        'namaBarang' => 'Perkembangan Anak Usia Dini',
        'jenisBarang' => 'Buku Ajar',
        'edisi'      => 4,
        'stok'       => 204,
        'harga'      => 250000,
        'cover'      => 'assets/img/covers/paud_perkembangan.jpg',
    ],
];

// Pesanan contoh. Kunci array = nomor Delivery Order (DO).
$dataTracking = [
    '20230012' => [
        'nomorDO'      => '20230012',
        'nama'         => 'Rina Wulandari',
        'akun'         => 'rina@gmail.com',
        'status'       => 'Dalam Perjalanan',
        'ekspedisi'    => 'JNE',
        'tanggalKirim' => '2025-08-25',
        'paket'        => 'Reguler',
        'kodePaket'    => '0JKT01',
        'pembayaran'   => 'Transfer Bank',
        'total'        => 180000,
        'perjalanan'   => [
            ['waktu' => '2025-08-25 10:12:20', 'keterangan' => 'Penerimaan di Loket: TANGERANG SELATAN. Pengirim: Toko Buku Kampus'],
            ['waktu' => '2025-08-25 14:07:56', 'keterangan' => 'Tiba di Hub: TANGERANG SELATAN'],
            ['waktu' => '2025-08-25 18:20:00', 'keterangan' => 'Diteruskan ke Kantor Jakarta Selatan'],
        ],
    ],
    '20230013' => [
        'nomorDO'      => '20230013',
        'nama'         => 'Agus Pranoto',
        'akun'         => 'agus@gmail.com',
        'status'       => 'Terkirim',
        'ekspedisi'    => 'Pos Indonesia',
        'tanggalKirim' => '2025-08-25',
        'paket'        => 'Express',
        'kodePaket'    => '0UPBJJBDG',
        'pembayaran'   => 'E-Wallet',
        'total'        => 220000,
        'perjalanan'   => [
            ['waktu' => '2025-08-25 10:12:20', 'keterangan' => 'Penerimaan di Loket: TANGERANG SELATAN. Pengirim: Toko Buku Kampus'],
            ['waktu' => '2025-08-25 14:07:56', 'keterangan' => 'Tiba di Hub: TANGERANG SELATAN'],
            ['waktu' => '2025-08-25 16:30:10', 'keterangan' => 'Diteruskan ke Kantor Kota Bandung'],
            ['waktu' => '2025-08-26 12:15:33', 'keterangan' => 'Tiba di Hub: Kota BANDUNG'],
            ['waktu' => '2025-08-26 15:06:12', 'keterangan' => 'Proses antar ke Cimahi'],
            ['waktu' => '2025-08-26 20:00:00', 'keterangan' => 'Selesai Antar. Penerima: Agus Pranoto'],
        ],
    ],
];
