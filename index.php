<?php
/**
 * index.php — HALAMAN LOGIN
 * Alur: form (POST) -> validasi server-side -> cek akun -> simpan ke $_SESSION -> redirect.
 */
require_once __DIR__ . '/includes/config.php';

if (userLogin() !== null) {
    redirect('dashboard.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(inputPost('email'));
    $password = $_POST['password'] ?? '';
    $password = is_string($password) ? $password : '';

    if (!csrfValid()) {
        $errors['umum'] = 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        if ($email === '') {
            $errors['email'] = 'Email wajib diisi.';
        } elseif (!emailValid($email)) {
            $errors['email'] = 'Format email tidak valid.';
        }
        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        }

        if (!$errors) {
            $akun = cariPengguna($email);
            // password_verify membandingkan input dengan hash yang tersimpan
            if ($akun === null || !password_verify($password, $akun['password'])) {
                $errors['umum'] = 'Email atau password yang Anda masukkan salah.';
            } else {
                session_regenerate_id(true); // cegah session fixation
                $_SESSION['user'] = [
                    'id'    => $akun['id'],
                    'nama'  => $akun['nama'],
                    'email' => $akun['email'],
                    'role'  => $akun['role'],
                ];
                setFlash('sukses', 'Login berhasil. ' . sapaan() . ', ' . $akun['nama'] . '!');
                redirect('dashboard.php');
            }
        }
    }
}

$judul = 'Masuk';
require __DIR__ . '/includes/auth_head.php';
?>
      <h1>Masuk</h1>
      <p class="sub-judul">Silakan login untuk memesan buku.</p>

      <?php if (isset($errors['umum'])): ?>
        <div class="alert alert-error" role="alert"><?= e($errors['umum']) ?></div>
      <?php endif; ?>

      <aside class="info-demo">
        <strong>Akun demo</strong>
        Pembeli: rina@gmail.com / rina123<br>
        Admin: siti@gmail.com / siti123
      </aside>

      <form method="POST" action="" novalidate>
        <?= csrfField() ?>
        <div class="form-group<?= kelasError($errors, 'email') ?>">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?= e($email) ?>"
                 placeholder="nama@email.com" autocomplete="username" required>
          <?= pesanError($errors, 'email') ?>
        </div>
        <div class="form-group<?= kelasError($errors, 'password') ?>">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Masukkan password" autocomplete="current-password" required>
          <?= pesanError($errors, 'password') ?>
        </div>
        <label class="cek" for="tampilPass">
          <input type="checkbox" id="tampilPass" data-toggle-password="#password"> Tampilkan password
        </label>
        <button type="submit" class="btn btn-blok">Login</button>
      </form>

      <div class="tautan-bawah">
        <a href="lupa.php">Lupa password?</a>
        <a href="daftar.php">Daftar akun baru</a>
      </div>
<?php require __DIR__ . '/includes/auth_foot.php'; ?>
