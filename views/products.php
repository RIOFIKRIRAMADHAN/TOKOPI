<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$page_title = 'Produk';
$active_menu = 'products';
$tenant_id = current_tenant_id();

// Ambil semua produk + kategori + jumlah varian
$stmt = $pdo->prepare("
    SELECT p.*, c.nama AS kategori_nama,
           (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id) AS total_varian,
           (SELECT MIN(harga_jual) FROM product_variants v WHERE v.product_id = p.id) AS harga_min,
           (SELECT MAX(harga_jual) FROM product_variants v WHERE v.product_id = p.id) AS harga_max
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.tenant_id = ?
    ORDER BY p.id DESC
");
$stmt->execute([$tenant_id]);
$products = $stmt->fetchAll();

// Kategori untuk dropdown
$stmt = $pdo->prepare("SELECT id, nama FROM categories WHERE tenant_id = ? AND is_active = 1 ORDER BY sort_order, nama");
$stmt->execute([$tenant_id]);
$categories = $stmt->fetchAll();

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
        <h5 class="mb-0">Daftar Produk</h5>
        <small class="text-muted">Total: <?= count($products) ?> produk</small>
    </div>
    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Produk
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th width="120" class="text-center">Varian</th>
                    <th width="180" class="text-end">Rentang Harga</th>
                    <th width="110" class="text-center">Status</th>
                    <th width="160" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Belum ada produk. Klik "Tambah Produk" untuk memulai.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= e($p['nama']) ?></strong></td>
                            <td><span class="badge bg-light text-dark"><?= e($p['kategori_nama']) ?></span></td>
                            <td class="text-center">
                                <?php if ($p['total_varian'] > 0): ?>
                                    <span class="badge bg-dark"><?= (int)$p['total_varian'] ?> varian</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Belum ada</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end money">
                                <?php if ($p['harga_min']): ?>
                                    <?php if ($p['harga_min'] == $p['harga_max']): ?>
                                        <?= rupiah($p['harga_min']) ?>
                                    <?php else: ?>
                                        <?= rupiah($p['harga_min']) ?> – <?= rupiah($p['harga_max']) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/tokopi/views/product_detail.php?id=<?= (int)$p['id'] ?>"
                                   class="btn btn-sm btn-outline-dark" title="Kelola Varian">
                                    <i class="bi bi-list-ul"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-dark" title="Edit"
                                        onclick='openEdit(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" title="Hapus"
                                        onclick="confirmDelete(<?= (int)$p['id'] ?>, '<?= e($p['nama']) ?>', <?= (int)$p['total_varian'] ?>)">
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
        <form action="/tokopi/actions/product_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required
                           placeholder="contoh: Es Kopi Susu">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">— Pilih Kategori —</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Opsional"></textarea>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="add_active" checked>
                    <label class="form-check-label" for="add_active">Aktif</label>
                </div>
                <div class="alert alert-info small mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Setelah produk dibuat, tambahkan <strong>varian</strong> (ukuran, suhu, harga)
                    di halaman detail produk.
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
        <form action="/tokopi/actions/product_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" id="edit_category_id" class="form-select" required>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"><?= e($c['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
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
        <form action="/tokopi/actions/product_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapus_id">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus produk <strong id="hapus_nama"></strong>?</p>
                <div id="hapus_warning" class="alert alert-warning d-none small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Produk ini punya <strong id="hapus_jumlah"></strong> varian.
                    Menghapus produk akan <strong>menghapus semua varian & resep</strong>-nya.
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
    document.getElementById('edit_id').value            = data.id;
    document.getElementById('edit_nama').value          = data.nama;
    document.getElementById('edit_category_id').value   = data.category_id;
    document.getElementById('edit_description').value   = data.description || '';
    document.getElementById('edit_is_active').checked   = data.is_active == 1;
    new bootstrap.Modal('#modalEdit').show();
}

function confirmDelete(id, nama, totalVarian) {
    document.getElementById('hapus_id').value = id;
    document.getElementById('hapus_nama').textContent = nama;

    const warn = document.getElementById('hapus_warning');
    if (totalVarian > 0) {
        document.getElementById('hapus_jumlah').textContent = totalVarian;
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }
    new bootstrap.Modal('#modalHapus').show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
EOF