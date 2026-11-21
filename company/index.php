<?php
// company/index.php — Beranda
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Tokopi';
$page_desc  = 'Kedai kopi lokal di Jakarta. Lihat menu & harga, temukan cabang terdekat, dan pesan langsung dari meja lewat QR.';
$active_nav = 'home';
require_once __DIR__ . '/../includes/header_public.php';

$tenant_id = $PUBLIC_TENANT_ID;

// Menu unggulan: produk terlaris 30 hari (fallback: terbaru)
$featured = [];
$stats = ['products' => 0, 'members' => 0, 'min_price' => 0];
if ($tenant_id > 0) {
    try {
        $st = $pdo->prepare("
            SELECT p.id, p.nama, p.description, p.image_path, c.nama AS kategori,
                   MIN(pv.harga_jual) AS harga_min,
                   COALESCE((SELECT SUM(oi.qty) FROM order_items oi
                             JOIN product_variants v2 ON v2.id = oi.variant_id
                             JOIN orders o ON o.id = oi.order_id
                             WHERE v2.product_id = p.id AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                               AND o.status IN ('PAID','PREPARING','READY','COMPLETED')), 0) AS sold
            FROM products p
            JOIN categories c ON c.id = p.category_id
            JOIN product_variants pv ON pv.product_id = p.id AND pv.is_active = 1
            WHERE p.tenant_id = ? AND p.is_active = 1 AND c.slug <> 'promo-combo'
            GROUP BY p.id, p.nama, p.description, p.image_path, c.nama
            ORDER BY sold DESC, p.id DESC
            LIMIT 6
        ");
        $st->execute([$tenant_id]);
        $featured = $st->fetchAll(PDO::FETCH_ASSOC);

        $st = $pdo->prepare("SELECT COUNT(*) FROM products WHERE tenant_id = ? AND is_active = 1");
        $st->execute([$tenant_id]);
        $stats['products'] = (int)$st->fetchColumn();

        $st = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE tenant_id = ?");
        $st->execute([$tenant_id]);
        $stats['members'] = (int)$st->fetchColumn();

        $st = $pdo->prepare("SELECT MIN(pv.harga_jual) FROM product_variants pv JOIN products p ON p.id = pv.product_id WHERE p.tenant_id = ? AND p.is_active = 1 AND pv.is_active = 1");
        $st->execute([$tenant_id]);
        $stats['min_price'] = (float)$st->fetchColumn();
    } catch (Throwable $e) {
        error_log('[company/index] ' . $e->getMessage());
    }
}

// Testimoni (teks saja — tanpa foto wajah)
$testimonials = [];
try {
    $testimonials = $pdo->query("SELECT nama, teks, rating, created_at FROM testimonials WHERE is_visible = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[company/index] testimonials: ' . $e->getMessage());
}

$board = array_slice($featured, 0, 5);
function rp_short($n) { return number_format((float)$n / 1000, 0, ',', '.') . 'k'; }
?>

<!-- HERO -->
<section class="hero-p">
    <div class="wrap hero-grid">
        <div>
            <div class="hero-meta">
                <span class="open-dot <?= $IS_OPEN_NOW ? '' : 'closed' ?>"><?= $IS_OPEN_NOW ? 'Buka sekarang' : 'Sedang tutup' ?> · <?= e($PUBLIC_HOURS['label']) ?></span>
                <span><i class="bi bi-geo-alt" aria-hidden="true"></i> <?= count($PUBLIC_OUTLETS) ?: 'Beberapa' ?> cabang di Jakarta</span>
            </div>
            <h1 class="display">Kopi lokal,<br>pesan <em class="accent">dari meja.</em></h1>
            <p class="lead">Duduk dulu, scan QR di meja, pilih menu, bayar pakai QRIS atau di kasir. Pesananmu langsung masuk ke barista, tanpa antre di depan.</p>
            <div class="hero-actions">
                <a href="<?= $COMPANY_BASE ?>/menu.php" class="btn-p">Lihat menu &amp; harga <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <a href="#cara-pesan" class="btn-p ghost">Cara pesan via QR</a>
            </div>
        </div>

        <?php if ($board): ?>
        <aside class="board" aria-labelledby="boardTitle">
            <div class="board-head">
                <h2 id="boardTitle">Favorit bulan ini</h2>
                <span><?= date('d.m.Y') ?></span>
            </div>
            <ul>
                <?php foreach ($board as $p): ?>
                    <li>
                        <span class="name"><?= e($p['nama']) ?></span>
                        <span class="dots" aria-hidden="true"></span>
                        <span class="price"><span class="visually-hidden">mulai </span><?= rp_short($p['harga_min']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="board-foot">
                <span>Harga dalam ribu rupiah</span>
                <a href="<?= $COMPANY_BASE ?>/menu.php">Menu lengkap →</a>
            </div>
        </aside>
        <?php endif; ?>
    </div>
</section>

<!-- FAKTA -->
<section aria-label="Tokopi dalam angka">
    <div class="wrap facts">
        <div class="fact"><strong><?= count($PUBLIC_OUTLETS) ?></strong><span>cabang di Jakarta</span></div>
        <div class="fact"><strong><?= $stats['products'] ?></strong><span>menu kopi, non-kopi &amp; makanan</span></div>
        <div class="fact"><strong class="mono" style="font-family:var(--p-display)">Rp<?= rp_short($stats['min_price']) ?></strong><span>harga mulai dari</span></div>
        <div class="fact"><strong>1 poin</strong><span>setiap belanja Rp10.000</span></div>
    </div>
</section>

<!-- MENU UNGGULAN -->
<?php if ($featured): ?>
<section class="section">
    <div class="wrap split">
        <div class="sticky-col">
            <div class="kicker"><b>01</b> Menu</div>
            <h2 class="h-xl">Yang paling sering dipesan.</h2>
            <p class="lead" style="margin-top:18px;">Diurutkan dari pesanan 30 hari terakhir di semua cabang.</p>
            <p style="margin-top:28px;"><a class="link-arrow" href="<?= $COMPANY_BASE ?>/menu.php">Semua menu <i class="bi bi-arrow-right" aria-hidden="true"></i></a></p>
        </div>
        <div>
            <?php foreach ($featured as $p): ?>
                <div class="menu-row">
                    <?= product_img($p['image_path'], $p['nama']) ?>
                    <div>
                        <h3 style="font-family:var(--p-sans);font-size:17px;font-weight:700;letter-spacing:0;line-height:1.3;"><?= e($p['nama']) ?></h3>
                        <p><?= e($p['description'] ?: $p['kategori']) ?></p>
                    </div>
                    <div class="price">Rp<?= number_format((float)$p['harga_min'], 0, ',', '.') ?><small>mulai dari</small></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CARA PESAN (satu-satunya section gelap) -->
<section class="section night" id="cara-pesan">
    <div class="wrap">
        <div class="kicker"><b>02</b> Pesan dari meja</div>
        <h2 class="h-xl" style="max-width:16ch;">Tidak perlu antre di kasir.</h2>
        <p class="lead" style="margin-top:18px;">Setiap meja punya QR sendiri. Pesananmu tercatat dengan nomor meja, jadi barista tahu harus mengantar ke mana.</p>
        <div class="steps-p">
            <div class="step-p">
                <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                <h3>Scan QR di meja</h3>
                <p>Buka kamera HP, arahkan ke QR. Menu langsung terbuka, tanpa install aplikasi.</p>
            </div>
            <div class="step-p">
                <i class="bi bi-cup-hot" aria-hidden="true"></i>
                <h3>Pilih &amp; atur pesanan</h3>
                <p>Pilih ukuran, tambah catatan seperti <i>less ice</i> atau tanpa gula, lalu masukkan ke keranjang.</p>
            </div>
            <div class="step-p">
                <i class="bi bi-qr-code" aria-hidden="true"></i>
                <h3>Bayar, lalu tunggu</h3>
                <p>Bayar QRIS dari e-wallet atau m-banking, atau bayar tunai di kasir. Status pesanan bisa dipantau di HP.</p>
            </div>
        </div>
        <div class="hero-actions" style="margin-top:48px;">
            <a href="<?= $APP_BASE ?>/customer/home.php" class="btn-p on-dark">Coba pesan sekarang <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            <a href="<?= $COMPANY_BASE ?>/faq.php" class="btn-p on-dark ghost">Pertanyaan umum</a>
        </div>
    </div>
</section>

<!-- CABANG -->
<?php if ($PUBLIC_OUTLETS): ?>
<section class="section">
    <div class="wrap">
        <div class="kicker"><b>03</b> Cabang</div>
        <h2 class="h-xl" style="margin-bottom:40px;">Mampir ke yang terdekat.</h2>
        <div class="branches">
            <?php foreach ($PUBLIC_OUTLETS as $o): ?>
                <article class="branch">
                    <span class="code"><?= e($o['code']) ?></span>
                    <h3><?= e(outlet_short($o['nama'])) ?></h3>
                    <p><?= e($o['alamat'] ?: 'Jakarta') ?></p>
                    <ul>
                        <li><i class="bi bi-clock" aria-hidden="true"></i> <?= e($PUBLIC_HOURS['label']) ?></li>
                        <?php if (!empty($o['no_hp'])): ?><li><i class="bi bi-telephone" aria-hidden="true"></i> <?= e($o['no_hp']) ?></li><?php endif; ?>
                    </ul>
                    <a class="link-arrow" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&amp;query=<?= urlencode(outlet_map_query($o)) ?>">Buka di Maps <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- KATA PELANGGAN -->
<?php if ($testimonials): ?>
<section class="section" style="background:var(--p-paper-2);">
    <div class="wrap">
        <div class="kicker"><b>04</b> Kata pelanggan</div>
        <h2 class="h-xl" style="margin-bottom:48px;">Ditulis oleh yang sudah mampir.</h2>
        <div class="quotes">
            <?php foreach ($testimonials as $t): ?>
                <figure class="quote reveal">
                    <blockquote><?= e($t['teks']) ?></blockquote>
                    <figcaption>
                        <b><?= e($t['nama']) ?></b>
                        <span class="stars" aria-label="Rating <?= (int)$t['rating'] ?> dari 5"><?= str_repeat('★', (int)$t['rating']) ?></span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- MEMBER -->
<section class="section">
    <div class="wrap split even" style="align-items:center;">
        <div>
            <div class="kicker"><b>05</b> Member</div>
            <h2 class="h-xl">Ngopi rutin, <em class="accent">dapat poin.</em></h2>
            <p class="lead" style="margin-top:18px;">Daftar gratis pakai nomor HP. Setiap Rp10.000 jadi 1 poin, yang bisa ditukar voucher diskon atau minuman gratis.</p>
            <div class="hero-actions" style="margin-top:28px;">
                <a href="<?= $APP_BASE ?>/customer/register.php" class="btn-p">Daftar member</a>
                <a href="<?= $APP_BASE ?>/customer/login.php" class="btn-p ghost">Sudah punya akun</a>
            </div>
        </div>
        <div class="tiers" role="list">
            <div class="tier" role="listitem" style="--c:#A9713F;"><span class="badge-t">Bronze</span><h3>Mulai</h3><p>Langsung dapat poin dari pesanan pertama.</p></div>
            <div class="tier" role="listitem" style="--c:#9AA0A6;"><span class="badge-t">Silver</span><h3>Rutin</h3><p>Pilihan reward lebih banyak.</p></div>
            <div class="tier" role="listitem" style="--c:#C9A227;"><span class="badge-t">Gold</span><h3>Langganan</h3><p>Benefit tertinggi untuk pelanggan setia.</p></div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
