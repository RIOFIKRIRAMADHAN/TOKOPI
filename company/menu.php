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
                p.id AS product_id, p.nama AS product_nama,
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

$page_title = 'Menu — Tokopi';
$active_nav = 'menu';
require_once __DIR__ . '/../includes/header_public.php';
?>

<style>
    .page-hero {
        background: linear-gradient(135deg, var(--espresso) 0%, var(--espresso-dark) 100%);
        color: #fff;
        padding: 5rem 0 4rem;
        position: relative; overflow: hidden;
    }
    .page-hero::before {
        content: ''; position: absolute; inset: 0;
        background: radial-gradient(circle at 80% 20%, rgba(225,220,201,.08) 0%, transparent 55%);
        pointer-events: none;
    }
    .page-hero > .container { position: relative; z-index: 1; }
    .page-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2rem, 4vw, 3.25rem);
        font-weight: 700; line-height: 1.15; letter-spacing: -0.02em;
    }

    .menu-nav {
        display: flex;
        gap: .5rem;
        overflow-x: auto;
        padding-bottom: .75rem;
        margin-bottom: 3rem;
        scrollbar-width: none;
    }
    .menu-nav::-webkit-scrollbar { display: none; }
    .menu-nav a {
        white-space: nowrap;
        padding: .5rem 1.25rem;
        background: #fff;
        border: 1px solid rgba(65,45,21,.12);
        border-radius: 999px;
        color: var(--espresso);
        text-decoration: none;
        font-size: .875rem;
        font-weight: 600;
        transition: all .2s;
    }
    .menu-nav a:hover { background: var(--espresso); color: #fff; border-color: var(--espresso); }

    .category-section { margin-bottom: 4rem; scroll-margin-top: 100px; }
    .category-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .category-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--espresso);
        margin: 0;
        white-space: nowrap;
    }
    .category-line { flex: 1; height: 1px; background: rgba(65,45,21,.15); }

    .menu-item {
        background: #fff;
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid rgba(65,45,21,.08);
        height: 100%;
        transition: all .25s ease;
    }
    .menu-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(65,45,21,.1);
    }
    .product-name {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 1.15rem;
        color: var(--espresso);
        margin-bottom: .75rem;
    }
    .variant-list { list-style: none; padding: 0; margin: 0; }
    .variant-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .5rem 0;
        border-bottom: 1px dashed rgba(65,45,21,.08);
        font-size: .9rem;
    }
    .variant-list li:last-child { border-bottom: none; }
    .variant-name {
        color: var(--text-muted);
        display: flex;
        align-items: center;
        gap: .35rem;
    }
    .variant-price {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        color: var(--espresso);
        white-space: nowrap;
    }

    .empty-state {
        text-align: center;
        padding: 5rem 1rem;
        color: var(--text-muted);
    }
    .empty-state i { font-size: 4rem; opacity: .3; margin-bottom: 1rem; }

    @media (max-width: 576px) {
        .page-hero { padding: 3.5rem 0 3rem; }
        .category-title { font-size: 1.4rem; }
        .menu-item { padding: 1.25rem; }
        .product-name { font-size: 1.05rem; }
    }
</style>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="container text-center">
    <span class="eyebrow" style="color:var(--accent);">Menu Kami</span>
    <h1 class="page-title mt-3 mb-3">Setiap Cangkir, Pilihan Hati</h1>
    <p class="lead mx-auto" style="max-width:38rem;opacity:.9;">
      Dari espresso klasik hingga signature latte — pilih favoritmu, atau biarkan barista kami merekomendasikan.
    </p>
  </div>
</section>

<!-- MENU -->
<section style="padding:4rem 0;">
  <div class="container">
    <?php if (empty($menu)): ?>
      <div class="empty-state">
        <i class="bi bi-cup-hot"></i>
        <h3 class="section-title mt-3 mb-2">Menu Segera Hadir</h3>
        <p class="mb-0">Kami sedang menyiapkan menu terbaru. Cek kembali sebentar lagi.</p>
      </div>
    <?php else: ?>

      <!-- Nav pills ke tiap kategori -->
      <div class="menu-nav">
        <?php foreach ($menu as $cid => $cat): ?>
          <a href="#cat-<?= (int)$cid ?>"><?= htmlspecialchars($cat['nama']) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- Sections -->
      <?php $i = 0; foreach ($menu as $cid => $cat): ?>
        <div class="category-section" id="cat-<?= (int)$cid ?>">
          <div class="category-header">
            <h2 class="category-title"><?= htmlspecialchars($cat['nama']) ?></h2>
            <div class="category-line"></div>
          </div>
          <div class="row g-4">
            <?php foreach ($cat['products'] as $pid => $prod): ?>
              <div class="col-md-6 col-lg-4 fade-in-up" style="transition-delay:<?= ($i++ % 3) * 80 ?>ms;">
                <div class="menu-item">
                  <h3 class="product-name"><?= htmlspecialchars($prod['nama']) ?></h3>
                  <ul class="variant-list">
                    <?php foreach ($prod['variants'] as $v): ?>
                      <li>
                        <span class="variant-name">
                          <?= htmlspecialchars($v['nama']) ?>
                          <?php if ($v['temperature'] === 'ICED'): ?>
                            <i class="bi bi-snow text-info"></i>
                          <?php elseif ($v['temperature'] === 'HOT'): ?>
                            <i class="bi bi-fire text-danger"></i>
                          <?php endif; ?>
                        </span>
                        <span class="variant-price">
                          Rp <?= number_format($v['harga'], 0, ',', '.') ?>
                        </span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

    <?php endif; ?>
  </div>
</section>

<!-- CTA -->
<section style="padding:0 0 5rem;">
  <div class="container">
    <div class="cta-band fade-in-up">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h2 class="section-title display-6 mb-2" style="color:#fff;">Mau pesan dari meja?</h2>
          <p class="mb-0" style="color:var(--sand);opacity:.9;">Scan QR di meja, pilih menu, bayar — pesanan langsung masuk dapur. Tanpa antre.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="<?= htmlspecialchars($COMPANY_BASE) ?>/contact.php" class="btn btn-light btn-lg px-4 fw-semibold text-dark">
            <i class="bi bi-geo-alt me-2"></i> Kunjungi Kami
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>