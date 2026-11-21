<?php
// company/privacy.php — Privacy Policy
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Kebijakan privasi';
$page_desc  = 'Data apa yang Tokopi kumpulkan, untuk apa, dan bagaimana kamu bisa mengaturnya.';
$active_nav = 'privacy';
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
