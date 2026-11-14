<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

if (!empty($_SESSION['customer_account_id'])) {
    redirect('/tokopi/customer/profile.php');
}

$error = flash_get('error');
$success = flash_get('success');
$table_nama = $_SESSION['customer_table_nama'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a1a1a">
    <title>Daftar · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="/tokopi/assets/css/app.css" rel="stylesheet">
</head>

<body class="auth-page">
    <div class="bg-ornament"></div>

    <div class="auth-container">
        <a href="/tokopi/customer/home.php" class="btn-back">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div class="brand">Daftar.</div>
        <div class="tagline">Gabung member Tokopi, kumpulkan poin.</div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="alert alert-info" style="background: rgba(225,220,201,.1); border-color: rgba(225,220,201,.2); color: var(--sand);">
            <strong style="color:#fff; display:block; margin-bottom:4px;">Keuntungan Daftar:</strong>
            • Dapat 1 poin setiap Rp 10.000 belanja<br>
            • Tukar poin dengan kopi gratis & diskon<br>
            • Riwayat order tersimpan otomatis
        </div>

        <form action="/tokopi/customer/account_action.php?action=register" method="POST" id="register-form">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="nama">Nama Lengkap</label>
                <input type="text" name="nama" id="nama" class="form-control" required maxlength="100" placeholder="Nama kamu" autocomplete="name">
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" required maxlength="150" placeholder="kamu@email.com" autocomplete="email">
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">No HP <span class="optional">(opsional)</span></label>
                <input type="tel" name="phone" id="phone" class="form-control" maxlength="20" placeholder="08xxxxxxxxxx" autocomplete="tel">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirm">Konfirmasi Password</label>
                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required minlength="6" placeholder="Ulangi password" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-sand btn-block btn-lg" id="btn-submit">
                Buat Akun
            </button>
        </form>

        <div class="auth-footer">
            Sudah punya akun? <a href="/tokopi/customer/login.php">Login di sini</a>
        </div>
    </div>

    <script>
        document.getElementById('register-form').addEventListener('submit', function() {
            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner spinner-sm me-2"></span> Memproses...';
        });
    </script>
</body>
</html>