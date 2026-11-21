<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','finance']);

$page_title = 'Pengeluaran';
$active_menu = 'expenses';
$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

// Filter
$filter_outlet = is_role('owner') ? (int)($_GET['outlet'] ?? 0) : (int)$outlet_id;
$filter_month  = $_GET['month'] ?? date('Y-m');
$filter_cat    = $_GET['cat'] ?? '';

if (!preg_match('/^\d{4}-\d{2}$/', $filter_month)) $filter_month = date('Y-m');

$month_start = $filter_month . '-01';
$month_end   = date('Y-m-t', strtotime($month_start));

// Build WHERE
$where = ["e.tenant_id = ?"];
$params = [$tenant_id];

if ($filter_outlet > 0) {
    $where[] = "e.outlet_id = ?";
    $params[] = $filter_outlet;
}
$where[] = "e.tanggal BETWEEN ? AND ?";
$params[] = $month_start;
$params[] = $month_end;

if ($filter_cat !== '') {
    $where[] = "e.category = ?";
    $params[] = $filter_cat;
}

$where_sql = implode(' AND ', $where);

// Ambil expenses
$stmt = $pdo->prepare("
    SELECT e.*, o.nama AS outlet_nama, u.nama AS user_nama
    FROM expenses e
    LEFT JOIN outlets o ON o.id = e.outlet_id
    LEFT JOIN users u ON u.id = e.created_by_id
    WHERE $where_sql
    ORDER BY e.tanggal DESC, e.id DESC
");
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Total bulan ini
$total_month = 0;
foreach ($expenses as $e) {
    $total_month += (float)$e['amount'];
}

// Breakdown per kategori (bulan ini)
$stmt = $pdo->prepare("
    SELECT category, COUNT(*) AS jumlah, SUM(amount) AS total
    FROM expenses e
    WHERE $where_sql
    GROUP BY category
    ORDER BY total DESC
");
$stmt->execute($params);
$by_category = $stmt->fetchAll();

// Outlets untuk dropdown
$stmt = $pdo->prepare("SELECT id, code, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
$stmt->execute([$tenant_id]);
$outlets = $stmt->fetchAll();

// Kategori yang sudah pernah dipakai (untuk autocomplete)
$stmt = $pdo->prepare("SELECT DISTINCT category FROM expenses WHERE tenant_id = ? ORDER BY category");
$stmt->execute([$tenant_id]);
$existing_cats = $stmt->fetchAll(PDO::FETCH_COLUMN);

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

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Bulan</label>
                <input type="month" name="month" class="form-control form-control-sm" value="<?= e($filter_month) ?>">
            </div>

            <?php if (is_role('owner')): ?>
                <div class="col-md-3">
                    <label class="form-label small">Outlet</label>
                    <select name="outlet" class="form-select form-select-sm">
                        <option value="0">Semua Outlet</option>
                        <?php foreach ($outlets as $o): ?>
                            <option value="<?= (int)$o['id'] ?>" <?= $filter_outlet === (int)$o['id'] ? 'selected' : '' ?>>
                                <?= e($o['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label small">Kategori</label>
                <select name="cat" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($existing_cats as $c): ?>
                        <option value="<?= e($c) ?>" <?= $filter_cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-espresso btn-sm flex-fill">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="/tokopi/views/expenses.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Total Pengeluaran Bulan Ini</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:#A83232;">
                <?= rupiah($total_month) ?>
            </div>
            <small class="text-muted"><?= count($expenses) ?> transaksi</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Jumlah Kategori</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);">
                <?= count($by_category) ?>
            </div>
            <small class="text-muted">Jenis pengeluaran</small>
        </div>
    </div>
</div>

<!-- Header + Tambah -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Riwayat Pengeluaran</h5>
    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Catat Pengeluaran
    </button>
</div>

<!-- Daftar Expenses -->
<div class="card">
    <div class="d-flex justify-content-end p-2 border-bottom"><div class="table-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" class="form-control form-control-sm" placeholder="Cari pengeluaran…" aria-label="Cari pengeluaran" data-table-filter="#tbl_expenses"></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tbl_expenses" data-enhance data-page-size="25">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th width="110">Tanggal</th>
                    <th width="140">Kategori</th>
                    <th>Keterangan</th>
                    <th width="160">Outlet</th>
                    <th width="140" class="text-end">Jumlah</th>
                    <th data-nosort width="140" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Belum ada pengeluaran bulan ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $i => $e): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= date('d/m/Y', strtotime($e['tanggal'])) ?></td>
                            <td>
                                <span class="badge bg-light text-dark"><?= e($e['category']) ?></span>
                            </td>
                            <td>
                                <?= e($e['note'] ?? '—') ?>
                                <div class="small text-muted">oleh <?= e($e['user_nama'] ?? 'System') ?></div>
                            </td>
                            <td>
                                <?= $e['outlet_nama'] ? e($e['outlet_nama']) : '<span class="text-muted small">Semua Outlet</span>' ?>
                            </td>
                            <td class="text-end money" style="color:#A83232;">
                                <strong><?= rupiah($e['amount']) ?></strong>
                            </td>
                            <td class="text-end">
                                <?php if (in_array(current_user_role(), ['owner','manager','finance'])): ?>
                                    <button class="btn btn-sm btn-outline-dark"
                                            onclick='openEdit(<?= json_encode($e, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(<?= (int)$e['id'] ?>, '<?= e($e['category']) ?>', '<?= rupiah($e['amount']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Breakdown per Kategori -->
<?php if (!empty($by_category)): ?>
<div class="card mt-3">
    <div class="card-header">
        <i class="bi bi-pie-chart me-1"></i> Breakdown per Kategori (Bulan Ini)
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($by_category as $c): ?>
                    <?php $pct = $total_month > 0 ? ((float)$c['total'] / $total_month * 100) : 0; ?>
                    <tr>
                        <td><strong><?= e($c['category']) ?></strong></td>
                        <td class="text-center money"><?= (int)$c['jumlah'] ?></td>
                        <td class="text-end money"><?= rupiah($c['total']) ?></td>
                        <td class="text-end money text-muted"><?= number_format($pct, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background:#FAF9F5; font-weight:700;">
                    <td>TOTAL</td>
                    <td class="text-center money"><?= count($expenses) ?></td>
                    <td class="text-end money"><?= rupiah($total_month) ?></td>
                    <td class="text-end money">100%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/expense_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Catat Pengeluaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="category" class="form-control" required
                               list="cat-list" placeholder="contoh: Gaji">
                        <datalist id="cat-list">
                            <?php foreach ($existing_cats as $c): ?>
                                <option value="<?= e($c) ?>"></option>
                            <?php endforeach; ?>
                            <option value="Gaji"></option>
                            <option value="Sewa Tempat"></option>
                            <option value="Listrik & Air"></option>
                            <option value="Internet & Telepon"></option>
                            <option value="Marketing"></option>
                            <option value="Maintenance"></option>
                            <option value="Transport"></option>
                            <option value="Lainnya"></option>
                        </datalist>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Outlet</label>
                    <select name="outlet_id" class="form-select">
                        <option value="0">Semua Outlet (biaya pusat)</option>
                        <?php foreach ($outlets as $o): ?>
                            <option value="<?= (int)$o['id'] ?>"><?= e($o['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="amount" class="form-control" required
                               min="0" step="1000" placeholder="0">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="note" class="form-control" rows="2"
                              placeholder="contoh: Gaji 3 barista bulan Oktober"></textarea>
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
        <form action="/tokopi/actions/expense_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Edit Pengeluaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="category" id="edit_category" class="form-control" required list="cat-list">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Outlet</label>
                    <select name="outlet_id" id="edit_outlet_id" class="form-select">
                        <option value="0">Semua Outlet (biaya pusat)</option>
                        <?php foreach ($outlets as $o): ?>
                            <option value="<?= (int)$o['id'] ?>"><?= e($o['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="amount" id="edit_amount" class="form-control" required min="0" step="1000">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="note" id="edit_note" class="form-control" rows="2"></textarea>
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
        <form action="/tokopi/actions/expense_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapus_id">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Pengeluaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus pengeluaran <strong id="hapus_kategori"></strong> sebesar <strong id="hapus_amount"></strong>?</p>
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
    document.getElementById('edit_id').value        = data.id;
    document.getElementById('edit_tanggal').value   = data.tanggal;
    document.getElementById('edit_category').value  = data.category;
    document.getElementById('edit_outlet_id').value = data.outlet_id || 0;
    document.getElementById('edit_amount').value    = Math.round(data.amount);
    document.getElementById('edit_note').value      = data.note || '';
    new bootstrap.Modal('#modalEdit').show();
}

function confirmDelete(id, kategori, amount) {
    document.getElementById('hapus_id').value = id;
    document.getElementById('hapus_kategori').textContent = kategori;
    document.getElementById('hapus_amount').textContent = amount;
    new bootstrap.Modal('#modalHapus').show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>