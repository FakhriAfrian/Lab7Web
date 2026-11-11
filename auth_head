<?php
/**
 * auth_head.php — kepala halaman untuk Login / Daftar / Lupa Password
 * (halaman tanpa menu navigasi). Dipakai bersama auth_foot.php.
 */
$judul = $judul ?? 'Masuk';
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
<body class="halaman-auth">
  <div class="auth-layout">
    <section class="promo" aria-label="Katalog pilihan">
      <img class="promo-logo" src="assets/img/logo.png" alt="Logo <?= e(NAMA_TOKO) ?>" width="84" height="84">
      <h2><?= e(NAMA_TOKO) ?></h2>
      <p>Pesan buku ajar dengan mudah, lalu pantau pengirimannya sampai ke tangan Anda.</p>
      <div class="rak-login">
        <?php foreach (array_slice(getKatalog(), 0, 5) as $buku): ?>
          <?= coverBuku($buku, 'cover-promo') ?>
        <?php endforeach; ?>
      </div>
    </section>
    <main class="auth-kartu" id="isi">
      <?php foreach (ambilFlash() as $f): ?>
        <div class="alert alert-<?= e($f['tipe']) ?>" role="<?= $f['tipe'] === 'error' ? 'alert' : 'status' ?>">
          <?= e($f['pesan']) ?>
        </div>
      <?php endforeach; ?>
