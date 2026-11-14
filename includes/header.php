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
    <link href="/tokopi/assets/css/app.css" rel="stylesheet">
    <style>
        /* Admin-specific overrides */
        .sidebar {
            background: #000;
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            z-index: var(--z-fixed);
            overflow-y: auto;
        }
        .sidebar-brand { background: #000; }
        .sidebar-brand small { color: rgba(255,255,255,.5); }
        .sidebar-section { color: rgba(255,255,255,.4); }
        .sidebar .nav-link { color: rgba(255,255,255,.75); }
        .sidebar .nav-link:hover { background: rgba(255,255,255,.06); color: #fff; }
        .sidebar .nav-link.active { background: var(--color-espresso); color: #fff; }

        .main-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            background: #FAF9F5;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--sand);
            padding: 0.875rem 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .topbar .page-title {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 1.125rem !important;
            font-weight: 600;
            color: var(--espresso);
            margin: 0;
        }

        .outlet-badge {
            background: var(--sand-light);
            color: var(--espresso);
            padding: .35rem .875rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: .625rem;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--espresso);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: .875rem;
        }

        .user-name {
            font-size: .8rem;
            font-weight: 600;
            color: var(--espresso);
        }

        .user-role {
            font-size: .7rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .content { padding: var(--space-6); }

        .btn-espresso { background: var(--color-espresso); color: #fff; }
        .btn-espresso:hover { background: var(--color-espresso-dark); color: #fff; }

        .card { border-color: var(--color-border); border-radius: var(--radius-xl); box-shadow: var(--shadow-sm); }
        .card-header { background: var(--color-bg-white); border-bottom: 1px solid var(--color-border); color: var(--color-espresso); }

        .table thead th { color: var(--color-text-muted); border-bottom: 2px solid var(--color-border); }
        .table-hover tbody tr:hover { background: var(--color-sand-50); }

        /* Badge pulse */
        @keyframes softPulse {
            0% { box-shadow: 0 0 0 0 rgba(45,27,10,0.4); }
            70% { box-shadow: 0 0 0 6px rgba(45,27,10,0); }
            100% { box-shadow: 0 0 0 0 rgba(45,27,10,0); }
        }
        .badge.bg-success { animation: softPulse 2s infinite; }

        /* Sidebar toggle for mobile - now inside topbar */
        .sidebar-toggle { display: none; background: var(--color-espresso); color: #fff; border: none; border-radius: var(--radius-md); width: 44px; height: 44px; box-shadow: var(--shadow-md); align-items: center; justify-content: center; }

        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: calc(var(--z-fixed) - 1); opacity: 0; visibility: hidden; transition: all var(--transition-base); }

        @media (max-width: 991.98px) {
            /* 1. Lepas margin sidebar di mobile */
            .main-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }

            /* 2. Sembunyikan sidebar asli di mobile (karena udah ada hamburger) */
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            /* 3. Benerin Topbar di mobile biar ga meluber */
            .topbar {
                padding: 0.75rem 1rem !important;
                flex-wrap: wrap !important;
                gap: 10px;
            }

            /* 4. Kecilin atau sembunyikan info user (Rayhan Owner) di mobile biar rapi */
            .topbar .user-badge .text-start {
                display: none !important; /* Menyembunyikan teks, sisakan foto/avatar */
            }

            .topbar .page-title {
                font-size: 1rem !important;
            }
        }

        /* Modal backdrop fix - gelap transparan + blur */
        .modal-backdrop.show {
            opacity: 1 !important;
            background-color: rgba(26, 26, 26, 0.4) !important;
            backdrop-filter: blur(6px) !important;
            -webkit-backdrop-filter: blur(6px) !important;
        }
        .modal-content {
            background-color: #ffffff !important;
            border: none !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
        }
        .modal {
            background: transparent !important;
        }
    </style>
</head>

<body>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand d-flex align-items-center gap-2">
            <div style="width: 36px; height: 36px; background: var(--color-sand); color: var(--color-espresso); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-cup-hot-fill" style="font-size: 1.2rem;"></i>
            </div>
            <div style="line-height: 1;">
                <span style="font-family: var(--font-serif); font-size: var(--text-xl); font-weight: var(--font-bold); letter-spacing: 0.5px;">Tokopi</span>
                <small style="font-size: var(--text-xs); display: block; margin-top: 4px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px;">Coffee ERP</small>
            </div>
        </div>

        <nav class="nav flex-column">
            <?php if (in_array(current_user_role(), ['owner', 'manager', 'finance'])): ?>
                <div class="sidebar-section">Utama</div>
                <a class="nav-link <?= $active_menu === 'dashboard' ? 'active' : '' ?>" href="/tokopi/views/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            <?php endif; ?>

            <?php if (in_array(current_user_role(), ['owner', 'manager', 'kasir'])): ?>
                <div class="sidebar-section">Operasional</div>
                <a class="nav-link <?= $active_menu === 'pos' ? 'active' : '' ?>" href="/tokopi/views/pos.php">
                    <i class="bi bi-cash-register"></i> POS / Kasir
                </a>
                <a class="nav-link <?= $active_menu === 'shift' ? 'active' : '' ?>" href="/tokopi/views/shift.php">
                    <i class="bi bi-clock-history"></i> Shift
                </a>
                <a class="nav-link <?= $active_menu === 'qr_orders' ? 'active' : '' ?>" href="/tokopi/views/qr_orders.php">
                    <i class="bi bi-bell"></i> Antrian QR <span class="badge bg-danger ms-1" id="sidebar-qr-count" style="display:none;">0</span>
                </a>
            <?php endif; ?>

            <?php if (in_array(current_user_role(), ['owner', 'manager', 'gudang'])): ?>
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
                <a class="nav-link <?= $active_menu === 'settings' ? 'active' : '' ?>" href="/tokopi/views/settings.php">
                    <i class="bi bi-gear"></i> Pengaturan Toko
                </a>
                <a class="nav-link <?= $active_menu === 'tables' ? 'active' : '' ?>" href="/tokopi/views/tables.php">
                    <i class="bi bi-grid-3x3"></i> Meja & QR
                </a>
                <a class="nav-link <?= $active_menu === 'posts' ? 'active' : '' ?>" href="/tokopi/views/posts.php">
                    <i class="bi bi-journal-text"></i> Artikel & Blog
                </a>
            <?php endif; ?>

            <?php if (in_array(current_user_role(), ['owner', 'manager', 'finance'])): ?>
                <div class="sidebar-section">Laporan</div>
                <a class="nav-link <?= $active_menu === 'reports_sales' ? 'active' : '' ?>" href="/tokopi/views/reports/sales.php">
                    <i class="bi bi-graph-up"></i> Penjualan
                </a>
                <a class="nav-link <?= $active_menu === 'expenses' ? 'active' : '' ?>" href="/tokopi/views/expenses.php">
                    <i class="bi bi-wallet2"></i> Pengeluaran
                </a>
                <a class="nav-link <?= $active_menu === 'reports_pl' ? 'active' : '' ?>" href="/tokopi/views/reports/profit_loss.php">
                    <i class="bi bi-calculator"></i> Laba Rugi
                </a>
            <?php endif; ?>
            <?php if (in_array(current_user_role(), ['owner', 'manager', 'finance'])): ?>
                <div class="sidebar-section">Loyalty</div>
                <a class="nav-link <?= $active_menu === 'customers' ? 'active' : '' ?>" href="/tokopi/views/customers.php">
                    <i class="bi bi-people"></i> Pelanggan
                </a>
                <?php if (in_array(current_user_role(), ['owner', 'manager'])): ?>
                    <a class="nav-link <?= $active_menu === 'rewards_manage' ? 'active' : '' ?>" href="/tokopi/views/rewards_manage.php">
                        <i class="bi bi-gift"></i> Rewards
                    </a>
                <?php endif; ?>
                <a class="nav-link <?= $active_menu === 'reports_loyalty' ? 'active' : '' ?>" href="/tokopi/views/reports/loyalty.php">
                    <i class="bi bi-bar-chart"></i> Laporan Loyalty
                </a>
                <a class="nav-link <?= $active_menu === 'contact_inbox' ? 'active' : '' ?>" href="/tokopi/views/contact_inbox.php">
                    <i class="bi bi-inbox"></i> Inbox Pesan
                </a>
                <a class="nav-link <?= $active_menu === 'testimonials' ? 'active' : '' ?>" href="/tokopi/views/testimonials.php">
                    <i class="bi bi-chat-quote"></i> Testimoni
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <div class="main-wrapper">
        <div class="topbar">
            <!-- Mobile Sidebar Toggle -->
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Buka Menu" aria-expanded="false">
                <i class="bi bi-list" style="font-size: 1.25rem;"></i>
            </button>
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
                        <div class="user-name"><?= e(current_user_name()) ?></div>
                        <div class="user-role"><?= e(current_user_role()) ?></div>
                    </div>
                </div>

                <a href="/tokopi/logout.php" class="btn btn-sm btn-outline-espresso">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="content">

<script>
    // Sidebar mobile toggle
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    function openSidebar() {
        sidebar.classList.add('open');
        backdrop.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.innerHTML = '<i class="bi bi-x" style="font-size: 1.25rem;"></i>';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<i class="bi bi-list" style="font-size: 1.25rem;"></i>';
    }

    toggle.addEventListener('click', () => {
        if (sidebar.classList.contains('open')) closeSidebar();
        else openSidebar();
    });

    backdrop.addEventListener('click', closeSidebar);

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
    });
</script>