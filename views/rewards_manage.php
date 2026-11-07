<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager']);

$page_title = 'Kelola Rewards';
$active_menu = 'rewards_manage';
$tenant_id = current_tenant_id();

$stmt = $pdo->prepare("
    SELECT r.*, v.nama AS variant_nama, p.nama AS product_nama
    FROM rewards r
    LEFT JOIN product_variants v ON v.id = r.variant_id
    LEFT JOIN products p ON p.id = v.product_id
    WHERE r.tenant_id = ?
    ORDER BY r.points_cost ASC
");
$stmt->execute([$tenant_id]);
$rewards = $stmt->fetchAll();

// Varian untuk dropdown (FREE_ITEM)
$stmt = $pdo->prepare("
    SELECT v.id, v.nama AS variant_nama, p.nama AS product_nama, v.harga_jual
    FROM product_variants v
    JOIN products p ON p.id = v.product_id
    WHERE p.tenant_id = ? AND p.is_active = 1 AND v.is_active = 1
    ORDER BY p.nama, v.nama
");
$stmt->execute([$tenant_id]);
$variants_list = $stmt->fetchAll();

$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Daftar Rewards</h5>
        <small class="text-muted">Total: <?= count($rewards) ?> reward</small>
    </div>
    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Reward
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th class="text-end">Poin</th>
                    <th class="text-end">Value</th>
                    <th>Produk</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rewards)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada reward.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rewards as $r):
                        $type_label = $r['type'] === 'FREE_ITEM' ? '☕ Gratis Item' : ($r['type'] === 'DISCOUNT_NOMINAL' ? '💰 Diskon Nominal' : '🏷️ Diskon %');
                        $value_disp = $r['type'] === 'FREE_ITEM' ? '-' : ($r['type'] === 'DISCOUNT_NOMINAL' ? rupiah($r['value']) : $r['value'] . '%');
                    ?>
                        <tr>
                            <td><strong><?= e($r['nama']) ?></strong><br><small class="text-muted"><?= e($r['deskripsi'] ?? '') ?></small></td>
                            <td><small><?= $type_label ?></small></td>
                            <td class="text-end money"><strong><?= (int)$r['points_cost'] ?></strong></td>
                            <td class="text-end money"><?= $value_disp ?></td>
                            <td><small><?= $r['variant_id'] ? e($r['product_nama'] . ' · ' . $r['variant_nama']) : '-' ?></small></td>
                            <td class="text-center">
                                <?php if ($r['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-dark"
                                    onclick='openEdit(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete(<?= (int)$r['id'] ?>, '<?= e($r['nama']) ?>')">
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
        <form action="/tokopi/actions/reward_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Reward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" class="form-control" required maxlength="100"></div>
                <div class="mb-3"><label class="form-label">Deskripsi</label><input type="text" name="deskripsi" class="form-control" maxlength="255"></div>
                <div class="mb-3"><label class="form-label">Poin Dibutuhkan</label><input type="number" name="points_cost" class="form-control" required min="1"></div>
                <div class="mb-3">
                    <label class="form-label">Tipe</label>
                    <select name="type" id="add_type" class="form-select" required onchange="toggleAddFields()">
                        <option value="">— Pilih —</option>
                        <option value="DISCOUNT_NOMINAL">Diskon Nominal</option>
                        <option value="DISCOUNT_PERCENT">Diskon Persen</option>
                        <option value="FREE_ITEM">Gratis Item</option>
                    </select>
                </div>
                <div class="mb-3" id="add_value_group" style="display:none;">
                    <label class="form-label" id="add_value_label">Value</label>
                    <input type="number" name="value" id="add_value" class="form-control" min="0" step="any">
                    <small class="text-muted" id="add_value_hint"></small>
                </div>
                <div class="mb-3" id="add_variant_group" style="display:none;">
                    <label class="form-label">Produk (Varian)</label>
                    <select name="variant_id" class="form-select">
                        <option value="">— Pilih Varian —</option>
                        <?php foreach ($variants_list as $v): ?>
                            <option value="<?= (int)$v['id'] ?>"><?= e($v['product_nama']) ?> · <?= e($v['variant_nama']) ?> (<?= rupiah($v['harga_jual']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                    <label class="form-check-label">Aktif</label>
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
        <form action="/tokopi/actions/reward_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Reward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="nama" id="edit_nama" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Deskripsi</label><input type="text" name="deskripsi" id="edit_deskripsi" class="form-control"></div>
                <div class="mb-3"><label class="form-label">Poin Dibutuhkan</label><input type="number" name="points_cost" id="edit_points_cost" class="form-control" required min="1"></div>
                <div class="mb-3">
                    <label class="form-label">Tipe</label>
                    <select name="type" id="edit_type" class="form-select" required>
                        <option value="DISCOUNT_NOMINAL">Diskon Nominal</option>
                        <option value="DISCOUNT_PERCENT">Diskon Persen</option>
                        <option value="FREE_ITEM">Gratis Item</option>
                    </select>
                </div>
                <div class="mb-3" id="edit_value_group">
                    <label class="form-label">Value</label>
                    <input type="number" name="value" id="edit_value" class="form-control" min="0" step="any">
                    <small class="text-muted" id="edit_value_hint"></small>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                    <label class="form-check-label">Aktif</label>
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
        <form action="/tokopi/actions/reward_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapus_id">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Reward</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin hapus reward <strong id="hapus_nama"></strong>?</p>
                <p class="text-muted small mb-0">Voucher yang sudah ditukar tetap berlaku.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleAddFields() {
        const type = document.getElementById('add_type').value;
        const valueGroup = document.getElementById('add_value_group');
        const variantGroup = document.getElementById('add_variant_group');
        const valueLabel = document.getElementById('add_value_label');
        const valueHint = document.getElementById('add_value_hint');
        const valueInput = document.getElementById('add_value');

        if (type === 'FREE_ITEM') {
            valueGroup.style.display = 'none';
            variantGroup.style.display = 'block';
        } else if (type === 'DISCOUNT_NOMINAL') {
            valueGroup.style.display = 'block';
            variantGroup.style.display = 'none';
            valueLabel.textContent = 'Diskon (Rp)';
            valueHint.textContent = 'Contoh: 5000 untuk diskon Rp 5.000';
            valueInput.step = 'any';
            valueInput.min = '0';
        } else if (type === 'DISCOUNT_PERCENT') {
            valueGroup.style.display = 'block';
            variantGroup.style.display = 'none';
            valueLabel.textContent = 'Diskon (%)';
            valueHint.textContent = 'Contoh: 10 untuk diskon 10%';
            valueInput.step = '0.1';
            valueInput.min = '0';
            valueInput.max = '100';
        } else {
            valueGroup.style.display = 'none';
            variantGroup.style.display = 'none';
        }
    }

    function openEdit(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_nama').value = data.nama;
        document.getElementById('edit_deskripsi').value = data.deskripsi || '';
        document.getElementById('edit_points_cost').value = data.points_cost;
        document.getElementById('edit_type').value = data.type;
        document.getElementById('edit_value').value = data.value;
        document.getElementById('edit_is_active').checked = data.is_active == 1;
        new bootstrap.Modal('#modalEdit').show();
    }

    function confirmDelete(id, nama) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('hapus_nama').textContent = nama;
        new bootstrap.Modal('#modalHapus').show();
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>