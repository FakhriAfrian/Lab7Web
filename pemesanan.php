<?php
/**
 * pemesanan.php — KERANJANG & CHECKOUT
 * Alur: tambah buku -> ubah jumlah -> isi data pemesan (POST) -> validasi server-side
 *       -> simpan pesanan + kurangi stok -> redirect ke halaman tracking (pola PRG).
 * Pemrosesan (di atas) dipisahkan dari tampilan HTML (di bawah).
 */
require_once __DIR__ . '/includes/config.php';
$user = wajibLogin();

$errors = [];
$old = [
    'nama' => $user['nama'], 'email' => $user['email'], 'telepon' => '', 'kota' => '',
    'kodepos' => '', 'alamat' => '', 'ekspedisi' => '', 'paket' => '', 'metode' => '', 'setuju' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = inputPost('aksi');

    if (!csrfValid()) {
        setFlash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        redirect('pemesanan.php');
    }

    switch ($aksi) {
        case 'tambah':
            $error = tambahKeKeranjang(inputPost('kode'), $_POST['qty'] ?? '');
            setFlash($error === null ? 'sukses' : 'error', $error ?? 'Buku ditambahkan ke keranjang.');
            redirect('pemesanan.php');
            break;

        case 'ubah': // perbarui jumlah semua item, hapus satu item, atau kosongkan keranjang
            if (isset($_POST['kosongkan'])) {
                $_SESSION['keranjang'] = [];
                setFlash('sukses', 'Keranjang dikosongkan.');
                redirect('pemesanan.php');
            }
            if (isset($_POST['hapus']) && is_string($_POST['hapus'])) {
                unset($_SESSION['keranjang'][$_POST['hapus']]);
                setFlash('sukses', 'Item dihapus dari keranjang.');
                redirect('pemesanan.php');
            }
            $qtyBaru = is_array($_POST['qty'] ?? null) ? $_POST['qty'] : [];
            foreach ($qtyBaru as $kode => $qty) {
                $buku = cariBuku((string)$kode);
                if ($buku === null || !isset($_SESSION['keranjang'][$kode])) {
                    continue;
                }
                if (!is_string($qty) || !ctype_digit($qty)) {
                    setFlash('error', 'Jumlah untuk "' . $buku['namaBarang'] . '" harus bilangan bulat.');
                    continue;
                }
                $n = (int)$qty;
                if ($n === 0) {
                    unset($_SESSION['keranjang'][$kode]);
                } elseif ($n > $buku['stok']) {
                    setFlash('error', 'Stok "' . $buku['namaBarang'] . '" hanya ' . $buku['stok'] . '. Jumlah tidak diubah.');
                } else {
                    $_SESSION['keranjang'][$kode] = $n;
                }
            }
            redirect('pemesanan.php');
            break;

        case 'checkout':
            foreach ($old as $k => $_) {
                $old[$k] = inputPost($k);
            }
            $items = isiKeranjang();

            if (!$items) {
                $errors['umum'] = 'Keranjang masih kosong. Tambahkan minimal satu buku.';
            }
            // pastikan stok masih cukup saat checkout
            foreach ($items as $it) {
                if ($it['qty'] > $it['stok']) {
                    $errors['umum'] = 'Stok "' . $it['nama'] . '" tinggal ' . $it['stok'] . '. Perbarui jumlah di keranjang.';
                    break;
                }
            }

            if (mb_strlen($old['nama']) < 3) {
                $errors['nama'] = 'Nama minimal 3 karakter.';
            }
            if (!emailValid($old['email'])) {
                $errors['email'] = 'Format email tidak valid.';
            }
            if (!preg_match('/^(\+62|62|0)8[0-9]{8,12}$/', $old['telepon'])) {
                $errors['telepon'] = 'Nomor HP tidak valid (contoh: 081234567890).';
            }
            if (mb_strlen($old['kota']) < 3) {
                $errors['kota'] = 'Kota wajib diisi.';
            }
            if (!preg_match('/^[0-9]{5}$/', $old['kodepos'])) {
                $errors['kodepos'] = 'Kode pos harus 5 digit angka.';
            }
            if (mb_strlen($old['alamat']) < 10) {
                $errors['alamat'] = 'Alamat minimal 10 karakter.';
            }
            if (!in_array($old['ekspedisi'], EKSPEDISI, true)) {
                $errors['ekspedisi'] = 'Pilih ekspedisi.';
            }
            if (!array_key_exists($old['paket'], ONGKIR)) {
                $errors['paket'] = 'Pilih jenis paket.';
            }
            if (!in_array($old['metode'], METODE_BAYAR, true)) {
                $errors['metode'] = 'Pilih metode pembayaran.';
            }
            if ($old['setuju'] !== '1') {
                $errors['setuju'] = 'Anda harus menyetujui syarat pemesanan.';
            }

            if (!$errors) {
                $ring = hitungRingkasan($items, $old['paket']);
                $noDO = buatNomorDO();

                $pesanan = [
                    'nomorDO'      => $noDO,
                    'nama'         => $old['nama'],
                    'akun'         => $user['email'],
                    'email'        => $old['email'],
                    'telepon'      => $old['telepon'],
                    'alamat'       => $old['alamat'] . ', ' . $old['kota'] . ' ' . $old['kodepos'],
                    'status'       => 'Diproses',
                    'ekspedisi'    => $old['ekspedisi'],
                    'tanggalKirim' => date('Y-m-d'),
                    'paket'        => $old['paket'],
                    'pembayaran'   => $old['metode'],
                    'items'        => array_map(function ($it) {
                        return ['kode' => $it['kode'], 'nama' => $it['nama'], 'harga' => $it['harga'], 'qty' => $it['qty']];
                    }, $items),
                    'subtotal'     => $ring['subtotal'],
                    'diskon'       => $ring['diskon'],
                    'ongkir'       => $ring['ongkir'],
                    'total'        => $ring['total'],
                    'perjalanan'   => [
                        ['waktu' => date('Y-m-d H:i:s'), 'keterangan' => 'Pesanan diterima dan sedang diproses oleh toko'],
                    ],
                ];

                if (simpanPesanan($pesanan)) {
                    foreach ($items as $it) {
                        ubahStok($it['kode'], -$it['qty']);
                    }
                    $_SESSION['keranjang'] = [];
                    setFlash('sukses', "Pesanan berhasil dibuat! Nomor Delivery Order Anda: $noDO");
                    redirect('tracking.php?do=' . urlencode($noDO));
                }
                $errors['umum'] = 'Pesanan tidak dapat disimpan. Pastikan folder data/ dapat ditulis.';
            }
            break;

        default:
            setFlash('error', 'Aksi tidak dikenal.');
            redirect('pemesanan.php');
    }
}

