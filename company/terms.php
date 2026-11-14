<?php
// company/terms.php — Syarat & Ketentuan
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Syarat & Ketentuan — Tokopi';
$active_nav = 'terms';
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

    .legal-content { max-width: 820px; margin: 0 auto; }
    .legal-content h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--espresso);
        margin-top: 2.5rem;
        margin-bottom: 1rem;
        padding-bottom: .5rem;
        border-bottom: 2px solid var(--sand);
    }
    .legal-content h2:first-child { margin-top: 0; }
    .legal-content p, .legal-content li {
        color: var(--text-muted);
        line-height: 1.8;
        margin-bottom: .75rem;
    }
    .legal-content ul, .legal-content ol { padding-left: 1.25rem; }
    .legal-content strong { color: var(--text); }
    .legal-meta {
        font-size: .85rem;
        color: var(--text-muted);
        background: var(--bg);
        padding: 1rem 1.25rem;
        border-radius: .5rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 576px) {
        .page-hero { padding: 3.5rem 0 3rem; }
        .legal-content h2 { font-size: 1.25rem; }
    }
</style>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="container text-center">
    <span class="eyebrow" style="color:var(--accent);">Legal</span>
    <h1 class="page-title mt-3 mb-3">Syarat &amp; Ketentuan</h1>
    <p class="lead mx-auto" style="max-width:38rem;opacity:.9;">
      Ketentuan penggunaan layanan Tokopi — baik untuk pemesanan langsung maupun melalui platform digital.
    </p>
  </div>
</section>

<!-- CONTENT -->
<section style="padding:4rem 0;">
  <div class="container">
    <div class="legal-content">

      <div class="legal-meta">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Terakhir diperbarui:</strong> <?= date('d F Y') ?> &nbsp;·&nbsp; <strong>Versi:</strong> 1.0
      </div>

      <h2>1. Penerimaan Ketentuan</h2>
      <p>
        Dengan mengakses situs, melakukan pemesanan, atau menggunakan layanan Tokopi (baik secara langsung di outlet maupun melalui platform digital), kamu dianggap telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan yang tercantum di halaman ini.
      </p>
      <p>
        Jika kamu tidak setuju dengan salah satu ketentuan, mohon untuk tidak melanjutkan penggunaan layanan kami.
      </p>

      <h2>2. Pemesanan &amp; Pembayaran</h2>
      <ul>
        <li>Pesanan dianggap sah setelah pembayaran diterima dan dikonfirmasi oleh sistem kami.</li>
        <li>Metode pembayaran yang tersedia: <strong>tunai</strong> (di kasir), dan <strong>QRIS</strong> (via Midtrans).</li>
        <li>Untuk pemesanan via QR, pesanan akan masuk ke dapur setelah pembayaran terkonfirmasi lunas.</li>
        <li>Harga yang tertera sudah termasuk pajak yang berlaku (kecuali dinyatakan lain).</li>
      </ul>

      <h2>3. Pembatalan &amp; Refund</h2>
      <ul>
        <li>Pesanan yang <strong>sudah dibayar</strong> dan <strong>sudah mulai diproses</strong> oleh barista/dapur <strong>tidak dapat dibatalkan</strong> atau di-refund.</li>
        <li>Jika pesanan belum diproses, pembatalan dapat dilakukan dengan menghubungi kasir/barista secara langsung.</li>
        <li>Jika terjadi kesalahan dari pihak kami (salah item, kualitas tidak sesuai), kami akan berupaya memberikan solusi terbaik — dapat berupa penggantian pesanan atau refund penuh.</li>
        <li>Refund untuk pembayaran QRIS akan diproses sesuai kebijakan Midtrans dan penyedia layanan pembayaran, biasanya dalam 3–7 hari kerja.</li>
      </ul>

      <h2>4. Program Loyalitas &amp; Poin</h2>
      <ul>
        <li>Setiap <strong>Rp 10.000</strong> belanja yang berhasil dibayar akan mendapatkan <strong>1 poin</strong>.</li>
        <li>Poin memiliki masa berlaku dan dapat hangus jika tidak digunakan dalam periode tertentu (akan diberitahukan melalui aplikasi).</li>
        <li>Poin <strong>tidak dapat</strong> diuangkan, dipindahtangankan, atau digabungkan antar akun.</li>
        <li>Voucher yang sudah ditukar dengan poin memiliki masa berlaku dan <strong>tidak dapat dikembalikan</strong> menjadi poin.</li>
        <li>Kami berhak membatalkan poin atau voucher jika terdapat indikasi kecurangan.</li>
      </ul>

      <h2>5. Akun Pelanggan</h2>
      <ul>
        <li>Kamu bertanggung jawab menjaga kerahasiaan kredensial akun (email, password, PIN).</li>
        <li>Segala aktivitas yang terjadi di bawah akunmu adalah tanggung jawabmu.</li>
        <li>Kami berhak menangguhkan atau menonaktifkan akun jika ditemukan pelanggaran terhadap ketentuan ini.</li>
      </ul>

      <h2>6. Hak Kekayaan Intelektual</h2>
      <p>
        Seluruh konten di situs ini — termasuk logo, nama <strong>Tokopi</strong>, desain, teks, gambar, dan kode — dilindungi oleh hak cipta dan merupakan milik Tokopi atau pemberi lisensinya. Kamu tidak diperkenankan menggunakan, menyalin, atau mendistribusikan konten tanpa izin tertulis dari kami.
      </p>

      <h2>7. Batasan Tanggung Jawab</h2>
      <p>Kami berupaya memberikan layanan terbaik, namun tidak bertanggung jawab atas:</p>
      <ul>
        <li>Gangguan layanan yang disebabkan oleh faktor di luar kendali kami (force majeure, gangguan jaringan, dsb).</li>
        <li>Kerugian tidak langsung yang timbul dari penggunaan layanan.</li>
        <li>Kesalahan input yang dilakukan oleh pengguna saat melakukan pemesanan.</li>
      </ul>

      <h2>8. Perubahan Ketentuan</h2>
      <p>
        Kami dapat memperbarui syarat dan ketentuan ini sewaktu-waktu. Perubahan akan berlaku efektif setelah dipublikasikan di halaman ini. Kami menyarankan kamu untuk meninjau halaman ini secara berkala.
      </p>

      <h2>9. Hukum yang Berlaku</h2>
      <p>
        Syarat dan ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum <strong>Republik Indonesia</strong>. Setiap perselisihan akan diselesaikan secara musyawarah terlebih dahulu, dan jika tidak tercapai kesepakatan, akan diselesaikan melalui jalur hukum yang berlaku.
      </p>

      <h2>10. Hubungi Kami</h2>
      <p>
        Untuk pertanyaan mengenai syarat dan ketentuan ini, silakan hubungi kami melalui halaman <a href="<?= $COMPANY_BASE ?>/contact.php" style="color:var(--espresso);font-weight:600;">Contact</a> atau email <strong>legal@tokopi.id</strong>.
      </p>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>