<?php
require_once __DIR__ . '/config/bootstrap.php';

if (is_logged_in()) {
    redirect('/tokopi/views/dashboard.php');
}

$error = flash_get('error');
$old = $_SESSION['old'] ?? [];
old_clear();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root { --espresso: #412D15; --sand: #E1DCC9; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #FAF9F5; min-height: 100vh; }
        h1, h2, h3 { font-family: 'Playfair Display', serif; color: var(--espresso); }
        .auth-card {
            max-width: 480px; margin: 5vh auto; background: #fff;
            border-radius: 1.5rem; border: 1px solid var(--sand);
            padding: 2.5rem 2rem; box-shadow: 0 8px 30px rgba(65,45,21,.08);
        }
        .brand-logo {
            font-family: 'Playfair Display', serif; font-size: 2rem;
            color: var(--espresso); font-weight: 700; text-align: center; margin-bottom: .25rem;
        }
        .brand-sub { text-align: center; color: #6c757d; font-size: .875rem; margin-bottom: 2rem; }
        .btn-espresso {
            background: var(--espresso); color: #fff; font-weight: 600;
            padding: .75rem; border-radius: .75rem;
        }
        .btn-espresso:hover { background: #2a1d0e; color: #fff; }
        .form-control { border-radius: .75rem; padding: .625rem .875rem; border-color: var(--sand); }
        .form-control:focus { border-color: var(--espresso); box-shadow: 0 0 0 .2rem rgba(65,45,21,.15); }
        .form-label { font-weight: 500; font-size: .875rem; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand-logo">Tokopi</div>
        <div class="brand-sub">Daftarkan Coffee Shop Anda</div>

        <?php if ($error): ?>
            <div class="alert alert-danger small"><?= e($error) ?></div>
        <?php endif; ?>

        <form action="/tokopi/actions/auth_action.php?action=register" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Nama Toko</label>
                <input type="text" name="nama_toko" class="form-control" required
                       value="<?= e($old['nama_toko'] ?? '') ?>"
                       placeholder="contoh: Kopi Senja">
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required
                       value="<?= e($old['email'] ?? '') ?>"
                       placeholder="owner@email.com">
            </div>
            <div class="mb-3">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control"
                       value="<?= e($old['no_hp'] ?? '') ?>"
                       placeholder="08xxxxxxxxxx">
            </div>
            <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="2"
                          placeholder="Alamat outlet pusat"><?= e($old['alamat'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="6"
                       placeholder="minimal 6 karakter">
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirm" class="form-control" required
                       placeholder="ulangi password">
            </div>
            <button type="submit" class="btn btn-espresso w-100">
                Daftar <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <hr class="my-4">

        <div class="text-center small">
            Sudah punya akun?
            <a href="/tokopi/login.php" class="fw-semibold text-decoration-none" style="color: var(--espresso);">
                Login di sini
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
