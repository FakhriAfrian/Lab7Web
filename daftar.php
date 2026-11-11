<?php
/**
 * daftar.php — PENDAFTARAN AKUN BARU
 * Validasi: nama, email (format + belum terdaftar), password min. 6 karakter, konfirmasi sama.
 * Password disimpan sebagai hash (password_hash).
 */
require_once __DIR__ . '/includes/config.php';

if (userLogin() !== null) {
    redirect('dashboard.php');
}

$errors = [];
$old    = ['nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['nama']  = inputPost('nama');
    $old['email'] = strtolower(inputPost('email'));
    $pass  = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $pass2 = is_string($_POST['password2'] ?? null) ? $_POST['password2'] : '';

    if (!csrfValid()) {
        $errors['umum'] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        if (mb_strlen($old['nama']) < 3) {
            $errors['nama'] = 'Nama minimal 3 karakter.';
        } elseif (mb_strlen($old['nama']) > 60) {
            $errors['nama'] = 'Nama maksimal 60 karakter.';
        }

        if (!emailValid($old['email'])) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif (cariPengguna($old['email']) !== null) {
            $errors['email'] = 'Email sudah terdaftar.';
        }

        if (strlen($pass) < 6) {
            $errors['password'] = 'Password minimal 6 karakter.';
        }
        if ($pass2 !== $pass) {
            $errors['password2'] = 'Konfirmasi password tidak sama.';
        }

        if (!$errors) {
            $baru = bacaData('pengguna');
            $baru[] = [
                'id'       => 100 + count($baru) + 1,
                'nama'     => $old['nama'],
                'email'    => $old['email'],
                'password' => password_hash($pass, PASSWORD_DEFAULT),
                'role'     => 'User',
            ];
            if (simpanData('pengguna', $baru)) {
                setFlash('sukses', 'Akun berhasil dibuat. Silakan login dengan email ' . $old['email'] . '.');
                redirect('index.php');
            }
            $errors['umum'] = 'Akun tidak dapat disimpan. Pastikan folder data/ dapat ditulis.';
        }
    }
}

$judul = 'Daftar';
require __DIR__ . '/includes/auth_head.php';
?>
      <h1>Daftar Akun Baru</h1>
      <p class="sub-judul">Isi data berikut untuk membuat akun pembeli.</p>

      <?php if (isset($errors['umum'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['umum']) ?></div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <?= csrfField() ?>
        <div class="form-group<?= kelasError($errors, 'nama') ?>">
          <label for="nama">Nama Lengkap</label>
          <input type="text" id="nama" name="nama" value="<?= e($old['nama']) ?>" maxlength="60" required>
          <?= pesanError($errors, 'nama') ?>
        </div>
        <div class="form-group<?= kelasError($errors, 'email') ?>">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" placeholder="nama@email.com" required>
          <?= pesanError($errors, 'email') ?>
        </div>
        <div class="form-group<?= kelasError($errors, 'password') ?>">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" autocomplete="new-password" required>
          <?= pesanError($errors, 'password') ?>
        </div>
        <div class="form-group<?= kelasError($errors, 'password2') ?>">
          <label for="password2">Konfirmasi Password</label>
          <input type="password" id="password2" name="password2" placeholder="Ulangi password" autocomplete="new-password" required>
          <?= pesanError($errors, 'password2') ?>
        </div>
        <button type="submit" class="btn btn-emas btn-blok">Daftar</button>
      </form>

      <div class="tautan-bawah">
        <a href="index.php">&larr; Kembali ke login</a>
      </div>
<?php require __DIR__ . '/includes/auth_foot.php'; ?>
