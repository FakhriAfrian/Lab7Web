<?php
/**
 * lupa.php — LUPA PASSWORD (simulasi)
 * Pesan yang ditampilkan sama baik email terdaftar maupun tidak, supaya
 * halaman ini tidak bisa dipakai untuk menebak email siapa saja yang terdaftar.
 */
require_once __DIR__ . '/includes/config.php';

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(inputPost('email'));
    if (!csrfValid()) {
        $errors['umum'] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!emailValid($email)) {
        $errors['email'] = 'Masukkan alamat email yang valid.';
    } else {
        setFlash('sukses', 'Jika email ' . $email . ' terdaftar, tautan reset password (simulasi) telah dikirim.');
        redirect('index.php');
    }
}

$judul = 'Lupa Password';
require __DIR__ . '/includes/auth_head.php';
?>
      <h1>Lupa Password</h1>
      <p class="sub-judul">Masukkan email Anda. Kami kirim tautan reset (simulasi, tidak ada email sungguhan).</p>

      <?php if (isset($errors['umum'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['umum']) ?></div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <?= csrfField() ?>
        <div class="form-group<?= kelasError($errors, 'email') ?>">
          <label for="email">Email terdaftar</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="nama@email.com" required>
          <?= pesanError($errors, 'email') ?>
        </div>
        <button type="submit" class="btn btn-blok">Kirim Tautan Reset</button>
      </form>

      <div class="tautan-bawah">
        <a href="index.php">&larr; Kembali ke login</a>
      </div>
<?php require __DIR__ . '/includes/auth_foot.php'; ?>
