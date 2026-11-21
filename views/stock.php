<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'gudang']);

$page_title = 'Stok';
$active_menu = 'stock';
$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

// Owner bisa pilih outlet mana yang dilihat
$filter_outlet =  current_outlet_id();

// Ambil semua outlet (untuk dropdown kalau owner)
$stmt = $pdo->prepare("SELECT id, code, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
$stmt->execute([$tenant_id]);
$outlets = $stmt->fetchAll();

// Kalau owner belum pilih outlet, pakai outlet pertama
if (is_role('owner') && !$filter_outlet && !empty($outlets)) {
    $filter_outlet = (int)$outlets[0]['id'];
}

// Ambil stok bahan + info ingredient
$stocks = [];
if ($filter_outlet) {
    $stmt = $pdo->prepare("
        SELECT
            i.id AS ingredient_id,
            i.code,
            i.nama AS ingredient_nama,
            i.base_unit,
            i.category,
            COALESCE(s.qty, 0) AS qty,
            COALESCE(s.reorder_point, 0) AS reorder_point
        FROM ingredients i
        LEFT JOIN stocks s ON s.ingredient_id = i.id AND s.outlet_id = ?
        WHERE i.tenant_id = ? AND i.is_active = 1
        ORDER BY i.category ASC, i.nama ASC
    ");
    $stmt->execute([$filter_outlet, $tenant_id]);
    $stocks = $stmt->fetchAll();
}

// Hitung stats
$total_bahan = count($stocks);
$kritis = 0;
foreach ($stocks as $s) {
    if ((float)$s['reorder_point'] > 0 && (float)$s['qty'] <= (float)$s['reorder_point']) {
        $kritis++;
    }
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

<!-- Filter Outlet (khusus owner) -->
<?php if (is_role('owner')): ?>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2">
            <label class="col-form-label text-muted small">Lihat Outlet:</label>
            <select class="form-select form-select-sm" style="width:auto;"
                onchange="window.location.href='/tokopi/actions/switch_outlet.php?outlet_id=' + this.value + '&redirect=' + encodeURIComponent('/tokopi/views/stock.php')">
                <?php foreach ($outlets as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= $filter_outlet == $o['id'] ? 'selected' : '' ?>>
                        <?= e($o['nama']) ?> (<?= e($o['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalAdjust">
            <i class="bi bi-sliders me-1"></i> Adjust Stok
        </button>
    </div>
<?php else: ?>
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalAdjust">
            <i class="bi bi-sliders me-1"></i> Adjust Stok
        </button>
    </div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Total Bahan Aktif</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:var(--espresso);"><?= $total_bahan ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Stok Kritis</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:#A83232;"><?= $kritis ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Outlet</div>
            <div class="money" style="font-size:1.1rem; font-weight:700; color:var(--espresso);">
                <?php
                foreach ($outlets as $o) {
                    if ((int)$o['id'] === $filter_outlet) {
                        echo e($o['nama']);
                        break;
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php if ($kritis > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong><?= $kritis ?> bahan</strong> sudah mencapai titik kritis. Segera lakukan restock.
    </div>
<?php endif; ?>

<div class="card">
    <div class="d-flex justify-content-end p-2 border-bottom"><div class="table-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" class="form-control form-control-sm" placeholder="Cari bahan / outlet…" aria-label="Cari bahan / outlet" data-table-filter="#tbl_stock"></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tbl_stock" data-enhance data-page-size="25">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th width="120">Kode</th>
                    <th>Nama Bahan</th>
                    <th width="140">Kategori</th>
                    <th width="100" class="text-center">Unit</th>
                    <th width="140" class="text-end">Stok</th>
                    <th width="140" class="text-end">Reorder Point</th>
                    <th width="120" class="text-center">Status</th>
                    <th data-nosort width="100" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stocks)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Belum ada bahan baku. Tambahkan dulu di menu Bahan Baku.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stocks as $i => $s): ?>
                        <?php
                        $qty = (float)$s['qty'];
                        $rp  = (float)$s['reorder_point'];
                        $is_kritis = $rp > 0 && $qty <= $rp;
                        $is_zero = $qty <= 0;
                        ?>
                        <tr class="<?= $is_kritis ? 'table-danger' : '' ?>">
                            <td><?= $i + 1 ?></td>
                            <td><code><?= e($s['code']) ?></code></td>
                            <td><strong><?= e($s['ingredient_nama']) ?></strong></td>
                            <td>
                                <?php if ($s['category']): ?>
                                    <span class="badge bg-light text-dark"><?= e($s['category']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge bg-dark"><?= e($s['base_unit']) ?></span></td>
                            <td class="text-end money">
                                <strong style="font-size:1rem;"><?= number_format($qty, 2, ',', '.') ?></strong>
                            </td>
                            <td class="text-end money text-muted">
                                <?= $rp > 0 ? number_format($rp, 2, ',', '.') : '—' ?>
                            </td>
                            <td class="text-center">
                                <?php if ($is_zero): ?>
                                    <span class="badge bg-dark">Habis</span>
                                <?php elseif ($is_kritis): ?>
                                    <span class="badge bg-danger">Kritis</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Aman</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-dark"
                                    onclick='openAdjustSingle(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-sliders"></i>
                                </button>
                                <a href="/tokopi/views/stock_history.php?ingredient_id=<?= (int)$s['ingredient_id'] ?>&outlet_id=<?= (int)$filter_outlet ?>"
                                    class="btn btn-sm btn-outline-dark" title="Riwayat">
                                    <i class="bi bi-clock-history"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL ADJUST STOK (Pilih bahan) -->
<div class="modal fade" id="modalAdjust" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/stock_action.php?action=adjust" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="outlet_id" value="<?= (int)$filter_outlet ?>">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Pilih Bahan <span class="text-danger">*</span></label>
                    <select name="ingredient_id" id="adj_ingredient" class="form-select" required onchange="showCurrentQty()">
                        <option value="">— Pilih Bahan —</option>
                        <?php foreach ($stocks as $s): ?>
                            <option value="<?= (int)$s['ingredient_id'] ?>"
                                data-qty="<?= (float)$s['qty'] ?>"
                                data-unit="<?= e($s['base_unit']) ?>"
                                data-nama="<?= e($s['ingredient_nama']) ?>">
                                [<?= e($s['code']) ?>] <?= e($s['ingredient_nama']) ?> — <?= number_format((float)$s['qty'], 2, ',', '.') ?> <?= e($s['base_unit']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="alert alert-secondary small" id="current-info" style="display:none;">
                    Stok saat ini: <strong id="current-qty">0</strong> <span id="current-unit">-</span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Penyesuaian <span class="text-danger">*</span></label>
                    <select name="mode" id="adj_mode" class="form-select" required onchange="updatePreview()">
                        <option value="add">Tambah (Stock In)</option>
                        <option value="subtract">Kurangi (Waste / Loss)</option>
                        <option value="set">Set ke angka baru</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="qty" id="adj_qty" class="form-control" required
                            min="0.01" step="0.01" placeholder="0">
                        <span class="input-group-text" id="adj_unit">—</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alasan / Catatan</label>
                    <input type="text" name="note" class="form-control"
                        placeholder="contoh: stok opname, bahan tumpah, dsb">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ADJUST SINGLE (dari tombol per row) -->
<div class="modal fade" id="modalAdjustSingle" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/stock_action.php?action=adjust" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="outlet_id" value="<?= (int)$filter_outlet ?>">
            <input type="hidden" name="ingredient_id" id="s_ingredient_id">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stok: <span id="s_nama"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary small">
                    Stok saat ini: <strong id="s_current_qty">0</strong> <span id="s_unit">-</span>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jenis Penyesuaian <span class="text-danger">*</span></label>
                    <select name="mode" class="form-select" required>
                        <option value="add">Tambah (Stock In)</option>
                        <option value="subtract">Kurangi (Waste / Loss)</option>
                        <option value="set">Set ke angka baru</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="qty" class="form-control" required min="0.01" step="0.01">
                        <span class="input-group-text" id="s_unit_input">—</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Alasan / Catatan</label>
                    <input type="text" name="note" class="form-control"
                        placeholder="contoh: stok opname, bahan tumpah">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showCurrentQty() {
        const sel = document.getElementById('adj_ingredient');
        const opt = sel.options[sel.selectedIndex];
        if (!opt.value) {
            document.getElementById('current-info').style.display = 'none';
            document.getElementById('adj_unit').textContent = '—';
            return;
        }
        const qty = opt.getAttribute('data-qty');
        const unit = opt.getAttribute('data-unit');

        document.getElementById('current-info').style.display = 'block';
        document.getElementById('current-qty').textContent = parseFloat(qty).toLocaleString('id-ID');
        document.getElementById('current-unit').textContent = unit;
        document.getElementById('adj_unit').textContent = unit;
    }

    function updatePreview() {}

    function openAdjustSingle(data) {
        document.getElementById('s_ingredient_id').value = data.ingredient_id;
        document.getElementById('s_nama').textContent = data.ingredient_nama;
        document.getElementById('s_current_qty').textContent = parseFloat(data.qty).toLocaleString('id-ID');
        document.getElementById('s_unit').textContent = data.base_unit;
        document.getElementById('s_unit_input').textContent = data.base_unit;
        new bootstrap.Modal('#modalAdjustSingle').show();
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>