<?php
// company/index.php — Homepage Company Profile Tokopi
require_once __DIR__ . '/../config/bootstrap.php';

// ---------- Ambil Tenant ID ----------
$tenant_id = 0;
try {
    $stmt = $pdo->query("SELECT id FROM tenants ORDER BY id LIMIT 1");
    $tenant_id = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
    error_log('[company/index] tenant: ' . $e->getMessage());
}

// ---------- Testimonials ----------
$testimonials = [];
try {
    $stmt = $pdo->prepare("
        SELECT nama, teks, rating FROM testimonials
        WHERE is_visible = 1 ORDER BY created_at DESC LIMIT 6
    ");
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[company/index] testimonials: ' . $e->getMessage());
}


// ---------- Featured Products (DIJOIN DENGAN TENANT & VARIAN AKTIF) ----------
$featured = [];
if ($tenant_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.nama, MIN(pv.harga_jual) AS harga_min
            FROM products p
            JOIN product_variants pv ON pv.product_id = p.id
            WHERE p.tenant_id = ? AND p.is_active = 1 AND pv.is_active = 1
            GROUP BY p.id, p.nama
            ORDER BY p.id DESC LIMIT 4
        ");
        $stmt->execute([$tenant_id]);
        $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[company/index] featured: ' . $e->getMessage());
    }
}

// Fallback dummy jika produk kosong
if (empty($featured)) {
    $featured = [
        ['nama'=>'Espresso','harga_min'=>18000],
        ['nama'=>'Cappuccino','harga_min'=>25000],
        ['nama'=>'Caramel Macchiato','harga_min'=>32000],
        ['nama'=>'Cold Brew','harga_min'=>28000],
    ];
}

$page_title = 'Tokopi — Kopi Pilihan, Suasana Hangat';
$active_nav = 'home';
require_once __DIR__ . '/../includes/header_public.php';
?>

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="eyebrow">Specialty Coffee · Est. 2024</span>
        <h1 class="display-hero mt-3 mb-4">Setiap Cangkir,<br><span style="color:var(--color-sand);">Sebuah Cerita.</span></h1>
        <p class="lead mb-4" style="max-width:32rem;opacity:.9;">
          Kopi pilihan dari biji terbaik, diracik dengan tangan-tangan yang peduli. Suasana hangat, koneksi nyata, dalam setiap tegukan.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= $COMPANY_BASE ?>/menu.php" class="btn btn-light btn-lg px-4 fw-semibold text-dark">
            <i class="bi bi-cup-hot me-2"></i> Lihat Menu
          </a>
          <a href="<?= $COMPANY_BASE ?>/about.php" class="btn btn-outline-light btn-lg px-4 fw-semibold">Cerita Kami</a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block">
        <div class="hero-visual"><i class="bi bi-cup-hot-fill"></i></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ ABOUT PREVIEW ============ -->
<section style="padding:5rem 0;">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5 fade-in-up">
        <div class="about-visual"><i class="bi bi-heart-fill"></i></div>
      </div>
      <div class="col-lg-7 fade-in-up">
        <span class="eyebrow">Tentang Kami</span>
        <h2 class="section-title display-6 mt-2 mb-3">Lebih dari sekadar kedai kopi.</h2>
        <p class="text-secondary lh-lg mb-3">
          Tokopi lahir dari kecintaan sederhana pada kopi dan obrolan panjang. Kami percaya setiap cangkir bisa menjadi alasan untuk berhenti, bernapas, dan tersenyum.
        </p>
        <p class="text-secondary lh-lg mb-4">
          Biji pilihan dari petani lokal, di-roast dengan presisi, dan diseduh dengan sepenuh hati — itulah janji kami di setiap cabang.
        </p>
        <a href="<?= $COMPANY_BASE ?>/about.php" class="btn btn-espresso">Pelajari Lebih Lanjut <i class="bi bi-arrow-right ms-2"></i></a>
      </div>
    </div>
  </div>
</section>

