<?php
/**
 * tracking.php — INFORMASI PENGIRIMAN
 * - Pencarian nomor DO memakai GET: tracking.php?do=20230012
 * - Pembeli hanya dapat melacak pesanannya sendiri; Admin dapat melacak semua dan
 *   memperbarui status pengiriman (POST).
 */
require_once __DIR__ . '/includes/config.php';
$user = wajibLogin();

$errors = [];

// ---- Admin memperbarui status (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    wajibAdmin();
    $noDO = inputPost('do');
    if (!csrfValid()) {
        setFlash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        redirect('tracking.php?do=' . urlencode($noDO));
    }
    $p = cariPesanan($noDO, $user);
    $statusBaru = inputPost('status');
    $ket = inputPost('keterangan');

    if ($p === null) {
        setFlash('error', 'Pesanan tidak ditemukan.');
        redirect('tracking.php');
    }
    if (!in_array($statusBaru, TAHAP, true)) {
        setFlash('error', 'Status tidak valid.');
    } elseif (mb_strlen($ket) > 200) {
        setFlash('error', 'Keterangan maksimal 200 karakter.');
    } else {
        if ($ket === '') {
            $ket = ($statusBaru === 'Terkirim')
                ? 'Selesai Antar. Penerima: ' . $p['nama']
                : 'Status diperbarui menjadi ' . $statusBaru;
        }
        $p['status'] = $statusBaru;
        $p['perjalanan'][] = ['waktu' => date('Y-m-d H:i:s'), 'keterangan' => $ket];
        simpanPesanan($p);
        setFlash('sukses', 'Status pesanan ' . $noDO . ' diperbarui menjadi "' . $statusBaru . '".');
    }
    redirect('tracking.php?do=' . urlencode($noDO));
}

// ---- Pencarian (GET) ----
$noDO  = inputGet('do');
$hasil = null;
if (isset($_GET['do'])) {
    if ($noDO === '') {
        $errors['do'] = 'Nomor Delivery Order wajib diisi.';
    } elseif (!preg_match('/^[0-9]{4,12}$/', $noDO)) {
        $errors['do'] = 'Nomor DO hanya berisi angka (4–12 digit).';
    } else {
        $hasil = cariPesanan($noDO, $user);
        if ($hasil === null) {
            $errors['do'] = 'Nomor Delivery Order "' . $noDO . '" tidak ditemukan.';
        }
    }
}
$contoh = array_slice(array_keys(pesananUntukUser($user)), 0, 5);

