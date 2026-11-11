<?php
/**
 * riwayat.php — HISTORY TRANSAKSI
 * Admin melihat seluruh pesanan, pembeli hanya pesanannya sendiri.
 * Pencarian (nomor DO / nama pemesan) memakai metode GET.
 */
require_once __DIR__ . '/includes/config.php';
$user = wajibLogin();

$kata  = inputGet('kata');
$daftar = array_filter(pesananUntukUser($user), function ($p) use ($kata) {
    return $kata === '' || stripos($p['nomorDO'] . ' ' . $p['nama'], $kata) !== false;
});

$judul = 'Riwayat Transaksi';
$menu  = 'riwayat';
require __DIR__ . '/includes/header.php';
?>
    <h1 class="judul-halaman">Riwayat Transaksi</h1>
    <p class="sub-judul"><?= isAdmin() ? 'Seluruh pesanan di toko.' : 'Semua pesanan yang pernah Anda buat.' ?></p>

    <section class="kartu">
      <form method="GET" action="riwayat.php" class="cari-box" role="search">
        <label class="sr-only" for="kata">Cari pesanan</label>
        <input type="text" id="kata" name="kata" value="<?= e($kata) ?>" placeholder="Cari nomor DO atau nama pemesan…">
        <button type="submit" class="btn">Cari</button>
        <?php if ($kata !== ''): ?><a class="btn btn-garis" href="riwayat.php">Reset</a><?php endif; ?>
      </form>

      <div class="tabel-wrap">
        <table>
          <thead>
            <tr><th>Tanggal</th><th>No. DO</th><th>Pemesan</th><th>Ekspedisi</th><th class="kanan">Total</th><th>Status</th><th class="tengah">Aksi</th></tr>
          </thead>
          <tbody>
            <?php if (!$daftar): ?>
              <tr><td colspan="7" class="kosong">Belum ada transaksi<?= $kata !== '' ? ' yang cocok' : '' ?>.</td></tr>
            <?php endif; ?>
            <?php foreach ($daftar as $p): $st = statusAkhir($p); ?>
              <tr>
                <td><?= e(formatTanggal($p['tanggalKirim'])) ?></td>
                <td><?= e($p['nomorDO']) ?></td>
                <td><?= e($p['nama']) ?></td>
                <td><?= e($p['ekspedisi']) ?></td>
                <td class="kanan"><?= e(rupiah($p['total'])) ?></td>
                <td><span class="badge <?= e($st['kelas']) ?>"><?= e($st['label']) ?></span></td>
                <td class="tengah"><a href="tracking.php?do=<?= urlencode($p['nomorDO']) ?>">Lacak</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
