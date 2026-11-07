<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager']);

$page_title = 'Meja & QR Code';
$active_menu = 'tables';
$tenant_id = current_tenant_id();

// Owner bisa pilih outlet, manager terkunci
if (is_role('owner')) {
    $filter_outlet = (int)($_GET['outlet'] ?? 0);
} else {
    $filter_outlet = current_outlet_id();
}

// Ambil outlets
$stmt = $pdo->prepare("SELECT id, code, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
$stmt->execute([$tenant_id]);
$outlets = $stmt->fetchAll();

// Kalau owner belum pilih, ambil outlet pertama
if ($filter_outlet <= 0 && !empty($outlets)) {
    $filter_outlet = (int)$outlets[0]['id'];
}

// Ambil meja di outlet terpilih
$tables = [];
if ($filter_outlet > 0) {
    $stmt = $pdo->prepare("
        SELECT t.*,
            (SELECT COUNT(*) FROM orders o WHERE o.table_id = t.id AND o.source = 'QR' AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')) AS total_order
        FROM `tables` t
        WHERE t.outlet_id = ?
        ORDER BY t.code ASC
    ");
    $stmt->execute([$filter_outlet]);
    $tables = $stmt->fetchAll();
}

$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter + Tombol Aksi -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 align-items-center">
        <label class="text-muted small mb-0">Outlet:</label>
        <select class="form-select form-select-sm" style="width:auto;"
                onchange="window.location.href='?outlet=' + this.value">
            <?php foreach ($outlets as $o): ?>
                <option value="<?= (int)$o['id'] ?>" <?= $filter_outlet === (int)$o['id'] ? 'selected' : '' ?>>
                    <?= e($o['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="badge bg-light text-dark"><?= count($tables) ?> meja</span>
    </div>

    <div class="d-flex gap-2">
        <a href="/tokopi/views/tables_print.php?outlet=<?= (int)$filter_outlet ?>" target="_blank"
           class="btn btn-outline-dark btn-sm">
            <i class="bi bi-printer me-1"></i> Print Semua QR
        </a>
        <button class="btn btn-espresso btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-1"></i> Tambah Meja
        </button>
    </div>
</div>

<!-- Grid Meja -->
<div class="row g-3">
    <?php if (empty($tables)): ?>
        <div class="col-12">
            <div class="card p-5 text-center text-muted">
                Belum ada meja di outlet ini. Klik "Tambah Meja" untuk mulai.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tables as $t): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-0" style="font-size:1.1rem;"><?= e($t['nama']) ?></h5>
                                <code style="font-size:0.75rem;"><?= e($t['code']) ?></code>
                            </div>
                            <?php if ($t['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </div>

                        <div class="text-muted small mb-3">
                            <i class="bi bi-receipt me-1"></i><?= (int)$t['total_order'] ?> order QR
                        </div>

                        <!-- QR Preview -->
                        <div class="text-center mb-3 p-2 rounded" style="background:#FAF9F5;">
                            <?php
                            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                                      . '://' . $_SERVER['HTTP_HOST'];
                            $qr_url = $base_url . '/tokopi/customer/t.php?token=' . urlencode($t['qr_token']);
                            ?>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qr_url) ?>"
                                 alt="QR <?= e($t['code']) ?>"
                                 style="width:120px;height:120px;background:#fff;padding:6px;border-radius:6px;">
                        </div>

                        <div class="text-muted small mb-0" style="word-break:break-all; font-size:0.7rem;">
                            Token: <code><?= e(substr($t['qr_token'], 0, 16)) ?>...</code>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-1">
                        <a href="/tokopi/views/table_detail.php?id=<?= (int)$t['id'] ?>" class="btn btn-sm btn-outline-dark">
                            <i class="bi bi-qr-code"></i> QR
                        </a>
                        <button class="btn btn-sm btn-outline-dark"
                                onclick='openEdit(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-warning"
                                onclick="confirmRotate(<?= (int)$t['id'] ?>, '<?= e($t['nama']) ?>')">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= (int)$t['id'] ?>, '<?= e($t['nama']) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/table_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="outlet_id" value="<?= (int)$filter_outlet ?>">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Meja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Meja <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required
                           placeholder="contoh: A01, B05, VIP-1"
                           pattern="[A-Za-z0-9\-]+" style="text-transform:uppercase;">
                    <small class="text-muted">Kode unik per outlet.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Meja <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required
                           placeholder="contoh: Meja A01, Sofa VIP">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="add_active" checked>
                    <label class="form-check-label" for="add_active">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/table_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Meja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Meja <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="edit_code" class="form-control" required style="text-transform:uppercase;">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Meja <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                    <label class="form-check-label" for="edit_is_active">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ROTATE -->
<div class="modal fade" id="modalRotate" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/table_action.php?action=rotate" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="rotate_id">
            <div class="modal-header">
                <h5 class="modal-title">Rotate Token QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Token QR akan diganti. <strong>QR lama tidak akan berfungsi lagi.</strong>
                </div>
                <p>Yakin rotate token untuk <strong id="rotate_nama"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning">Rotate Token</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/table_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapus_id">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Meja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin hapus meja <strong id="hapus_nama"></strong>?</p>
                <p class="text-muted small mb-0">Order QR yang pernah masuk dari meja ini tetap tersimpan.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_code').value = data.code;
    document.getElementById('edit_nama').value = data.nama;
    document.getElementById('edit_is_active').checked = data.is_active == 1;
    new bootstrap.Modal('#modalEdit').show();
}

function confirmDelete(id, nama) {
    document.getElementById('hapus_id').value = id;
    document.getElementById('hapus_nama').textContent = nama;
    new bootstrap.Modal('#modalHapus').show();
}

function confirmRotate(id, nama) {
    document.getElementById('rotate_id').value = id;
    document.getElementById('rotate_nama').textContent = nama;
    new bootstrap.Modal('#modalRotate').show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