$judul = 'Tracking Pengiriman';
$menu  = 'tracking';
require __DIR__ . '/includes/header.php';
?>
    <h1 class="judul-halaman">Informasi Pengiriman</h1>
    <p class="sub-judul">Masukkan Nomor Delivery Order untuk melacak status pesanan.</p>

    <section class="kartu" aria-labelledby="hCari">
      <h2 id="hCari">Cari Nomor Delivery Order</h2>
      <form method="GET" action="tracking.php" novalidate>
        <div class="form-group<?= kelasError($errors, 'do') ?>">
          <label for="do">Nomor Delivery Order</label>
          <div class="cari-box">
            <input type="text" id="do" name="do" value="<?= e($noDO) ?>" placeholder="Contoh: 20230012"
                   inputmode="numeric" autocomplete="off">
            <button type="submit" class="btn">Cari</button>
          </div>
          <?= pesanError($errors, 'do') ?>
        </div>
      </form>
      <?php if ($contoh): ?>
        <div class="chip-wrap">Pesanan <?= isAdmin() ? 'terbaru' : 'Anda' ?>:
          <?php foreach ($contoh as $no): ?>
            <a class="chip" href="tracking.php?do=<?= urlencode((string)$no) ?>"><?= e($no) ?></a>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="catatan">Anda belum memiliki pesanan. <a href="katalog.php">Mulai belanja</a>.</p>
      <?php endif; ?>
    </section>

    <?php if ($hasil !== null):
        $st     = statusAkhir($hasil);
        $persen = (int)round($st['level'] / count(TAHAP) * 100);
        $rute   = array_reverse($hasil['perjalanan'] ?? []);
    ?>
    <article class="kartu hasil-kartu" aria-live="polite">
      <div class="hasil-judul">
        <h2>Hasil Pelacakan — <?= e($hasil['nomorDO']) ?></h2>
        <span class="badge <?= e($st['kelas']) ?>"><?= e($st['label']) ?></span>
      </div>

      <dl class="detail-grid">
        <div><dt>Nama Pemesan</dt><dd><?= e($hasil['nama']) ?></dd></div>
        <div><dt>Ekspedisi</dt><dd><?= e($hasil['ekspedisi']) ?></dd></div>
        <div><dt>Tanggal Kirim</dt><dd><?= e(formatTanggal($hasil['tanggalKirim'])) ?></dd></div>
        <div><dt>Jenis Paket</dt><dd><?= e($hasil['paket']) ?><?= isset($hasil['kodePaket']) ? ' · ' . e($hasil['kodePaket']) : '' ?></dd></div>
        <div><dt>Pembayaran</dt><dd><?= e($hasil['pembayaran'] ?? '-') ?></dd></div>
        <div><dt>Total Pembayaran</dt><dd><?= e(rupiah($hasil['total'])) ?></dd></div>
        <?php if (isset($hasil['alamat'])): ?>
          <div class="lebar"><dt>Alamat Tujuan</dt><dd><?= e($hasil['alamat']) ?></dd></div>
        <?php endif; ?>
      </dl>

      <h3>Status Pengiriman</h3>
      <div class="progress" role="progressbar" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-isi <?= e($st['kelas']) ?>" style="width: <?= $persen ?>%"></div>
      </div>
      <p class="progress-teks"><?= $persen ?>% — <?= e($st['label']) ?></p>

      <ol class="tahap">
        <?php foreach (TAHAP as $i => $namaTahap):
            $nomor = $i + 1;
            $kelas = $nomor < $st['level'] ? 'selesai' : ($nomor === $st['level'] ? 'aktif' : '');
            $tanda = ($nomor < $st['level'] || $st['level'] === count(TAHAP)) ? '✔' : $nomor;
        ?>
          <li class="<?= $kelas ?>"><span class="no-tahap"><?= $tanda ?></span><?= e($namaTahap) ?></li>
        <?php endforeach; ?>
      </ol>

      <h3>Riwayat Perjalanan</h3>
      <ol class="timeline">
        <?php foreach ($rute as $i => $r): ?>
          <li class="<?= $i === 0 ? 'terbaru' : '' ?>">
            <time><?= e(formatWaktu($r['waktu'])) ?></time>
            <span><?= e($r['keterangan']) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>

      <?php if (!empty($hasil['items'])): ?>
        <h3>Rincian Buku</h3>
        <div class="tabel-wrap">
          <table>
            <thead><tr><th>Judul</th><th class="tengah">Jumlah</th><th class="kanan">Harga</th><th class="kanan">Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($hasil['items'] as $it): ?>
                <tr>
                  <td><?= e($it['nama']) ?></td>
                  <td class="tengah"><?= (int)$it['qty'] ?></td>
                  <td class="kanan"><?= e(rupiah($it['harga'])) ?></td>
                  <td class="kanan"><?= e(rupiah($it['harga'] * $it['qty'])) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!empty($hasil['diskon'])): ?>
                <tr><td colspan="3" class="kanan">Diskon</td><td class="kanan">− <?= e(rupiah($hasil['diskon'])) ?></td></tr>
              <?php endif; ?>
              <tr><td colspan="3" class="kanan">Ongkos kirim</td><td class="kanan"><?= e(rupiah($hasil['ongkir'] ?? 0)) ?></td></tr>
              <tr class="baris-total"><td colspan="3" class="kanan">Total</td><td class="kanan"><?= e(rupiah($hasil['total'])) ?></td></tr>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php if (isAdmin()): ?>
        <h3>Perbarui Status (Admin)</h3>
        <form method="POST" action="tracking.php" class="form-status">
          <?= csrfField() ?>
          <input type="hidden" name="do" value="<?= e($hasil['nomorDO']) ?>">
          <div class="form-group">
            <label for="status">Status baru</label>
            <select id="status" name="status">
              <?php foreach (TAHAP as $t): ?>
                <option value="<?= e($t) ?>"<?= $hasil['status'] === $t ? ' selected' : '' ?>><?= e($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="keterangan">Keterangan (opsional)</label>
            <input type="text" id="keterangan" name="keterangan" maxlength="200" placeholder="Contoh: Tiba di hub Bandung">
          </div>
          <button type="submit" class="btn btn-emas">Simpan status</button>
        </form>
      <?php endif; ?>
    </article>
    <?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
