<?php
// customer/profile_edit.php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_account_id'])) {
    redirect('/tokopi/customer/login.php');
}

$customer_id = (int)$_SESSION['customer_account_id'];

// Ambil data terbaru
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect('/tokopi/customer/logout_account.php');
}

$error = flash_get('error');
$success = flash_get('success');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#412D15">
    <title>Edit Profil · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --sand: #E1DCC9;
            --sand-light: #F3F0E6;
            --bg: #EDEAE3;
            --text-muted: #8a8478;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        html, body {
            margin: 0; padding: 0; background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif; padding-bottom: 80px;
        }

        /* HEADER NAVIGASI */
        .page-header {
            background: linear-gradient(160deg, #1a1a1a 0%, #2a1d0e 60%, #3d2a15 100%);
            color: #fff; padding: 20px 22px; display: flex; align-items: center; gap: 16px;
            border-radius: 0 0 24px 24px;
        }
        .btn-back {
            color: #fff; font-size: 1.2rem; text-decoration: none;
            width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.1); border-radius: 50%;
        }
        .page-title { font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 700; margin: 0; }

        /* FORM STYLE */
        .section-card {
            background: #fff; margin: 20px; border-radius: 16px; padding: 20px;
            box-shadow: 0 2px 12px rgba(65,45,21,0.04); border: 1px solid #f5f2ea;
        }
        .section-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; font-weight: 700; color: var(--espresso); margin-bottom: 16px; }
        
        .form-label { font-size: 0.8rem; font-weight: 600; color: var(--espresso); margin-bottom: 6px; }
        .form-control {
            border-radius: 10px; padding: 12px 14px; border: 1.5px solid #f0ede5;
            background: #FAF9F5; font-size: 0.9rem; font-family: inherit;
        }
        .form-control:focus { border-color: var(--espresso); box-shadow: none; background: #fff; }
        .form-control:disabled { background: #e9ecef; color: #6c757d; }

        .btn-submit {
            background: var(--espresso); color: #fff; font-weight: 700; border-radius: 12px;
            padding: 14px; border: none; width: 100%; font-size: 0.95rem; margin-top: 10px;
            transition: 0.15s;
        }
        .btn-submit:active { transform: scale(0.98); }

        .btn-outline-dark {
            border-radius: 12px; padding: 14px; font-weight: 700; font-size: 0.95rem; width: 100%; border-width: 1.5px;
        }

        /* NOTIFIKASI */
        .alert-custom { margin: 20px; border-radius: 12px; border: none; font-size: 0.85rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="page-header">
        <a href="/tokopi/customer/profile.php" class="btn-back"><i class="bi bi-arrow-left"></i></a>
        <h1 class="page-title">Edit Profil</h1>
    </div>

    <!-- NOTIFIKASI -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-custom"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-custom"><i class="bi bi-check-circle-fill me-2"></i> <?= e($success) ?></div>
    <?php endif; ?>

    <!-- FORM 1: EDIT DATA DIRI -->
    <div class="section-card">
        <h3 class="section-title">Informasi Pribadi</h3>
        <form action="/tokopi/customer/profile_update.php" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= e($customer['nama']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email (Tidak bisa diubah)</label>
                <input type="email" class="form-control" value="<?= e($customer['email']) ?>" disabled>
            </div>

            <div class="mb-3">
                <label class="form-label">Nomor HP</label>
                <input type="text" name="phone" class="form-control" value="<?= e($customer['phone'] ?? '') ?>" placeholder="08xxxxx">
            </div>

            <div class="mb-4">
                <label class="form-label">Foto Profil (Opsional)</label>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <?php if (!empty($customer['avatar_path'])): ?>
                        <img src="/tokopi/<?= e($customer['avatar_path']) ?>?t=<?= time() ?>" alt="Avatar" style="width:56px; height:56px; border-radius:50%; object-fit:cover; border:2px solid #f0ede5;">
                    <?php else: ?>
                        <div style="width:56px; height:56px; border-radius:50%; background:#F3F0E6; color:var(--espresso); display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.2rem; border:2px solid #f0ede5;">
                            <?= strtoupper(substr($customer['nama'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <small class="text-muted" style="font-size:0.75rem;">Pratinjau foto saat ini</small>
                </div>
                <input type="file" name="avatar" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-text" style="font-size:0.72rem;">Maksimal 2MB. Format: JPG, PNG, atau WEBP.</div>
            </div>

            <button type="submit" class="btn-submit">Simpan Perubahan</button>
        </form>
    </div>

    <!-- FORM 2: GANTI PASSWORD -->
    <div class="section-card" style="margin-bottom: 30px;">
        <h3 class="section-title">Keamanan Akun</h3>
        <form action="/tokopi/customer/password_update.php" method="POST">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label class="form-label">Password Saat Ini</label>
                <input type="password" name="old_password" class="form-control" required placeholder="••••••••">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="new_password" class="form-control" required placeholder="Minimal 6 karakter">
            </div>
            
            <div class="mb-4">
                <label class="form-label">Ulangi Password Baru</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Ketik ulang password baru">
            </div>

            <button type="submit" class="btn btn-outline-dark">Perbarui Password</button>
        </form>
    </div>

</body>
</html>