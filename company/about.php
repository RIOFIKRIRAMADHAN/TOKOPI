<?php
// company/about.php — Visi misi, sejarah, tim
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'About — Tokopi';
$active_nav = 'about';
require_once __DIR__ . '/../includes/header_public.php';
?>

<style>
    .page-hero {
        background: linear-gradient(135deg, var(--espresso) 0%, var(--espresso-dark) 100%);
        color: #fff;
        padding: 5rem 0 4rem;
        position: relative;
        overflow: hidden;
    }
    .page-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(circle at 80% 20%, rgba(225,220,201,.08) 0%, transparent 55%);
        pointer-events: none;
    }
    .page-hero > .container { position: relative; z-index: 1; }
    .page-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(2rem, 4vw, 3.25rem);
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: -0.02em;
    }

    .story-visual {
        aspect-ratio: 4/3;
        border-radius: 1.5rem;
        background: linear-gradient(135deg, var(--sand) 0%, #C9C1A7 100%);
        display: flex; align-items: center; justify-content: center;
        font-size: 6rem; color: var(--espresso); opacity: .55;
    }

    .vm-card {
        background: #fff;
        border-radius: 1rem;
        padding: 2rem;
        height: 100%;
        border: 1px solid rgba(65,45,21,.08);
        transition: transform .25s ease, box-shadow .25s ease;
    }
    .vm-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(65,45,21,.1);
    }
    .vm-icon {
        width: 56px; height: 56px;
        border-radius: .875rem;
        background: var(--sand);
        color: var(--espresso);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    .vm-card h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.25rem;
        color: var(--espresso);
        margin-bottom: .75rem;
    }

    .value-item {
        display: flex;
        gap: 1rem;
        padding: 1rem 0;
        border-bottom: 1px solid rgba(65,45,21,.08);
    }
    .value-item:last-child { border-bottom: none; }
    .value-num {
        flex-shrink: 0;
        width: 40px; height: 40px;
        border-radius: 50%;
        background: var(--espresso);
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-family: 'JetBrains Mono', monospace;
        font-weight: 700;
        font-size: .9rem;
    }

    .team-card {
        background: #fff;
        border-radius: 1rem;
        padding: 2rem 1.5rem;
        text-align: center;
        border: 1px solid rgba(65,45,21,.08);
    }
    .team-avatar {
        width: 96px; height: 96px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--sand) 0%, #C9C1A7 100%);
        color: var(--espresso);
        display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Playfair Display', serif;
        font-size: 2rem; font-weight: 700;
        margin-bottom: 1rem;
    }
    .team-name {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        color: var(--espresso);
        font-size: 1.15rem;
        margin-bottom: .25rem;
    }
    .team-role {
        color: var(--accent);
        font-size: .8rem;
        text-transform: uppercase;
        letter-spacing: .1em;
        font-weight: 600;
    }

    @media (max-width: 576px) {
        .page-hero { padding: 3.5rem 0 3rem; }
        .vm-card { padding: 1.5rem; }
        .story-visual { aspect-ratio: 3/2; font-size: 4rem; }
    }
</style>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="container text-center">
    <span class="eyebrow" style="color:var(--accent);">Tentang Kami</span>
    <h1 class="page-title mt-3 mb-3">Cerita di Balik Setiap Cangkir</h1>
    <p class="lead mx-auto" style="max-width:38rem;opacity:.9;">
      Bukan sekadar kedai kopi — ini adalah rumah kedua untuk obrolan panjang, ide segar, dan momen yang layak dikenang.
    </p>
  </div>
</section>

<!-- STORY -->
<section style="padding:5rem 0;">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6 fade-in-up">
        <div class="story-visual"><i class="bi bi-cup-hot-fill"></i></div>
      </div>
      <div class="col-lg-6 fade-in-up">
        <span class="eyebrow">Sejarah</span>
        <h2 class="section-title display-6 mt-2 mb-3">Berawal dari satu mesin espresso.</h2>
        <p class="text-secondary lh-lg mb-3">
          Tokopi dimulai pada 2024 dari sebuah dapur kecil dengan satu mesin espresso bekas dan mimpi besar: menghadirkan kopi berkualitas yang jujur, tanpa jargon, tanpa gimmick.
        </p>
        <p class="text-secondary lh-lg mb-3">
          Dari satu pelanggan, kemudian dua, lalu tiga — kami tumbuh karena cerita dari mulut ke mulut. Kini Tokopi hadir di <strong>3 cabang</strong> di Jakarta, dengan satu janji yang sama: setiap cangkir dibuat dengan sepenuh hati.
        </p>
        <p class="text-secondary lh-lg mb-0">
          Kami percaya kopi bukan hanya soal rasa, tapi soal momen yang tercipta di sekitarnya.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- VISI MISI -->
