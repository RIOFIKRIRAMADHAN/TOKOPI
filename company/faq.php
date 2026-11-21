<?php
// company/faq.php — Pertanyaan umum
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Pertanyaan umum';
$page_desc  = 'Cara pesan via QR, metode bayar, poin member, refund, dan pertanyaan lain seputar Tokopi.';
$active_nav = 'faq';
require_once __DIR__ . '/../includes/header_public.php';
?>

<section class="page-intro">
    <div class="wrap">
        <div class="crumb"><a href="<?= $COMPANY_BASE ?>/index.php">Beranda</a> / Bantuan</div>
        <h1>Pertanyaan umum</h1>
        <p class="lead">Belum ketemu jawabannya? <a href="<?= $COMPANY_BASE ?>/contact.php" style="color:var(--p-accent);">Kirim pesan ke kami</a>.</p>
    </div>
</section>

<section class="section-tight">
    <div class="wrap" style="max-width:860px;">
        <div class="faq">
            <details open>
                <summary>Bagaimana cara memesan di Tokopi?</summary>
                <div class="ans">Ada dua cara: <strong>langsung ke kasir</strong> untuk pesan take-away, atau <strong>scan QR di meja</strong> untuk pesan langsung dari tempat duduk. Pesanan via QR akan otomatis masuk ke sistem kami — tidak perlu antre.</div>
            </details>
            <details>
                <summary>Metode pembayaran apa saja yang diterima?</summary>
                <div class="ans">Kami menerima <strong>tunai</strong> (bayar di kasir) dan <strong>QRIS</strong> (GoPay, OVO, Dana, ShopeePay, LinkAja, m-Banking, dan aplikasi lain yang mendukung QRIS). Untuk pesanan via QR, kamu bisa memilih metode sesuai preferensi.</div>
            </details>
            <details>
                <summary>Apakah bisa pesan antar (delivery)?</summary>
                <div class="ans">Saat ini fokus kami adalah <strong>dine-in dan take-away</strong> di 3 cabang. Untuk pesan antar, silakan hubungi cabang terdekat via telepon — beberapa cabang bekerja sama dengan layanan ojek online.</div>
            </details>
            <details>
                <summary>Bagaimana cara kerja program loyalitas?</summary>
                <div class="ans">Setiap <strong>Rp 10.000</strong> belanja = <strong>1 poin</strong>. Poin bisa ditukar dengan voucher diskon atau menu gratis. Semakin sering ngopi, semakin tinggi tier kamu: <strong>BRONZE → SILVER → GOLD</strong> dengan benefit yang meningkat tiap tier.</div>
            </details>
            <details>
                <summary>Apakah ada menu untuk vegetarian atau alergi tertentu?</summary>
                <div class="ans">Kami menyediakan beberapa pilihan <strong>non-dairy</strong> (oat milk, almond milk, soy milk). Untuk alergi spesifik, silakan beri tahu barista atau kasir — kami akan bantu cek bahan yang digunakan sebelum pesanan diproses.</div>
            </details>
            <details>
                <summary>Apakah Tokopi menerima reservasi tempat?</summary>
                <div class="ans">Untuk grup kecil (di bawah 6 orang), tidak perlu reservasi — datang langsung. Untuk grup besar atau acara khusus, silakan hubungi kami via halaman <a href="<?= $COMPANY_BASE ?>/contact.php" style="color:var(--p-accent);">Contact</a> minimal 3 hari sebelumnya.</div>
            </details>
            <details>
                <summary>Apakah pesanan bisa di-refund atau di-cancel?</summary>
                <div class="ans">Pesanan yang sudah dibayar dan sudah mulai diproses <strong>tidak dapat dibatalkan</strong>. Kalau ada masalah dengan pesanan (misal salah item atau rasa tidak sesuai), sampaikan ke barista/kasir — kami akan bantu cari solusi terbaik.</div>
            </details>
            <details>
                <summary>Di mana saja cabang Tokopi?</summary>
                <div class="ans">
                    Saat ini ada <strong><?= count($PUBLIC_OUTLETS) ?> cabang</strong> di Jakarta:
                    <?= e(implode(', ', array_map('outlet_short', array_column($PUBLIC_OUTLETS, 'nama')))) ?>.
                    Alamat lengkap dan petunjuk arah ada di halaman <a href="<?= $COMPANY_BASE ?>/contact.php" style="color:var(--p-accent);">Cabang &amp; kontak</a>.
                </div>
            </details>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
