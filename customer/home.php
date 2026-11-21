<?php
// customer/home.php
require_once __DIR__ . '/../config/bootstrap.php';

$has_table   = !empty($_SESSION['customer_table_id']);
$has_account = !empty($_SESSION['customer_account_id']);

// Blokir redirect DIHAPUS. User bebas lihat home.

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
    // Fallback untuk login dari luar / tanpa meja
    $stmt = $pdo->query("SELECT id FROM tenants ORDER BY id LIMIT 1");
    $tenant_id = (int)$stmt->fetchColumn();
}

// ============================================
// PROMO (dari kategori promo-combo)
// ============================================
$stmt = $pdo->prepare("
    SELECT p.id AS product_id, p.nama, p.image_path, MIN(v.harga_jual) AS harga_min
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
    SELECT p.id AS product_id, p.nama, p.image_path, MIN(v.harga_jual) AS harga_min,
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
        SELECT p.id AS product_id, p.nama, p.image_path, MIN(v.harga_jual) AS harga_min, 0 AS total_terjual
        FROM products p JOIN product_variants v ON v.product_id = p.id
        WHERE p.tenant_id = ? AND p.is_active = 1 AND v.is_active = 1
        GROUP BY p.id ORDER BY RAND() LIMIT 6
    ");
    $stmt->execute([$tenant_id]);
    $popular_products = $stmt->fetchAll();
}

// Menu habis di outlet meja ini
$soldout_p = $has_table ? menu_soldout_products($pdo, $outlet_id, array_merge(array_column($popular_products, 'product_id'), array_column($promo_products, 'product_id'))) : [];

// Member info (poin & tier)
$member = null;
if ($has_account) {
    $stmt = $pdo->prepare("SELECT points, tier FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_account_id']]);
    $member = $stmt->fetch();
}
$jam = (int)date('G');
$sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));

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
    <link href="/tokopi/assets/css/app.css?v=20261121f" rel="stylesheet">
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>

