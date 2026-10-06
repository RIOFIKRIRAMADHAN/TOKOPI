<?php
// includes/header.php
// Butuh variabel $page_title (opsional)
$page_title = $page_title ?? 'Dashboard';
$active_menu = $active_menu ?? '';

// Ambil nama outlet untuk ditampilkan di topbar
$outlet_info = null;
if (current_outlet_id()) {
    $stmt = $pdo->prepare("SELECT nama, code FROM outlets WHERE id = ?");
    $stmt->execute([current_outlet_id()]);
    $outlet_info = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --sand: #E1DCC9;
            --sand-light: #F3F0E6;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #FAF9F5;
            margin: 0;
        }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; color: var(--espresso); }
        .money { font-variant-numeric: tabular-nums; font-family: 'JetBrains Mono', monospace; }

        /* Sidebar */
        .sidebar {
            background: #000;
            color: #fff;
            width: 260px;
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            padding: 1.25rem 0.875rem;
            overflow-y: auto;
        }
        .sidebar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            padding: 0.5rem 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            margin-bottom: 1rem;
        }
        .sidebar-brand small {
            display: block;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .7rem;
            color: rgba(255,255,255,.5);
            font-weight: 400;
            letter-spacing: .05em;
            text-transform: uppercase;
            margin-top: .25rem;
        }
        .sidebar-section {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255,255,255,.4);
            padding: .75rem 1rem .25rem;
            font-weight: 600;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
            border-radius: .5rem;
            padding: .625rem 1rem;
            font-size: .875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .625rem;
            transition: .15s;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.06);
            color: #fff;
        }
        .sidebar .nav-link.active {
            background: var(--espresso);
            color: #fff;
        }
        .sidebar .nav-link i { font-size: 1.05rem; }

        /* Main area */
        .main-wrapper {
            margin-left: 260px;
            min-height: 100vh;
        }
        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--sand);
            padding: .875rem 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar .page-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--espresso);
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .outlet-badge {
            background: var(--sand-light);
            color: var(--espresso);
            padding: .35rem .875rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
        }
        .user-badge {
            display: flex;
            align-items: center;
            gap: .625rem;
        }
        .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--espresso);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: .875rem;
        }
        .content {
            padding: 1.75rem;
        }

        /* Card override */
        .card {
            border-color: var(--sand);
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(65,45,21,.04);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid var(--sand);
            font-weight: 600;
            color: var(--espresso);
            padding: 1rem 1.25rem;
            border-radius: 1rem 1rem 0 0 !important;
        }
        .btn-espresso {
            background: var(--espresso);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: .75rem;
            padding: .5rem 1rem;
        }
        .btn-espresso:hover { background: #2a1d0e; color: #fff; }

        /* Table */
        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6c757d;
            font-weight: 600;
            border-bottom: 2px solid var(--sand);
        }
        .table td { vertical-align: middle; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        Tokopi
        <small>Coffee Shop ERP</small>
    </div>

    <nav class="nav flex-column">
        <?php if (in_array(current_user_role(), ['owner','manager','finance'])): ?>
            <div class="sidebar-section">Utama</div>
            <a class="nav-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>" href="/tokopi/views/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        <?php endif; ?>

        <?php if (in_array(current_user_role(), ['owner','manager','kasir'])): ?>
            <div class="sidebar-section">Operasional</div>
            <a class="nav-link <?= $active_menu === 'pos' ? 'active' : '' ?>" href="/tokopi/views/pos.php">
                <i class="bi bi-cash-register"></i> POS / Kasir
            </a>
            <a class="nav-link <?= $active_menu === 'shift' ? 'active' : '' ?>" href="/tokopi/views/shift.php">
                <i class="bi bi-clock-history"></i> Shift
            </a>
        <?php endif; ?>

        <?php if (in_array(current_user_role(), ['owner','manager','gudang'])): ?>
            <div class="sidebar-section">Master Data</div>
            <a class="nav-link <?= $active_menu === 'categories' ? 'active' : '' ?>" href="/tokopi/views/categories.php">
                <i class="bi bi-tags"></i> Kategori
            </a>
            <a class="nav-link <?= $active_menu === 'products' ? 'active' : '' ?>" href="/tokopi/views/products.php">
                <i class="bi bi-cup-hot"></i> Produk
            </a>
            <a class="nav-link <?= $active_menu === 'ingredients' ? 'active' : '' ?>" href="/tokopi/views/ingredients.php">
                <i class="bi bi-box-seam"></i> Bahan Baku
            </a>
            <a class="nav-link <?= $active_menu === 'recipes' ? 'active' : '' ?>" href="/tokopi/views/recipes.php">
                <i class="bi bi-journal-text"></i> Resep
            </a>
            <a class="nav-link <?= $active_menu === 'stock' ? 'active' : '' ?>" href="/tokopi/views/stock.php">
                <i class="bi bi-archive"></i> Stok
            </a>
        <?php endif; ?>

        <?php if (current_user_role() === 'owner'): ?>
            <div class="sidebar-section">Pengaturan</div>
            <a class="nav-link <?= $active_menu === 'outlets' ? 'active' : '' ?>" href="/tokopi/views/outlets.php">
                <i class="bi bi-shop"></i> Outlet
            </a>
            <a class="nav-link <?= $active_menu === 'users' ? 'active' : '' ?>" href="/tokopi/views/users.php">
                <i class="bi bi-people"></i> Pengguna
            </a>
        <?php endif; ?>

        <?php if (in_array(current_user_role(), ['owner','manager','finance'])): ?>
            <div class="sidebar-section">Laporan</div>
            <a class="nav-link <?= $active_menu === 'reports_sales' ? 'active' : '' ?>" href="/tokopi/views/reports/sales.php">
                <i class="bi bi-graph-up"></i> Penjualan
            </a>
            <a class="nav-link <?= $active_menu === 'expenses' ? 'active' : '' ?>" href="/tokopi/views/expenses.php">
                <i class="bi bi-wallet2"></i> Pengeluaran
            </a>
        <?php endif; ?>
    </nav>
</aside>

<div class="main-wrapper">
    <div class="topbar">
        <h1 class="page-title"><?= e($page_title) ?></h1>
        <div class="d-flex align-items-center gap-3">
            <?php if ($outlet_info): ?>
                <span class="outlet-badge">
                    <i class="bi bi-shop me-1"></i><?= e($outlet_info['nama']) ?>
                </span>
            <?php else: ?>
                <span class="outlet-badge">
                    <i class="bi bi-shop me-1"></i>Semua Outlet
                </span>
            <?php endif; ?>

            <div class="user-badge">
                <div class="user-avatar">
                    <?= strtoupper(substr(current_user_name(), 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size:.8rem; font-weight:600; color: var(--espresso);">
                        <?= e(current_user_name()) ?>
                    </div>
                    <div style="font-size:.7rem; color:#6c757d; text-transform:uppercase; letter-spacing:.05em;">
                        <?= e(current_user_role()) ?>
                    </div>
                </div>
            </div>

            <a href="/tokopi/logout.php" class="btn btn-sm btn-outline-dark">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="content">
