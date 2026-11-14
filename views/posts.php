<?php
// views/posts.php — Admin CRUD Blog Posts (List View)
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager']);

$page_title = 'Artikel & Blog';
$active_menu = 'posts';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Kelola Artikel</h4>
        <small class="text-muted">Tulis, edit, dan kelola artikel blog Tokopi</small>
    </div>
    <a href="/tokopi/views/post_form.php" class="btn btn-espresso">
        <i class="bi bi-plus-lg me-1"></i> Tulis Artikel Baru
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Tanggal Publish</th>
                        <th width="140" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT p.id, p.title, p.slug, p.status, p.published_at, p.created_at, u.nama AS author_name
                        FROM posts p
                        LEFT JOIN users u ON u.id = p.author_id
                        ORDER BY p.created_at DESC
                    ");
                    $stmt->execute();
                    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (empty($posts)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-25"></i>
                                Belum ada artikel. Klik <strong>Tulis Artikel Baru</strong> untuk memulai.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($posts as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= e($p['title']) ?></strong>
                                <?php if ($p['slug']): ?>
                                <div class="small text-muted">/blog/<?= e($p['slug']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($p['author_name'] ?: '—') ?></td>
                            <td class="text-center">
                                <?php if ($p['status'] === 'published'): ?>
                                    <span class="badge bg-success">Published</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['published_at']): ?>
                                    <span class="small"><?= date('d M Y H:i', strtotime($p['published_at'])) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="/tokopi/views/post_form.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-espresso" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="/tokopi/actions/post_delete.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus artikel ini?')" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>