<section style="background:var(--bg);padding:5rem 0;">
  <div class="container">
    <div class="text-center mb-5 fade-in-up">
      <span class="eyebrow">Visi & Misi</span>
      <h2 class="section-title display-6 mt-2">Arah yang Kami Tuju</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-6 fade-in-up">
        <div class="vm-card">
          <div class="vm-icon"><i class="bi bi-binoculars-fill"></i></div>
          <h3>Visi</h3>
          <p class="text-secondary mb-0 lh-lg">
            Menjadi kedai kopi lokal yang dipercaya karena konsistensi rasa, kualitas bahan, dan pelayanan yang hangat — tempat orang kembali bukan hanya karena kopinya, tapi karena suasananya.
          </p>
        </div>
      </div>
      <div class="col-md-6 fade-in-up" style="transition-delay:100ms;">
        <div class="vm-card">
          <div class="vm-icon"><i class="bi bi-list-check"></i></div>
          <h3>Misi</h3>
          <ul class="text-secondary lh-lg mb-0 ps-3">
            <li class="mb-2">Menyajikan kopi dari biji lokal pilihan dengan standar seduh konsisten di setiap cabang.</li>
            <li class="mb-2">Menciptakan ruang yang nyaman untuk bekerja, belajar, dan berbincang.</li>
            <li class="mb-2">Mendukung petani kopi lokal melalui pengadaan yang adil dan berkelanjutan.</li>
            <li class="mb-0">Memberikan pengalaman pesan-antar yang cepat, ramah, dan transparan.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- VALUES -->
<section style="padding:5rem 0;">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5 fade-in-up">
        <span class="eyebrow">Nilai Kami</span>
        <h2 class="section-title display-6 mt-2 mb-3">Tiga prinsip yang tidak kami tawar.</h2>
        <p class="text-secondary lh-lg mb-0">
          Di balik setiap cangkir, ada standar yang kami pegang sejak hari pertama. Ini yang membuat Tokopi tetap Tokopi.
        </p>
      </div>
      <div class="col-lg-7 fade-in-up">
        <div class="value-item">
          <div class="value-num">01</div>
          <div>
            <h5 class="fw-bold mb-1" style="color:var(--espresso);">Kualitas Tanpa Kompromi</h5>
            <p class="text-secondary mb-0">Biji pilihan, roast presisi, dan resep yang diuji berulang — setiap cangkir harus bisa kami banggakan.</p>
          </div>
        </div>
        <div class="value-item">
          <div class="value-num">02</div>
          <div>
            <h5 class="fw-bold mb-1" style="color:var(--espresso);">Hangat &amp; Ramah</h5>
            <p class="text-secondary mb-0">Pelanggan bukan "transaksi". Kami ingat nama, pesanan favorit, dan cerita yang mereka bawa.</p>
          </div>
        </div>
        <div class="value-item">
          <div class="value-num">03</div>
          <div>
            <h5 class="fw-bold mb-1" style="color:var(--espresso);">Jujur &amp; Transparan</h5>
            <p class="text-secondary mb-0">Harga wajar, bahan jelas, proses terbuka. Tidak ada yang kami sembunyikan di balik cangkir.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TIM -->
<section style="background:var(--bg);padding:5rem 0;">
  <div class="container">
    <div class="text-center mb-5 fade-in-up">
      <span class="eyebrow">Tim Kami</span>
      <h2 class="section-title display-6 mt-2">Orang-Orang di Balik Cangkir</h2>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-6 col-md-4 col-lg-3 fade-in-up">
        <div class="team-card">
          <div class="team-avatar">R</div>
          <div class="team-name">Raka Wijaya</div>
          <div class="team-role">Founder</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-3 fade-in-up" style="transition-delay:100ms;">
        <div class="team-card">
          <div class="team-avatar">S</div>
          <div class="team-name">Sinta Maharani</div>
          <div class="team-role">Head Barista</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-3 fade-in-up" style="transition-delay:200ms;">
        <div class="team-card">
          <div class="team-avatar">B</div>
          <div class="team-name">Bayu Pratama</div>
          <div class="team-role">Operations</div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>