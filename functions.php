<?php
/**
 * functions.php — APPLICATION TIER (logika bisnis)
 * Materi: function, return, percabangan (if/elseif/switch/ternary),
 *         perulangan (foreach), array, validasi & sanitasi input,
 *         $_SERVER, $_SESSION, $GLOBALS.
 */

/* ============================================================
   1. FUNGSI DASAR: output aman, format, redirect
   ============================================================ */

// Mengubah karakter khusus HTML agar input pengguna tidak dieksekusi sebagai tag (anti-XSS)
function e($teks): string
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// 180000 -> "Rp 180.000"
function rupiah($angka): string
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Sama seperti Latihan 4 pada modul: harga setelah dipotong diskon (persen)
function hitungDiskon($harga, $persen)
{
    $potongan = $harga * ($persen / 100);
    return $harga - $potongan;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// "2025-08-25" -> "25 Agustus 2025"
function formatTanggal(string $iso): string
{
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($iso);
    if ($t === false) {
        return $iso;
    }
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}

// "2025-08-25 14:07:56" -> "25/08/2025 14:07"
function formatWaktu(string $waktu): string
{
    $t = strtotime($waktu);
    return $t === false ? $waktu : date('d/m/Y H:i', $t);
}

// Nama hari berbahasa Indonesia — contoh penggunaan switch (materi Flow Control)
function namaHari(): string
{
    switch (date('N')) {
        case 1:  return 'Senin';
        case 2:  return 'Selasa';
        case 3:  return 'Rabu';
        case 4:  return 'Kamis';
        case 5:  return "Jumat";
        case 6:  return 'Sabtu';
        default: return 'Minggu';
    }
}

// Sapaan sesuai jam server — contoh if / elseif / else
function sapaan(): string
{
    $jam = (int)date('G');
    if ($jam >= 4 && $jam < 11) {
        return 'Selamat pagi';
    } elseif ($jam >= 11 && $jam < 15) {
        return 'Selamat siang';
    } elseif ($jam >= 15 && $jam < 18) {
        return 'Selamat sore';
    } else {
        return 'Selamat malam';
    }
}

/* ============================================================
   2. PENYIMPANAN DATA (Data tier) — file JSON di folder data/
   File diawali baris PHP "exit" sehingga isinya tidak bisa dibaca
   langsung lewat browser (http://.../data/pesanan.json.php).
   ============================================================ */

const GUARD_DATA = "<?php http_response_code(403); exit; ?>\n";

function bacaData(string $nama, array $awal = []): array
{
    $file = DATA_DIR . '/' . $nama . '.json.php';
    if (!is_file($file)) {
        return $awal;
    }
    $isi = file_get_contents($file);
    if ($isi === false) {
        return $awal;
    }
    if (strpos($isi, GUARD_DATA) === 0) {
        $isi = substr($isi, strlen(GUARD_DATA));
    }
    $data = json_decode($isi, true);
    return is_array($data) ? $data : $awal;
}

function simpanData(string $nama, array $data): bool
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return file_put_contents(DATA_DIR . '/' . $nama . '.json.php', GUARD_DATA . $json, LOCK_EX) !== false;
}

/* ============================================================
   3. KEAMANAN: CSRF token & flash message
   ============================================================ */

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function csrfValid(): bool
{
    $kirim = $_POST['csrf'] ?? '';
    return is_string($kirim) && $kirim !== '' && hash_equals(csrfToken(), $kirim);
}

