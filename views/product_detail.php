<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'gudang']);

$tenant_id  = current_tenant_id();
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    flash_set('error', 'Produk tidak valid.');
    redirect('/tokopi/views/products.php');
}

// Ambil produk
$stmt = $pdo->prepare("
    SELECT p.*, c.nama AS kategori_nama
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.id = ? AND p.tenant_id = ?
");
$stmt->execute([$product_id, $tenant_id]);
$product = $stmt->fetch();

if (!$product) {
    flash_set('error', 'Produk tidak ditemukan.');
    redirect('/tokopi/views/products.php');
}

// Ambil varian
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll();

$page_title = 'Detail Produk: ' . $product['nama'];
$active_menu = 'products';
$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="/tokopi/views/products.php" class="text-decoration-none small" style="color: var(--espresso);">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Produk
    </a>
</div>

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

<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-1"><?= e($product['nama']) ?></h4>
                <span class="badge bg-light text-dark mb-2"><?= e($product['kategori_nama']) ?></span>
                <?php if (!$product['is_active']): ?>
                    <span class="badge bg-secondary mb-2">Nonaktif</span>
                <?php endif; ?>
                <?php if (!empty($product['description'])): ?>
                    <p class="text-muted mt-2 mb-0"><?= e($product['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span class="text-muted small">Total Varian</span>
                    <strong class="money"><?= count($variants) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Varian Produk</h5>
    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambahVarian">
        <i class="bi bi-plus-lg me-1"></i> Tambah Varian
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>SKU</th>
                    <th>Nama Varian</th>
                    <th width="100" class="text-center">Suhu</th>
                    <th width="80" class="text-center">Size</th>
                    <th width="160" class="text-end">Harga</th>
                    <th width="110" class="text-center">Status</th>
                    <th width="140" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($variants)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Belum ada varian. Klik "Tambah Varian" untuk membuat SKU pertama.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($variants as $i => $v): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><code><?= e($v['sku']) ?></code></td>
                            <td><strong><?= e($v['nama']) ?></strong></td>
                            <td class="text-center">
                                <?php if ($v['temperature'] === 'ICED'): ?>
                                    <span class="badge bg-info text-dark"><i class="bi bi-snow"></i> Iced</span>
                                <?php elseif ($v['temperature'] === 'HOT'): ?>
                                    <span class="badge bg-danger"><i class="bi bi-fire"></i> Hot</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?= $v['size'] ? '<span class="badge bg-dark">' . e($v['size']) . '</span>' : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="text-end money"><?= rupiah($v['harga_jual']) ?></td>
                            <td class="text-center">
                                <?php if ($v['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-dark"
                                    onclick='openEditVarian(<?= json_encode($v, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDeleteVarian(<?= (int)$v['id'] ?>, '<?= e($v['nama']) ?>')">
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

<!-- MODAL TAMBAH VARIAN -->
<div class="modal fade" id="modalTambahVarian" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/variant_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Varian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Varian <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required
                        placeholder="contoh: Iced · Regular">
                    <small class="text-muted">Kombinasi suhu + ukuran, contoh "Iced · Large".</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Suhu</label>
                        <select name="temperature" class="form-select">
                            <option value="">— Tidak ada —</option>
                            <option value="HOT">Hot</option>
                            <option value="ICED">Iced</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ukuran</label>
                        <input type="text" name="size" class="form-control" maxlength="10"
                            placeholder="R / L / XL">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" name="sku" class="form-control" required
                        placeholder="contoh: KOPI-ES-R"
                        pattern="[A-Za-z0-9\-]+"
                        title="Hanya huruf, angka, dan tanda minus">
                    <small class="text-muted">Kode unik. Huruf besar semua, tanpa spasi.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="harga_jual" class="form-control" required
                            min="0" step="500" placeholder="18000">
                    </div>
                    <small class="text-muted">Harga pusat. Berlaku di semua outlet.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">HPP Manual (Opsional)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="hpp_manual" class="form-control"
                            min="0" step="100" placeholder="0">
                    </div>
                    <small class="text-muted">
                        Isi kalau varian ini <strong>tidak punya resep</strong>. Kalau ada resep, biarkan 0 —
                        HPP otomatis dihitung dari bahan.
                    </small>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="addv_active" checked>
                    <label class="form-check-label" for="addv_active">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT VARIAN -->
<div class="modal fade" id="modalEditVarian" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/variant_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="editv_id">
            <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
            <div class="modal-header">
                <h5 class="modal-title">Edit Varian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Varian <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="editv_nama" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Suhu</label>
                        <select name="temperature" id="editv_temperature" class="form-select">
                            <option value="">— Tidak ada —</option>
                            <option value="HOT">Hot</option>
                            <option value="ICED">Iced</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Ukuran</label>
                        <input type="text" name="size" id="editv_size" class="form-control" maxlength="10">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">SKU <span class="text-danger">*</span></label>
                    <input type="text" name="sku" id="editv_sku" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="harga_jual" id="editv_harga" class="form-control" required min="0" step="500">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">HPP Manual (Opsional)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="hpp_manual" id="editv_hpp_manual" class="form-control"
                            min="0" step="100">
                    </div>
                    <small class="text-muted">Untuk varian tanpa resep.</small>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editv_active">
                    <label class="form-check-label" for="editv_active">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HAPUS VARIAN -->
<div class="modal fade" id="modalHapusVarian" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/variant_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapusv_id">
            <input type="hidden" name="product_id" value="<?= (int)$product_id ?>">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Varian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus varian <strong id="hapusv_nama"></strong>?</p>
                <p class="text-muted small mb-0">Resep yang terkait juga akan terhapus.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditVarian(data) {
        document.getElementById('editv_id').value = data.id;
        document.getElementById('editv_nama').value = data.nama;
        document.getElementById('editv_temperature').value = data.temperature || '';
        document.getElementById('editv_size').value = data.size || '';
        document.getElementById('editv_sku').value = data.sku;
        document.getElementById('editv_harga').value = Math.round(data.harga_jual);
        document.getElementById('editv_hpp_manual').value = Math.round(data.hpp_manual || 0);
        document.getElementById('editv_active').checked = data.is_active == 1;
        new bootstrap.Modal('#modalEditVarian').show();
    }

    function confirmDeleteVarian(id, nama) {
        document.getElementById('hapusv_id').value = id;
        document.getElementById('hapusv_nama').textContent = nama;
        new bootstrap.Modal('#modalHapusVarian').show();
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>