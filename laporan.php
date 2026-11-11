<?php
/**
 * laporan.php — LAPORAN PEMESANAN
 * Ringkasan jumlah pesanan, total pembayaran, rekap per ekspedisi, dan buku terlaris.
 */
require_once __DIR__ . '/includes/config.php';
$user = wajibLogin();

$pesanan = pesananUntukUser($user);

$omzet   = 0;
$selesai = 0;
$terjual = []; // [namaBuku => jumlah]
foreach ($pesanan as $p) {
    $omzet += (int)$p['total'];
    if (statusAkhir($p)['level'] >= 5) {
        $selesai++;
    }
    foreach ($p['items'] ?? [] as $it) {
        $terjual[$it['nama']] = ($terjual[$it['nama']] ?? 0) + (int)$it['qty'];
    }
}
arsort($terjual);
$rekap = rekapEkspedisi($pesanan);

$judul = 'Laporan Pemesanan';
$menu  = 'laporan';
require __DIR__ . '/includes/header.php';
?>
    <h1 class="judul-halaman">Laporan Pemesanan</h1>
    <p class="sub-judul"><?= isAdmin() ? 'Ringkasan seluruh pesanan toko.' : 'Ringkasan pesanan Anda.' ?></p>

    <section class="stat-grid" aria-label="Ringkasan">
      <div class="stat"><b><?= count($pesanan) ?></b><small>Total pesanan</small></div>
      <div class="stat"><b><?= e(rupiah($omzet)) ?></b><small>Total pembayaran</small></div>
      <div class="stat"><b><?= $selesai ?></b><small>Sudah diterima</small></div>
      <div class="stat"><b><?= count($pesanan) - $selesai ?></b><small>Dalam proses</small></div>
    </section>

    <section class="kartu">
      <h2>Rekap per Ekspedisi</h2>
      <div class="tabel-wrap">
        <table>
          <thead><tr><th>Ekspedisi</th><th class="tengah">Jumlah Pesanan</th><th class="kanan">Total Pembayaran</th></tr></thead>
          <tbody>
            <?php if (!$rekap): ?>
              <tr><td colspan="3" class="kosong">Belum ada data.</td></tr>
            <?php endif; ?>
            <?php foreach ($rekap as $eks => $r): ?>
              <tr>
                <td><?= e($eks) ?></td>
                <td class="tengah"><?= (int)$r['jumlah'] ?></td>
                <td class="kanan"><?= e(rupiah($r['total'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="kartu">
      <h2>Buku Terlaris</h2>
      <?php if (!$terjual): ?>
        <p class="kosong">Belum ada pesanan dengan rincian buku.</p>
      <?php else: ?>
        <ol class="peringkat">
          <?php foreach (array_slice($terjual, 0, 5, true) as $nama => $jml): ?>
            <li><span><?= e($nama) ?></span><b><?= (int)$jml ?> eks.</b></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