// Pesan sekali tampil (disimpan di session lalu dihapus setelah ditampilkan)
function setFlash(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function ambilFlash(): array
{
    $pesan = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $pesan;
}

/* ============================================================
   4. AUTENTIKASI & OTORISASI
   ============================================================ */

function userLogin(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool
{
    $user = userLogin();
    return $user !== null && $user['role'] === 'Admin';
}

function wajibLogin(): array
{
    $user = userLogin();
    if ($user === null) {
        redirect('index.php');
    }
    return $user;
}

function wajibAdmin(): array
{
    $user = wajibLogin();
    if (!isAdmin()) {
        setFlash('error', 'Halaman tersebut hanya untuk Admin.');
        redirect('dashboard.php');
    }
    return $user;
}

// Semua pengguna = data awal + akun yang mendaftar
function semuaPengguna(): array
{
    global $dataPengguna;
    return array_merge($dataPengguna, bacaData('pengguna'));
}

function cariPengguna(string $email): ?array
{
    $email = strtolower(trim($email));
    foreach (semuaPengguna() as $u) {
        if (strtolower($u['email']) === $email) {
            return $u;
        }
    }
    return null;
}

/* ============================================================
   5. VALIDASI INPUT (selalu dilakukan di sisi server)
   ============================================================ */

function emailValid(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Mengambil input POST sebagai string yang sudah di-trim
function inputPost(string $kunci): string
{
    $nilai = $_POST[$kunci] ?? '';
    return is_string($nilai) ? trim($nilai) : '';
}

function inputGet(string $kunci): string
{
    $nilai = $_GET[$kunci] ?? '';
    return is_string($nilai) ? trim($nilai) : '';
}

// Menampilkan pesan error di bawah sebuah field
function pesanError(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<small class="pesan-error">' . e($errors[$field]) . '</small>'
        : '';
}

function kelasError(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' error' : '';
}

/* ============================================================
   6. KATALOG BUKU & STOK
   ============================================================ */

// Katalog = data awal + buku tambahan admin; stok sudah disesuaikan
// dengan pesanan (berkurang) dan restock (bertambah).
function getKatalog(): array
{
    global $dataKatalogBuku;
    $semua = array_merge($dataKatalogBuku, bacaData('stok_tambahan'));
    $delta = bacaData('stok_delta');

    foreach ($semua as $i => $buku) {
        $kode = $buku['kodeBarang'];
        $semua[$i]['stok'] = max(0, (int)$buku['stok'] + (int)($delta[$kode] ?? 0));
        $semua[$i]['tambahan'] = $i >= count($dataKatalogBuku);
    }
    return $semua;
}

function cariBuku(string $kode): ?array
{
    foreach (getKatalog() as $buku) {
        if ($buku['kodeBarang'] === $kode) {
            return $buku;
        }
    }
    return null;
}

function ubahStok(string $kode, int $selisih): void
{
    $delta = bacaData('stok_delta');
    $delta[$kode] = (int)($delta[$kode] ?? 0) + $selisih;
    simpanData('stok_delta', $delta);
}

// Label & kelas badge berdasarkan jumlah stok
function badgeStok(int $stok): string
{
    if ($stok <= 0) {
        return '<span class="badge habis">Habis</span>';
    } elseif ($stok < 100) {
        return '<span class="badge proses">Menipis · ' . $stok . '</span>';
    }
    return '<span class="badge sukses">Tersedia · ' . $stok . '</span>';
}

function coverBuku(array $buku, string $kelas = 'cover'): string
{
    $src = $buku['cover'] ?? COVER_DEFAULT;
    return '<img class="' . e($kelas) . '" src="' . e($src) . '" alt="Sampul buku ' . e($buku['namaBarang']) . '" loading="lazy">';
}

/**
 * Menyimpan cover yang diunggah ($_FILES). Mengembalikan [path|null, pesanError|null].
 * Dicek: kode error upload, ukuran, dan isi file (benar-benar gambar), bukan sekadar ekstensi.
 */
function simpanCoverUpload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload cover gagal. Coba lagi.'];
    }
    if ($file['size'] > MAKS_UPLOAD) {
        return [null, 'Ukuran cover maksimal 2 MB.'];
    }
    $info = @getimagesize($file['tmp_name']);
    $ekstensi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($ekstensi[$info[2]])) {
        return [null, 'Cover harus berupa gambar JPG, PNG, atau WEBP.'];
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $nama = 'cover_' . bin2hex(random_bytes(8)) . '.' . $ekstensi[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $nama)) {
        return [null, 'Cover tidak dapat disimpan di server.'];
    }
    return ['uploads/' . $nama, null];
}

/* ============================================================
   7. KERANJANG (disimpan di $_SESSION['keranjang'] = [kodeBarang => jumlah])
   ============================================================ */

function jumlahItemKeranjang(): int
{
    return array_sum($_SESSION['keranjang'] ?? []);
}

// Menambah buku ke keranjang. Mengembalikan pesan error, atau null jika berhasil.
function tambahKeKeranjang(string $kode, $qtyInput): ?string
{
    $buku = cariBuku($kode);
    if ($buku === null) {
        return 'Buku tidak ditemukan.';
    }
    if (!ctype_digit((string)$qtyInput) || (int)$qtyInput < 1) {
        return 'Jumlah harus berupa bilangan bulat minimal 1.';
    }
    $qty   = (int)$qtyInput;
    $total = ($_SESSION['keranjang'][$kode] ?? 0) + $qty;
    if ($total > $buku['stok']) {
        return 'Stok "' . $buku['namaBarang'] . '" hanya ' . $buku['stok'] . ' eksemplar.';
    }
    $_SESSION['keranjang'][$kode] = $total;
    return null;
}

// Isi keranjang lengkap dengan detail buku. Harga selalu diambil dari katalog
// (bukan dari input pengguna) supaya tidak bisa dimanipulasi.
function isiKeranjang(): array
{
    $isi = [];
    foreach ($_SESSION['keranjang'] ?? [] as $kode => $qty) {
        $buku = cariBuku((string)$kode);
        if ($buku === null) {
            unset($_SESSION['keranjang'][$kode]);
            continue;
        }
        $isi[] = [
            'kode'     => $buku['kodeBarang'],
            'nama'     => $buku['namaBarang'],
            'harga'    => (int)$buku['harga'],
            'qty'      => (int)$qty,
            'stok'     => (int)$buku['stok'],
            'subtotal' => (int)$buku['harga'] * (int)$qty,
            'cover'    => $buku['cover'] ?? COVER_DEFAULT,
        ];
    }
    return $isi;
}

