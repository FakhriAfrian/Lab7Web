<?php
/**
 * dashboard.php — MENU UTAMA
 */
require_once __DIR__ . '/includes/config.php';
$user = wajibLogin();

// ---- Application tier: siapkan data ----
$katalog   = getKatalog();
$pesanan   = pesananUntukUser($user);
$dalamProses = 0;
foreach ($pesanan as $p) {
    if (statusAkhir($p)['level'] < 5) {
        $dalamProses++;
    }
}
$totalStok = array_sum(array_column($katalog, 'stok'));

$menuUtama = [
    ['katalog.php',   'buku',      'Katalog Buku',        'Cari buku & tambahkan ke keranjang'],
    ['pemesanan.php', 'keranjang', 'Pemesanan',           jumlahItemKeranjang() . ' item di keranjang'],
    ['tracking.php',  'truk',      'Tracking Pengiriman', 'Lacak dengan Nomor DO'],
    ['riwayat.php',   'nota',      'Riwayat Transaksi',   'Semua pesanan ' . (isAdmin() ? 'toko' : 'Anda')],
    ['stok.php',      'kotak',     'Informasi Stok',      isAdmin() ? 'Tambah buku & restock' : 'Lihat ketersediaan buku'],
    ['laporan.php',   'grafik',    'Laporan Pemesanan',   'Ringkasan & rekap ekspedisi'],
];

// ---- Presentation tier ----
$judul = 'Dashboard';
$menu  = 'dashboard';
require __DIR__ . '/includes/header.php';
?>
    <section class="hero" aria-labelledby="sapa">
      <div>
        <p class="hero-hari"><?= e(namaHari()) ?>, <?= e(formatTanggal(date('Y-m-d'))) ?></p>
        <h1 id="sapa"><?= e(sapaan()) ?>, <?= e(strtok($user['nama'], ' ')) ?>.</h1>
        <p>Anda masuk sebagai <strong><?= e($user['role']) ?></strong>.
           <?= isAdmin() ? 'Anda dapat mengelola stok dan memperbarui status pengiriman.' : 'Pilih buku, buat pesanan, lalu pantau pengirimannya.' ?></p>
      </div>
      <a href="katalog.php" class="btn btn-emas">Mulai Belanja</a>
    </section>

    <section class="stat-grid" aria-label="Ringkasan">
      <div class="stat"><b><?= count($katalog) ?></b><small>Judul buku</small></div>
      <div class="stat"><b><?= number_format($totalStok, 0, ',', '.') ?></b><small>Total stok</small></div>
      <div class="stat"><b><?= count($pesanan) ?></b><small>Pesanan <?= isAdmin() ? 'toko' : 'saya' ?></small></div>
      <div class="stat"><b><?= $dalamProses ?></b><small>Sedang diproses</small></div>
    </section>

    <h2 class="judul-bagian">Menu Utama</h2>
    <section class="menu-grid" aria-label="Menu utama">
      <?php foreach ($menuUtama as [$href, $ikon, $nama, $ket]): ?>
        <a class="kartu-menu" href="<?= e($href) ?>">
          <?= ikon($ikon) ?>
          <strong><?= e($nama) ?></strong>
          <span class="ket"><?= e($ket) ?></span>
        </a>
      <?php endforeach; ?>
    </section>

    <h2 class="judul-bagian">Katalog Pilihan</h2>
    <section class="rak" aria-label="Katalog pilihan">
      <?php foreach (array_slice($katalog, 0, 6) as $buku): ?>
        <figure>
          <a href="katalog.php?kata=<?= urlencode($buku['kodeBarang']) ?>">
            <?= coverBuku($buku, 'cover-besar') ?>
          </a>
          <figcaption><?= e($buku['namaBarang']) ?><span><?= e(rupiah($buku['harga'])) ?></span></figcaption>
        </figure>
      <?php endforeach; ?>
    </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
