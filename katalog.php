<?php
/**
 * katalog.php — KATALOG BUKU
 * - Pencarian & filter memakai metode GET (Latihan 5): ?kata=...&jenis=...
 * - Tombol "Tambah ke keranjang" memakai metode POST (mengubah data).
 */
require_once __DIR__ . '/includes/config.php';
$user = wajibLogin();

// ---- Proses tambah ke keranjang (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kataLama = inputPost('kembali');
    $kembali  = 'katalog.php' . ($kataLama !== '' ? '?kata=' . urlencode($kataLama) : '');
    if (!csrfValid()) {
        setFlash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
    } else {
        $kode  = inputPost('kode');
        $error = tambahKeKeranjang($kode, $_POST['qty'] ?? '');
        if ($error !== null) {
            setFlash('error', $error);
        } else {
            $buku = cariBuku($kode);
            setFlash('sukses', '"' . $buku['namaBarang'] . '" ditambahkan ke keranjang.');
        }
    }
    redirect($kembali);
}

// ---- Pencarian (GET) ----
$kata  = inputGet('kata');
$jenis = inputGet('jenis');
$hasil = [];
foreach (getKatalog() as $buku) {
    $cocokKata  = $kata === '' || stripos($buku['kodeBarang'] . ' ' . $buku['namaBarang'], $kata) !== false;
    $cocokJenis = $jenis === '' || $buku['jenisBarang'] === $jenis;
    if ($cocokKata && $cocokJenis) {
        $hasil[] = $buku;
    }
}

$judul = 'Katalog Buku';
$menu  = 'katalog';
require __DIR__ . '/includes/header.php';
?>
    <h1 class="judul-halaman">Katalog Buku</h1>
    <p class="sub-judul">Pilih buku yang Anda butuhkan, tentukan jumlahnya, lalu lanjutkan ke pemesanan.</p>

    <form class="kartu form-cari" method="GET" action="katalog.php" role="search">
      <div class="form-group">
        <label for="kata">Kata kunci</label>
        <input type="text" id="kata" name="kata" value="<?= e($kata) ?>" placeholder="Judul atau kode buku">
      </div>
      <div class="form-group">
        <label for="jenis">Jenis</label>
        <select id="jenis" name="jenis">
          <option value="">Semua jenis</option>
          <?php foreach (JENIS_BUKU as $j): ?>
            <option value="<?= e($j) ?>"<?= $j === $jenis ? ' selected' : '' ?>><?= e($j) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn">Cari</button>
      <?php if ($kata !== '' || $jenis !== ''): ?>
        <a class="btn btn-garis" href="katalog.php">Reset</a>
      <?php endif; ?>
    </form>

    <p class="info-hasil"><?= count($hasil) ?> buku ditemukan<?= $kata !== '' ? ' untuk &ldquo;' . e($kata) . '&rdquo;' : '' ?>.</p>

    <?php if (!$hasil): ?>
      <div class="kartu kosong">Tidak ada buku yang cocok. Coba kata kunci lain.</div>
    <?php else: ?>
      <section class="grid-buku" aria-label="Daftar buku">
        <?php foreach ($hasil as $buku): ?>
          <article class="kartu-buku">
            <?= coverBuku($buku, 'cover-besar') ?>
            <div class="info-buku">
              <h2><?= e($buku['namaBarang']) ?></h2>
              <p class="meta"><?= e($buku['kodeBarang']) ?> · <?= e($buku['jenisBarang']) ?> · Edisi <?= (int)$buku['edisi'] ?></p>
              <p class="harga"><?= e(rupiah($buku['harga'])) ?></p>
              <p><?= badgeStok((int)$buku['stok']) ?></p>

              <?php if ($buku['stok'] > 0): ?>
                <form method="POST" action="katalog.php" class="form-beli">
                  <?= csrfField() ?>
                  <input type="hidden" name="kode" value="<?= e($buku['kodeBarang']) ?>">
                  <input type="hidden" name="kembali" value="<?= e($kata) ?>">
                  <label class="sr-only" for="qty-<?= e($buku['kodeBarang']) ?>">Jumlah</label>
                  <input type="number" id="qty-<?= e($buku['kodeBarang']) ?>" name="qty" value="1"
                         min="1" max="<?= (int)$buku['stok'] ?>" step="1" class="input-qty">
                  <button type="submit" class="btn btn-emas">+ Keranjang</button>
                </form>
              <?php else: ?>
                <button type="button" class="btn" disabled>Stok habis</button>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
