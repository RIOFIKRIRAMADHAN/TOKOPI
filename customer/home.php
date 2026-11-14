<?php
// customer/home.php
require_once __DIR__ . '/../config/bootstrap.php';

$has_table   = !empty($_SESSION['customer_table_id']);
$has_account = !empty($_SESSION['customer_account_id']);

// Tendang ke scan QR hanya jika dia BUKAN guest (tanpa meja) DAN belum login
if (!$has_table && !$has_account) {
    redirect('/tokopi/customer/t.php');
}

// Jika dia login dari rumah, variabel ini kosong. Kita set default.
$outlet_id   = (int)($_SESSION['customer_outlet_id'] ?? 0);
$table_nama  = $_SESSION['customer_table_nama'] ?? 'Belum Scan Meja';
$outlet_nama = $_SESSION['customer_outlet_nama'] ?? 'Tokopi';
$customer_name = $_SESSION['customer_account_name'] ?? null;

// Ambil tenant_id
if ($outlet_id > 0) {
    $stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
    $stmt->execute([$outlet_id]);
    $tenant_id = (int)$stmt->fetchColumn();
} else {
    // Fallback untuk login dari luar
    $stmt = $pdo->query("SELECT id FROM tenants ORDER BY id LIMIT 1");
    $tenant_id = (int)$stmt->fetchColumn();
}

