<?php
// company/blog.php — Jurnal
require_once __DIR__ . '/../config/bootstrap.php';

$page_title = 'Jurnal';
$page_desc  = 'Cerita kopi, tips menyeduh di rumah, dan kabar terbaru dari Tokopi.';
$active_nav = 'blog';
require_once __DIR__ . '/../includes/header_public.php';

$posts = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.published_at, u.nama AS author_name
        FROM posts p
        LEFT JOIN users u ON u.id = p.author_id
        WHERE p.status = 'published'
        ORDER BY p.published_at DESC
    ");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[company/blog] ' . $e->getMessage());
}
$bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$tgl = function ($d) use ($bulan) { $t = strtotime($d); return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t); };
?>

<section class="page-intro">
    <div class="wrap">
        <div class="kicker"><b><?= count($posts) ?></b> tulisan</div>
        <h1>Jurnal</h1>
        <p class="lead">Catatan dari bar: cara menyeduh, cerita biji kopi, dan kabar promo.</p>
    </div>
</section>

<section class="section-tight">
    <div class="wrap">
        <?php if (empty($posts)): ?>
            <h2 class="h-lg">Belum ada tulisan.</h2>
            <p class="lead" style="margin-top:12px;">Tim kami sedang menulis. Sementara itu, <a href="<?= $COMPANY_BASE ?>/menu.php" style="color:var(--p-accent);">lihat menu</a>.</p>
        <?php else: ?>
            <div class="posts">
                <?php foreach ($posts as $i => $post):
                    $url = $COMPANY_BASE . '/blog_detail.php?slug=' . urlencode($post['slug']);
                    $meta = $tgl($post['published_at']) . ' · ' . read_minutes($post['content']) . ' menit baca';
                ?>
                    <a class="post <?= $i === 0 ? 'post-featured' : '' ?>" href="<?= e($url) ?>">
                        <div class="post-cover <?= post_cover_tone($post['title']) ?>" aria-hidden="true">
                            <span><?= e($post['title']) ?></span>
                        </div>
                        <div>
                            <div class="meta"><?= e($meta) ?></div>
                            <h2><?= e($post['title']) ?></h2>
                            <?php if (!empty($post['excerpt'])): ?><p><?= e($post['excerpt']) ?></p><?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
