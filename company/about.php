<?php
// company/about.php — Cerita Tokopi
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Cerita kami';
$page_desc  = 'Tokopi dimulai pada 2024 dari satu mesin espresso bekas. Ini cerita, prinsip, dan orang-orang di baliknya.';
$active_nav = 'about';
require_once __DIR__ . '/../includes/header_public.php';

$team = [
    ['Raka Wijaya', 'Founder', 'Memulai Tokopi dari dapur kecil dan masih ikut cupping biji baru tiap bulan.'],
    ['Sinta Maharani', 'Head Barista', 'Menyusun resep dan standar seduh yang sama di setiap cabang.'],
    ['Bayu Pratama', 'Operations', 'Mengurus stok, jadwal, dan memastikan setiap cabang buka tepat waktu.'],
];
?>

<section class="page-intro">
    <div class="wrap">
        <div class="kicker"><b>Est.</b> 2024 · Jakarta</div>
        <h1 style="max-width:14ch;">Berawal dari satu mesin espresso bekas.</h1>
    </div>
</section>

<section class="section">
    <div class="wrap split">
        <div class="sticky-col">
            <div class="kicker"><b>01</b> Cerita</div>
            <figure class="story-art">
                <?php include __DIR__ . '/../includes/illustrations/barista_chat.svg.php'; ?>
                <figcaption>Bar Tokopi · obrolan pagi</figcaption>
            </figure>
        </div>
        <div class="prose" style="font-size:clamp(1.1rem,1.6vw,1.3rem);">
            <p>Tokopi dimulai pada 2024 dari dapur kecil, satu mesin espresso bekas, dan keinginan sederhana: kopi yang enak, harganya masuk akal, dan dijelaskan tanpa jargon.</p>
            <p>Pelanggan pertama datang karena tetangga. Pelanggan berikutnya datang karena cerita dari mulut ke mulut. Sekarang Tokopi punya <strong><?= count($PUBLIC_OUTLETS) ?> cabang</strong> di Jakarta, dengan resep dan standar seduh yang sama.</p>
            <p>Kami juga membuat sistem pemesanan sendiri. Pelanggan bisa pesan dari meja lewat QR, barista melihat antrean di layar, dan stok bahan berkurang otomatis sesuai resep. Hasilnya, waktu kami lebih banyak dipakai untuk kopinya.</p>
        </div>
    </div>
</section>

<section class="section" style="background:var(--p-paper-2);">
    <div class="wrap">
        <div class="kicker"><b>02</b> Prinsip</div>
        <h2 class="h-xl" style="margin-bottom:48px;max-width:18ch;">Tiga hal yang kami jaga di setiap cangkir.</h2>
        <div class="branches" style="border-top-color:var(--p-ink);">
            <article class="branch">
                <span class="code">01</span>
                <h3>Konsisten</h3>
                <p>Resep tertulis, takaran ditimbang. Es kopi susu di Kemang harus sama rasanya dengan yang di Menteng.</p>
            </article>
            <article class="branch">
                <span class="code">02</span>
                <h3>Hangat</h3>
                <p>Pelanggan bukan nomor antrean. Barista kami hafal pesanan langganan, dan senang ditanya soal biji kopi.</p>
            </article>
            <article class="branch">
                <span class="code">03</span>
                <h3>Terbuka</h3>
                <p>Harga ditulis jelas di menu, termasuk per varian. Tidak ada biaya tambahan yang muncul di akhir.</p>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap split">
        <div class="sticky-col">
            <div class="kicker"><b>03</b> Orang-orangnya</div>
            <h2 class="h-xl">Di balik bar.</h2>
        </div>
        <div>
            <?php foreach ($team as $m): ?>
                <div class="menu-row" style="grid-template-columns:1fr auto;">
                    <div>
                        <h3 style="font-size:1.6rem;"><?= e($m[0]) ?></h3>
                        <p><?= e($m[2]) ?></p>
                    </div>
                    <div class="price" style="font-weight:500;color:var(--p-accent);"><?= e($m[1]) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section night">
    <div class="wrap split even" style="align-items:end;">
        <h2 class="display" style="font-size:clamp(2.4rem,6vw,4.6rem);">Mampir, <em style="color:#E8A27F;">ngobrol.</em></h2>
        <div>
            <p class="lead">Datang ke salah satu cabang, atau kirim pesan untuk kerja sama, acara, dan pertanyaan lain.</p>
            <div class="hero-actions" style="margin-top:24px;">
                <a href="<?= $COMPANY_BASE ?>/contact.php" class="btn-p on-dark">Cabang &amp; kontak</a>
                <a href="<?= $COMPANY_BASE ?>/menu.php" class="btn-p on-dark ghost">Lihat menu</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
