<?php
// company/menu.php — Menu publik (read-only)
require_once __DIR__ . '/../config/bootstrap.php';

// ---------- Ambil tenant pertama (single-tenant company profile) ----------
$tenant_id = 0;
try {
    $stmt = $pdo->query("SELECT id FROM tenants ORDER BY id LIMIT 1");
    $tenant_id = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    error_log('[company/menu] tenant: ' . $e->getMessage());
}

// ---------- Ambil kategori + produk + varian ----------
$menu = [];
if ($tenant_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                c.id AS category_id, c.nama AS category_nama, c.sort_order,
                p.id AS product_id, p.nama AS product_nama, p.description, p.image_path,
                v.id AS variant_id, v.nama AS variant_nama,
                v.harga_jual, v.temperature, v.size
            FROM categories c
            JOIN products p ON p.category_id = c.id
            JOIN product_variants v ON v.product_id = p.id
            WHERE c.tenant_id = ?
              AND c.is_active = 1
              AND p.is_active = 1
              AND v.is_active = 1
            ORDER BY c.sort_order, c.nama, p.nama, v.nama
        ");
        $stmt->execute([$tenant_id]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cid = (int)$row['category_id'];
            $pid = (int)$row['product_id'];

            if (!isset($menu[$cid])) {
                $menu[$cid] = [
                    'nama' => $row['category_nama'],
                    'products' => []
                ];
            }
            if (!isset($menu[$cid]['products'][$pid])) {
                $menu[$cid]['products'][$pid] = [
                    'nama' => $row['product_nama'],
                    'description' => $row['description'],
                    'image_path' => $row['image_path'],
                    'variants' => []
                ];
            }
            $menu[$cid]['products'][$pid]['variants'][] = [
                'nama' => $row['variant_nama'],
                'harga' => (float)$row['harga_jual'],
                'temperature' => $row['temperature'],
                'size' => $row['size'],
            ];
        }
    } catch (Throwable $e) {
        error_log('[company/menu] menu: ' . $e->getMessage());
    }
}

$page_title = 'Menu';
$page_desc  = 'Menu lengkap Tokopi: kopi, non-kopi, makanan, dan paket hemat, lengkap dengan harga per varian.';
$active_nav = 'menu';
require_once __DIR__ . '/../includes/header_public.php';
$total_items = 0;
foreach ($menu as $c) $total_items += count($c['products']);
?>

<section class="page-intro">
    <div class="wrap">
        <div class="kicker"><b><?= $total_items ?></b> item · <?= count($menu) ?> kategori</div>
        <h1>Menu</h1>
        <p class="lead">Harga di bawah adalah harga per varian. Untuk memesan, scan QR di meja saat kamu di cabang.</p>
    </div>
</section>

<?php if (empty($menu)): ?>
    <section class="section">
        <div class="wrap">
            <h2 class="h-lg">Menu sedang disiapkan.</h2>
            <p class="lead" style="margin-top:12px;">Cek lagi sebentar, atau langsung mampir ke cabang terdekat.</p>
        </div>
    </section>
<?php else: ?>
    <nav class="cat-nav" aria-label="Kategori menu">
        <div class="wrap" id="catNav">
            <?php foreach ($menu as $cid => $cat): ?>
                <a href="#cat-<?= (int)$cid ?>" data-target="cat-<?= (int)$cid ?>"><?= e($cat['nama']) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>

    <section class="section-tight">
        <div class="wrap" style="max-width:920px;">
            <?php foreach ($menu as $cid => $cat): ?>
                <section class="menu-cat" id="cat-<?= (int)$cid ?>" aria-labelledby="h-cat-<?= (int)$cid ?>">
                    <div class="menu-cat-head">
                        <h2 id="h-cat-<?= (int)$cid ?>" style="font-size:1.8rem;font-style:italic;"><?= e($cat['nama']) ?></h2>
                        <span><?= count($cat['products']) ?> item</span>
                    </div>
                    <?php foreach ($cat['products'] as $prod):
                        $prices = array_column($prod['variants'], 'harga');
                        $min = min($prices);
                    ?>
                        <div class="menu-row">
                            <?= product_img($prod['image_path'], $prod['nama']) ?>
                            <div>
                                <h3 style="font-family:var(--p-sans);font-size:17px;font-weight:700;letter-spacing:0;line-height:1.3;"><?= e($prod['nama']) ?></h3>
                                <?php if (!empty($prod['description'])): ?><p><?= e($prod['description']) ?></p><?php endif; ?>
                                <?php if (count($prod['variants']) > 1): ?>
                                    <div class="vars">
                                        <?php foreach ($prod['variants'] as $v): ?>
                                            <span>
                                                <?php if ($v['temperature'] === 'ICED'): ?><i class="bi bi-snow" aria-label="dingin"></i><?php elseif ($v['temperature'] === 'HOT'): ?><i class="bi bi-cup-hot" aria-label="panas"></i><?php endif; ?>
                                                <?= e($v['nama']) ?> <b>Rp<?= number_format($v['harga'], 0, ',', '.') ?></b>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="price">Rp<?= number_format($min, 0, ',', '.') ?><?php if (count($prod['variants']) > 1): ?><small>mulai dari</small><?php endif; ?></div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="section night">
    <div class="wrap split even" style="align-items:center;">
        <div>
            <div class="kicker"><b>QR</b> Pesan dari meja</div>
            <h2 class="h-xl">Sudah di cabang? Scan QR di mejamu.</h2>
        </div>
        <div>
            <p class="lead">Pesanan langsung masuk ke barista dengan nomor meja. Bayar QRIS atau tunai di kasir.</p>
            <div class="hero-actions" style="margin-top:24px;">
                <a href="<?= $COMPANY_BASE ?>/index.php#cara-pesan" class="btn-p on-dark">Lihat caranya</a>
                <a href="<?= $COMPANY_BASE ?>/contact.php" class="btn-p on-dark ghost">Cari cabang</a>
            </div>
        </div>
    </div>
</section>

<script>
// Tandai kategori yang sedang terlihat
(function () {
    var links = document.querySelectorAll('#catNav a');
    if (!links.length || !('IntersectionObserver' in window)) return;
    var map = {}; links.forEach(function (a) { map[a.dataset.target] = a; });
    var io = new IntersectionObserver(function (es) {
        es.forEach(function (e) {
            if (!e.isIntersecting) return;
            links.forEach(function (a) { a.classList.remove('active'); a.removeAttribute('aria-current'); });
            var a = map[e.target.id]; if (!a) return;
            a.classList.add('active'); a.setAttribute('aria-current', 'true');
            a.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });
    }, { rootMargin: '-45% 0px -50% 0px' });
    document.querySelectorAll('.menu-cat').forEach(function (s) { io.observe(s); });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
