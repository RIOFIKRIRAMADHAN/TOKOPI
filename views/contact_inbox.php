<?php
// views/contact_inbox.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager']);

$page_title = 'Inbox Pesan';
$active_menu = 'contact_inbox';

// Ambil semua pesan
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Kotak Masuk</h4>
        <p class="text-muted small mb-0">Pesan dari form kontak Company Profile.</p>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="bi bi-check-circle me-2"></i> <?= e($_GET['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="border-radius:1rem; overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead style="background:#FAF9F5;">
                <tr>
                    <th class="ps-4" style="width:50px;">Status</th>
                    <th>Pengirim</th>
                    <th>Subjek</th>
                    <th>Tanggal</th>
                    <th class="text-end pe-4" style="width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada pesan masuk.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <tr style="<?= $m['is_read'] ? 'opacity:0.7;' : 'font-weight:600;' ?>">
                            <td class="ps-4">
                                <?php if (!$m['is_read']): ?>
                                    <span class="badge bg-danger rounded-circle p-1" title="Belum dibaca" style="width:10px;height:10px;">&nbsp;</span>
                                <?php else: ?>
                                    <i class="bi bi-check2-all text-success"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="color:var(--espresso);"><?= e($m['nama']) ?></div>
                                <div class="small text-muted"><?= e($m['email']) ?></div>
                            </td>
                            <td>
                                <div style="color:var(--espresso);"><?= e($m['subject']) ?></div>
                                <div class="small text-muted text-truncate" style="max-width:300px;">
                                    <?= e($m['message']) ?>
                                </div>
                            </td>
                            <td class="small text-muted">
                                <?= date('d M Y, H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td class="text-end pe-4">
                                <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalPesan<?= $m['id'] ?>">
                                    <i class="bi bi-envelope-open"></i> Baca
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Baca Pesan -->
                        <div class="modal fade" id="modalPesan<?= $m['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="border-radius:1rem; border:none;">
                                    <div class="modal-header border-bottom-0 pb-0">
                                        <h5 class="modal-title"><?= e($m['subject']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="d-flex align-items-center gap-3 mb-4">
                                            <div class="user-avatar bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.2rem;">
                                                <?= strtoupper(substr($m['nama'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold" style="color:var(--espresso);"><?= e($m['nama']) ?></div>
                                                <div class="small text-muted"><?= e($m['email']) ?> <?= $m['phone'] ? '· '.e($m['phone']) : '' ?></div>
                                            </div>
                                            <div class="ms-auto small text-muted">
                                                <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="p-3 rounded" style="background:#FAF9F5; min-height:100px; white-space:pre-wrap;"><?= e($m['message']) ?></div>
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0">
                                        <?php if (!$m['is_read']): ?>
                                            <a href="/tokopi/actions/contact_mark_read.php?id=<?= $m['id'] ?>&action=read" class="btn btn-espresso">
                                                <i class="bi bi-check2-circle me-1"></i> Tandai Dibaca
                                            </a>
                                        <?php endif; ?>
                                        <a href="/tokopi/actions/contact_mark_read.php?id=<?= $m['id'] ?>&action=delete" class="btn btn-outline-danger" onclick="return confirm('Hapus pesan ini permanen?');">
                                            <i class="bi bi-trash"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>