<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner']);

$page_title = 'Outlet';
$active_menu = 'outlets';
$tenant_id = current_tenant_id();

// Ambil semua outlet + statistik
$stmt = $pdo->prepare("
    SELECT o.*,
           (SELECT COUNT(*) FROM users u WHERE u.outlet_id = o.id AND u.is_active = 1) AS total_user,
           (SELECT COUNT(*) FROM orders ord WHERE ord.outlet_id = o.id AND DATE(ord.created_at) = CURDATE()) AS order_hari_ini
    FROM outlets o
    WHERE o.tenant_id = ?
    ORDER BY o.id ASC
");
$stmt->execute([$tenant_id]);
$outlets = $stmt->fetchAll();

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Daftar Outlet / Cabang</h5>
        <small class="text-muted">Total: <?= count($outlets) ?> cabang</small>
    </div>
    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Outlet
    </button>
</div>

<div class="row g-3">
    <?php if (empty($outlets)): ?>
        <div class="col-12">
            <div class="card p-5 text-center text-muted">
                Belum ada outlet. Klik "Tambah Outlet" untuk memulai.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($outlets as $o): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="mb-1" style="font-size:1.1rem;"><?= e($o['nama']) ?></h5>
                                <code><?= e($o['code']) ?></code>
                            </div>
                            <?php if ($o['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($o['alamat']): ?>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-geo-alt me-1"></i><?= e($o['alamat']) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($o['no_hp']): ?>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-telephone me-1"></i><?= e($o['no_hp']) ?>
                            </p>
                        <?php endif; ?>

                        <div class="d-flex gap-3 pt-2 border-top">
                            <div>
                                <div class="text-muted" style="font-size:.7rem; text-transform:uppercase;">Pengguna</div>
                                <div class="money" style="font-weight:700;"><?= (int)$o['total_user'] ?></div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size:.7rem; text-transform:uppercase;">Order Hari Ini</div>
                                <div class="money" style="font-weight:700;"><?= (int)$o['order_hari_ini'] ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-2">
                        <button class="btn btn-sm btn-outline-dark"
                            onclick='openEdit(<?= json_encode($o, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <i class="bi bi-pencil me-1"></i> Edit
                        </button>
                        <?php if (count($outlets) > 1): ?>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= (int)$o['id'] ?>, '<?= e($o['nama']) ?>', <?= (int)$o['total_user'] ?>, <?= (int)$o['order_hari_ini'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Minimal harus ada 1 outlet">
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/outlet_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Outlet Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Outlet <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required
                        placeholder="contoh: TPI-04"
                        pattern="[A-Za-z0-9\-]+" style="text-transform:uppercase;">
                    <small class="text-muted">Kode unik, huruf/angka/minus. Contoh: TPI-01, JKT-02.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Outlet <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required
                        placeholder="contoh: Tokopi - Bandung">
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"
                        placeholder="Alamat lengkap"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="no_hp" class="form-control"
                        placeholder="021-xxxx / 08xx">
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
        <form action="/tokopi/actions/outlet_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Outlet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Outlet <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="edit_code" class="form-control" required style="text-transform:uppercase;">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Outlet <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" id="edit_alamat" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="no_hp" id="edit_no_hp" class="form-control">
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

<!-- MODAL HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/outlet_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapus_id">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Outlet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus outlet <strong id="hapus_nama"></strong>?</p>
                <div id="hapus_warning" class="alert alert-warning small d-none mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Outlet ini punya <strong id="hapus_jumlah_user"></strong> pengguna.
                    Semua data terkait (stok, order) akan <strong>ikut terhapus</strong>.
                </div>
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
        document.getElementById('edit_alamat').value = data.alamat || '';
        document.getElementById('edit_no_hp').value = data.no_hp || '';
        document.getElementById('edit_is_active').checked = data.is_active == 1;
        new bootstrap.Modal('#modalEdit').show();
    }

    function confirmDelete(id, nama, totalUser) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('hapus_nama').textContent = nama;

        const warn = document.getElementById('hapus_warning');
        if (totalUser > 0) {
            document.getElementById('hapus_jumlah_user').textContent = totalUser;
            warn.classList.remove('d-none');
        } else {
            warn.classList.add('d-none');
        }
        new bootstrap.Modal('#modalHapus').show();
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>