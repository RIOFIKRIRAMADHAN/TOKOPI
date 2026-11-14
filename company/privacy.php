<?php
// company/privacy.php — Privacy Policy
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Privacy Policy — Tokopi';
$active_nav = 'privacy';
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
    .legal-content table {
        width: 100%; margin: 1rem 0;
        border-collapse: separate; border-spacing: 0;
        font-size: .9rem;
    }
    .legal-content th, .legal-content td {
        padding: .75rem 1rem;
        text-align: left;
        border-bottom: 1px solid rgba(65,45,21,.08);
    }
    .legal-content th {
        background: var(--bg);
        color: var(--espresso);
        font-weight: 600;
    }

    @media (max-width: 576px) {
        .page-hero { padding: 3.5rem 0 3rem; }
        .legal-content h2 { font-size: 1.25rem; }
        .legal-content table { font-size: .8rem; }
        .legal-content th, .legal-content td { padding: .5rem .65rem; }
    }
</style>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="container text-center">
    <span class="eyebrow" style="color:var(--accent);">Legal</span>
    <h1 class="page-title mt-3 mb-3">Kebijakan Privasi</h1>
    <p class="lead mx-auto" style="max-width:38rem;opacity:.9;">
      Kami menghargai privasimu. Halaman ini menjelaskan data apa yang kami kumpulkan, bagaimana kami menggunakannya, dan hak-hakmu sebagai pengguna.
    </p>
  </div>
</section>

