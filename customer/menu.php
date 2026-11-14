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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Menu · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link href="/tokopi/assets/css/app.css" rel="stylesheet">
</head>

<body class="customer-layout">

    <!-- HEADER -->
    <div class="customer-app-header" style="padding-bottom: 20px;">
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

    <script src="/tokopi/assets/js/cart.js"></script>
    <script src="/tokopi/assets/js/ui.js"></script>
</body>

</html>