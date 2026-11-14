<?php
// company/blog.php — Halaman Daftar Blog & Berita
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Blog & Berita';
$active_nav = 'blog';
require_once __DIR__ . '/../includes/header_public.php';
?>

<!-- ============ PAGE HERO ============ -->
<section class="page-hero" style="background: linear-gradient(135deg, #2D1B0A 0%, #1A0F06 100%); color: #fff; padding: 6rem 0 4rem; position: relative; overflow: hidden;">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <span class="eyebrow">Artikel & Berita Terbaru</span>
                <h1 class="display-4 fw-bold mt-2 mb-3">Blog & Berita Tokopi</h1>
                <p class="lead mb-0" style="opacity: 0.85;">Cerita kopi, tips menyeduh, update menu, dan hal-hal hangat dari tim Tokopi.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ BLOG GRID ============ -->
<section style="padding: 5rem 0; background: var(--bg-alt);">
    <div class="container">
        <?php
        // Ambil artikel published
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.slug, p.excerpt, p.published_at, u.nama AS author_name
            FROM posts p
            LEFT JOIN users u ON u.id = p.author_id
            WHERE p.status = 'published'
            ORDER BY p.published_at DESC
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php
    // Mapping gambar cover untuk blog (berdasarkan index)
    $blog_images = [
        0 => 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?q=80&w=800&auto=format&fit=crop', // Rahasia Menyeduh Kopi
        1 => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?q=80&w=800&auto=format&fit=crop', // Arabika vs Robusta
        2 => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?q=80&w=800&auto=format&fit=crop', // Promo Akhir Pekan
    ];
    if (empty($posts)): ?>
            <!-- Empty State -->
            <div class="text-center py-5 fade-in-up">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; background: var(--color-sand-light); color: var(--color-espresso);">
                    <i class="bi bi-journal-text" style="font-size: 2rem;"></i>
                </div>
                <h3 class="text-espresso fw-bold mb-2">Belum Ada Artikel</h3>
                <p class="text-muted mb-4">Tim kami sedang menyiapkan cerita-cerita menarik. Cek kembali nanti ya!</p>
                <a href="<?= $COMPANY_BASE ?>/index.php" class="btn btn-espresso">
                    <i class="bi bi-house-door me-2"></i> Kembali ke Beranda
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($posts as $i => $post):
                    $cover_img = $blog_images[$i % count($blog_images)] ?? 'https://placehold.co/800x450?text=Tokopi';
                ?>
                <div class="col-md-6 col-lg-4 fade-in-up" style="transition-delay: <?= $i * 100 ?>ms;">
                    <article class="card h-100 border-0 shadow-sm hover-shadow transition-all duration-300" style="border-radius: var(--radius-xl); overflow: hidden;">
                        <div class="card-cover" style="height: 200px; overflow: hidden;">
                            <img src="<?= $cover_img ?>"
                                 alt="<?= e($post['title']) ?>"
                                 loading="lazy"
                                 style="width: 100%; height: 100%; object-fit: cover;"
                                 onerror="this.onerror=null; this.src='https://placehold.co/800x450?text=Tokopi';">
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                                <i class="bi bi-calendar3"></i>
                                <time datetime="<?= date('c', strtotime($post['published_at'])) ?>">
                                    <?= date('d M Y', strtotime($post['published_at'])) ?>
                                </time>
                                <?php if (!empty($post['author_name'])): ?>
                                <span class="mx-1">·</span>
                                <i class="bi bi-person"></i>
                                <span><?= e($post['author_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title text-espresso fw-bold mb-3" style="font-family: var(--font-serif); font-size: 1.15rem; line-height: 1.3;">
                                <a href="<?= $COMPANY_BASE ?>/blog_detail.php?slug=<?= e($post['slug']) ?>" class="text-decoration-none text-espresso hover-text-accent transition-colors">
                                    <?= e($post['title']) ?>
                                </a>
                            </h3>
                            <p class="card-text text-muted small flex-grow-1 mb-3" style="line-height: 1.6;">
                                <?= e($post['excerpt'] ?: 'Baca selengkapnya...') ?>
                            </p>
                            <a href="<?= $COMPANY_BASE ?>/blog_detail.php?slug=<?= e($post['slug']) ?>" class="btn btn-sm btn-outline-espresso mt-auto align-self-start fw-semibold">
                                Baca Selengkapnya <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>