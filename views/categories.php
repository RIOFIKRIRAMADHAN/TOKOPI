<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$page_title = 'Kategori';
$active_menu = 'categories';
$tenant_id = current_tenant_id();

// Ambil semua kategori tenant ini
$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS total_produk
    FROM categories c
    WHERE c.tenant_id = ?
    ORDER BY c.sort_order ASC, c.id ASC
");
$stmt->execute([$tenant_id]);
$categories = $stmt->fetchAll();

// Ambil flash message
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
        <h5 class="mb-0">Daftar Kategori</h5>
        <small class="text-muted">Total: <?= count($categories) ?> kategori</small>
    </div>
    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
    </button>
</div>

<div class="card">
    <div class="d-flex justify-content-end p-2 border-bottom"><div class="table-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" class="form-control form-control-sm" placeholder="Cari kategori…" aria-label="Cari kategori" data-table-filter="#tbl_categories"></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tbl_categories" data-enhance data-page-size="25">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Nama</th>
                    <th>Slug</th>
                    <th width="120" class="text-center">Urutan</th>
                    <th width="120" class="text-center">Produk</th>
                    <th width="120" class="text-center">Status</th>
                    <th data-nosort width="160" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Belum ada kategori. Klik "Tambah Kategori" untuk memulai.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $i => $c): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= e($c['nama']) ?></strong></td>
                            <td><code><?= e($c['slug']) ?></code></td>
                            <td class="text-center money"><?= (int)$c['sort_order'] ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark"><?= (int)$c['total_produk'] ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ($c['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-dark"
                                        onclick='openEdit(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="confirmDelete(<?= (int)$c['id'] ?>, '<?= e($c['nama']) ?>', <?= (int)$c['total_produk'] ?>)">
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
        <form action="/tokopi/actions/category_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required
                           placeholder="contoh: Coffee, Non Coffee">
                </div>
                <div class="mb-3">
                    <label class="form-label">Urutan Tampil</label>
                    <input type="number" name="sort_order" class="form-control" value="0" min="0">
                    <small class="text-muted">Angka kecil = tampil lebih dulu.</small>
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
        <form action="/tokopi/actions/category_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Urutan Tampil</label>
                    <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="0">
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
        <form action="/tokopi/actions/category_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapus_id">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Kategori</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus kategori <strong id="hapus_nama"></strong>?</p>
                <div id="hapus_warning" class="alert alert-warning d-none small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Kategori ini memiliki <strong id="hapus_jumlah"></strong> produk.
                    Hapus kategori akan <strong>menghapus semua produk</strong> di dalamnya.
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
    document.getElementById('edit_nama').value        = data.nama;
    document.getElementById('edit_sort_order').value  = data.sort_order;
    document.getElementById('edit_is_active').checked = data.is_active == 1;
    new bootstrap.Modal('#modalEdit').show();
}

function confirmDelete(id, nama, totalProduk) {
    document.getElementById('hapus_id').value = id;
    document.getElementById('hapus_nama').textContent = nama;

    const warn = document.getElementById('hapus_warning');
    if (totalProduk > 0) {
        document.getElementById('hapus_jumlah').textContent = totalProduk;
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }

    new bootstrap.Modal('#modalHapus').show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
