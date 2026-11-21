<?php
// company/blog_detail.php — Baca artikel
require_once __DIR__ . '/../config/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    redirect('/tokopi/company/blog.php');
}

$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.published_at, u.nama AS author_name
    FROM posts p
    LEFT JOIN users u ON u.id = p.author_id
    WHERE p.slug = ? AND p.status = 'published'
    LIMIT 1
");
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    redirect('/tokopi/company/blog.php');
}

// Artikel lain
$stmt = $pdo->prepare("SELECT title, slug, published_at FROM posts WHERE status = 'published' AND id <> ? ORDER BY published_at DESC LIMIT 3");
$stmt->execute([$post['id']]);
$others = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = $post['title'];
$page_desc  = $post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 150);
$active_nav = 'blog';
require_once __DIR__ . '/../includes/header_public.php';

$bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$t = strtotime($post['published_at']);
?>

<article>
    <header class="page-intro">
        <div class="wrap" style="max-width:860px;">
            <div class="crumb"><a href="<?= $COMPANY_BASE ?>/blog.php">Jurnal</a> / Artikel</div>
            <h1 style="font-size:clamp(2.2rem,5vw,3.8rem);"><?= e($post['title']) ?></h1>
            <p class="muted mono" style="margin-top:20px;font-size:13.5px;">
                <?= e($post['author_name'] ?: 'Tim Tokopi') ?> · <time datetime="<?= date('c', $t) ?>"><?= date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t) ?></time> · <?= read_minutes($post['content']) ?> menit baca
            </p>
        </div>
    </header>

    <div class="section-tight">
        <div class="wrap" style="max-width:860px;">
            <?php if (!empty($post['excerpt'])): ?>
                <p class="lead" style="font-family:var(--p-display);font-size:clamp(1.25rem,2vw,1.5rem);color:var(--p-ink);line-height:1.45;margin-bottom:32px;"><?= e($post['excerpt']) ?></p>
            <?php endif; ?>
            <div class="prose">
                <?= $post['content'] ?>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-top:56px;padding-top:24px;border-top:1px solid var(--p-line);">
                <a class="link-arrow" href="<?= $COMPANY_BASE ?>/blog.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Semua tulisan</a>
                <button type="button" class="btn-p ghost" id="shareBtn" style="min-height:44px;"><i class="bi bi-share" aria-hidden="true"></i> <span>Bagikan</span></button>
            </div>
        </div>
    </div>
</article>

<?php if ($others): ?>
<section class="section-tight" style="background:var(--p-paper-2);">
    <div class="wrap">
        <div class="kicker"><b>Baca juga</b></div>
        <div class="posts">
            <?php foreach ($others as $o): ?>
                <a class="post" href="<?= $COMPANY_BASE ?>/blog_detail.php?slug=<?= urlencode($o['slug']) ?>">
                    <div class="post-cover <?= post_cover_tone($o['title']) ?>" aria-hidden="true" style="aspect-ratio:16/8;"><span><?= e($o['title']) ?></span></div>
                    <h2 style="font-size:1.25rem;"><?= e($o['title']) ?></h2>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
document.getElementById('shareBtn').addEventListener('click', async function () {
    var data = { title: <?= json_encode($post['title']) ?>, url: location.href };
    var label = this.querySelector('span');
    try {
        if (navigator.share) { await navigator.share(data); return; }
        await navigator.clipboard.writeText(location.href);
        label.textContent = 'Link disalin';
        setTimeout(function () { label.textContent = 'Bagikan'; }, 2000);
    } catch (e) { /* dibatalkan pengguna */ }
});
</script>

<?php require_once __DIR__ . '/../includes/footer_public.php'; ?>
