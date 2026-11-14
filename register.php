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
    <link href="/tokopi/assets/css/app.css" rel="stylesheet">
    <style>
        /* Register page specific */
        body { background: var(--color-bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: var(--space-6); }
        .auth-card { max-width: 480px; width: 100%; background: var(--color-bg-white); border-radius: var(--radius-2xl); border: 1px solid var(--color-border); padding: var(--space-8) var(--space-6); box-shadow: var(--shadow-lg); }
        .brand-logo { font-family: var(--font-serif); font-size: var(--text-3xl); color: var(--color-espresso); font-weight: var(--font-bold); text-align: center; margin-bottom: var(--space-1); }
        .brand-sub { text-align: center; color: var(--color-text-muted); font-size: var(--text-sm); margin-bottom: var(--space-6); }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="brand-logo">Tokopi</div>
        <div class="brand-sub">Daftarkan Coffee Shop Anda</div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form action="/tokopi/actions/auth_action.php?action=register" method="POST">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="nama_toko">Nama Toko</label>
                <input type="text" name="nama_toko" id="nama_toko" class="form-control" required value="<?= e($old['nama_toko'] ?? '') ?>" placeholder="contoh: Kopi Senja" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?= e($old['email'] ?? '') ?>" placeholder="owner@email.com" autocomplete="email">
            </div>
            <div class="form-group">
                <label class="form-label" for="no_hp">No. HP</label>
                <input type="text" name="no_hp" id="no_hp" class="form-control" value="<?= e($old['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx" autocomplete="tel">
            </div>
            <div class="form-group">
                <label class="form-label" for="alamat">Alamat</label>
                <textarea name="alamat" id="alamat" class="form-control" rows="2" placeholder="Alamat outlet pusat"><?= e($old['alamat'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required minlength="6" placeholder="minimal 6 karakter" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirm">Konfirmasi Password</label>
                <input type="password" name="password_confirm" id="password_confirm" class="form-control" required placeholder="ulangi password" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-espresso btn-block btn-lg">
                Daftar <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>

        <div class="divider"><span>atau</span></div>

        <div class="text-center small">
            Sudah punya akun?
            <a href="/tokopi/login.php" class="fw-semibold text-decoration-none" style="color: var(--color-espresso);">
                Login di sini
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>