<body>
    <!-- HEADER -->
    <header class="cx-header">
        <div class="cx-topline">
            <span class="cx-brand">Tokopi</span>
            <?php if ($has_table): ?>
                <span class="cx-table"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> <?= htmlspecialchars($table_nama) ?> · <?= htmlspecialchars(preg_replace('/^.*?-\s*/', '', $outlet_nama)) ?></span>
            <?php else: ?>
                <a href="/tokopi/customer/t.php" class="cx-table is-off"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Scan meja</a>
            <?php endif; ?>
        </div>
        <div class="cx-hello"><?= $sapaan ?><?= $customer_name ? ',' : '' ?></div>
        <h1 class="cx-title"><?= $customer_name ? htmlspecialchars($customer_name) : 'Mau ngopi apa hari ini?' ?></h1>
    </header>

    <?php if (!$has_table): ?>
        <div class="cx-notice" role="status">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span>Kamu bisa lihat-lihat menu dulu. Untuk memesan, scan QR di meja.</span>
            <a href="/tokopi/customer/t.php">Cara scan</a>
        </div>
    <?php endif; ?>

    <!-- MEMBER -->
    <?php if ($member): ?>
        <a href="/tokopi/customer/rewards.php" class="cx-member">
            <span class="cx-member-ico"><i class="bi bi-gift" aria-hidden="true"></i></span>
            <span>
                <b><?= number_format((int)$member['points'], 0, ',', '.') ?> poin · <?= htmlspecialchars(ucfirst(strtolower($member['tier'] ?? 'Bronze'))) ?></b>
                <span>Tukar poin dengan voucher & menu gratis</span>
            </span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    <?php else: ?>
        <a href="/tokopi/customer/register.php" class="cx-member">
            <span class="cx-member-ico"><i class="bi bi-stars" aria-hidden="true"></i></span>
            <span>
                <b>Kumpulkan poin tiap belanja</b>
                <span>1 poin setiap Rp10.000 · daftar gratis</span>
            </span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    <?php endif; ?>

    <!-- PROMO -->
    <?php if (!empty($promo_products)): ?>
        <a class="cx-promo" href="/tokopi/customer/menu.php?cat=promo-combo">
            <small>Promo hari ini</small>
            <strong><?= count($promo_products) ?> paket hemat tersedia</strong>
            <span>Combo kopi + camilan, harga lebih ringan.</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    <?php endif; ?>

    <!-- MENU POPULER -->
    <?php if (!empty($popular_products)): ?>
        <div class="section-head">
            <h2 class="h3-like" style="font-family:var(--font-serif);font-size:1.1rem;font-weight:700;color:var(--color-espresso);margin:0;">Paling sering dipesan</h2>
            <a href="/tokopi/customer/menu.php">Lihat semua <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="h-scroll">
            <?php foreach ($popular_products as $p):
                $link = '/tokopi/customer/product.php?id=' . (int)$p['product_id'];
            ?>
                <a href="<?= $link ?>" class="product-h <?= !empty($soldout_p[(int)$p['product_id']]) ? 'is-soldout' : '' ?>">
                    <div class="product-h-img">
                        <?php if (!empty($soldout_p[(int)$p['product_id']])): ?><span class="badge-out">Habis</span><?php endif; ?>
                        <?php if ((int)$p['total_terjual'] > 0): ?>
                            <span class="badge-hot">Favorit</span>
                        <?php endif; ?>
                        <?= product_img($p['image_path'], $p['nama']) ?>
                    </div>
                    <div class="product-h-body">
                        <div class="product-h-name"><?= htmlspecialchars($p['nama']) ?></div>
                        <div class="product-h-price"><?= $p['harga_min'] ? harga_fmt($p['harga_min']) : '—' ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- PROMO LIST -->
    <?php if (!empty($promo_products)): ?>
        <div class="section-head">
            <h2 style="font-family:var(--font-serif);font-size:1.1rem;font-weight:700;color:var(--color-espresso);margin:0;">Promo & combo</h2>
            <a href="/tokopi/customer/menu.php?cat=promo-combo">Lihat semua <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="h-scroll" style="padding-bottom: 20px;">
            <?php foreach ($promo_products as $p):
                $link = '/tokopi/customer/product.php?id=' . (int)$p['product_id'];
            ?>
                <a href="<?= $link ?>" class="product-h <?= !empty($soldout_p[(int)$p['product_id']]) ? 'is-soldout' : '' ?>">
                    <div class="product-h-img"><?php if (!empty($soldout_p[(int)$p['product_id']])): ?><span class="badge-out">Habis</span><?php endif; ?><?= product_img($p['image_path'], $p['nama']) ?></div>
                    <div class="product-h-body">
                        <div class="product-h-name"><?= htmlspecialchars($p['nama']) ?></div>
                        <div class="product-h-price"><?= $p['harga_min'] ? harga_fmt($p['harga_min']) : '—' ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div style="height: calc(var(--bottom-nav-height) + 24px);"></div>

    <!-- CART FLOAT -->
    <a href="<?= $has_table ? '/tokopi/customer/cart.php' : '/tokopi/customer/t.php' ?>" class="cart-float" aria-label="Keranjang">
        <i class="bi bi-bag"></i>
        <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
    </a>

    <!-- BOTTOM NAV -->
    <nav class="bottom-nav" aria-label="Navigasi">
        <a href="/tokopi/customer/home.php" class="nav-item active" aria-current="page"><i class="bi bi-house-door-fill" aria-hidden="true"></i>Home</a>
        <a href="/tokopi/customer/menu.php" class="nav-item"><i class="bi bi-cup-hot"></i>Menu</a>
        <a href="/tokopi/customer/orders.php" class="nav-item"><i class="bi bi-receipt"></i>Order</a>
        <a href="/tokopi/customer/profile.php" class="nav-item"><i class="bi bi-person"></i>Profile</a>
    </nav>

    <script src="/tokopi/assets/js/cart.js?v=20261121d"></script>
    <script src="/tokopi/assets/js/ui.js?v=20261121b"></script>
</body>
</html>