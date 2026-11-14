<?php
require_once __DIR__ . '/config/bootstrap.php';

if (is_logged_in()) {
    redirect('/tokopi/views/dashboard.php');
}

$error = flash_get('error');
$success = flash_get('success');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="/tokopi/assets/css/app.css" rel="stylesheet">
    <style>
        /* Login page specific */
        body { background: var(--color-bg-alt); min-height: 100vh; }
        .login-wrapper { display: flex; min-height: 100vh; }

        /* LEFT: VISUAL */
        .login-visual {
            flex: 1;
            background: linear-gradient(135deg, #1a1a1a 0%, var(--color-espresso) 100%);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            padding: var(--space-10); color: var(--color-text-on-dark);
            position: relative; overflow: hidden;
        }
        .login-visual::before {
            content: '☕'; position: absolute; font-size: 25rem; opacity: 0.05;
            bottom: -50px; right: -50px; transform: rotate(-15deg); pointer-events: none;
        }
        .login-visual h1 { font-family: var(--font-serif); font-size: clamp(2.5rem, 5vw, 3.5rem); font-weight: var(--font-bold); z-index: 1; margin-bottom: var(--space-3); }
        .login-visual p { font-size: var(--text-lg); color: var(--color-sand); z-index: 1; text-align: center; max-width: 400px; line-height: var(--leading-relaxed); }

        /* RIGHT: FORM */
        .login-form-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-10); background: var(--color-bg-white); }
        .login-box { width: 100%; max-width: 400px; }
        .brand-logo { font-family: var(--font-serif); font-size: var(--text-3xl); color: var(--color-espresso); font-weight: var(--font-bold); margin-bottom: var(--space-2); }

        .nav-pills .nav-link { color: var(--color-espresso); font-weight: var(--font-semibold); border-radius: var(--radius-lg); padding: var(--space-3) var(--space-5); }
        .nav-pills .nav-link.active { background: var(--color-espresso); color: #fff; }

        @media (max-width: 991.98px) {
            .login-visual { display: none; }
            .login-form-container { min-height: 100vh; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- VISUAL (LEFT) -->
    <div class="login-visual d-none d-lg-flex">
        <h1>Tokopi.</h1>
        <p>Sistem ERP & Point of Sale untuk Coffee Shop Multi-Cabang. Kelola cabang, stok, dan pelanggan dalam satu genggaman.</p>
    </div>

    <!-- FORM (RIGHT) -->
    <div class="login-form-container">
        <div class="login-box">
            <div class="brand-logo">Selamat Datang</div>
            <p class="text-muted mb-4">Silakan masuk ke akun Anda</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <ul class="nav nav-pills nav-fill mb-4" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-owner" type="button">
                        Owner / Manager
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-kasir" type="button">
                        Kasir
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- TAB OWNER -->
                <div class="tab-pane fade show active" id="tab-owner">
                    <form action="/tokopi/actions/auth_action.php?action=login_owner" method="POST">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label class="form-label" for="owner-email">Email</label>
                            <input type="email" name="email" id="owner-email" class="form-control" required placeholder="owner@tokopi.test" autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="owner-password">Password</label>
                            <input type="password" name="password" id="owner-password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-espresso btn-block btn-lg">Masuk</button>
                    </form>
                </div>

                <!-- TAB KASIR -->
                <div class="tab-pane fade" id="tab-kasir">
                    <form action="/tokopi/actions/auth_action.php?action=login_kasir" method="POST">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label class="form-label" for="kasir-username">Username</label>
                            <input type="text" name="username" id="kasir-username" class="form-control" required placeholder="kasir1" autocomplete="username">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="kasir-pin">PIN</label>
                            <input type="password" name="pin" id="kasir-pin" class="form-control" required inputmode="numeric" maxlength="6" placeholder="••••" autocomplete="off">
                        </div>
                        <button type="submit" class="btn btn-espresso btn-block btn-lg">Masuk</button>
                    </form>
                </div>
            </div>

            <div class="divider"><span>atau</span></div>

            <div class="text-center small text-muted">
                Belum punya toko? <a href="/tokopi/register.php" class="fw-semibold text-decoration-none" style="color: var(--color-espresso);">Daftar di sini</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>