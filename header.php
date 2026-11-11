<?php
/**
 * header.php — PRESENTATION TIER (kepala halaman + navigasi)
 * Variabel yang diisi oleh halaman pemanggil:
 *   $judul   : judul tab browser
 *   $menu    : nama menu yang aktif (dashboard|katalog|stok|pemesanan|tracking|riwayat|laporan)
 */
$user  = userLogin();
$menu  = $menu ?? '';
$judul = $judul ?? NAMA_TOKO;

$navItems = [
    'dashboard' => ['dashboard.php', 'Dashboard'],
    'katalog'   => ['katalog.php', 'Katalog'],
    'stok'      => ['stok.php', 'Stok'],
    'pemesanan' => ['pemesanan.php', 'Pemesanan'],
    'tracking'  => ['tracking.php', 'Tracking'],
    'riwayat'   => ['riwayat.php', 'Riwayat'],
    'laporan'   => ['laporan.php', 'Laporan'],
];
$jumlahKeranjang = jumlahItemKeranjang();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judul) ?> — <?= e(NAMA_TOKO) ?></title>
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <a class="lompat" href="#isi">Lewati ke konten</a>
  <header class="topbar">
    <div class="topbar-isi">
      <a class="brand" href="dashboard.php">
        <img src="assets/img/logo.png" alt="" width="40" height="40">
        <span><?= e(NAMA_TOKO) ?></span>
      </a>
      <nav aria-label="Navigasi utama">
        <?php foreach ($navItems as $kunci => [$href, $label]): ?>
          <a href="<?= e($href) ?>"<?= $kunci === $menu ? ' class="aktif" aria-current="page"' : '' ?>>
            <?= e($label) ?>
            <?php if ($kunci === 'pemesanan' && $jumlahKeranjang > 0): ?>
              <span class="hitung"><?= (int)$jumlahKeranjang ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="user-box">
        <span class="nama-user"><?= e($user['nama'] ?? '') ?>
          <small><?= e($user['role'] ?? '') ?></small></span>
        <a class="btn btn-kecil btn-garis" href="logout.php">Keluar</a>
      </div>
    </div>
  </header>

  <main class="container" id="isi">
    <?php foreach (ambilFlash() as $f): ?>
      <div class="alert alert-<?= e($f['tipe']) ?>" role="<?= $f['tipe'] === 'error' ? 'alert' : 'status' ?>">
        <?= e($f['pesan']) ?>
      </div>
    <?php endforeach; ?>
