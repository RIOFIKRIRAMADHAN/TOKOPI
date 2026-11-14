<?php
// company/blog_detail.php — Halaman Baca Artikel
require_once __DIR__ . '/../config/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    redirect($COMPANY_BASE . '/blog.php');
}

// Ambil artikel berdasarkan slug, hanya yang published
$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.published_at, u.nama AS author_name
    FROM posts p
    LEFT JOIN users u ON u.id = p.author_id
    WHERE p.slug = ? AND p.status = 'published'
    LIMIT 1
");
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika tidak ditemukan atau draft, redirect ke blog
if (!$post) {
    redirect($COMPANY_BASE . '/blog.php');
}

$page_title = $post['title'];
$active_nav = 'blog';
require_once __DIR__ . '/../includes/header_public.php';
?>

<?php
    // Mapping gambar cover untuk blog detail (berdasarkan id atau slug)
    $blog_images = [
        0 => 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?q=80&w=800&auto=format&fit=crop',
        1 => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?q=80&w=800&auto=format&fit=crop',
        2 => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?q=80&w=800&auto=format&fit=crop',
    ];
    $cover_img = $blog_images[($post['id'] ?? 0) % count($blog_images)] ?? 'https://placehold.co/1200x400?text=Tokopi';
?>

<!-- ============ ARTICLE HERO WITH COVER ============ -->
<section class="page-hero" style="background: linear-gradient(135deg, #2D1B0A 0%, #1A0F06 100%); color: #fff; padding: 6rem 0 4rem; position: relative; overflow: hidden;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 text-center">
                <nav aria-label="Breadcrumb" class="mb-3">
                    <ol class="breadcrumb justify-content-center mb-0" style="background: transparent; padding: 0;">
                        <li class="breadcrumb-item"><a href="<?= $COMPANY_BASE ?>/index.php" class="text-sand text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= $COMPANY_BASE ?>/blog.php" class="text-sand text-decoration-none">Blog</a></li>
                        <li class="breadcrumb-item active text-warning" aria-current="page"><?= e($post['title']) ?></li>
                    </ol>
                </nav>
                <h1 class="display-4 fw-bold mt-3 mb-3" style="font-family: var(--font-serif);"><?= e($post['title']) ?></h1>
                <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap text-muted small">
                    <span><i class="bi bi-person me-1"></i><?= e($post['author_name'] ?: 'Tim Tokopi') ?></span>
                    <span><i class="bi bi-calendar3 me-1"></i><?= date('d F Y', strtotime($post['published_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ ARTICLE COVER IMAGE ============ -->
<section style="padding: 0 0 3rem; background: var(--bg-alt); margin-top: -4rem; position: relative; z-index: 1;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div style="border-radius: var(--radius-xl); overflow: hidden; box-shadow: var(--shadow-xl); max-height: 400px;">
                    <img src="<?= $cover_img ?>"
                         alt="<?= e($post['title']) ?>"
                         loading="lazy"
                         style="width: 100%; height: auto; max-height: 400px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='https://placehold.co/1200x400?text=Tokopi';">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ ARTICLE CONTENT ============ -->
<section style="padding: 3rem 0 5rem; background: var(--bg-alt);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article class="card border-0 shadow-sm" style="border-radius: var(--radius-xl); overflow: hidden;">
                    <div class="card-body p-4 p-lg-5">
                        <?php if (!empty($post['excerpt'])): ?>
                        <div class="lead text-muted mb-4 pb-4 border-bottom" style="font-family: var(--font-serif); font-size: 1.15rem; line-height: 1.6; color: var(--color-text-muted) !important;">
                            <?= e($post['excerpt']) ?>
                        </div>
                        <?php endif; ?>

                        <div class="article-content" style="font-size: 1.05rem; line-height: 1.8; color: var(--color-text);">
                            <?= $post['content'] ?>
                        </div>

                        <hr class="my-5" style="border-color: var(--color-border);">

                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                            <a href="<?= $COMPANY_BASE ?>/blog.php" class="btn btn-outline-espresso fw-semibold">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Blog
                            </a>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-sand fw-semibold" onclick="navigator.share?.({title: '<?= addslashes($post['title']) ?>', text: '', url: window.location.href}) || alert('Fitur share belum didukung browser ini')">
                                    <i class="bi bi-share me-1"></i> Share
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

<!-- Inline style khusus untuk content Summernote -->
<style>
    .article-content img { max-width: 100%; height: auto; border-radius: var(--radius-lg); }
    .article-content p { margin-bottom: 1.25rem; }
    .article-content h1, .article-content h2, .article-content h3 { font-family: var(--font-serif); color: var(--color-espresso); margin-top: 2rem; margin-bottom: 1rem; }
    .article-content ul, .article-content ol { margin-bottom: 1.25rem; padding-left: 1.5rem; }
    .article-content li { margin-bottom: 0.5rem; }
    .article-content blockquote { border-left: 4px solid var(--color-sand); padding-left: 1.5rem; margin: 1.5rem 0; font-style: italic; color: var(--color-text-muted); }
    .article-content strong { color: var(--color-espresso); }
    .article-content a { color: var(--color-espresso); text-decoration: underline; }
    .article-content a:hover { color: var(--color-espresso-dark); }
</style>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>