<?php
// company/faq.php — Frequently Asked Questions
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'FAQ — Tokopi';
$active_nav = 'faq';
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

    .faq-accordion .accordion-item {
        border: 1px solid rgba(65,45,21,.08);
        border-radius: 1rem !important;
        margin-bottom: 1rem;
        overflow: hidden;
        background: #fff;
    }
    .faq-accordion .accordion-button {
        background: #fff;
        color: var(--espresso);
        font-weight: 600;
        font-size: 1rem;
        padding: 1.25rem 1.5rem;
        border: none;
        box-shadow: none;
    }
        .faq-accordion .accordion-button:not(.collapsed) {
        background: #F3F0E6; /* Warna Sand Light (krem hangat) */
        color: var(--espresso);
        box-shadow: inset 0 -1px 0 rgba(65, 45, 21, .05); /* Garis tipis di bawah */
    }
    .faq-accordion .accordion-button:focus {
        box-shadow: none;
        border-color: transparent;
    }
    .faq-accordion .accordion-body {
        padding: 0 1.5rem 1.5rem;
        color: var(--text-muted);
        line-height: 1.7;
    }

    .faq-cta { text-align: center; padding: 3rem 0 0; }

    @media (max-width: 576px) {
        .page-hero { padding: 3.5rem 0 3rem; }
        .faq-accordion .accordion-button { padding: 1rem 1.25rem; font-size: .95rem; }
        .faq-accordion .accordion-body { padding: 0 1.25rem 1.25rem; font-size: .9rem; }
    }
</style>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="container text-center">
    <span class="eyebrow" style="color:var(--accent);">FAQ</span>
    <h1 class="page-title mt-3 mb-3">Sering Ditanyakan</h1>
    <p class="lead mx-auto" style="max-width:38rem;opacity:.9;">
      Jawaban untuk pertanyaan yang paling sering muncul. Kalau belum terjawab, hubungi kami langsung.
    </p>
  </div>
</section>

<!-- FAQ LIST -->
<section style="padding:4rem 0;">
  <div class="container" style="max-width:820px;">
    <div class="accordion faq-accordion" id="faqAccordion">

      <div class="accordion-item fade-in-up">
        <h3 class="accordion-header">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true">
            Bagaimana cara memesan di Tokopi?
          </button>
        </h3>
        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Ada dua cara: <strong>langsung ke kasir</strong> untuk pesan take-away, atau <strong>scan QR di meja</strong> untuk pesan langsung dari tempat duduk. Pesanan via QR akan otomatis masuk ke sistem kami — tidak perlu antre.
          </div>
        </div>
      </div>

      <div class="accordion-item fade-in-up">
        <h3 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
            Metode pembayaran apa saja yang diterima?
          </button>
        </h3>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Kami menerima <strong>tunai</strong> (bayar di kasir) dan <strong>QRIS</strong> (GoPay, OVO, Dana, ShopeePay, LinkAja, m-Banking, dan aplikasi lain yang mendukung QRIS). Untuk pesanan via QR, kamu bisa memilih metode sesuai preferensi.
          </div>
        </div>
      </div>

      <div class="accordion-item fade-in-up">
        <h3 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
            Apakah bisa pesan antar (delivery)?
          </button>
        </h3>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Saat ini fokus kami adalah <strong>dine-in dan take-away</strong> di 3 cabang. Untuk pesan antar, silakan hubungi cabang terdekat via telepon — beberapa cabang bekerja sama dengan layanan ojek online.
          </div>
        </div>
      </div>

      <div class="accordion-item fade-in-up">
        <h3 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
            Bagaimana cara kerja program loyalitas?
          </button>
        </h3>
        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Setiap <strong>Rp 10.000</strong> belanja = <strong>1 poin</strong>. Poin bisa ditukar dengan voucher diskon atau menu gratis. Semakin sering ngopi, semakin tinggi tier kamu: <strong>BRONZE → SILVER → GOLD</strong> dengan benefit yang meningkat tiap tier.
          </div>
        </div>
      </div>

      <div class="accordion-item fade-in-up">
        <h3 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
            Apakah ada menu untuk vegetarian atau alergi tertentu?
          </button>
        </h3>
        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Kami menyediakan beberapa pilihan <strong>non-dairy</strong> (oat milk, almond milk, soy milk). Untuk alergi spesifik, silakan beri tahu barista atau kasir — kami akan bantu cek bahan yang digunakan sebelum pesanan diproses.
          </div>
        </div>
      </div>

      <div class="accordion-item fade-in-up">
        <h3 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
            Apakah Tokopi menerima reservasi tempat?
          </button>
        </h3>
        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Untuk grup kecil (di bawah 6 orang), tidak perlu reservasi — datang langsung. Untuk grup besar atau acara khusus, silakan hubungi kami via halaman <a href="<?= $COMPANY_BASE ?>/contact.php" style="color:var(--espresso);font-weight:600;">Contact</a> minimal 3 hari sebelumnya.
          </div>
        </div>
      </div>

      <div class="accordion-item fade-in-up">
        <h3 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
            Apakah pesanan bisa di-refund atau di-cancel?
          </button>
        </h3>
        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Pesanan yang sudah dibayar dan sudah mulai diproses <strong>tidak dapat dibatalkan</strong>. Kalau ada masalah dengan pesanan (misal salah item atau rasa tidak sesuai), sampaikan ke barista/kasir — kami akan bantu cari solusi terbaik.
          </div>
        </div>
      </div>

      <div class="accordion-item fade-in-up">
        <h3 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
            Di mana saja cabang Tokopi?
          </button>
        </h3>
        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
         Saat ini kami hadir di <strong>3 lokasi</strong> di Jakarta. Cabang utama (pusat) berlokasi di <strong>Lippo Mall Kemang, Lantai GF No. 12, Jl. Pangeran Antasari No.36, Jakarta Selatan</strong>. Untuk cabang lainnya, cek halaman <a href="<?= $COMPANY_BASE ?>/contact.php" style="color:var(--espresso);font-weight:600;">Contact</a>.
        </div>
      </div>

    </div>

    <div class="faq-cta">
      <p class="text-secondary mb-3">Belum menemukan jawaban?</p>
      <a href="<?= $COMPANY_BASE ?>/contact.php" class="btn btn-espresso">
        <i class="bi bi-envelope me-2"></i> Hubungi Kami
      </a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>