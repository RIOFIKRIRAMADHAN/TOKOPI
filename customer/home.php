<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$outlet_id   = (int)$_SESSION['customer_outlet_id'];
$table_id    = (int)$_SESSION['customer_table_id'];
$table_nama  = $_SESSION['customer_table_nama'];
$table_code  = $_SESSION['customer_table_code'];
$outlet_nama = $_SESSION['customer_outlet_nama'];

// Ambil tenant_id
$stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$tenant_id = (int)$stmt->fetchColumn();

$customer_name = $_SESSION['customer_account_name'] ?? null;

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

function harga_fmt($n)
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#412D15">
    <title>Tokopi · Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --espresso-dark: #2a1d0e;
            --sand: #E1DCC9;
            --sand-light: #F3F0E6;
            --sand-50: #FAF6EC;
            --bg: #EDEAE3;
            --text-muted: #8a8478;
            --green: #2E7D5B;
            --red: #A83232;
            --gold: #B8860B;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1a1a1a;
            overflow-x: hidden;
            padding-bottom: 80px;
        }

        /* HEADER */
        .app-header {
            background: linear-gradient(160deg, #1a1a1a 0%, #2a1d0e 60%, #3d2a15 100%);
            color: #fff;
            padding: 20px 22px 26px;
            border-radius: 0 0 28px 28px;
            position: relative;
            overflow: hidden;
        }

        .app-header::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(225, 220, 201, .15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .header-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 2px;
            position: relative;
        }

        .header-tagline {
            font-size: 0.7rem;
            color: var(--sand);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            font-weight: 500;
            position: relative;
            margin-bottom: 16px;
        }

        .greeting {
            margin-top: 10px;
            position: relative;
        }

        .greeting .hello {
            font-size: 0.85rem;
            color: var(--sand);
            margin-bottom: 2px;
        }

        .greeting .name {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.1;
        }

        .table-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(225, 220, 201, .95);
            color: var(--espresso);
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            margin-top: 14px;
            position: relative;
        }

        /* HERO PROMO (Hybrid) */
        .promo-hero {
            margin: 16px 20px 8px;
            border-radius: 20px;
            padding: 24px 22px;
            background: linear-gradient(135deg, var(--espresso) 0%, #6b4a26 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(65, 45, 21, .15);
        }

        .promo-hero::after {
            content: '🎁';
            position: absolute;
            right: -20px;
            top: -20px;
            font-size: 8rem;
            opacity: 0.1;
            filter: grayscale(100%);
        }

        .promo-hero h4 {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem;
            font-weight: 700;
            margin: 0 0 4px;
            position: relative;
        }

        .promo-hero p {
            font-size: 0.8rem;
            color: var(--sand);
            margin: 0;
            position: relative;
        }

        /* QUICK ACTIONS (Pill) */
        /* QUICK ACTIONS (Grid 4 Kolom Simetris) */
        .qa-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            padding: 12px 20px 4px;
        }

        .qa-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: #fff;
            padding: 12px 4px;
            border-radius: 14px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--espresso);
            text-align: center;
            box-shadow: 0 2px 6px rgba(65, 45, 21, .05);
            text-decoration: none;
            transition: 0.15s;
        }

        .qa-item:active {
            transform: scale(0.95);
        }

        .qa-item i {
            font-size: 1.2rem;
            color: var(--espresso);
            margin-bottom: 2px;
        }

        /* SECTION */
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 22px 22px 12px;
        }

        .section-head h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--espresso);
            margin: 0;
            font-weight: 700;
        }

        .section-head a {
            font-size: 0.72rem;
            color: var(--espresso);
            text-decoration: none;
            font-weight: 700;
            opacity: 0.6;
        }

        /* HORIZONTAL SCROLL */
        .h-scroll {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding: 0 22px 6px;
            scrollbar-width: none;
        }

        .h-scroll::-webkit-scrollbar {
            display: none;
        }

        /* PRODUCT CARD (HORIZONTAL) */
        .product-h {
            flex-shrink: 0;
            width: 160px;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(65, 45, 21, .05);
            text-decoration: none;
            color: inherit;
            transition: 0.15s;
        }

        .product-h:active {
            transform: scale(0.97);
        }

        .product-h-img {
            height: 110px;
            position: relative;
            overflow: hidden;
        }

        .product-h-body {
            padding: 10px 12px 12px;
        }

        .product-h-name {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--espresso);
            line-height: 1.2;
            margin-bottom: 4px;
            min-height: 32px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-h-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--espresso);
        }

        .badge-hot {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--red);
            color: #fff;
            font-size: 0.55rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            z-index: 1;
        }

        /* ICON MONOKROM PALETTE */
        .product-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-icon i {
            font-size: 2.5rem;
        }

        .bg-cream {
            background: linear-gradient(135deg, var(--sand-50) 0%, var(--sand-light) 100%);
            color: var(--espresso);
        }

        .bg-sand {
            background: linear-gradient(135deg, var(--sand) 0%, #C9C0A3 100%);
            color: var(--espresso);
        }

        .bg-dark {
            background: linear-gradient(135deg, #3d2a15 0%, #1a1a1a 100%);
            color: var(--sand);
        }

        /* BOTTOM NAV */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 68px;
            background: #fff;
            border-top: 1px solid #f5f2ea;
            display: flex;
            align-items: center;
            box-shadow: 0 -4px 20px rgba(65, 45, 21, .06);
            z-index: 100;
        }

        .nav-item {
            flex: 1;
            text-align: center;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            padding: 6px 0;
        }

        .nav-item i {
            display: block;
            font-size: 1.3rem;
            margin-bottom: 2px;
        }

        .nav-item.active {
            color: var(--espresso);
        }

        /* CART FLOAT */
        .cart-float {
            position: fixed;
            bottom: 86px;
            right: 20px;
            width: 56px;
            height: 56px;
            background: var(--espresso);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.4rem;
            box-shadow: 0 8px 24px rgba(65, 45, 21, .4);
            text-decoration: none;
            z-index: 90;
        }

        .cart-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #dc2626;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 700;
            min-width: 22px;
            height: 22px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="app-header">
        <div class="header-brand">Tokopi</div>
        <div class="header-tagline">Coffee · Eatery · Moments</div>

        <div class="greeting">
            <div class="hello"><?= $customer_name ? 'Hai,' : 'Selamat datang di Tokopi' ?></div>
            <div class="name"><?= $customer_name ? htmlspecialchars($customer_name) : 'Kopi Lovers ☕' ?></div>
        </div>

        <div class="table-badge">
            <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($table_nama) ?> · <?= htmlspecialchars($outlet_nama) ?>
        </div>
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

    <!-- QUICK ACTIONS (Pill) -->
    <!-- QUICK ACTIONS (Grid) -->
    <div class="qa-grid">
        <a href="/tokopi/customer/menu.php" class="qa-item">
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
            <a href="/tokopi/customer/menu.php">Lihat semua →</a>
        </div>
        <div class="h-scroll">
            <?php foreach ($popular_products as $index => $p): ?>
                <a href="/tokopi/customer/product.php?id=<?= (int)$p['product_id'] ?>" class="product-h">
                    <div class="product-h-img">
                        <?php if ((int)$p['total_terjual'] > 0): ?>
                            <span class="badge-hot">Best</span>
                        <?php endif; ?>
                        <?php
                        // Rotasi warna background icon
                        $bg_class = $index % 3 === 0 ? 'bg-dark' : ($index % 3 === 1 ? 'bg-sand' : 'bg-cream');
                        ?>
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
            <a href="/tokopi/customer/menu.php?cat=promo-combo">Lihat semua →</a>
        </div>
        <div class="h-scroll" style="padding-bottom: 20px;">
            <?php foreach ($promo_products as $index => $p): ?>
                <a href="/tokopi/customer/product.php?id=<?= (int)$p['product_id'] ?>" class="product-h">
                    <div class="product-h-img">
                        <?php
                        $bg_class = $index % 2 === 0 ? 'bg-dark' : 'bg-sand';
                        ?>
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
        <a href="/tokopi/customer/menu.php" class="nav-item"><i class="bi bi-cup-hot"></i>Menu</a>
        <a href="/tokopi/customer/orders.php" class="nav-item"><i class="bi bi-receipt"></i>Order</a>
        <a href="/tokopi/customer/profile.php" class="nav-item"><i class="bi bi-person"></i>Profile</a>
    </div>

    <script>
        function updateCartBadge() {
            try {
                const cart = JSON.parse(localStorage.getItem('tokopi_cart') || '{}');
                const count = Object.values(cart).reduce((s, it) => s + (it.qty || 0), 0);
                const badge = document.getElementById('cart-badge');
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            } catch (e) {}
        }
        updateCartBadge();
        window.addEventListener('focus', updateCartBadge);
    </script>

</body>

</html>