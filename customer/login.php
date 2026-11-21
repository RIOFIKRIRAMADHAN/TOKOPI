<?php
// customer/login.php
require_once __DIR__ . '/../config/bootstrap.php';

if (!empty($_SESSION['customer_account_id'])) {
    redirect('/tokopi/customer/profile.php');
}

$error = flash_get('error');
$success = flash_get('success');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Login · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #FAF9F5;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ===== HERO SECTION ===== */
        .auth-hero {
            position: relative;
            background: linear-gradient(165deg, #1A0F06 0%, #2D1B0A 55%, #4A3418 100%);
            padding: 60px 28px 110px;
            border-radius: 0 0 40px 40px;
            overflow: hidden;
        }

        /* Ornamen lingkaran */
        .auth-hero::before {
            content: '';
            position: absolute;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(225,220,201,.18) 0%, transparent 65%);
            top: -100px; right: -120px;
        }
        .auth-hero::after {
            content: '';
            position: absolute;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(184,133,58,.15) 0%, transparent 65%);
            bottom: 20px; left: -80px;
        }

        /* Ornamen garis diagonal (subtle) */
        .hero-line {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(45deg, transparent 47%, rgba(225,220,201,.03) 48%, rgba(225,220,201,.03) 52%, transparent 53%);
            background-size: 30px 30px;
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px 8px 10px;
            background: rgba(225,220,201,.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(225,220,201,.2);
            border-radius: 100px;
            margin-bottom: 32px;
        }
        .brand-badge-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, #E1DCC9 0%, #B8AC8F 100%);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #2D1B0A;
            font-size: 1rem;
        }
        .brand-badge-text {
            color: #FAF6EC;
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.05;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        .hero-title .accent {
            color: #B8853A;
            font-style: italic;
        }
        .hero-subtitle {
            font-size: 0.88rem;
            color: rgba(225,220,201,.7);
            line-height: 1.6;
            max-width: 300px;
            margin: 0 auto;
        }

        /* Tombol back di pojok kiri atas */
        .btn-back-top {
            position: absolute;
            top: 24px; left: 24px;
            width: 42px; height: 42px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
            color: #FAF6EC;
            font-size: 1.05rem;
            text-decoration: none;
            z-index: 3;
            transition: all .25s ease;
        }
        .btn-back-top:active { background: rgba(255,255,255,.2); transform: scale(.95); }

        /* ===== FORM CARD (OVERLAP) ===== */
        .auth-card-wrap {
            margin: -80px 20px 40px;
            position: relative;
            z-index: 10;
        }

        .auth-card {
            background: #fff;
            border-radius: 28px;
            padding: 36px 28px 32px;
            box-shadow: 0 20px 60px rgba(45, 27, 10, 0.15), 0 4px 12px rgba(45, 27, 10, 0.05);
        }

        .card-heading {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #2D1B0A;
            letter-spacing: -0.01em;
            margin-bottom: 6px;
        }
        .card-sub {
            font-size: 0.85rem;
            color: #8A8478;
            margin-bottom: 28px;
        }

        /* ===== FORM ELEMENTS ===== */
        .form-group {
            margin-bottom: 18px;
            position: relative;
        }
        .form-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            color: #412D15;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap .icon {
            position: absolute;
            left: 18px; top: 50%;
            transform: translateY(-50%);
            color: #B5ABA0;
            font-size: 1.1rem;
            transition: color .2s ease;
            pointer-events: none;
        }
        .form-control {
            width: 100%;
            padding: 15px 18px 15px 48px;
            font-size: 0.95rem;
            font-family: inherit;
            color: #2B1F12;
            background: #FAF9F5;
            border: 1.5px solid #EBE6DA;
            border-radius: 14px;
            transition: all .25s ease;
            outline: none;
        }
        .form-control::placeholder { color: #C2BDB2; }
        .form-control:focus {
            background: #fff;
            border-color: #2D1B0A;
            box-shadow: 0 0 0 4px rgba(45, 27, 10, 0.06);
        }
        .input-wrap:focus-within .icon { color: #2D1B0A; }

        /* Toggle password */
        .toggle-pwd {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #B5ABA0;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 6px;
            border-radius: 8px;
            transition: color .2s;
        }
        .toggle-pwd:active { background: #F0EDE5; }

        /* ===== SUBMIT BUTTON ===== */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #2D1B0A 0%, #1A0F06 100%);
            color: #fff;
            border: none;
            border-radius: 16px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            margin-top: 12px;
            transition: all .3s ease;
            box-shadow: 0 8px 24px rgba(45, 27, 10, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.02em;
        }
        .btn-submit:active:not(:disabled) { transform: scale(.98); box-shadow: 0 4px 12px rgba(45, 27, 10, 0.3); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

        /* ===== ALERTS ===== */
        .alert-msg {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.83rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }
        .alert-msg i { flex-shrink: 0; margin-top: 2px; }
        .alert-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FEE2E2; }
        .alert-success { background: #F0FDF4; color: #166534; border: 1px solid #DCFCE7; }

        /* ===== DIVIDER ===== */
        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 24px 0;
            color: #C2BDB2;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #F0EDE5;
        }

        /* ===== FOOTER ===== */
        .auth-footer {
            text-align: center;
            font-size: 0.85rem;
            color: #8A8478;
        }
        .auth-footer a {
            color: #2D1B0A;
            font-weight: 700;
            text-decoration: none;
        }
        .auth-footer a:hover { text-decoration: underline; }

        /* Spinner saat loading */
        .spinner {
            display: inline-block;
            width: 14px; height: 14px;
            border: 2px solid #fff;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin .75s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>
<body>

    <!-- HERO -->
    <div class="auth-hero">
        <div class="hero-line"></div>
        <a href="/tokopi/customer/home.php" class="btn-back-top">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div class="hero-content">
            <div class="brand-badge">
                <div class="brand-badge-icon"><i class="bi bi-cup-hot-fill"></i></div>
                <span class="brand-badge-text">Tokopi</span>
            </div>

            <h1 class="hero-title">Selamat<br>Datang <span class="accent">Kembali.</span></h1>
            <p class="hero-subtitle">Login untuk nikmati kopi favoritmu & kumpulkan poin reward.</p>
        </div>
    </div>

    <!-- FORM CARD -->
    <div class="auth-card-wrap">
        <div class="auth-card">
            <h2 class="card-heading">Login Akun</h2>
            <p class="card-sub">Masukkan email dan password kamu</p>

            <?php if ($error): ?>
                <div class="alert-msg alert-error">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert-msg alert-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?= e($success) ?></span>
                </div>
            <?php endif; ?>

            <form action="/tokopi/customer/account_action.php?action=login" method="POST" id="login-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <div class="input-wrap">
                        <i class="bi bi-envelope icon"></i>
                        <input type="email" name="email" id="email" class="form-control" required placeholder="kamu@email.com" autocomplete="email" autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock icon"></i>
                        <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••" autocomplete="current-password" style="padding-right: 48px;">
                        <button type="button" class="toggle-pwd" onclick="togglePwd()">
                            <i class="bi bi-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btn-submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Masuk Sekarang</span>
                </button>
            </form>

            <div class="divider">Baru di Tokopi?</div>

            <div class="auth-footer">
                <a href="/tokopi/customer/register.php">Buat Akun Baru →</a>
            </div>
        </div>
    </div>

    <script>
        function togglePwd() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.getElementById('login-form').addEventListener('submit', function() {
            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Memproses...';
        });
    </script>
</body>
</html>