// Diskon bertingkat berdasarkan subtotal
function persenDiskon(int $subtotal): int
{
    if ($subtotal >= 500000) {
        return 10;
    } elseif ($subtotal >= 300000) {
        return 5;
    }
    return 0;
}

function hitungRingkasan(array $items, string $paket): array
{
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['subtotal'];
    }
    $persen = persenDiskon($subtotal);
    $diskon = (int)round($subtotal - hitungDiskon($subtotal, $persen));
    $ongkir = (count($items) > 0 && isset(ONGKIR[$paket])) ? ONGKIR[$paket] : 0;

    return [
        'subtotal' => $subtotal,
        'persen'   => $persen,
        'diskon'   => $diskon,
        'ongkir'   => $ongkir,
        'total'    => $subtotal - $diskon + $ongkir,
    ];
}

/* ============================================================
   8. PESANAN & TRACKING
   ============================================================ */

// Pesanan = data awal ($dataTracking) ditimpa/ditambah pesanan tersimpan.
function semuaPesanan(): array
{
    global $dataTracking;
    // array_replace (bukan array_merge) agar kunci numerik nomor DO tidak diberi nomor ulang
    $gabung = array_replace($dataTracking, bacaData('pesanan'));
    uasort($gabung, function ($a, $b) {
        return strcmp($b['tanggalKirim'], $a['tanggalKirim']) ?: strcmp($b['nomorDO'], $a['nomorDO']);
    });
    return $gabung;
}

// Admin melihat semua pesanan, pengguna biasa hanya miliknya sendiri.
function pesananUntukUser(array $user): array
{
    $semua = semuaPesanan();
    if ($user['role'] === 'Admin') {
        return $semua;
    }
    return array_filter($semua, function ($p) use ($user) {
        return strtolower($p['akun'] ?? '') === strtolower($user['email']);
    });
}

function cariPesanan(string $noDO, array $user): ?array
{
    $milik = pesananUntukUser($user);
    return $milik[$noDO] ?? null;
}

// Level (1..5) & label status terakhir sebuah pesanan
function statusAkhir(array $pesanan): array
{
    $level = array_search($pesanan['status'], TAHAP, true);
    $level = ($level === false) ? 1 : $level + 1;

    $rute      = $pesanan['perjalanan'] ?? [];
    $terakhir  = $rute ? $rute[count($rute) - 1]['keterangan'] : '';
    if (stripos($terakhir, 'Selesai') === 0) {
        $level = 5;
    }
    $kelas = ($level >= 5) ? 'sukses' : (($level >= 3) ? 'proses' : 'baru'); // operator ternary
    return ['level' => $level, 'label' => TAHAP[$level - 1], 'kelas' => $kelas];
}

function buatNomorDO(): string
{
    $semua = semuaPesanan();
    do {
        $no = date('Y') . random_int(1000, 9999);
    } while (isset($semua[$no]));
    return $no;
}

function simpanPesanan(array $pesanan): bool
{
    $tersimpan = bacaData('pesanan');
    $tersimpan[$pesanan['nomorDO']] = $pesanan;
    return simpanData('pesanan', $tersimpan);
}

// Rekap per ekspedisi: [namaEkspedisi => ['jumlah' => n, 'total' => rupiah]]
function rekapEkspedisi(array $pesanan): array
{
    $rekap = [];
    foreach ($pesanan as $p) {
        $eks = $p['ekspedisi'];
        if (!isset($rekap[$eks])) {
            $rekap[$eks] = ['jumlah' => 0, 'total' => 0];
        }
        $rekap[$eks]['jumlah']++;
        $rekap[$eks]['total'] += (int)$p['total'];
    }
    ksort($rekap);
    return $rekap;
}

/* ============================================================
   9. TAMPILAN BANTU: ikon SVG sederhana
   ============================================================ */

function ikon(string $nama): string
{
    $path = [
        'buku'    => '<path d="M4 19.5V5a2 2 0 0 1 2-2h14v14H6.5a2.5 2.5 0 0 0 0 5H20"/>',
        'keranjang' => '<path d="M3 4h2l2.4 11.2a1 1 0 0 0 1 .8h8.8a1 1 0 0 0 1-.8L20 8H6"/><circle cx="9" cy="20" r="1.2"/><circle cx="17" cy="20" r="1.2"/>',
        'kotak'   => '<path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'truk'    => '<path d="M2 6h11v10H2zM13 10h4l3 3v3h-7"/><circle cx="6" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/>',
        'nota'    => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z"/><path d="M9 8h6M9 12h6"/>',
        'grafik'  => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    ];
    return '<svg class="ikon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($path[$nama] ?? '') . '</svg>';
}
