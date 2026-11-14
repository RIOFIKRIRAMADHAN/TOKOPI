<?php
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

$stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$tenant_id = (int)$stmt->fetchColumn();

// Kategori
$stmt = $pdo->prepare("SELECT id, nama FROM categories WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, nama");
$stmt->execute([$tenant_id]);
$categories = $stmt->fetchAll();

// Semua varian
$stmt = $pdo->prepare("
    SELECT v.id AS variant_id, v.nama AS variant_nama, v.harga_jual,
           p.id AS product_id, p.nama AS product_nama, p.description, p.category_id
    FROM product_variants v
    JOIN products p ON p.id = v.product_id
    WHERE p.tenant_id = ? AND v.is_active = 1 AND p.is_active = 1
    ORDER BY p.nama, v.id
");
$stmt->execute([$tenant_id]);
$all_variants = $stmt->fetchAll();

// Kelompok per produk
$products_map = [];
foreach ($all_variants as $v) {
    $pid = (int)$v['product_id'];
    if (!isset($products_map[$pid])) {
        $products_map[$pid] = [
            'product_id'   => $pid,
            'product_nama' => $v['product_nama'],
            'description'  => $v['description'],
            'category_id'  => $v['category_id'],
            'variants'     => [],
        ];
    }
    $products_map[$pid]['variants'][] = $v;
}

// Featured (2 produk pertama)
$featured_ids = array_slice(array_keys($products_map), 0, 2);
$featured = [];
foreach ($featured_ids as $fid) {
    $featured[] = $products_map[$fid];
}

// Sisa produk
$regular_products = [];
foreach ($products_map as $pid => $prod) {
    if (!in_array($pid, $featured_ids, true)) {
        $regular_products[] = $prod;
    }
}

function harga_termurah($variants)
{
    $min = PHP_INT_MAX;
    foreach ($variants as $v) {
        $h = (float)$v['harga_jual'];
        if ($h < $min) $min = $h;
    }
    return $min === PHP_INT_MAX ? 0 : $min;
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
    <title>Menu · Tokopi</title>
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
            overflow-x: hidden;
            padding-bottom: 80px;
        }

        /* HEADER */
        .app-header {
            background: linear-gradient(160deg, #1a1a1a 0%, #2a1d0e 60%, #3d2a15 100%);
            color: #fff;
            padding: 20px 22px 20px;
            border-radius: 0 0 24px 24px;
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

        .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            position: relative;
        }

        .brand-tagline {
            font-size: 0.7rem;
            color: var(--sand);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            margin-bottom: 10px;
            position: relative;
        }

        .table-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(225, 220, 201, .95);
            color: var(--espresso);
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            position: relative;
        }

        /* SEARCH */
        .search-wrap {
            padding: 16px 22px 8px;
        }

        .search-bar {
            background: #fff;
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 12px rgba(65, 45, 21, .06);
            font-size: 0.85rem;
        }

        .search-bar i {
            color: var(--espresso);
            opacity: 0.5;
            font-size: 1rem;
        }

        .search-bar input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 0.9rem;
            font-family: inherit;
            background: transparent;
        }

        .search-bar input::placeholder {
            color: #bbb;
        }

        /* KATEGORI */
        .cat-scroll {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 12px 22px 4px;
            scrollbar-width: none;
        }

        .cat-scroll::-webkit-scrollbar {
            display: none;
        }

        .cat-pill {
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
            flex-shrink: 0;
            background: #fff;
            color: var(--espresso);
            border: 1.5px solid #f0ede5;
            cursor: pointer;
            transition: 0.15s;
            font-family: inherit;
        }

        .cat-pill.active {
            background: var(--espresso);
            color: #fff;
            border-color: var(--espresso);
        }

        /* FEATURED MINI */
        .featured-mini {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 12px 22px 16px;
        }

        .feat-card {
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            height: 140px;
            text-decoration: none;
            color: inherit;
            transition: 0.15s;
        }

        .feat-card:active {
            transform: scale(0.97);
        }

        .feat-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px;
            background: linear-gradient(transparent, rgba(0, 0, 0, .85));
            color: #fff;
        }

        .feat-title {
            font-family: 'Playfair Display', serif;
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .feat-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--sand);
            margin-top: 2px;
        }

        /* ICON MONOKROM */
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

        /* LIST MENU */
        /* LIST MENU (Card Style) */
        .menu-list {
            padding: 4px 20px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            /* Jarak antar card */
        }

        .menu-item {
            display: flex;
            gap: 14px;
            padding: 14px;
            background: #fff;
            /* Kasih background putih */
            border-radius: 16px;
            /* Corner radius */
            box-shadow: 0 2px 8px rgba(65, 45, 21, .04);
            /* Shadow halus */
            border: 1px solid #f5f2ea;
            text-decoration: none;
            color: inherit;
            transition: 0.15s;
        }

        .menu-item:active {
            transform: scale(0.98);
            /* Efek pencet */
            background: var(--sand-50);
        }

        .menu-img {
            width: 84px;
            height: 84px;
            border-radius: 14px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .menu-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .menu-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--espresso);
            margin-bottom: 2px;
            line-height: 1.25;
        }

        .menu-desc {
            font-size: 0.72rem;
            color: var(--text-muted);
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .menu-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
        }

        .menu-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--espresso);
        }

        .menu-add {
            width: 32px;
            height: 32px;
            background: var(--espresso);
            color: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(65, 45, 21, .2);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #bbb;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 12px;
            display: block;
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
        <div class="brand-name">Menu</div>
        <div class="brand-tagline">Pilih menu favorit kamu</div>
        <div class="table-badge">
            <i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($table_nama) ?>
        </div>
    </div>

    <!-- SEARCH -->
    <div class="search-wrap">
        <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" id="search-input" placeholder="Cari menu..." autocomplete="off">
        </div>
    </div>

    <!-- KATEGORI -->
    <div class="cat-scroll" id="category-scroll">
        <button class="cat-pill active" data-cat="all">Semua</button>
        <?php foreach ($categories as $c): ?>
            <button class="cat-pill" data-cat="<?= (int)$c['id'] ?>">
                <?= htmlspecialchars($c['nama']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- FEATURED MINI -->
    <?php if (!empty($featured)): ?>
        <div class="featured-mini">
            <?php foreach ($featured as $i => $f):
                $harga = harga_termurah($f['variants']);
                $bg = $i === 0 ? 'bg-dark' : 'bg-sand';
            ?>
                <a href="/tokopi/customer/product.php?id=<?= (int)$f['product_id'] ?>" class="feat-card">
                    <div class="product-icon <?= $bg ?>"><i class="bi bi-cup-hot-fill"></i></div>
                    <div class="feat-content">
                        <div class="feat-title"><?= htmlspecialchars($f['product_nama']) ?></div>
                        <div class="feat-price"><?= $harga > 0 ? harga_fmt($harga) : '—' ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- MENU LIST -->
    <div class="menu-list" id="menu-list">
        <?php if (empty($regular_products)): ?>
            <div class="empty-state">
                <i class="bi bi-cup-hot"></i>
                <p>Belum ada menu lain.</p>
            </div>
        <?php else: ?>
            <?php foreach ($regular_products as $index => $p):
                $harga = harga_termurah($p['variants']);
                $bg_class = $index % 3 === 0 ? 'bg-cream' : ($index % 3 === 1 ? 'bg-sand' : 'bg-dark');
            ?>
                <a href="/tokopi/customer/product.php?id=<?= (int)$p['product_id'] ?>"
                    class="menu-item"
                    data-cat="<?= (int)$p['category_id'] ?>"
                    data-name="<?= htmlspecialchars(strtolower($p['product_nama'])) ?>">
                    <div class="menu-img">
                        <div class="product-icon <?= $bg_class ?>"><i class="bi bi-cup-hot"></i></div>
                    </div>
                    <div class="menu-info">
                        <div>
                            <div class="menu-name"><?= htmlspecialchars($p['product_nama']) ?></div>
                            <div class="menu-desc">
                                <?= htmlspecialchars($p['description'] ?: 'Menu pilihan kami') ?>
                            </div>
                        </div>
                        <div class="menu-foot">
                            <span class="menu-price"><?= harga_fmt($harga) ?></span>
                            <span class="menu-add">+</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- CART FLOAT -->
    <a href="/tokopi/customer/cart.php" class="cart-float">
        <i class="bi bi-bag"></i>
        <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
    </a>

    <!-- BOTTOM NAV -->
    <div class="bottom-nav">
        <a href="/tokopi/customer/home.php" class="nav-item">
            <i class="bi bi-house-door"></i>Home
        </a>
        <a href="/tokopi/customer/menu.php" class="nav-item active">
            <i class="bi bi-cup-hot"></i>Menu
        </a>
        <a href="/tokopi/customer/orders.php" class="nav-item">
            <i class="bi bi-receipt"></i>Order
        </a>
        <a href="/tokopi/customer/profile.php" class="nav-item">
            <i class="bi bi-person"></i>Profile
        </a>
    </div>

    <script>
        // Filter kategori & search
        const searchInput = document.getElementById('search-input');
        const catPills = document.querySelectorAll('.cat-pill');
        const menuItems = document.querySelectorAll('.menu-item');

        let activeCat = 'all';
        let searchQuery = '';

        function applyFilter() {
            menuItems.forEach(item => {
                const catMatch = activeCat === 'all' || item.dataset.cat === activeCat;
                const searchMatch = !searchQuery || item.dataset.name.includes(searchQuery);
                item.style.display = (catMatch && searchMatch) ? '' : 'none';
            });

            const visibleCount = [...menuItems].filter(i => i.style.display !== 'none').length;
            let empty = document.querySelector('.menu-list .empty-state.dynamic');
            if (visibleCount === 0 && menuItems.length > 0) {
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'empty-state dynamic';
                    empty.innerHTML = '<i class="bi bi-search"></i><p>Tidak ada menu yang cocok.</p>';
                    document.getElementById('menu-list').appendChild(empty);
                }
                empty.style.display = '';
            } else if (empty) {
                empty.style.display = 'none';
            }
        }

        catPills.forEach(pill => {
            pill.addEventListener('click', () => {
                catPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                activeCat = pill.dataset.cat;
                applyFilter();
            });
        });

        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.trim().toLowerCase();
            applyFilter();
        });

        // Cart badge
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