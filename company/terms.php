<?php
// company/terms.php — Syarat & Ketentuan
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Syarat & ketentuan';
$page_desc  = 'Ketentuan penggunaan layanan Tokopi, termasuk pemesanan via QR, pembayaran, refund, dan program poin.';
$active_nav = 'terms';
require_once __DIR__ . '/../includes/header_public.php';
?>

<section class="page-intro">
    <div class="wrap">
        <div class="crumb"><a href="<?= $COMPANY_BASE ?>/index.php">Beranda</a> / Legal</div>
        <h1><?= e($page_title) ?></h1>
    </div>
</section>

<section class="section-tight">
    <div class="wrap">
        <div class="prose">

      <p class="muted" style="font-size:14px;">Terakhir diperbarui: <?= date('d M Y', filemtime(__FILE__)) ?> · Versi 1.0</p>

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