// ============================================
// PROMO (dari kategori promo-combo)
// ============================================
$stmt = $pdo->prepare("
    SELECT p.id AS product_id, p.nama, MIN(v.harga_jual) AS harga_min
    FROM products p
    JOIN product_variants v ON v.product_id = p.id
    JOIN categories c ON c.id = p.category_id
    WHERE p.tenant_id = ? AND c.slug = 'promo-combo' AND p.is_active = 1 AND v.is_active = 1
    GROUP BY p.id ORDER BY p.id ASC LIMIT 4
");
$stmt->execute([$tenant_id]);
$promo_products = $stmt->fetchAll();

// ============================================
// MENU POPULER (7 hari terakhir)
// ============================================
$stmt = $pdo->prepare("
    SELECT p.id AS product_id, p.nama, MIN(v.harga_jual) AS harga_min,
           COALESCE(SUM(oi.qty), 0) AS total_terjual
    FROM products p
    JOIN product_variants v ON v.product_id = p.id
    LEFT JOIN order_items oi ON oi.variant_id = v.id
    LEFT JOIN orders o ON o.id = oi.order_id
        AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND o.outlet_id = ?
    WHERE p.tenant_id = ? AND p.is_active = 1 AND v.is_active = 1
    GROUP BY p.id HAVING total_terjual > 0 ORDER BY total_terjual DESC LIMIT 6
");
$stmt->execute([$outlet_id, $tenant_id]);
$popular_products = $stmt->fetchAll();

if (empty($popular_products)) {
    $stmt = $pdo->prepare("
        SELECT p.id AS product_id, p.nama, MIN(v.harga_jual) AS harga_min, 0 AS total_terjual
        FROM products p JOIN product_variants v ON v.product_id = p.id
        WHERE p.tenant_id = ? AND p.is_active = 1 AND v.is_active = 1
        GROUP BY p.id ORDER BY RAND() LIMIT 6
    ");
    $stmt->execute([$tenant_id]);
    $popular_products = $stmt->fetchAll();
}

function harga_fmt($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Tokopi · Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="/tokopi/assets/css/app.css" rel="stylesheet">
</head>

<body class="customer-layout">
    <!-- HEADER -->
    <div class="customer-app-header">
        <div class="brand-name">Tokopi</div>
        <div class="brand-tagline">Coffee · Eatery · Moments</div>

        <div class="greeting">
            <div class="hello"><?= $customer_name ? 'Hai,' : 'Selamat datang di Tokopi' ?></div>
            <div class="name"><?= $customer_name ? htmlspecialchars($customer_name) : 'Kopi Lovers ☕' ?></div>
        </div>

        <?php if ($has_table): ?>
            <div class="table-badge">
                <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($table_nama) ?> · <?= htmlspecialchars($outlet_nama) ?>
            </div>
        <?php else: ?>
            <div class="table-badge offline">
                <i class="bi bi-exclamation-circle-fill"></i> Belum Scan Meja
            </div>
        <?php endif; ?>
    </div>

    <!-- PROMO HERO (Hybrid) -->
    <?php if (!empty($promo_products)): ?>
        <div class="promo-hero">
            <h4>🎁 Promo Spesial Hari Ini</h4>
            <p>Diskon spesial untuk menu pilihan. Cek sekarang!</p>
        </div>
    <?php else: ?>
        <div class="promo-hero" style="background: linear-gradient(135deg, #2a1d0e 0%, #1a1a1a 100%);">
            <h4>☕ Ngopi Sore, Yuk</h4>
            <p>Pesan sekarang, ambil tanpa antre.</p>
        </div>
    <?php endif; ?>

    <!-- QUICK ACTIONS -->
    <div class="qa-grid">
        <!-- PINTAR: Kalau tidak punya meja, arahkan ke t.php -->
        <a href="<?= $has_table ? '/tokopi/customer/menu.php' : '/tokopi/customer/t.php' ?>" class="qa-item">
            <i class="bi bi-cup-hot-fill"></i> Pesan
        </a>
        <a href="/tokopi/customer/orders.php" class="qa-item">
            <i class="bi bi-receipt"></i> Order
        </a>
        <a href="/tokopi/customer/rewards.php" class="qa-item">
            <i class="bi bi-gift-fill"></i> Rewards
        </a>
        <a href="/tokopi/customer/profile.php" class="qa-item">
            <i class="bi bi-person-fill"></i> Profil
        </a>
    </div>

    <!-- MENU POPULER -->
    <?php if (!empty($popular_products)): ?>
        <div class="section-head">
            <h3>🔥 Menu Populer</h3>
            <a href="<?= $has_table ? '/tokopi/customer/menu.php' : '/tokopi/customer/t.php' ?>">Lihat semua →</a>
        </div>
        <div class="h-scroll">
            <?php foreach ($popular_products as $index => $p): ?>
                <a href="<?= $has_table ? '/tokopi/customer/product.php?id=' . (int)$p['product_id'] : '/tokopi/customer/t.php' ?>" class="product-h">
                    <div class="product-h-img">
                        <?php if ((int)$p['total_terjual'] > 0): ?>
                            <span class="badge-hot">Best</span>
                        <?php endif; ?>
                        <?php $bg_class = $index % 3 === 0 ? 'bg-dark' : ($index % 3 === 1 ? 'bg-sand' : 'bg-cream'); ?>
                        <div class="product-icon <?= $bg_class ?>"><i class="bi bi-cup-hot"></i></div>
                    </div>
                    <div class="product-h-body">
                        <div class="product-h-name"><?= htmlspecialchars($p['nama']) ?></div>
                        <div class="product-h-price"><?= $p['harga_min'] ? harga_fmt($p['harga_min']) : '—' ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- PROMO SECTION -->
    <?php if (!empty($promo_products)): ?>
        <div class="section-head">
            <h3>🎁 Promo & Combo</h3>
            <a href="<?= $has_table ? '/tokopi/customer/menu.php?cat=promo-combo' : '/tokopi/customer/t.php' ?>">Lihat semua →</a>
        </div>
        <div class="h-scroll" style="padding-bottom: 20px;">
            <?php foreach ($promo_products as $index => $p): ?>
                <a href="<?= $has_table ? '/tokopi/customer/product.php?id=' . (int)$p['product_id'] : '/tokopi/customer/t.php' ?>" class="product-h">
                    <div class="product-h-img">
                        <?php $bg_class = $index % 2 === 0 ? 'bg-dark' : 'bg-sand'; ?>
                        <div class="product-icon <?= $bg_class ?>"><i class="bi bi-bag-heart"></i></div>
                    </div>
                    <div class="product-h-body">
                        <div class="product-h-name"><?= htmlspecialchars($p['nama']) ?></div>
                        <div class="product-h-price"><?= $p['harga_min'] ? harga_fmt($p['harga_min']) : '—' ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- CART FLOAT -->
    <a href="/tokopi/customer/cart.php" class="cart-float">
        <i class="bi bi-bag"></i>
        <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
    </a>

    <!-- BOTTOM NAV -->
    <div class="bottom-nav">
        <a href="/tokopi/customer/home.php" class="nav-item active"><i class="bi bi-house-door-fill"></i>Home</a>
        <a href="<?= $has_table ? '/tokopi/customer/menu.php' : '/tokopi/customer/t.php' ?>" class="nav-item"><i class="bi bi-cup-hot"></i>Menu</a>
        <a href="/tokopi/customer/orders.php" class="nav-item"><i class="bi bi-receipt"></i>Order</a>
        <a href="/tokopi/customer/profile.php" class="nav-item"><i class="bi bi-person"></i>Profile</a>
    </div>

    <script src="/tokopi/assets/js/cart.js"></script>
    <script src="/tokopi/assets/js/ui.js"></script>
</body>
</html>