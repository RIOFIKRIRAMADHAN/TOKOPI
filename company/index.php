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

// ---------- Featured Products ----------
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

<!-- ============ CSS MANDIRI HOMEPAGE (ANTI RUSAK) ============ -->
<style>
    body { padding-top: 76px; }
    
    /* HERO SECTION */
    .hero { 
        background: linear-gradient(135deg, #2D1B0A 0%, #1A0F06 100%) !important; 
        color: #fff !important; 
        padding: 100px 0 80px; 
        position: relative; 
        overflow: hidden; 
    }
    .hero::before { 
        content: ''; position: absolute; inset: 0; 
        background: radial-gradient(circle at 80% 30%, rgba(225,220,201,.1) 0%, transparent 55%); 
        pointer-events: none; 
    }
    .hero .container { position: relative; z-index: 1; }
    
    .hero-eyebrow { color: #B8853A !important; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; font-size: 0.8rem; }
    .hero h1 { font-family: 'Playfair Display', serif; font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 700; line-height: 1.1; color: #fff !important; margin-top: 12px; margin-bottom: 24px; }
    .hero h1 span { color: #E1DCC9 !important; }
    .hero p { color: #fff !important; opacity: .9; max-width: 32rem; font-size: 1.1rem; line-height: 1.6; margin-bottom: 32px; }
    
    .hero-visual { 
        aspect-ratio: 1; border-radius: 32px; 
        background: linear-gradient(135deg, rgba(225,220,201,.12), rgba(225,220,201,.02)); 
        border: 1px solid rgba(225,220,201,.15); 
        display: flex; align-items: center; justify-content: center; 
        font-size: 10rem; color: rgba(225,220,201,.55); 
    }
    
    .btn-hero-primary { 
        background: #E1DCC9; color: #2D1B0A; border: none; font-weight: 700; 
        padding: 16px 32px; border-radius: 12px; text-decoration: none; 
        display: inline-flex; align-items: center; transition: all 0.2s; font-size: 1rem;
    }
    .btn-hero-primary:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); color: #2D1B0A; }
    
    .btn-hero-outline { 
        background: transparent; color: #fff; border: 1.5px solid rgba(255,255,255,0.4); 
        font-weight: 700; padding: 16px 32px; border-radius: 12px; text-decoration: none; 
        display: inline-flex; align-items: center; transition: all 0.2s; font-size: 1rem;
    }
    .btn-hero-outline:hover { background: rgba(255,255,255,0.1); border-color: #fff; color: #fff; }

    /* MENU CARD */
    .menu-card { 
        background: #fff !important; 
        border: 1px solid #EBE6DA !important; 
        border-radius: 20px !important; 
        overflow: hidden; 
        display: flex; flex-direction: column; height: 100%;
        transition: all .3s ease;
    }
    .menu-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(65,45,21,.12); }
    .menu-card-img { 
        height: 160px; 
        background: linear-gradient(135deg, #F3F0E6 0%, #E1DCC9 100%) !important; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 3rem; color: #D4C4B0; 
    }
    
    /* TESTIMONIAL CARD */
    .testimonial-card { 
        background: #fff !important; 
        border: 1px solid #EBE6DA !important; 
        border-radius: 20px !important; 
        padding: 24px !important; 
        height: 100%; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    }
    .avatar-circle { 
        width: 50px; height: 50px; border-radius: 50%; 
        background: #E1DCC9 !important; color: #2D1B0A !important; 
        display: flex; align-items: center; justify-content: center; 
        font-weight: 800; font-size: 1.2rem; flex-shrink: 0; 
        font-family: 'Playfair Display', serif;
    }

    /* CTA BAND */
    .cta-band { 
        background: linear-gradient(135deg, #2D1B0A 0%, #1A0F06 100%) !important; 
        border-radius: 32px !important; 
        padding: 48px !important; 
        color: #fff !important; 
    }
</style>

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="hero-eyebrow">Specialty Coffee · Est. 2024</span>
        <h1>Setiap Cangkir,<br><span>Sebuah Cerita.</span></h1>
        <p>
          Kopi pilihan dari biji terbaik, diracik dengan tangan-tangan yang peduli. Suasana hangat, koneksi nyata, dalam setiap tegukan.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="<?= $COMPANY_BASE ?>/menu.php" class="btn-hero-primary">
            <i class="bi bi-cup-hot me-2"></i> Lihat Menu
          </a>
          <a href="<?= $COMPANY_BASE ?>/about.php" class="btn-hero-outline">Cerita Kami</a>
        </div>
      </div>
      <div class="col-lg-5 d-none d-lg-block">
        <div class="hero-visual" style="overflow: hidden;">
          <img src="https://images.unsplash.com/photo-1497935586351-b67a49e012bf?q=80&w=1200&auto=format&fit=crop"
               alt="Tokopi Coffee Shop Interior"
               loading="lazy"
               style="width: 100%; height: 100%; object-fit: cover; border-radius: 2rem;"
               onerror="this.onerror=null; this.src='https://placehold.co/800x800?text=Tokopi';">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ ABOUT PREVIEW ============ -->
<section style="padding:5rem 0;">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5 fade-in-up">
        <div class="about-visual" style="aspect-ratio:4/5; border-radius:2rem; overflow: hidden;">
          <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=1200&auto=format&fit=crop"
               alt="Tokopi Cafe Atmosphere"
               loading="lazy"
               style="width: 100%; height: 100%; object-fit: cover;"
               onerror="this.onerror=null; this.src='https://placehold.co/800x1000?text=Tokopi';">
        </div>
      </div>
      <div class="col-lg-7 fade-in-up">
        <span class="eyebrow" style="color: #B8853A !important; font-weight:700;">TENTANG KAMI</span>
        <h2 class="section-title display-6 mt-2 mb-3" style="color: #2D1B0A !important; font-family:'Playfair Display', serif; font-weight:700;">Lebih dari sekadar kedai kopi.</h2>
        <p class="text-muted lh-lg mb-3">
          Tokopi lahir dari kecintaan sederhana pada kopi dan obrolan panjang. Kami percaya setiap cangkir bisa menjadi alasan untuk berhenti, bernapas, dan tersenyum.
        </p>
        <p class="text-muted lh-lg mb-4">
          Biji pilihan dari petani lokal, di-roast dengan presisi, dan diseduh dengan sepenuh hati — itulah janji kami di setiap cabang.
        </p>
        <a href="<?= $COMPANY_BASE ?>/about.php" class="btn btn-espresso">Pelajari Lebih Lanjut <i class="bi bi-arrow-right ms-2"></i></a>
      </div>
    </div>
  </div>
</section>

<!-- ============ FEATURED MENU ============ -->
<section style="background:#F3F0E6; padding:5rem 0;">
  <div class="container">
    <div class="text-center mb-5 fade-in-up">
      <span class="eyebrow" style="color: #B8853A !important; font-weight:700;">MENU FAVORIT</span>
      <h2 class="section-title display-6 mt-2" style="color: #2D1B0A !important; font-family:'Playfair Display', serif; font-weight:700;">Yang Paling Dicintai</h2>
    </div>
    <div class="row g-4">
      <?php
    // Mapping gambar berdasarkan urutan produk (fallback dummy)
    $menu_images = [
        0 => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?q=80&w=600&auto=format&fit=crop', // Espresso
        1 => 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?q=80&w=600&auto=format&fit=crop', // Cappuccino
        2 => 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?q=80&w=600&auto=format&fit=crop', // Caramel Macchiato
        3 => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?q=80&w=600&auto=format&fit=crop', // Cold Brew
    ];
    foreach ($featured as $i => $p):
        $img_url = $menu_images[$i] ?? 'https://placehold.co/600x400?text=Tokopi';
    ?>
      <div class="col-md-6 col-lg-3 fade-in-up" style="transition-delay:<?= $i * 100 ?>ms;">
        <div class="menu-card">
          <div class="menu-card-img">
            <img src="<?= $img_url ?>"
                 alt="<?= e($p['nama']) ?>"
                 loading="lazy"
                 style="width: 100%; height: 160px; object-fit: cover;"
                 onerror="this.onerror=null; this.src='https://placehold.co/600x400?text=Tokopi';">
          </div>
          <div class="p-4 d-flex flex-column flex-grow-1">
            <h5 class="fw-bold mb-1" style="color:#2D1B0A !important;"><?= e($p['nama']) ?></h5>
            <p class="text-muted small mb-3">Mulai dari</p>
            <div class="mt-auto d-flex justify-content-between align-items-center">
              <span class="font-mono fw-bold" style="color:#2D1B0A;">Rp <?= number_format((int)($p['harga_min'] ?? 0), 0, ',', '.') ?></span>
              <a href="<?= $COMPANY_BASE ?>/menu.php" class="btn btn-sm btn-outline-dark rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px; border-color:#EBE6DA;"><i class="bi bi-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-5">
      <a href="<?= $COMPANY_BASE ?>/menu.php" class="btn btn-espresso btn-lg px-4" style="background:#2D1B0A; color:#fff;">Lihat Menu Lengkap <i class="bi bi-arrow-right ms-2"></i></a>
    </div>
  </div>
</section>

<!-- ============ TESTIMONI ============ -->
<section style="padding:5rem 0; background: #fff;">
  <div class="container">
    <div class="text-center mb-5 fade-in-up">
      <span class="eyebrow" style="color: #B8853A !important; font-weight:700;">TESTIMONI</span>
      <h2 class="section-title display-6 mt-2" style="color: #2D1B0A !important; font-family:'Playfair Display', serif; font-weight:700;">Kata Mereka</h2>
      <p class="text-muted mt-2">Cerita nyata dari pelanggan setia Tokopi.</p>
    </div>

    <?php $chunks = array_chunk($testimonials, 3); $slide_count = count($chunks); ?>

    <div id="testiCarousel" class="carousel slide fade-in-up" data-bs-ride="carousel" data-bs-interval="6000">
      <div class="carousel-inner">
        <?php foreach ($chunks as $ci => $chunk): ?>
        <div class="carousel-item <?= $ci === 0 ? 'active' : '' ?>">
          <div class="row g-4 px-md-4">
            <?php
            $avatar_pool = [
                'https://randomuser.me/api/portraits/women/44.jpg',
                'https://randomuser.me/api/portraits/men/32.jpg',
                'https://randomuser.me/api/portraits/women/68.jpg',
                'https://randomuser.me/api/portraits/men/75.jpg',
                'https://randomuser.me/api/portraits/women/12.jpg',
            ];
            $avatar_idx = 0;
            foreach ($chunk as $t):
                $avatar_url = $avatar_pool[$avatar_idx % count($avatar_pool)];
                $avatar_idx++;
            ?>
            <div class="col-md-4">
              <div class="testimonial-card">
                <div class="d-flex align-items-center gap-3 mb-3">
                  <div class="avatar-circle" style="overflow: hidden; background: none;">
                    <img src="<?= $avatar_url ?>"
                         alt="<?= e($t['nama']) ?>"
                         loading="lazy"
                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                         onerror="this.onerror=null; this.src='https://placehold.co/50x50?text=' + encodeURIComponent('<?= e($t['nama']) ?>'.charAt(0)); this.style.background='#E1DCC9'; this.style.color='#2D1B0A';">
                  </div>
                  <div>
                    <h6 class="mb-1 fw-bold" style="color:#2D1B0A !important;"><?= e($t['nama']) ?></h6>
                    <div class="text-warning small" style="color: #B8853A !important;">
                      <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="bi bi-star<?= $s <= (int)$t['rating'] ? '-fill' : '' ?>"></i>
                      <?php endfor; ?>
                    </div>
                  </div>
                </div>
                <p class="mb-0 text-muted lh-lg" style="font-style:italic;">&ldquo;<?= e($t['teks']) ?>&rdquo;</p>
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
      <div class="carousel-indicators" style="position:static; margin-top:2rem;">
        <?php for ($i = 0; $i < $slide_count; $i++): ?>
        <button type="button" data-bs-target="#testiCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i === 0 ? 'active' : '' ?>" style="background: #B8853A !important; border-radius: 50%; width:10px; height:10px; border:none; margin:0 4px;"></button>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section style="padding:5rem 0;">
  <div class="container">
    <div class="cta-band fade-in-up">
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h2 class="section-title display-6 mb-2" style="color:#fff !important; font-family:'Playfair Display', serif; font-weight:700;">Ngopi bareng kami hari ini.</h2>
          <p class="mb-0" style="color:#E1DCC9 !important; opacity:.9;">Kunjungi cabang terdekat, atau pesan langsung dari meja lewat QR — tanpa antre, tanpa ribet.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="<?= $COMPANY_BASE ?>/contact.php" class="btn-hero-primary">
            <i class="bi bi-geo-alt me-2"></i> Temukan Kami
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>