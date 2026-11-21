<?php
require_once __DIR__ . '/../config/bootstrap.php';

$has_table   = !empty($_SESSION['customer_table_id']);
$has_account = !empty($_SESSION['customer_account_id']);

// Blokir redirect DIHAPUS. User bebas lihat menu.

$outlet_id   = (int)($_SESSION['customer_outlet_id'] ?? 0);
$table_nama  = $_SESSION['customer_table_nama'] ?? 'Belum Scan Meja';
$outlet_nama = $_SESSION['customer_outlet_nama'] ?? 'Tokopi';

// FALLBACK TENANT ID (Penting kalau gak punya meja)
if ($outlet_id > 0) {
    $stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
    $stmt->execute([$outlet_id]);
    $tenant_id = (int)$stmt->fetchColumn();
} else {
    $stmt = $pdo->query("SELECT id FROM tenants ORDER BY id LIMIT 1");
    $tenant_id = (int)$stmt->fetchColumn();
}

// Kategori
$stmt = $pdo->prepare("SELECT id, nama FROM categories WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, nama");
$stmt->execute([$tenant_id]);
$categories = $stmt->fetchAll();

// Semua varian
$stmt = $pdo->prepare("
    SELECT v.id AS variant_id, v.nama AS variant_nama, v.harga_jual,
           p.id AS product_id, p.nama AS product_nama, p.description, p.category_id, p.image_path
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
            'image_path'   => $v['image_path'],
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
// Opsi menu & stok habis (per outlet meja)
$option_groups = menu_option_groups($pdo, array_keys($products_map));
$soldout = $has_table ? menu_soldout_variants($pdo, $outlet_id, array_column($all_variants, 'variant_id')) : [];
foreach ($products_map as $pid => &$prod) {
    $prod['has_options'] = !empty($option_groups[$pid]);
    $prod['available'] = array_values(array_filter($prod['variants'], function ($v) use ($soldout) { return empty($soldout[(int)$v['variant_id']]); }));
    $prod['soldout'] = $has_table && !$prod['available'];
}
unset($prod);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Menu · Tokopi</title>
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
            <a href="/tokopi/customer/home.php" class="cx-brand" style="color:#fff;text-decoration:none;">Tokopi</a>
            <?php if ($has_table): ?>
                <span class="cx-table"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> <?= htmlspecialchars($table_nama) ?></span>
            <?php else: ?>
                <a href="/tokopi/customer/t.php" class="cx-table is-off"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Scan meja</a>
            <?php endif; ?>
        </div>
        <h1 class="cx-title" style="margin-top:12px;">Menu</h1>
    </header>

    <?php if (!$has_table): ?>
        <div class="cx-notice" role="status">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <span>Lihat-lihat dulu boleh. Untuk memesan, scan QR di meja.</span>
            <a href="/tokopi/customer/t.php">Cara scan</a>
        </div>
    <?php endif; ?>

    <!-- SEARCH -->
    <div class="search-wrap">
        <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="search" id="search-input" placeholder="Cari menu…" autocomplete="off" aria-label="Cari menu">
        </div>
    </div>

    <!-- KATEGORI -->
    <div class="cat-scroll" id="category-scroll" role="toolbar" aria-label="Kategori">
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
                $link = '/tokopi/customer/product.php?id=' . (int)$f['product_id'];
            ?>
                <a href="<?= $link ?>" class="feat-card <?= !empty($products_map[(int)$f['product_id']]['soldout']) ? 'is-soldout' : '' ?>">
                    <?php if (!empty($products_map[(int)$f['product_id']]['soldout'])): ?><span class="badge-out">Habis</span><?php endif; ?>
                    <?= product_img($f['image_path'], $f['product_nama']) ?>
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
                $link = '/tokopi/customer/product.php?id=' . (int)$p['product_id'];
                $single = (count($p['variants']) === 1 && !$p['has_options'] && !$p['soldout']) ? $p['variants'][0] : null;
            ?>
                <div class="menu-item <?= $p['soldout'] ? 'is-soldout' : '' ?>"
                    data-cat="<?= (int)$p['category_id'] ?>"
                    data-name="<?= htmlspecialchars(strtolower($p['product_nama'])) ?>">
                    <a href="<?= $link ?>" class="menu-img" aria-hidden="true" tabindex="-1">
                        <?= product_img($p['image_path'], $p['product_nama']) ?>
                    </a>
                    <div class="menu-info">
                        <div>
                            <a href="<?= $link ?>" class="menu-name stretched" style="text-decoration:none;display:block;"><?= htmlspecialchars($p['product_nama']) ?></a>
                            <div class="menu-desc">
                                <?= htmlspecialchars($p['description'] ?: 'Menu pilihan kami') ?>
                            </div>
                        </div>
                        <div class="menu-foot">
                            <span>
                                <span class="menu-price"><?= harga_fmt($harga) ?></span>
                                <?php if ($p['soldout']): ?><span class="menu-soldout d-block">Habis hari ini</span>
                                <?php elseif (count($p['variants']) > 1 || $p['has_options']): ?><span class="menu-variants-hint d-block"><?= count($p['variants']) > 1 ? count($p['variants']) . ' varian' : 'Bisa diatur' ?><?= count($p['variants']) > 1 && $p['has_options'] ? ' · bisa diatur' : '' ?></span><?php endif; ?>
                            </span>
                            <?php if ($has_table && $single): ?>
                                <button type="button" class="menu-add js-quick-add"
                                    data-variant-id="<?= (int)$single['variant_id'] ?>"
                                    data-product-id="<?= (int)$p['product_id'] ?>"
                                    data-product-nama="<?= htmlspecialchars($p['product_nama']) ?>"
                                    data-variant-nama="<?= htmlspecialchars($single['variant_nama']) ?>"
                                    data-harga="<?= (float)$single['harga_jual'] ?>"
                                    aria-label="Tambah <?= htmlspecialchars($p['product_nama']) ?> ke keranjang">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                </button>
                            <?php else: ?>
                                <a href="<?= $link ?>" class="menu-add" aria-label="Pilih varian <?= htmlspecialchars($p['product_nama']) ?>" style="text-decoration:none;">
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- CART FLOAT -->
    <a href="<?= $has_table ? '/tokopi/customer/cart.php' : '/tokopi/customer/t.php' ?>" class="cart-float" aria-label="Keranjang">
        <i class="bi bi-bag"></i>
        <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
    </a>

    <!-- BOTTOM NAV -->
    <nav class="bottom-nav" aria-label="Navigasi">
        <a href="/tokopi/customer/home.php" class="nav-item">
            <i class="bi bi-house-door"></i>Home
        </a>
        <a href="/tokopi/customer/menu.php" class="nav-item active" aria-current="page">
            <i class="bi bi-cup-hot"></i>Menu
        </a>
        <a href="/tokopi/customer/orders.php" class="nav-item">
            <i class="bi bi-receipt"></i>Order
        </a>
        <a href="/tokopi/customer/profile.php" class="nav-item">
            <i class="bi bi-person"></i>Profile
        </a>
    </nav>

    <script src="/tokopi/assets/js/cart.js?v=20261121d"></script>
    <div class="tk-toast" id="tkToast" role="status" aria-live="polite"></div>
    <script src="/tokopi/assets/js/ui.js?v=20261121b"></script>
    <script>
    // Tambah cepat untuk produk 1 varian (format sama dengan product.php)
    document.querySelectorAll('.js-quick-add').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = btn.dataset, cart = {};
            try { cart = JSON.parse(localStorage.getItem('tokopi_cart') || '{}'); } catch (e) {}
            var key = 'v' + d.variantId;
            if (cart[key]) cart[key].qty += 1;
            else cart[key] = { variant_id: +d.variantId, product_id: +d.productId, product_nama: d.productNama, variant_nama: d.variantNama, harga: +d.harga, qty: 1, note: '' };
            localStorage.setItem('tokopi_cart', JSON.stringify(cart));
            if (window.TokopiCart) { TokopiCart.cart = TokopiCart.load(); TokopiCart.emit(); }
            var badge = document.getElementById('cart-badge');
            if (badge) { badge.classList.remove('bump'); void badge.offsetWidth; badge.classList.add('bump'); }
            btn.classList.add('is-added'); btn.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i>';
            setTimeout(function () { btn.classList.remove('is-added'); btn.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>'; }, 900);
            var t = document.getElementById('tkToast');
            t.innerHTML = '<i class="bi bi-check-circle-fill" aria-hidden="true"></i> ' + d.productNama + ' masuk keranjang';
            t.classList.add('show'); clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove('show'); }, 1600);
        });
    });
    </script>
</body>

</html>