<!-- CONTENT -->
<section style="padding:4rem 0;">
  <div class="container">
    <div class="legal-content">

      <div class="legal-meta">
        <i class="bi bi-shield-check me-2"></i>
        <strong>Terakhir diperbarui:</strong> <?= date('d F Y') ?> &nbsp;·&nbsp; <strong>Versi:</strong> 1.0
      </div>

      <h2>1. Data yang Kami Kumpulkan</h2>
      <p>Kami mengumpulkan beberapa jenis data untuk memberikan layanan yang lebih baik:</p>

      <table>
        <thead>
          <tr>
            <th>Kategori</th>
            <th>Contoh Data</th>
            <th>Tujuan</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Data Identitas</strong></td>
            <td>Nama, email, nomor telepon</td>
            <td>Akun pelanggan, komunikasi</td>
          </tr>
          <tr>
            <td><strong>Data Transaksi</strong></td>
            <td>Riwayat pesanan, metode pembayaran</td>
            <td>Proses pesanan, program loyalitas</td>
          </tr>
          <tr>
            <td><strong>Data Teknis</strong></td>
            <td>IP address, jenis perangkat, browser</td>
            <td>Keamanan, analitik, perbaikan layanan</td>
          </tr>
          <tr>
            <td><strong>Data Sesi</strong></td>
            <td>ID sesi untuk pemesanan via QR</td>
            <td>Menghubungkan pesanan ke sesi pelanggan</td>
          </tr>
        </tbody>
      </table>

      <h2>2. Bagaimana Kami Menggunakan Data</h2>
      <ul>
        <li>Memproses dan mengantarkan pesananmu.</li>
        <li>Mengelola program loyalitas (poin, tier, voucher).</li>
        <li>Mengirimkan konfirmasi pesanan, notifikasi status, dan informasi penting terkait layanan.</li>
        <li>Meningkatkan kualitas produk dan layanan berdasarkan pola penggunaan (data agregat &amp; anonim).</li>
        <li>Mencegah penipuan, penyalahgunaan sistem, dan aktivitas ilegal lainnya.</li>
        <li>Memenuhi kewajiban hukum yang berlaku.</li>
      </ul>

      <h2>3. Berbagi Data dengan Pihak Ketiga</h2>
      <p>Kami <strong>tidak menjual</strong> data pribadimu ke pihak ketiga. Kami hanya membagikan data terbatas dalam situasi berikut:</p>
      <ul>
        <li><strong>Payment Gateway (Midtrans)</strong> — untuk memproses pembayaran QRIS. Data yang dibagikan terbatas pada informasi transaksi yang diperlukan.</li>
        <li><strong>Penyedia hosting</strong> — sebagai penyedia infrastruktur, mereka memiliki akses teknis ke server, namun terikat perjanjian kerahasiaan.</li>
        <li><strong>Kewajiban hukum</strong> — jika diwajibkan oleh hukum atau perintah pengadilan yang sah.</li>
      </ul>

      <h2>4. Cookie &amp; Teknologi Serupa</h2>
      <p>
        Kami menggunakan <strong>cookie</strong> dan <strong>localStorage</strong> untuk menjaga sesi login, menyimpan isi keranjang belanja, dan mengingat preferensimu. Kamu dapat menghapus cookie melalui pengaturan browser, namun beberapa fitur mungkin tidak berfungsi dengan baik.
      </p>

      <h2>5. Penyimpanan &amp; Keamanan Data</h2>
      <ul>
        <li>Data disimpan di server yang dilindungi dengan enkripsi dan protokol keamanan standar.</li>
        <li>Password disimpan dalam bentuk <strong>hash</strong> (tidak dapat dibaca sebagai teks biasa).</li>
        <li>Akses ke data pelanggan dibatasi hanya untuk staf yang berwenang.</li>
        <li>Kami meninjau dan memperbarui praktik keamanan secara berkala.</li>
      </ul>

      <h2>6. Hak-Hakmu sebagai Pengguna</h2>
      <p>Sesuai dengan peraturan perlindungan data yang berlaku, kamu berhak untuk:</p>
      <ul>
        <li><strong>Mengakses</strong> data pribadi yang kami simpan tentangmu.</li>
        <li><strong>Memperbaiki</strong> data yang tidak akurat atau tidak lengkap.</li>
        <li><strong>Menghapus</strong> akun dan data pribadimu (dengan ketentuan tertentu, misal data transaksi tetap disimpan untuk keperluan hukum).</li>
        <li><strong>Menarik persetujuan</strong> atas penggunaan data tertentu (misal untuk pemasaran).</li>
        <li><strong>Mengajukan keluhan</strong> terkait pengelolaan data pribadi.</li>
      </ul>
      <p>
        Untuk menggunakan hak-hak tersebut, hubungi kami melalui halaman <a href="<?= $COMPANY_BASE ?>/contact.php" style="color:var(--espresso);font-weight:600;">Contact</a> atau email <strong>privacy@tokopi.id</strong>.
      </p>

      <h2>7. Data Anak-Anak</h2>
      <p>
        Layanan Tokopi tidak ditujukan untuk anak di bawah usia <strong>13 tahun</strong>. Kami tidak dengan sengaja mengumpulkan data pribadi dari anak-anak. Jika kamu mengetahui bahwa anak di bawah 13 tahun telah memberikan data kepada kami, silakan hubungi kami untuk menghapusnya.
      </p>

      <h2>8. Retensi Data</h2>
      <ul>
        <li><strong>Data akun aktif</strong> — disimpan selama akun masih aktif.</li>
        <li><strong>Data transaksi</strong> — disimpan minimal 5 tahun untuk keperluan audit dan kewajiban perpajakan.</li>
        <li><strong>Data sesi guest</strong> — dihapus otomatis setelah periode tertentu (biasanya 30 hari).</li>
      </ul>

      <h2>9. Perubahan Kebijakan</h2>
      <p>
        Kami dapat memperbarui kebijakan privasi ini sewaktu-waktu. Perubahan signifikan akan diberitahukan melalui email atau banner di situs. Versi terbaru selalu tersedia di halaman ini.
      </p>

      <h2>10. Hubungi Kami</h2>
      <p>Jika kamu memiliki pertanyaan, kekhawatiran, atau ingin menggunakan hak-hakmu terkait data pribadi, silakan hubungi:</p>
      <ul>
        <li><strong>Email:</strong> privacy@tokopi.id</li>
        <li><strong>Telepon:</strong> +62 21 1234 5678</li>
        <li><strong>Alamat:</strong> Lippo Mall Kemang, Lantai GF No. 12, Jl. Pangeran Antasari No.36, Jakarta Selatan 12150</li>
      </ul>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>