<!-- ============ FEATURED MENU ============ -->
<section style="background:var(--color-bg);padding:5rem 0;">
  <div class="container">
    <div class="text-center mb-5 fade-in-up">
      <span class="eyebrow">Menu Favorit</span>
      <h2 class="section-title display-6 mt-2">Yang Paling Dicintai</h2>
    </div>
    <div class="row g-4">
      <?php foreach ($featured as $i => $p): ?>
      <div class="col-md-6 col-lg-3 fade-in-up" style="transition-delay:<?= $i * 100 ?>ms;">
        <div class="menu-card h-100 d-flex flex-column">
          <div class="menu-card-img"><i class="bi bi-cup-hot"></i></div>
          <div class="p-4 flex-grow-1 d-flex flex-column">
            <h5 class="fw-bold mb-1" style="color:var(--color-espresso);"><?= e($p['nama']) ?></h5>
            <p class="text-secondary small mb-3">Mulai dari</p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <span class="menu-card-price">Rp <?= number_format((int)($p['harga_min'] ?? 0), 0, ',', '.') ?></span>
              <a href="<?= $COMPANY_BASE ?>/menu.php" class="btn btn-sm btn-outline-espresso rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;"><i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-5">
      <a href="<?= $COMPANY_BASE ?>/menu.php" class="btn btn-espresso btn-lg px-4">Lihat Menu Lengkap <i class="bi bi-arrow-right ms-2"></i></a>
    </div>
  </div>
</section>

<!-- ============ TESTIMONI ============ -->
<section style="padding:5rem 0;">
  <div class="container">
    <div class="text-center mb-5 fade-in-up">
      <span class="eyebrow">Testimoni</span>
      <h2 class="section-title display-6 mt-2">Kata Mereka</h2>
      <p class="text-secondary mt-2">Cerita nyata dari pelanggan setia Tokopi.</p>
    </div>

    <?php $chunks = array_chunk($testimonials, 3); $slide_count = count($chunks); ?>

    <div id="testiCarousel" class="carousel slide fade-in-up" data-bs-ride="carousel" data-bs-interval="6000">
      <div class="carousel-inner">
        <?php foreach ($chunks as $ci => $chunk): ?>
        <div class="carousel-item <?= $ci === 0 ? 'active' : '' ?>">
          <div class="row g-4 px-md-4">
            <?php foreach ($chunk as $t): ?>
            <div class="col-md-4">
              <div class="testimonial-card">
                <div class="d-flex align-items-center gap-3 mb-3">
                  <div class="avatar-circle"><?= strtoupper(mb_substr($t['nama'], 0, 1)) ?></div>
                  <div>
                    <h6 class="mb-1 fw-bold" style="color:var(--color-espresso);"><?= e($t['nama']) ?></h6>
                    <div class="text-warning small">
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="bi bi-star<?= $s <= (int)$t['rating'] ? '-fill' : '' ?>"></i>
                      <?php endfor; ?>
                    </div>
                  </div>
                </div>
                <p class="mb-0 text-secondary lh-lg" style="font-style:italic;">&ldquo;<?= e($t['teks']) ?>&rdquo;</p>
              </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($chunk) < 3): for ($pad = 0; $pad < 3 - count($chunk); $pad++): ?>
              <div class="col-md-4 d-none d-md-block"></div>
            <?php endfor; endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($slide_count > 1): ?>
      <div class="carousel-indicators">
        <?php for ($i = 0; $i < $slide_count; $i++): ?>
        <button type="button" data-bs-target="#testiCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section style="padding:0 0 5rem;">
  <div class="container">
    <div class="cta-band fade-in-up">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h2 class="section-title display-6 mb-2" style="color:#fff;">Ngopi bareng kami hari ini.</h2>
          <p class="mb-0" style="color:var(--color-sand);opacity:.9;">Kunjungi cabang terdekat, atau pesan langsung dari meja lewat QR — tanpa antre, tanpa ribet.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="<?= $COMPANY_BASE ?>/contact.php" class="btn btn-light btn-lg px-4 fw-semibold text-dark">
            <i class="bi bi-geo-alt me-2"></i> Temukan Kami
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>