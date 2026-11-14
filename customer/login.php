<?php
// customer/login.php
require_once __DIR__ . '/../config/bootstrap.php';

// HAPUS BLOKIR MEJA
// if (empty($_SESSION['customer_table_id'])) {
//     redirect('/tokopi/customer/t.php');
// }

// Kalau sudah login, tendang ke profile
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
    <title>Login · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="/tokopi/assets/css/app.css" rel="stylesheet">
</head>

<body class="theme-dark">
    <div class="bg-ornament"></div>

    <div class="auth-container">
        <a href="/tokopi/customer/home.php" class="btn-back">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div class="brand">Login.</div>
        <div class="tagline">Selamat datang kembali di Tokopi.</div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form action="/tokopi/customer/account_action.php?action=login" method="POST" id="login-form">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="kamu@email.com" autocomplete="email" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-sand btn-block btn-lg" id="btn-submit">
                Masuk
            </button>
        </form>

        <div class="auth-footer">
            Belum punya akun? <a href="/tokopi/customer/register.php">Daftar di sini</a>
        </div>
    </div>

    <script src="/tokopi/assets/js/ui.js"></script>
    <script>
        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner spinner-sm me-2"></span> Memproses...';
        });
    </script>
</body>
</html>