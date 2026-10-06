<?php
require_once __DIR__ . '/config/bootstrap.php';

// Kalau sudah login, langsung redirect
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --sand: #E1DCC9;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #FAF9F5;
            min-height: 100vh;
        }
        h1, h2, h3 { font-family: 'Playfair Display', serif; color: var(--espresso); }
        .auth-card {
            max-width: 440px;
            margin: 5vh auto;
            background: #fff;
            border-radius: 1.5rem;
            border: 1px solid var(--sand);
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 30px rgba(65,45,21,.08);
        }
        .brand-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--espresso);
            font-weight: 700;
            text-align: center;
            margin-bottom: .25rem;
        }
        .brand-sub {
            text-align: center;
            color: #6c757d;
            font-size: .875rem;
            margin-bottom: 2rem;
        }
        .nav-pills .nav-link {
            color: var(--espresso);
            font-weight: 600;
            border-radius: .75rem;
        }
        .nav-pills .nav-link.active {
            background: var(--espresso);
            color: #fff;
        }
        .btn-espresso {
            background: var(--espresso);
            color: #fff;
            font-weight: 600;
            padding: .75rem;
            border-radius: .75rem;
        }
        .btn-espresso:hover { background: #2a1d0e; color: #fff; }
        .form-control, .form-select {
            border-radius: .75rem;
            padding: .625rem .875rem;
            border-color: var(--sand);
        }
        .form-control:focus {
            border-color: var(--espresso);
            box-shadow: 0 0 0 .2rem rgba(65,45,21,.15);
        }
        .form-label { font-weight: 500; font-size: .875rem; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand-logo">Tokopi</div>
        <div class="brand-sub">Coffee Shop ERP</div>

        <?php if ($error): ?>
            <div class="alert alert-danger small"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success small"><?= e($success) ?></div>
        <?php endif; ?>

        <!-- Tab: Owner / Kasir -->
        <ul class="nav nav-pills nav-fill mb-4" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-owner" type="button">
                    <i class="bi bi-person-badge me-1"></i>Owner / Manager
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-kasir" type="button">
                    <i class="bi bi-cash-register me-1"></i>Kasir
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB OWNER -->
            <div class="tab-pane fade show active" id="tab-owner">
                <form action="/tokopi/actions/auth_action.php?action=login_owner" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autofocus
                               placeholder="owner@tokopi.test">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required
                               placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-espresso w-100">
                        Masuk <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </form>
            </div>

            <!-- TAB KASIR -->
            <div class="tab-pane fade" id="tab-kasir">
                <form action="/tokopi/actions/auth_action.php?action=login_kasir" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required
                               placeholder="kasir1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PIN</label>
                        <input type="password" name="pin" class="form-control" required
                               inputmode="numeric" maxlength="6" placeholder="••••">
                    </div>
                    <button type="submit" class="btn btn-espresso w-100">
                        Masuk <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </form>
            </div>
        </div>

        <hr class="my-4">

        <div class="text-center small">
            Belum punya toko?
            <a href="/tokopi/register.php" class="fw-semibold text-decoration-none" style="color: var(--espresso);">
                Daftar di sini
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
