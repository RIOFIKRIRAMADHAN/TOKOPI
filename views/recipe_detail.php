<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$tenant_id  = current_tenant_id();
$variant_id = (int)($_GET['variant_id'] ?? 0);

if ($variant_id <= 0) {
    flash_set('error', 'Varian tidak valid.');
    redirect('/tokopi/views/recipes.php');
}

// Ambil varian + produk
$stmt = $pdo->prepare("
    SELECT v.*, p.nama AS product_nama, p.id AS product_id, c.nama AS kategori_nama, p.tenant_id
    FROM product_variants v
    JOIN products p ON p.id = v.product_id
    JOIN categories c ON c.id = p.category_id
    WHERE v.id = ? AND p.tenant_id = ?
");
$stmt->execute([$variant_id, $tenant_id]);
$variant = $stmt->fetch();

if (!$variant) {
    flash_set('error', 'Varian tidak ditemukan.');
    redirect('/tokopi/views/recipes.php');
}

// Cek / buat resep
$stmt = $pdo->prepare("SELECT * FROM recipes WHERE variant_id = ?");
$stmt->execute([$variant_id]);
$recipe = $stmt->fetch();

if (!$recipe) {
    $stmt = $pdo->prepare("INSERT INTO recipes (variant_id, yield_qty) VALUES (?, 1)");
    $stmt->execute([$variant_id]);
    $recipe_id = $pdo->lastInsertId();
    $recipe = ['id' => $recipe_id, 'variant_id' => $variant_id, 'yield_qty' => 1];
} else {
    $recipe_id = $recipe['id'];
}

// Ambil bahan-bahan di resep
$stmt = $pdo->prepare("
    SELECT ri.*, i.nama AS ingredient_nama, i.code AS ingredient_code,
           i.base_unit, i.avg_cost,
           (ri.qty * i.avg_cost) AS subtotal
    FROM recipe_items ri
    JOIN ingredients i ON i.id = ri.ingredient_id
    WHERE ri.recipe_id = ?
    ORDER BY ri.id ASC
");
$stmt->execute([$recipe_id]);
$items = $stmt->fetchAll();

// Hitung HPP total
$hpp_total = 0;
foreach ($items as $it) {
    $hpp_total += (float)$it['subtotal'];
}
$harga_jual = (float)$variant['harga_jual'];
$margin = $harga_jual - $hpp_total;
$margin_pct = $harga_jual > 0 ? ($margin / $harga_jual) * 100 : 0;

// Ambil semua bahan aktif untuk dropdown
$stmt = $pdo->prepare("SELECT id, code, nama, base_unit, avg_cost FROM ingredients WHERE tenant_id = ? AND is_active = 1 ORDER BY nama ASC");
$stmt->execute([$tenant_id]);
$all_ingredients = $stmt->fetchAll();

$page_title = 'Resep: ' . $variant['product_nama'] . ' - ' . $variant['nama'];
$active_menu = 'recipes';
$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="/tokopi/views/recipes.php" class="text-decoration-none small" style="color: var(--espresso);">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Resep
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

<!-- Header Info Varian -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4 class="mb-1"><?= e($variant['product_nama']) ?></h4>
                <div class="text-muted">
                    <?= e($variant['nama']) ?>
                    · <code><?= e($variant['sku']) ?></code>
                    · <span class="badge bg-light text-dark"><?= e($variant['kategori_nama']) ?></span>
                </div>
            </div>
            <div class="text-end">
                <div class="text-muted small text-uppercase">Harga Jual</div>
                <div class="money" style="font-size:1.5rem; font-weight:700; color:var(--espresso);">
                    <?= rupiah($harga_jual) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HPP Summary -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">HPP Total per Cup</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:#A83232;">
                <?= rupiah($hpp_total) ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Margin (Rp)</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:<?= $margin > 0 ? '#2E7D5B' : '#A83232' ?>;">
                <?= rupiah($margin) ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Margin (%)</div>
            <div class="money" style="font-size:1.5rem; font-weight:700;
                color:<?= $margin_pct >= 50 ? '#2E7D5B' : ($margin_pct >= 30 ? '#B8860B' : '#A83232') ?>;">
                <?= number_format($margin_pct, 1) ?>%
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Bahan-bahan Resep</h5>
    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambahBahan">
        <i class="bi bi-plus-lg me-1"></i> Tambah Bahan
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th width="120">Kode</th>
                    <th>Nama Bahan</th>
                    <th width="160" class="text-end">Jumlah Pakai</th>
                    <th width="160" class="text-end">HPP / Unit</th>
                    <th width="160" class="text-end">Subtotal</th>
                    <th width="100" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Belum ada bahan. Klik "Tambah Bahan" untuk mulai menyusun resep.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $i => $it): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><code><?= e($it['ingredient_code']) ?></code></td>
                            <td><strong><?= e($it['ingredient_nama']) ?></strong></td>
                            <td class="text-end money">
                                <?= rtrim(rtrim(number_format((float)$it['qty'], 4, ',', '.'), '0'), ',') ?>
                                <span class="badge bg-dark ms-1"><?= e($it['unit']) ?></span>
                            </td>
                            <td class="text-end money"><?= rupiah($it['avg_cost']) ?></td>
                            <td class="text-end money"><strong><?= rupiah($it['subtotal']) ?></strong></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-dark"
                                        onclick='openEditBahan(<?= json_encode($it, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="confirmHapusBahan(<?= (int)$it['id'] ?>, '<?= e($it['ingredient_nama']) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #FAF9F5;">
                        <td colspan="5" class="text-end"><strong>Total HPP</strong></td>
                        <td class="text-end money"><strong style="color:#A83232;"><?= rupiah($hpp_total) ?></strong></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH BAHAN -->
<div class="modal fade" id="modalTambahBahan" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/recipe_action.php?action=add_item" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="recipe_id" value="<?= (int)$recipe_id ?>">
            <input type="hidden" name="variant_id" value="<?= (int)$variant_id ?>">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Bahan ke Resep</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Bahan <span class="text-danger">*</span></label>
                    <select name="ingredient_id" id="add_ingredient_id" class="form-select" required
                            onchange="updateAddUnit()">
                        <option value="">— Pilih Bahan —</option>
                        <?php foreach ($all_ingredients as $ing): ?>
                            <option value="<?= (int)$ing['id'] ?>"
                                    data-unit="<?= e($ing['base_unit']) ?>"
                                    data-cost="<?= (float)$ing['avg_cost'] ?>">
                                [<?= e($ing['code']) ?>] <?= e($ing['nama']) ?>
                                (<?= e($ing['base_unit']) ?> · <?= rupiah($ing['avg_cost']) ?>/<?= e($ing['base_unit']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Pakai <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="qty" id="add_qty" class="form-control" required
                               min="0.0001" step="any" placeholder="contoh: 18">
                        <span class="input-group-text" id="add_unit_display">—</span>
                    </div>
                    <small class="text-muted">Untuk 1 porsi/cup.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="note" class="form-control" placeholder="Opsional">
                </div>
                <div class="alert alert-info small mb-0" id="add_preview" style="display:none;">
                    Subtotal: <strong id="add_preview_value">Rp 0</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Tambah</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT BAHAN -->
<div class="modal fade" id="modalEditBahan" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/recipe_action.php?action=edit_item" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="editb_id">
            <input type="hidden" name="recipe_id" value="<?= (int)$recipe_id ?>">
            <input type="hidden" name="variant_id" value="<?= (int)$variant_id ?>">
            <div class="modal-header">
                <h5 class="modal-title">Edit Bahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Bahan</label>
                    <input type="text" id="editb_nama" class="form-control" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Pakai <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="qty" id="editb_qty" class="form-control" required
                               min="0.0001" step="any">
                        <span class="input-group-text" id="editb_unit">—</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="note" id="editb_note" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HAPUS BAHAN -->
<div class="modal fade" id="modalHapusBahan" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/recipe_action.php?action=delete_item" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapusb_id">
            <input type="hidden" name="recipe_id" value="<?= (int)$recipe_id ?>">
            <input type="hidden" name="variant_id" value="<?= (int)$variant_id ?>">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Bahan dari Resep</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus <strong id="hapusb_nama"></strong> dari resep ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateAddUnit() {
    const sel = document.getElementById('add_ingredient_id');
    const opt = sel.options[sel.selectedIndex];
    const unit = opt.getAttribute('data-unit') || '—';
    const cost = parseFloat(opt.getAttribute('data-cost') || 0);
    document.getElementById('add_unit_display').textContent = unit;

    const qty = parseFloat(document.getElementById('add_qty').value || 0);
    updatePreview(qty, cost);
}

document.getElementById('add_qty').addEventListener('input', function() {
    const sel = document.getElementById('add_ingredient_id');
    const opt = sel.options[sel.selectedIndex];
    const cost = parseFloat(opt.getAttribute('data-cost') || 0);
    updatePreview(parseFloat(this.value || 0), cost);
});

function updatePreview(qty, cost) {
    const box = document.getElementById('add_preview');
    if (qty > 0 && cost > 0) {
        const subtotal = qty * cost;
        document.getElementById('add_preview_value').textContent =
            'Rp ' + subtotal.toLocaleString('id-ID', {maximumFractionDigits: 0});
        box.style.display = 'block';
    } else {
        box.style.display = 'none';
    }
}

function openEditBahan(data) {
    document.getElementById('editb_id').value   = data.id;
    document.getElementById('editb_nama').value = '[' + data.ingredient_code + '] ' + data.ingredient_nama;
    document.getElementById('editb_qty').value  = parseFloat(data.qty);
    document.getElementById('editb_unit').textContent = data.unit;
    document.getElementById('editb_note').value = data.note || '';
    new bootstrap.Modal('#modalEditBahan').show();
}

function confirmHapusBahan(id, nama) {
    document.getElementById('hapusb_id').value = id;
    document.getElementById('hapusb_nama').textContent = nama;
    new bootstrap.Modal('#modalHapusBahan').show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
