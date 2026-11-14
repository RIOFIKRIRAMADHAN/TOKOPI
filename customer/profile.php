<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$table_nama  = $_SESSION['customer_table_nama'];
$outlet_nama = $_SESSION['customer_outlet_nama'];

// Cek apakah sudah login akun
$is_logged_in = !empty($_SESSION['customer_account_id']);
$customer_name = $_SESSION['customer_account_name'] ?? null;
$customer_email = $_SESSION['customer_account_email'] ?? null;

// Ambil data customer dari DB (kalau login)
$customer = null;
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_account_id']]);
    $customer = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Profil · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="/tokopi/assets/css/app.css" rel="stylesheet">
    <style>
        /* Profile-specific tier badges */
        .tier-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: var(--radius-pill); font-size: var(--text-xs); font-weight: var(--font-extrabold); letter-spacing: 0.05em; text-transform: uppercase; }
        .tier-BRONZE { background: #cd7f32; color: #fff; }
        .tier-SILVER { background: #c0c0c0; color: #333; }
        .tier-GOLD { background: #FFD700; color: #6b4a00; }

        /* Profile header avatar */
        .profile-avatar { width: 90px; height: 90px; background: var(--color-sand); color: var(--color-espresso); border-radius: var(--radius-circle); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto var(--space-4); font-weight: var(--font-extrabold); font-family: var(--font-serif); position: relative; z-index: 1; overflow: hidden; border: 3px solid rgba(255,255,255,.2); }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }

        /* Stats row */
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-3); margin: calc(-1.5 * var(--space-5)) var(--space-5) var(--space-5); position: relative; z-index: 2; }
        .stat-card { background: var(--color-bg-white); border-radius: var(--radius-xl); padding: var(--space-4) var(--space-2); text-align: center; box-shadow: var(--shadow-md); }
        .stat-value { font-family: var(--font-mono); font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--color-espresso); line-height: 1; margin-bottom: var(--space-1); }
        .stat-label { font-size: var(--text-xs); color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: var(--font-semibold); }

        /* Menu list items */
        .menu-list { background: var(--color-bg-white); margin: var(--space-4) var(--space-5); border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid var(--color-border); }
        .menu-item-link { display: flex; align-items: center; gap: var(--space-4); padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--color-border); text-decoration: none; color: inherit; transition: background var(--transition-fast); }
        .menu-item-link:last-child { border-bottom: none; }
        .menu-item-link:hover { background: var(--color-bg-alt); }
        .menu-item-link i:first-child { font-size: 1.2rem; color: var(--color-espresso); width: 24px; text-align: center; }
        .menu-item-link .label { flex: 1; font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--color-espresso); }
        .menu-item-link .arrow { color: var(--color-text-muted); font-size: 1rem; }

        /* Logout button */
        .logout-btn { display: block; margin: var(--space-5); padding: var(--space-3) var(--space-5); background: transparent; border: 1.5px solid var(--color-danger); color: var(--color-danger); border-radius: var(--radius-lg); font-weight: var(--font-bold); font-size: var(--text-base); text-align: center; text-decoration: none; transition: all var(--transition-fast); }
        .logout-btn:active { background: var(--color-danger); color: #fff; }

        /* Guest mode */
        .guest-card { margin: var(--space-5); padding: var(--space-8) var(--space-6); background: var(--color-bg-white); border-radius: var(--radius-2xl); text-align: center; box-shadow: var(--shadow-md); border: 1px solid var(--color-border); }
        .guest-icon { font-size: 3rem; margin-bottom: var(--space-3); }
        .guest-title { font-family: var(--font-serif); font-size: var(--text-xl); color: var(--color-espresso); margin: 0 0 var(--space-2); font-weight: var(--font-bold); }
        .guest-desc { font-size: var(--text-base); color: var(--color-text-muted); line-height: 1.55; margin-bottom: var(--space-5); }

        .btn-primary-c { display: block; width: 100%; padding: var(--space-3) var(--space-5); background: var(--color-espresso); color: #fff; border: none; border-radius: var(--radius-lg); font-weight: var(--font-bold); font-size: var(--text-base); text-decoration: none; text-align: center; margin-bottom: var(--space-3); }
        .btn-secondary-c { display: block; width: 100%; padding: var(--space-3) var(--space-5); background: transparent; color: var(--color-espresso); border: 1.5px solid var(--color-sand); border-radius: var(--radius-lg); font-weight: var(--font-bold); font-size: var(--text-base); text-decoration: none; text-align: center; }

        /* Profile header */
        .profile-header { background: linear-gradient(160deg, #1a1a1a 0%, #2a1d0e 60%, #3d2a15 100%); color: #fff; padding: var(--space-8) var(--space-5) var(--space-10); text-align: center; border-radius: 0 0 var(--radius-2xl) var(--radius-2xl); position: relative; overflow: hidden; }
        .profile-header::before { content: ''; position: absolute; top: -80px; right: -80px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(225,220,201,.15) 0%, transparent 70%); border-radius: 50%; pointer-events: none; }
        .profile-name { font-family: var(--font-serif); font-size: var(--text-2xl); font-weight: var(--font-bold); margin-bottom: var(--space-1); position: relative; z-index: 1; }
        .profile-email { font-size: var(--text-sm); color: var(--color-sand); position: relative; z-index: 1; }
    </style>
</head>

<body class="customer-layout">

    <?php if ($is_logged_in): ?>

        <!-- PROFILE HEADER -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($customer['avatar_path'])): ?>
                    <img src="/tokopi/<?= e($customer['avatar_path']) ?>?t=<?= time() ?>" alt="Avatar">
                <?php else: ?>
                    <?= strtoupper(substr($customer_name, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="profile-name"><?= e($customer_name) ?></div>
            <div class="profile-email"><?= e($customer_email) ?></div>
            <div class="tier-badge tier-<?= e($customer['tier']) ?>">
                <i class="bi bi-star-fill"></i> <?= e($customer['tier']) ?>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-value"><?= number_format((int)$customer['points']) ?></div>
                <div class="stat-label">Poin</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= (int)$customer['total_orders'] ?></div>
                <div class="stat-label">Order</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="font-size:var(--text-base);">Rp <?= number_format((float)$customer['total_spent'] / 1000, 0, ',', '.') ?>k</div>
                <div class="stat-label">Total Belanja</div>
            </div>
        </div>

        <!-- MENU LIST -->
        <div class="menu-list">
            <a href="/tokopi/customer/orders.php" class="menu-item-link">
                <i class="bi bi-receipt"></i>
                <span class="label">Riwayat Order</span>
                <i class="bi bi-chevron-right arrow"></i>
            </a>
            <a href="/tokopi/customer/rewards.php" class="menu-item-link">
                <i class="bi bi-gift"></i>
                <span class="label">Reward & Poin</span>
                <i class="bi bi-chevron-right arrow"></i>
            </a>
            <a href="/tokopi/customer/my_vouchers.php" class="menu-item-link">
                <i class="bi bi-ticket-perforated"></i>
                <span class="label">Voucher Saya</span>
                <i class="bi bi-chevron-right arrow"></i>
            </a>
            <a href="/tokopi/customer/profile_edit.php" class="menu-item-link">
                <i class="bi bi-pencil"></i>
                <span class="label">Edit Profil</span>
                <i class="bi bi-chevron-right arrow"></i>
            </a>
            <a href="#" class="menu-item-link" onclick="alert('Fitur akan segera hadir'); return false;">
                <i class="bi bi-question-circle"></i>
                <span class="label">Bantuan</span>
                <i class="bi bi-chevron-right arrow"></i>
            </a>
        </div>

        <!-- INFO TABLE -->
        <div class="menu-list">
            <div class="menu-item-link" style="cursor:default;">
                <i class="bi bi-geo-alt"></i>
                <span class="label" style="font-size:var(--text-sm); font-weight:var(--font-medium);">
                    Meja: <strong><?= e($table_nama) ?></strong><br>
                    Outlet: <strong><?= e($outlet_nama) ?></strong>
                </span>
            </div>
        </div>
        <div class="menu-list">
        <a href="/tokopi/customer/reset_table.php" class="menu-item-link" onclick="return confirm('Lupakan meja saat ini? Kamu harus scan QR ulang untuk memesan.');">
            <i class="bi bi-x-circle text-danger"></i>
            <span class="label text-danger">Lupakan Meja Saat Ini</span>
            <i class="bi bi-chevron-right arrow"></i>
        </a>
        </div>

        <!-- LOGOUT ACCOUNT -->
        <a href="/tokopi/customer/logout_account.php" class="logout-btn"
            onclick="return confirm('Logout dari akun? Poin & riwayat kamu tetap tersimpan.');">
            <i class="bi bi-box-arrow-right me-1"></i> Logout Akun
        </a>

    <?php else: ?>

        <!-- GUEST MODE -->
        <div class="profile-header" style="padding-bottom:var(--space-12);">
            <div class="profile-avatar">👤</div>
            <div class="profile-name">Pelanggan Tamu</div>
            <div class="profile-email">Login untuk dapat poin & reward</div>
        </div>

        <div class="guest-card">
            <div class="guest-icon">🎁</div>
            <div class="guest-title">Daftar & Dapat Poin</div>
            <div class="guest-desc">
                Dapatkan <strong>1 poin</strong> setiap Rp 10.000 belanja.
                Tukar poin dengan kopi gratis, diskon, dan banyak lagi!
            </div>

            <a href="/tokopi/customer/register.php" class="btn-primary-c">
                <i class="bi bi-person-plus-fill me-1"></i> Daftar Sekarang
            </a>
            <a href="/tokopi/customer/login.php" class="btn-secondary-c">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sudah Punya Akun
            </a>
        </div>

        <div class="menu-list">
            <div class="menu-item-link" style="cursor:default;">
                <i class="bi bi-geo-alt"></i>
                <span class="label" style="font-size:var(--text-sm); font-weight:var(--font-medium);">
                    Meja: <strong><?= e($table_nama) ?></strong><br>
                    Outlet: <strong><?= e($outlet_nama) ?></strong>
                </span>
            </div>
        </div>

    <?php endif; ?>

    <!-- BOTTOM NAV -->
    <div class="bottom-nav">
        <a href="/tokopi/customer/home.php" class="nav-item">
            <i class="bi bi-house-door"></i>Home
        </a>
        <a href="/tokopi/customer/menu.php" class="nav-item">
            <i class="bi bi-cup-hot"></i>Menu
        </a>
        <a href="/tokopi/customer/orders.php" class="nav-item">
            <i class="bi bi-receipt"></i>Order
        </a>
        <a href="/tokopi/customer/profile.php" class="nav-item active">
            <i class="bi bi-person"></i>Profile
        </a>

    </div>

    <script src="/tokopi/assets/js/cart.js"></script>
    <script src="/tokopi/assets/js/ui.js"></script>
</body>

</html>