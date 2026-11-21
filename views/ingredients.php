<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$page_title = 'Bahan Baku';
$active_menu = 'ingredients';
$tenant_id = current_tenant_id();

// Ambil semua bahan + jumlah resep yang pakai
$stmt = $pdo->prepare("
    SELECT i.*,
           (SELECT COUNT(*) FROM recipe_items ri WHERE ri.ingredient_id = i.id) AS total_resep
    FROM ingredients i
    WHERE i.tenant_id = ?
    ORDER BY i.category ASC, i.nama ASC
");
$stmt->execute([$tenant_id]);
$ingredients = $stmt->fetchAll();

// Kelompok untuk dropdown kategori
$categories = [];
foreach ($ingredients as $ing) {
    $cat = $ing['category'] ?: 'Lainnya';
    if (!isset($categories[$cat])) $categories[$cat] = 0;
    $categories[$cat]++;
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Daftar Bahan Baku</h5>
        <small class="text-muted">Total: <?= count($ingredients) ?> bahan</small>
    </div>
    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Bahan
    </button>
</div>

<div class="card">
    <div class="d-flex justify-content-end p-2 border-bottom"><div class="table-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" class="form-control form-control-sm" placeholder="Cari bahan baku…" aria-label="Cari bahan baku" data-table-filter="#tbl_ingredients"></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tbl_ingredients" data-enhance data-page-size="25">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th width="120">Kode</th>
                    <th>Nama Bahan</th>
                    <th width="140">Kategori</th>
                    <th width="100" class="text-center">Unit</th>
                    <th width="160" class="text-end">HPP / Unit</th>
                    <th width="100" class="text-center">Dipakai</th>
                    <th width="110" class="text-center">Status</th>
                    <th data-nosort width="140" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ingredients)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Belum ada bahan baku. Klik "Tambah Bahan" untuk memulai.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ingredients as $i => $ing): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><code><?= e($ing['code']) ?></code></td>
                            <td><strong><?= e($ing['nama']) ?></strong></td>
                            <td>
                                <?php if ($ing['category']): ?>
                                    <span class="badge bg-light text-dark"><?= e($ing['category']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge bg-dark"><?= e($ing['base_unit']) ?></span></td>
                            <td class="text-end money">
                                <?php if ((float)$ing['avg_cost'] > 0): ?>
                                    <?= rupiah($ing['avg_cost']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Belum diset</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$ing['total_resep'] > 0): ?>
                                    <span class="badge bg-info text-dark"><?= (int)$ing['total_resep'] ?> resep</span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($ing['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-dark"
                                        onclick='openEdit(<?= json_encode($ing, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDelete(<?= (int)$ing['id'] ?>, '<?= e($ing['nama']) ?>', <?= (int)$ing['total_resep'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/ingredient_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Bahan Baku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Bahan <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required
                           placeholder="contoh: BHN-001"
                           pattern="[A-Za-z0-9\-]+">
                    <small class="text-muted">Kode unik per tenant.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Bahan <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required
                           placeholder="contoh: Biji Espresso House Blend">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Unit Dasar <span class="text-danger">*</span></label>
                        <select name="base_unit" class="form-select" required>
                            <option value="G">Gram (G)</option>
                            <option value="ML">Mililiter (ML)</option>
                            <option value="PCS">Pieces (PCS)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="category" class="form-control"
                               placeholder="Coffee, Dairy, Packaging">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">HPP Awal / Unit</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="avg_cost" class="form-control" min="0" step="0.01"
                               placeholder="0">
                    </div>
                    <small class="text-muted">
                        Harga beli per unit dasar. Contoh: kalau 1 kg biji = Rp 200.000, maka per gram = Rp 200.
                    </small>
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
        <form action="/tokopi/actions/ingredient_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Bahan Baku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kode Bahan <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="edit_code" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Bahan <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Unit Dasar <span class="text-danger">*</span></label>
                        <select name="base_unit" id="edit_base_unit" class="form-select" required>
                            <option value="G">Gram (G)</option>
                            <option value="ML">Mililiter (ML)</option>
                            <option value="PCS">Pieces (PCS)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="category" id="edit_category" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">HPP / Unit</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="avg_cost" id="edit_avg_cost" class="form-control" min="0" step="0.01">
                    </div>
                    <small class="text-muted">
                        Kalau ada perubahan harga beli, sebaiknya update HPP lewat modul Penerimaan Barang (GRN) supaya tercatat.
                    </small>
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
        <form action="/tokopi/actions/ingredient_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapus_id">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Bahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus bahan <strong id="hapus_nama"></strong>?</p>
                <div id="hapus_warning" class="alert alert-warning d-none small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Bahan ini dipakai di <strong id="hapus_jumlah"></strong> resep.
                    Hapus bahan akan <strong>gagal</strong> kalau masih dipakai resep.
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
    document.getElementById('edit_id').value          = data.id;
    document.getElementById('edit_code').value        = data.code;
    document.getElementById('edit_nama').value        = data.nama;
    document.getElementById('edit_base_unit').value   = data.base_unit;
    document.getElementById('edit_category').value    = data.category || '';
    document.getElementById('edit_avg_cost').value    = parseFloat(data.avg_cost);
    document.getElementById('edit_is_active').checked = data.is_active == 1;
    new bootstrap.Modal('#modalEdit').show();
}

function confirmDelete(id, nama, totalResep) {
    document.getElementById('hapus_id').value = id;
    document.getElementById('hapus_nama').textContent = nama;

    const warn = document.getElementById('hapus_warning');
    if (totalResep > 0) {
        document.getElementById('hapus_jumlah').textContent = totalResep;
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }
    new bootstrap.Modal('#modalHapus').show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>