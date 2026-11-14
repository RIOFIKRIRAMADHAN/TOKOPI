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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1a1a1a">
    <title>Daftar · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --sand: #E1DCC9;
            --sand-light: #F3F0E6;
            --red: #dc2626;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #1a1a1a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* BACKGROUND ORNAMENT */
        .bg-ornament {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: linear-gradient(160deg, #000000 0%, #1a1a1a 50%, #2a1d0e 100%);
        }

        .bg-ornament::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(225, 220, 201, .15) 0%, transparent 60%);
            border-radius: 50%;
        }

        /* CONTAINER */
        .auth-container {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 60px 28px 40px;
            max-width: 440px;
            margin: 0 auto;
            width: 100%;
        }

        /* BACK BUTTON */
        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, .1);
            border-radius: 50%;
            color: var(--sand);
            text-decoration: none;
            font-size: 1.1rem;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, .1);
        }

        /* BRAND */
        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }

        .tagline {
            color: var(--sand);
            font-size: 0.9rem;
            margin-bottom: 40px;
        }

        /* ALERTS */
        .alert-c {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .alert-error {
            background: rgba(220, 38, 38, .15);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, .3);
        }

        .alert-success {
            background: rgba(46, 125, 91, .15);
            color: #6ee7b7;
            border: 1px solid rgba(46, 125, 91, .3);
        }

        /* FORM */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--sand);
            margin-bottom: 8px;
        }

        .form-label .opt {
            color: rgba(255, 255, 255, .4);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 16px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 14px;
            color: #fff;
            font-size: 1rem;
            font-family: inherit;
            outline: none;
            transition: 0.2s;
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, .3);
        }

        .form-input:focus {
            border-color: var(--sand);
            background: rgba(255, 255, 255, .08);
            box-shadow: 0 0 0 4px rgba(225, 220, 201, .1);
        }

        /* BUTTON */
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: var(--sand);
            color: var(--espresso);
            border: none;
            border-radius: 16px;
            font-weight: 800;
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.15s;
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .btn-submit:disabled {
            opacity: 0.6;
        }

        /* INFO BOX */
        .info-box {
            background: rgba(225, 220, 201, .1);
            border: 1px solid rgba(225, 220, 201, .2);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 30px;
            font-size: 0.82rem;
            color: var(--sand);
            line-height: 1.6;
        }

        .info-box strong {
            color: #fff;
            display: block;
            margin-bottom: 4px;
        }

        /* FOOTER LINK */
        .auth-footer {
            margin-top: auto;
            padding-top: 30px;
            text-align: center;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, .5);
        }

        .auth-footer a {
            color: var(--sand);
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="bg-ornament"></div>

    <div class="auth-container">
        <a href="/tokopi/customer/home.php" class="btn-back">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div class="brand">Daftar.</div>
        <div class="tagline">Gabung member Tokopi, kumpulkan poin.</div>

        <?php if ($error): ?>
            <div class="alert-c alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>Keuntungan Daftar:</strong>
            • Dapat 1 poin setiap Rp 10.000 belanja<br>
            • Tukar poin dengan kopi gratis & diskon<br>
            • Riwayat order tersimpan otomatis
        </div>

        <form action="/tokopi/customer/account_action.php?action=register" method="POST" id="register-form">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-input" required maxlength="100" placeholder="Nama kamu" autocomplete="name">
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" required maxlength="150" placeholder="kamu@email.com" autocomplete="email">
            </div>

            <div class="form-group">
                <label class="form-label">No HP <span class="opt">(opsional)</span></label>
                <input type="tel" name="phone" class="form-input" maxlength="20" placeholder="08xxxxxxxxxx" autocomplete="tel">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required minlength="6" placeholder="Minimal 6 karakter" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirm" class="form-input" required minlength="6" placeholder="Ulangi password" autocomplete="new-password">
            </div>

            <button type="submit" class="btn-submit" id="btn-submit">
                Buat Akun </i>
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
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';
        });
    </script>

</body>

</html>