// ---- Data untuk tampilan ----
$items = isiKeranjang();
$ring  = hitungRingkasan($items, $old['paket']);
$katalog = getKatalog();

$judul = 'Pemesanan';
$menu  = 'pemesanan';
require __DIR__ . '/includes/header.php';
?>
    <h1 class="judul-halaman">Pemesanan Buku</h1>
    <p class="sub-judul">Periksa isi keranjang, lengkapi data pengiriman, lalu buat pesanan.</p>

    <?php if (isset($errors['umum'])): ?>
      <div class="alert alert-error" role="alert"><?= e($errors['umum']) ?></div>
    <?php elseif ($errors): ?>
      <div class="alert alert-error" role="alert">Data belum lengkap atau belum valid. Periksa kolom yang ditandai merah.</div>
    <?php endif; ?>

    <div class="checkout-grid">
      <div class="kolom-utama">

        <!-- 1. Keranjang -->
        <section class="kartu" aria-labelledby="h1">
          <h2 id="h1">1. Daftar Pesanan</h2>

          <form method="POST" action="pemesanan.php" class="form-tambah">
            <?= csrfField() ?>
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-group">
              <label for="pilihBuku">Tambah buku</label>
              <select id="pilihBuku" name="kode" required>
                <option value="">— Pilih buku —</option>
                <?php foreach ($katalog as $b): ?>
                  <option value="<?= e($b['kodeBarang']) ?>"<?= $b['stok'] <= 0 ? ' disabled' : '' ?>>
                    <?= e($b['namaBarang']) ?> — <?= e(rupiah($b['harga'])) ?><?= $b['stok'] <= 0 ? ' (habis)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="qtyBaru">Jumlah</label>
              <input type="number" id="qtyBaru" name="qty" value="1" min="1" step="1">
            </div>
            <button type="submit" class="btn btn-emas">+ Tambah</button>
          </form>

          <?php if (!$items): ?>
            <p class="kosong">Keranjang masih kosong. <a href="katalog.php">Lihat katalog buku</a>.</p>
          <?php else: ?>
            <form method="POST" action="pemesanan.php">
              <?= csrfField() ?>
              <input type="hidden" name="aksi" value="ubah">
              <div class="tabel-wrap">
                <table>
                  <thead>
                    <tr><th>Buku</th><th class="kanan">Harga</th><th class="tengah">Jumlah</th><th class="kanan">Subtotal</th><th class="tengah">Aksi</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($items as $it): ?>
                      <tr>
                        <td class="sel-buku"><img class="cover" src="<?= e($it['cover']) ?>" alt=""><span><?= e($it['nama']) ?><small>Stok: <?= (int)$it['stok'] ?></small></span></td>
                        <td class="kanan"><?= e(rupiah($it['harga'])) ?></td>
                        <td class="tengah">
                          <label class="sr-only" for="q-<?= e($it['kode']) ?>">Jumlah <?= e($it['nama']) ?></label>
                          <input type="number" class="input-qty" id="q-<?= e($it['kode']) ?>" name="qty[<?= e($it['kode']) ?>]"
                                 value="<?= (int)$it['qty'] ?>" min="0" max="<?= (int)$it['stok'] ?>">
                        </td>
                        <td class="kanan"><?= e(rupiah($it['subtotal'])) ?></td>
                        <td class="tengah"><button type="submit" name="hapus" value="<?= e($it['kode']) ?>" class="btn btn-kecil btn-bahaya">Hapus</button></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="aksi-keranjang">
                <button type="submit" class="btn btn-garis">Perbarui jumlah</button>
                <button type="submit" name="kosongkan" value="1" class="btn btn-garis btn-teks-bahaya" formnovalidate
                        data-konfirm-tombol="Kosongkan seluruh keranjang?">Kosongkan keranjang</button>
              </div>
            </form>
            <p class="catatan">Isi jumlah 0 lalu klik &ldquo;Perbarui jumlah&rdquo; untuk menghapus item.</p>
          <?php endif; ?>
        </section>

        <!-- 2 & 3. Data pemesan, pengiriman, pembayaran -->
        <form method="POST" action="pemesanan.php" id="formCheckout" novalidate>
          <?= csrfField() ?>
          <input type="hidden" name="aksi" value="checkout">

          <section class="kartu" aria-labelledby="h2">
            <h2 id="h2">2. Data Pemesan</h2>
            <div class="baris-form">
              <div class="form-group<?= kelasError($errors, 'nama') ?>">
                <label for="nama">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="<?= e($old['nama']) ?>" autocomplete="name">
                <?= pesanError($errors, 'nama') ?>
              </div>
              <div class="form-group<?= kelasError($errors, 'email') ?>">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email">
                <?= pesanError($errors, 'email') ?>
              </div>
              <div class="form-group<?= kelasError($errors, 'telepon') ?>">
                <label for="telepon">Nomor HP</label>
                <input type="tel" id="telepon" name="telepon" value="<?= e($old['telepon']) ?>" placeholder="081234567890" autocomplete="tel">
                <?= pesanError($errors, 'telepon') ?>
              </div>
              <div class="form-group<?= kelasError($errors, 'kota') ?>">
                <label for="kota">Kota</label>
                <input type="text" id="kota" name="kota" value="<?= e($old['kota']) ?>" placeholder="Contoh: Bekasi">
                <?= pesanError($errors, 'kota') ?>
              </div>
              <div class="form-group<?= kelasError($errors, 'kodepos') ?>">
                <label for="kodepos">Kode Pos</label>
                <input type="text" id="kodepos" name="kodepos" value="<?= e($old['kodepos']) ?>" maxlength="5" inputmode="numeric" placeholder="17111">
                <?= pesanError($errors, 'kodepos') ?>
              </div>
            </div>
            <div class="form-group<?= kelasError($errors, 'alamat') ?>">
              <label for="alamat">Alamat Lengkap</label>
              <textarea id="alamat" name="alamat" rows="3" placeholder="Jalan, nomor rumah, RT/RW, kelurahan, kecamatan"><?= e($old['alamat']) ?></textarea>
              <?= pesanError($errors, 'alamat') ?>
            </div>
          </section>

          <section class="kartu" aria-labelledby="h3">
            <h2 id="h3">3. Pengiriman &amp; Pembayaran</h2>
            <div class="baris-form">
              <div class="form-group<?= kelasError($errors, 'ekspedisi') ?>">
                <label for="ekspedisi">Ekspedisi</label>
                <select id="ekspedisi" name="ekspedisi">
                  <option value="">— Pilih ekspedisi —</option>
                  <?php foreach (EKSPEDISI as $eks): ?>
                    <option value="<?= e($eks) ?>"<?= $old['ekspedisi'] === $eks ? ' selected' : '' ?>><?= e($eks) ?></option>
                  <?php endforeach; ?>
                </select>
                <?= pesanError($errors, 'ekspedisi') ?>
              </div>
              <div class="form-group<?= kelasError($errors, 'paket') ?>">
                <label for="paket">Jenis Paket</label>
                <select id="paket" name="paket">
                  <option value="">— Pilih paket —</option>
                  <?php foreach (ONGKIR as $nama => $biaya): ?>
                    <option value="<?= e($nama) ?>"<?= $old['paket'] === $nama ? ' selected' : '' ?>><?= e($nama) ?> (<?= e(rupiah($biaya)) ?>)</option>
                  <?php endforeach; ?>
                </select>
                <?= pesanError($errors, 'paket') ?>
              </div>
            </div>

            <fieldset class="form-group<?= kelasError($errors, 'metode') ?>">
              <legend>Metode Pembayaran</legend>
              <div class="opsi-radio">
                <?php foreach (METODE_BAYAR as $m): ?>
                  <label><input type="radio" name="metode" value="<?= e($m) ?>"<?= $old['metode'] === $m ? ' checked' : '' ?>> <?= e($m === 'COD' ? 'COD (bayar di tempat)' : $m) ?></label>
                <?php endforeach; ?>
              </div>
              <?= pesanError($errors, 'metode') ?>
            </fieldset>

            <div class="form-group<?= kelasError($errors, 'setuju') ?>">
              <label class="cek" for="setuju">
                <input type="checkbox" id="setuju" name="setuju" value="1"<?= $old['setuju'] === '1' ? ' checked' : '' ?>>
                Saya menyatakan data di atas sudah benar dan menyetujui syarat pemesanan.
              </label>
              <?= pesanError($errors, 'setuju') ?>
            </div>
          </section>
        </form>
      </div>

      <!-- Ringkasan -->
      <aside class="kartu ringkasan" aria-labelledby="hR"
             data-subtotal="<?= (int)$ring['subtotal'] ?>" data-diskon="<?= (int)$ring['diskon'] ?>"
             data-ada-item="<?= $items ? '1' : '0' ?>"
             data-ongkir="<?= e(json_encode(ONGKIR)) ?>">
        <h2 id="hR">Ringkasan Pembayaran</h2>
        <dl>
          <div><dt>Subtotal</dt><dd><?= e(rupiah($ring['subtotal'])) ?></dd></div>
          <div><dt>Diskon<?= $ring['persen'] ? ' (' . (int)$ring['persen'] . '%)' : '' ?></dt><dd>− <?= e(rupiah($ring['diskon'])) ?></dd></div>
          <div><dt>Ongkos kirim</dt><dd id="rOngkir"><?= e(rupiah($ring['ongkir'])) ?></dd></div>
          <div class="total"><dt>Total</dt><dd id="rTotal"><?= e(rupiah($ring['total'])) ?></dd></div>
        </dl>
        <p class="catatan">Diskon 5% untuk belanja ≥ Rp 300.000 dan 10% untuk belanja ≥ Rp 500.000.</p>
        <button type="submit" form="formCheckout" class="btn btn-blok"<?= $items ? '' : ' disabled' ?>>Buat Pesanan</button>
      </aside>
    </div>
<?php require __DIR__ . '/includes/footer.php'; ?>
