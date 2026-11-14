<?php
// company/contact.php
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Contact — Tokopi';
$active_nav = 'contact';
require_once __DIR__ . '/../includes/header_public.php';

// Ambil status flash message
$success = flash_get('success');
$error   = flash_get('error');
?>

<style>
    .page-hero {
        background: linear-gradient(135deg, var(--espresso) 0%, var(--espresso-dark) 100%);
        color: #fff; padding: 5rem 0 4rem;
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

    .contact-card {
        background: #fff;
        border-radius: 1.5rem;
        padding: 3rem;
        border: 1px solid rgba(65,45,21,.08);
        box-shadow: 0 8px 32px rgba(65,45,21,.06);
    }

    .contact-info-item {
        display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem;
    }
    .contact-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: var(--sand); color: var(--espresso);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; flex-shrink: 0;
    }
    
    .form-control {
        border-radius: 0.75rem; padding: 0.875rem 1rem;
        border: 1px solid rgba(65,45,21,.15);
        background: var(--bg-alt);
    }
    .form-control:focus {
        border-color: var(--espresso);
        box-shadow: 0 0 0 0.25rem rgba(65,45,21,.1);
        background: #fff;
    }
    .form-label { font-weight: 600; font-size: 0.85rem; color: var(--espresso); }

    .map-container {
        border-radius: 1.5rem; overflow: hidden; border: 1px solid rgba(65,45,21,.08);
        box-shadow: 0 4px 24px rgba(65,45,21,.06);
    }

    /* Honeypot field (wajib ada tapi disembunyikan) */
    .hp-field { display: none !important; }

    @media (max-width: 576px) {
        .page-hero { padding: 3.5rem 0 3rem; }
        .contact-card { padding: 1.5rem; border-radius: 1rem; }
    }
</style>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="container text-center">
    <span class="eyebrow" style="color:var(--accent);">Kontak</span>
    <h1 class="page-title mt-3 mb-3">Mari Terhubung</h1>
    <p class="lead mx-auto" style="max-width:38rem;opacity:.9;">
      Punya pertanyaan, saran, atau ingin bekerja sama? Kami senang mendengar dari Anda.
    </p>
  </div>
</section>

<!-- KONTEN KONTAK -->
<section style="padding:4rem 0;">
  <div class="container">
    
    <?php if ($success): ?>
      <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:1rem;">
        <i class="bi bi-check-circle-fill me-2"></i> <?= e($success) ?>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius:1rem;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($error) ?>
      </div>
    <?php endif; ?>

    <div class="row g-5">
      <!-- KIRI: Info -->
      <div class="col-lg-5 fade-in-up">
        <h2 class="section-title display-6 mb-4">Informasi Kontak</h2>
        
        <div class="contact-info-item">
          <div class="contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
          <div>
            <h6 class="fw-bold mb-1" style="color:var(--espresso);">Alamat Utama</h6>
            <p class="text-secondary mb-0">Lippo Mall Kemang, Lantai GF No. 12, Jl. Pangeran Antasari No.36, Jakarta Selatan 12150</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-icon"><i class="bi bi-telephone-fill"></i></div>
          <div>
            <h6 class="fw-bold mb-1" style="color:var(--espresso);">Telepon</h6>
            <p class="text-secondary mb-0">+62 21 1234 5678</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
          <div>
            <h6 class="fw-bold mb-1" style="color:var(--espresso);">Email</h6>
            <p class="text-secondary mb-0">hello@tokopi.id</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-icon"><i class="bi bi-clock-fill"></i></div>
          <div>
            <h6 class="fw-bold mb-1" style="color:var(--espresso);">Jam Operasional</h6>
            <p class="text-secondary mb-0">Senin - Minggu, 08.00 – 22.00 WIB</p>
          </div>
        </div>

        <div class="map-container mt-4">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.1281547726275!2d106.812949!3d-6.260749!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNsKwMTUnMzguNyJTIDEwNsKwNDgnNDYuNiJF!5e0!3m2!1sid!2sid!4v1620000000000!5m2!1sid!2sid" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
        </div>
      </div>

      <!-- KANAN: Form -->
      <div class="col-lg-7 fade-in-up" style="transition-delay:150ms;">
        <div class="contact-card">
          <h3 class="section-title fs-3 mb-2">Kirim Pesan</h3>
          <p class="text-secondary small mb-4">Kami akan membalas pesan Anda melalui email maksimal 1x24 jam.</p>
          
          <form action="<?= $COMPANY_BASE ?>/contact_submit.php" method="POST">
            <?= csrf_field() ?>
            
            <!-- HONEYPOT (Umpan untuk Bot) -->
            <div class="hp-field">
              <input type="text" name="website_url" value="" tabindex="-1" autocomplete="off">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" required placeholder="Nama kamu">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="email@contoh.com">
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label">No. HP (Opsional)</label>
                <input type="text" name="phone" class="form-control" placeholder="08xxxxx">
              </div>
              <div class="col-md-6">
                <label class="form-label">Subjek <span class="text-danger">*</span></label>
                <input type="text" name="subject" class="form-control" required placeholder="Topik pesan">
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label">Pesan <span class="text-danger">*</span></label>
              <textarea name="message" class="form-control" rows="5" required placeholder="Tulis pesanmu di sini..."></textarea>
            </div>

            <button type="submit" class="btn btn-espresso w-100 py-3 rounded-3">
              <i class="bi bi-send-fill me-2"></i> Kirim Pesan
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>