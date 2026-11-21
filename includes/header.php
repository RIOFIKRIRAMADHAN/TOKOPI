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
<?php
// ---------- Navigasi (dikelompokkan per tugas) ----------
$__role = current_user_role();
$__nav = [
    'Ringkasan' => [
        ['dashboard', 'Dashboard', 'bi-grid-1x2', '/tokopi/views/dashboard.php', ['owner','manager','finance']],
    ],
    'Operasional' => [
        ['pos', 'POS / Kasir', 'bi-cash-stack', '/tokopi/views/pos.php', ['owner','manager','kasir']],
        ['qr_orders', 'Antrian QR', 'bi-qr-code-scan', '/tokopi/views/qr_orders.php', ['owner','manager','kasir'], 'sidebar-qr-count'],
        ['shift', 'Shift', 'bi-clock-history', '/tokopi/views/shift.php', ['owner','manager','kasir']],
        ['tables', 'Meja & QR', 'bi-grid-3x3-gap', '/tokopi/views/tables.php', ['owner']],
    ],
    'Katalog' => [
        ['products', 'Produk', 'bi-cup-hot', '/tokopi/views/products.php', ['owner','manager','gudang']],
        ['categories', 'Kategori', 'bi-tags', '/tokopi/views/categories.php', ['owner','manager','gudang']],
        ['modifiers', 'Opsi Menu', 'bi-ui-checks', '/tokopi/views/modifiers.php', ['owner','manager','gudang']],
        ['recipes', 'Resep', 'bi-journal-bookmark', '/tokopi/views/recipes.php', ['owner','manager','gudang']],
    ],
    'Inventori' => [
        ['ingredients', 'Bahan Baku', 'bi-box-seam', '/tokopi/views/ingredients.php', ['owner','manager','gudang']],
        ['stock', 'Stok', 'bi-boxes', '/tokopi/views/stock.php', ['owner','manager','gudang']],
    ],
    'Keuangan' => [
        ['reports_sales', 'Laporan Penjualan', 'bi-graph-up-arrow', '/tokopi/views/reports/sales.php', ['owner','manager','finance']],
        ['reports_pl', 'Laba Rugi', 'bi-calculator', '/tokopi/views/reports/profit_loss.php', ['owner','manager','finance']],
        ['expenses', 'Pengeluaran', 'bi-wallet2', '/tokopi/views/expenses.php', ['owner','manager','finance']],
    ],
    'Pelanggan' => [
        ['customers', 'Pelanggan', 'bi-person-hearts', '/tokopi/views/customers.php', ['owner','manager','finance']],
        ['rewards_manage', 'Rewards', 'bi-gift', '/tokopi/views/rewards_manage.php', ['owner','manager']],
        ['reports_loyalty', 'Laporan Loyalty', 'bi-bar-chart-line', '/tokopi/views/reports/loyalty.php', ['owner','manager','finance']],
    ],
    'Website' => [
        ['posts', 'Artikel & Blog', 'bi-journal-richtext', '/tokopi/views/posts.php', ['owner']],
        ['testimonials', 'Testimoni', 'bi-chat-quote', '/tokopi/views/testimonials.php', ['owner','manager','finance']],
        ['contact_inbox', 'Inbox Pesan', 'bi-inbox', '/tokopi/views/contact_inbox.php', ['owner','manager','finance']],
    ],
    'Pengaturan' => [
        ['outlets', 'Outlet', 'bi-shop', '/tokopi/views/outlets.php', ['owner']],
        ['users', 'Pengguna', 'bi-person-badge', '/tokopi/views/users.php', ['owner']],
        ['settings', 'Pengaturan Toko', 'bi-gear', '/tokopi/views/settings.php', ['owner']],
    ],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="/tokopi/assets/css/app.css?v=20261121f" rel="stylesheet">
    <link href="/tokopi/assets/css/admin.css?v=20261121g" rel="stylesheet">
</head>

<body>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="sidebar" id="sidebar" aria-label="Navigasi utama">
        <a class="sidebar-brand" href="/tokopi/views/dashboard.php">
            <span class="sidebar-brand-mark"><i class="bi bi-cup-hot-fill" aria-hidden="true"></i></span>
            <span>
                <span class="sidebar-brand-name">Tokopi</span>
                <span class="sidebar-brand-sub">Coffee ERP</span>
            </span>
        </a>

        <nav class="sidebar-nav">
            <?php foreach ($__nav as $__group => $__items):
                $__visible = array_filter($__items, function ($it) use ($__role) { return in_array($__role, $it[4], true); });
                if (!$__visible) continue;
                $__has_active = (bool)array_filter($__visible, function ($it) use ($active_menu) { return $it[0] === $active_menu; });
                $__gid = 'nav-' . strtolower($__group);
            ?>
                <div class="nav-group" data-group="<?= e($__gid) ?>" <?= $__has_active ? 'data-active="1"' : '' ?>>
                    <button type="button" class="nav-group-toggle" aria-expanded="true" aria-controls="<?= e($__gid) ?>">
                        <span><?= e($__group) ?></span><i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="nav-group-items" id="<?= e($__gid) ?>">
                        <?php foreach ($__visible as $it): ?>
                            <a class="nav-link <?= $active_menu === $it[0] ? 'active' : '' ?>" href="<?= $it[3] ?>" <?= $active_menu === $it[0] ? 'aria-current="page"' : '' ?>>
                                <i class="bi <?= $it[2] ?>" aria-hidden="true"></i> <?= e($it[1]) ?>
                                <?php if (!empty($it[5])): ?>
                                    <span class="badge bg-danger badge-live" id="<?= $it[5] ?>" style="display:none;">0</span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
                <i class="bi bi-list" style="font-size: 1.25rem;" aria-hidden="true"></i>
            </button>
            <h1 class="page-title"><?= e($page_title) ?></h1>
            <div class="topbar-actions">
                <span class="outlet-badge" title="Outlet aktif">
                    <i class="bi bi-shop" aria-hidden="true"></i>
                    <span class="outlet-name"><?= $outlet_info ? e($outlet_info['nama']) : 'Semua Outlet' ?></span>
                </span>

                <div class="dropdown">
                    <button class="user-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu akun">
                        <span class="user-avatar"><?= e(strtoupper(substr(current_user_name(), 0, 1))) ?></span>
                        <span class="user-meta">
                            <span class="user-name d-block"><?= e(current_user_name()) ?></span>
                            <span class="user-role d-block"><?= e(current_user_role()) ?></span>
                        </span>
                        <i class="bi bi-chevron-down small text-muted" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="/tokopi/company/index.php" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></i>Lihat website</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/tokopi/logout.php"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content" id="main">

<script>
(function () {
    // Sidebar mobile
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    function setOpen(open) {
        sidebar.classList.toggle('open', open);
        backdrop.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.innerHTML = '<i class="bi ' + (open ? 'bi-x-lg' : 'bi-list') + '" style="font-size:1.25rem" aria-hidden="true"></i>';
    }
    toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('open')));
    backdrop.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });

    // Grup sidebar bisa dilipat; status diingat per browser
    let saved = {};
    try { saved = JSON.parse(localStorage.getItem('tokopi_nav') || '{}'); } catch (e) {}
    document.querySelectorAll('.nav-group').forEach((g) => {
        const btn = g.querySelector('.nav-group-toggle');
        const key = g.dataset.group;
        const apply = (collapsed) => { g.classList.toggle('collapsed', collapsed); btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true'); };
        apply(!g.dataset.active && saved[key] === 1);
        btn.addEventListener('click', () => {
            const c = !g.classList.contains('collapsed');
            apply(c);
            saved[key] = c ? 1 : 0;
            try { localStorage.setItem('tokopi_nav', JSON.stringify(saved)); } catch (e) {}
        });
    });
    const active = sidebar.querySelector('.nav-link.active');
    if (active) active.scrollIntoView({ block: 'nearest' });
})();
</script>
