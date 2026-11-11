<?php
/**
 * stok.php — INFORMASI STOK / KATALOG (CRUD sederhana)
 *  Read   : semua pengguna melihat tabel stok (pencarian via GET)
 *  Create : Admin menambah buku baru (+ upload cover lewat $_FILES)
 *  Update : Admin menambah stok (restock)
 *  Delete : Admin menghapus buku tambahan (buku bawaan tidak bisa dihapus)
 */
require_once __DIR__ . '/includes/config.php';
$user = wajibLogin();

$errors = [];
$old    = ['kode' => '', 'nama' => '', 'jenis' => '', 'edisi' => '', 'stok' => '', 'harga' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    wajibAdmin();
    $aksi = inputPost('aksi');

    if (!csrfValid()) {
        setFlash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        redirect('stok.php');
    }

    switch ($aksi) {
        // ---------- CREATE ----------
        case 'tambah':
            foreach ($old as $k => $_) {
                $old[$k] = inputPost($k);
            }
            $old['kode'] = strtoupper($old['kode']);

            if (!preg_match('/^[A-Z]{4}[0-9]{4}$/', $old['kode'])) {
                $errors['kode'] = 'Kode harus 4 huruf + 4 angka, contoh: ASIP4302.';
            } elseif (cariBuku($old['kode']) !== null) {
                $errors['kode'] = 'Kode barang sudah terdaftar.';
            }
            if (mb_strlen($old['nama']) < 3 || mb_strlen($old['nama']) > 100) {
                $errors['nama'] = 'Nama buku 3–100 karakter.';
            }
            if (!in_array($old['jenis'], JENIS_BUKU, true)) {
                $errors['jenis'] = 'Pilih jenis barang.';
            }
            if (!ctype_digit($old['edisi']) || (int)$old['edisi'] < 1 || (int)$old['edisi'] > 20) {
                $errors['edisi'] = 'Edisi harus angka 1–20.';
            }
            if (!ctype_digit($old['stok']) || (int)$old['stok'] > 100000) {
                $errors['stok'] = 'Stok harus bilangan bulat 0–100.000.';
            }
            if (!ctype_digit($old['harga']) || (int)$old['harga'] < 1000 || (int)$old['harga'] > 10000000) {
                $errors['harga'] = 'Harga harus angka antara 1.000 dan 10.000.000.';
            }

            $coverPath = null;
            if (!$errors) {
                [$coverPath, $errCover] = simpanCoverUpload($_FILES['cover'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
                if ($errCover !== null) {
                    $errors['cover'] = $errCover;
                }
            }

            if (!$errors) {
                $tambahan   = bacaData('stok_tambahan');
                $tambahan[] = [
                    'kodeBarang'  => $old['kode'],
                    'namaBarang'  => $old['nama'],
                    'jenisBarang' => $old['jenis'],
                    'edisi'       => (int)$old['edisi'],
                    'stok'        => (int)$old['stok'],
                    'harga'       => (int)$old['harga'],
                    'cover'       => $coverPath ?? COVER_DEFAULT,
                ];
                simpanData('stok_tambahan', $tambahan);
                setFlash('sukses', 'Buku "' . $old['nama'] . '" berhasil ditambahkan.');
                redirect('stok.php');
            }
            break;

        // ---------- UPDATE (restock) ----------
        case 'restock':
            $kode = inputPost('kode');
            $jml  = inputPost('jumlah');
            if (cariBuku($kode) === null) {
                setFlash('error', 'Buku tidak ditemukan.');
            } elseif (!ctype_digit($jml) || (int)$jml < 1 || (int)$jml > 100000) {
                setFlash('error', 'Jumlah restock harus bilangan bulat 1–100.000.');
            } else {
                ubahStok($kode, (int)$jml);
                setFlash('sukses', "Stok $kode bertambah " . (int)$jml . ' eksemplar.');
            }
            redirect('stok.php');
            break;

        // ---------- DELETE ----------
        case 'hapus':
            $kode  = inputPost('kode');
            $buku  = cariBuku($kode);
            if ($buku === null || !$buku['tambahan']) {
                setFlash('error', 'Hanya buku yang Anda tambahkan sendiri yang dapat dihapus.');
                redirect('stok.php');
            }
            $sisa = array_values(array_filter(bacaData('stok_tambahan'), function ($b) use ($kode) {
                return $b['kodeBarang'] !== $kode;
            }));
            simpanData('stok_tambahan', $sisa);
            // hapus file cover yang diunggah (jika ada & berada di folder uploads/)
            if (strpos($buku['cover'], 'uploads/') === 0 && is_file(BASE_DIR . '/' . $buku['cover'])) {
                @unlink(BASE_DIR . '/' . $buku['cover']);
            }
            unset($_SESSION['keranjang'][$kode]);
            setFlash('sukses', 'Buku "' . $buku['namaBarang'] . '" dihapus dari katalog.');
            redirect('stok.php');
            break;

        default:
            setFlash('error', 'Aksi tidak dikenal.');
            redirect('stok.php');
    }
}

// ---- Data untuk tampilan ----
$kata    = inputGet('kata');
$katalog = getKatalog();
$tampil  = array_values(array_filter($katalog, function ($b) use ($kata) {
    return $kata === '' || stripos($b['kodeBarang'] . ' ' . $b['namaBarang'] . ' ' . $b['jenisBarang'], $kata) !== false;
}));
$jumlahHabis = count(array_filter($katalog, function ($b) { return $b['stok'] <= 0; }));

$judul = 'Informasi Stok';
$menu  = 'stok';
require __DIR__ . '/includes/header.php';
?>
    <h1 class="judul-halaman">Informasi Stok / Katalog</h1>
    <p class="sub-judul">Data stok diperbarui otomatis setiap ada pesanan baru.</p>

    <section class="stat-grid" aria-label="Ringkasan stok">
      <div class="stat"><b><?= count($katalog) ?></b><small>Judul buku</small></div>
      <div class="stat"><b><?= number_format(array_sum(array_column($katalog, 'stok')), 0, ',', '.') ?></b><small>Total stok</small></div>
      <div class="stat"><b><?= $jumlahHabis ?></b><small>Stok habis</small></div>
    </section>

    <?php if (isAdmin()): ?>
    <section class="kartu" aria-labelledby="hTambah">
      <h2 id="hTambah">Tambah Buku Baru</h2>
      <form method="POST" action="stok.php" enctype="multipart/form-data" novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="aksi" value="tambah">
        <div class="baris-form">
          <div class="form-group<?= kelasError($errors, 'kode') ?>">
            <label for="kode">Kode Barang</label>
            <input type="text" id="kode" name="kode" value="<?= e($old['kode']) ?>" maxlength="8" placeholder="ASIP4302">
            <?= pesanError($errors, 'kode') ?>
          </div>
          <div class="form-group<?= kelasError($errors, 'nama') ?>">
            <label for="nama">Nama Barang</label>
            <input type="text" id="nama" name="nama" value="<?= e($old['nama']) ?>" placeholder="Judul buku">
            <?= pesanError($errors, 'nama') ?>
          </div>
          <div class="form-group<?= kelasError($errors, 'jenis') ?>">
            <label for="jenis">Jenis Barang</label>
            <select id="jenis" name="jenis">
              <option value="">— Pilih jenis —</option>
              <?php foreach (JENIS_BUKU as $j): ?>
                <option value="<?= e($j) ?>"<?= $old['jenis'] === $j ? ' selected' : '' ?>><?= e($j) ?></option>
              <?php endforeach; ?>
            </select>
            <?= pesanError($errors, 'jenis') ?>
          </div>
          <div class="form-group<?= kelasError($errors, 'edisi') ?>">
            <label for="edisi">Edisi</label>
            <input type="number" id="edisi" name="edisi" value="<?= e($old['edisi']) ?>" min="1" max="20">
            <?= pesanError($errors, 'edisi') ?>
          </div>
          <div class="form-group<?= kelasError($errors, 'stok') ?>">
            <label for="stok">Jumlah Stok</label>
            <input type="number" id="stok" name="stok" value="<?= e($old['stok']) ?>" min="0">
            <?= pesanError($errors, 'stok') ?>
          </div>
          <div class="form-group<?= kelasError($errors, 'harga') ?>">
            <label for="harga">Harga (Rp)</label>
            <input type="number" id="harga" name="harga" value="<?= e($old['harga']) ?>" min="1000" placeholder="175000">
            <?= pesanError($errors, 'harga') ?>
          </div>
          <div class="form-group<?= kelasError($errors, 'cover') ?>">
            <label for="cover">Cover (opsional)</label>
            <input type="file" id="cover" name="cover" accept="image/jpeg,image/png,image/webp">
            <?= pesanError($errors, 'cover') ?>
          </div>
        </div>
        <button type="submit" class="btn btn-emas">+ Tambah Buku</button>
      </form>
    </section>
    <?php else: ?>
      <div class="alert alert-info">Penambahan buku dan restock hanya dapat dilakukan oleh Admin.</div>
    <?php endif; ?>

    <section class="kartu" aria-labelledby="hTabel">
      <h2 id="hTabel">Daftar Stok Buku</h2>
      <form method="GET" action="stok.php" class="cari-box" role="search">
        <label class="sr-only" for="cariStok">Cari buku</label>
        <input type="text" id="cariStok" name="kata" value="<?= e($kata) ?>" placeholder="Cari kode, nama, atau jenis…">
        <button type="submit" class="btn">Cari</button>
        <?php if ($kata !== ''): ?><a class="btn btn-garis" href="stok.php">Reset</a><?php endif; ?>
      </form>

      <div class="tabel-wrap">
        <table>
          <thead>
            <tr>
              <th class="tengah">No</th><th>Cover</th><th>Kode</th><th>Nama Barang</th><th>Jenis</th>
              <th class="tengah">Edisi</th><th class="tengah">Stok</th><th class="kanan">Harga</th>
              <?php if (isAdmin()): ?><th>Aksi</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (!$tampil): ?>
              <tr><td colspan="<?= isAdmin() ? 9 : 8 ?>" class="kosong">Data tidak ditemukan.</td></tr>
            <?php endif; ?>
            <?php foreach ($tampil as $i => $b): ?>
              <tr>
                <td class="tengah"><?= $i + 1 ?></td>
                <td><?= coverBuku($b, 'cover') ?></td>
                <td><?= e($b['kodeBarang']) ?></td>
                <td><?= e($b['namaBarang']) ?></td>
                <td><?= e($b['jenisBarang']) ?></td>
                <td class="tengah"><?= (int)$b['edisi'] ?></td>
                <td class="tengah"><?= badgeStok((int)$b['stok']) ?></td>
                <td class="kanan"><?= e(rupiah($b['harga'])) ?></td>
                <?php if (isAdmin()): ?>
                <td>
                  <form method="POST" action="stok.php" class="form-baris">
                    <?= csrfField() ?>
                    <input type="hidden" name="aksi" value="restock">
                    <input type="hidden" name="kode" value="<?= e($b['kodeBarang']) ?>">
                    <label class="sr-only" for="rs-<?= e($b['kodeBarang']) ?>">Jumlah restock</label>
                    <input type="number" id="rs-<?= e($b['kodeBarang']) ?>" name="jumlah" min="1" placeholder="+ stok" class="input-qty">
                    <button type="submit" class="btn btn-kecil">Tambah</button>
                  </form>
                  <?php if ($b['tambahan']): ?>
                    <form method="POST" action="stok.php" class="form-baris" data-konfirm="Hapus buku &quot;<?= e($b['namaBarang']) ?>&quot; dari katalog?">
                      <?= csrfField() ?>
                      <input type="hidden" name="aksi" value="hapus">
                      <input type="hidden" name="kode" value="<?= e($b['kodeBarang']) ?>">
                      <button type="submit" class="btn btn-kecil btn-bahaya">Hapus</button>
                    </form>
                  <?php endif; ?>
                </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
