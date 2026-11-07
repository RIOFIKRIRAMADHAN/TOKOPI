<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();
require_role(['owner','manager','finance']);

$page_title = 'Laporan Laba Rugi';
$active_menu = 'reports_pl';
$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

// Filter
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');
$filter_outlet = is_role('owner') ? (int)($_GET['outlet'] ?? 0) : (int)$outlet_id;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $date_to   = date('Y-m-d');
if (strtotime($date_from) > strtotime($date_to)) {
    $tmp = $date_from; $date_from = $date_to; $date_to = $tmp;
}

// ============================================
// OMZET & HPP (dari orders)
// ============================================
$outlet_sql = '';
$outlet_params = [];
if ($filter_outlet > 0) {
    $outlet_sql = " AND o.outlet_id = ? ";
    $outlet_params[] = $filter_outlet;
}

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_order,
        COALESCE(SUM(o.grand_total), 0) AS omzet,
        COALESCE(SUM(o.cost_total), 0) AS hpp
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND DATE(o.created_at) BETWEEN ? AND ?
      $outlet_sql
");
$params = array_merge([$tenant_id, $date_from, $date_to], $outlet_params);
$stmt->execute($params);
$sales = $stmt->fetch();

$omzet = (float)$sales['omzet'];
$hpp   = (float)$sales['hpp'];
$gross_profit = $omzet - $hpp;

// ============================================
// PENGELUARAN (dari expenses)
// ============================================
$expense_sql = '';
$expense_params = [];
if ($filter_outlet > 0) {
    // Kalau filter outlet spesifik: expense yang outlet_id-nya sama ATAU outlet_id NULL (biaya pusat)
    $expense_sql = " AND (e.outlet_id = ? OR e.outlet_id IS NULL) ";
    $expense_params[] = $filter_outlet;
}

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(e.amount), 0) AS total_expense,
        COUNT(*) AS jumlah_expense
    FROM expenses e
    WHERE e.tenant_id = ?
      AND e.tanggal BETWEEN ? AND ?
      $expense_sql
");
$params = array_merge([$tenant_id, $date_from, $date_to], $expense_params);
$stmt->execute($params);
$expense_row = $stmt->fetch();

$total_expense = (float)$expense_row['total_expense'];
$net_profit = $gross_profit - $total_expense;

// ============================================
// BREAKDOWN EXPENSE PER KATEGORI
// ============================================
$stmt = $pdo->prepare("
    SELECT e.category, COUNT(*) AS jumlah, SUM(e.amount) AS total
    FROM expenses e
    WHERE e.tenant_id = ?
      AND e.tanggal BETWEEN ? AND ?
      $expense_sql
    GROUP BY e.category
    ORDER BY total DESC
");
$params = array_merge([$tenant_id, $date_from, $date_to], $expense_params);
$stmt->execute($params);
$expense_by_category = $stmt->fetchAll();

// ============================================
// BREAKDOWN PER OUTLET (kalau filter = semua)
// ============================================
$by_outlet = [];
if ($filter_outlet === 0) {
    // Ambil outlet list
    $stmt = $pdo->prepare("SELECT id, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
    $stmt->execute([$tenant_id]);
    $outlets = $stmt->fetchAll();

    foreach ($outlets as $o) {
        // Omzet & HPP outlet ini
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS total_order,
                COALESCE(SUM(grand_total), 0) AS omzet,
                COALESCE(SUM(cost_total), 0) AS hpp
            FROM orders
            WHERE outlet_id = ? AND status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
              AND DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$o['id'], $date_from, $date_to]);
        $s = $stmt->fetch();

        // Expense outlet ini + expense pusat (NULL)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0)
            FROM expenses
            WHERE tenant_id = ?
              AND (outlet_id = ? OR outlet_id IS NULL)
              AND tanggal BETWEEN ? AND ?
        ");
        $stmt->execute([$tenant_id, $o['id'], $date_from, $date_to]);
        $exp = (float)$stmt->fetchColumn();

        $omz = (float)$s['omzet'];
        $h   = (float)$s['hpp'];
        $gp  = $omz - $h;
        $np  = $gp - $exp;

        $by_outlet[] = [
            'nama'     => $o['nama'],
            'order'    => (int)$s['total_order'],
            'omzet'    => $omz,
            'hpp'      => $h,
            'gross'    => $gp,
            'expense'  => $exp,
            'net'      => $np,
        ];
    }
}

// Margin %
$gross_margin_pct = $omzet > 0 ? ($gross_profit / $omzet * 100) : 0;
$net_margin_pct   = $omzet > 0 ? ($net_profit / $omzet * 100) : 0;

$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($date_from) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($date_to) ?>" required>
            </div>

            <?php if (is_role('owner')): ?>
                <div class="col-md-3">
                    <label class="form-label small">Outlet</label>
                    <select name="outlet" class="form-select form-select-sm">
                        <option value="0">Semua Outlet (Konsolidasi)</option>
                        <?php
                        $stmt = $pdo->prepare("SELECT id, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
                        $stmt->execute([$tenant_id]);
                        foreach ($stmt->fetchAll() as $o):
                        ?>
                            <option value="<?= (int)$o['id'] ?>" <?= $filter_outlet === (int)$o['id'] ? 'selected' : '' ?>>
                                <?= e($o['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-espresso btn-sm flex-fill">
                    <i class="bi bi-search me-1"></i> Filter
                </button>
                <a href="/tokopi/views/reports/profit_loss.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>

        <div class="d-flex gap-2 mt-3 pt-3 border-top">
            <span class="small text-muted me-2">Quick:</span>
            <a href="?date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?><?= $filter_outlet ? '&outlet=' . $filter_outlet : '' ?>" class="btn btn-sm btn-outline-dark">Hari ini</a>
            <a href="?date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?><?= $filter_outlet ? '&outlet=' . $filter_outlet : '' ?>" class="btn btn-sm btn-outline-dark">Bulan ini</a>
            <a href="?date_from=<?= date('Y-m-01', strtotime('first day of last month')) ?>&date_to=<?= date('Y-m-t', strtotime('last day of last month')) ?><?= $filter_outlet ? '&outlet=' . $filter_outlet : '' ?>" class="btn btn-sm btn-outline-dark">Bulan lalu</a>
            <a href="?date_from=<?= date('Y-01-01') ?>&date_to=<?= date('Y-m-d') ?><?= $filter_outlet ? '&outlet=' . $filter_outlet : '' ?>" class="btn btn-sm btn-outline-dark">Tahun ini</a>
        </div>
    </div>
</div>

<!-- Export Bar -->
<div class="d-flex gap-2 mb-3" id="export-bar">
    <button class="btn btn-outline-dark btn-sm" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Print Struk
    </button>
    <button class="btn btn-outline-dark btn-sm" onclick="exportCSV()">
        <i class="bi bi-download me-1"></i> Export CSV
    </button>
</div>

<!-- Print header -->
<div class="print-only" style="display:none;">
    <div style="text-align:center;">
        <h4 style="margin:0;">Laporan Laba Rugi</h4>
        <p style="margin:0; font-size:0.85rem;">
            Periode: <?= date('d/m/Y', strtotime($date_from)) ?> s/d <?= date('d/m/Y', strtotime($date_to)) ?>
            <?php if ($filter_outlet > 0): ?>
                <br>Outlet: <?php
                $stmt = $pdo->prepare("SELECT nama FROM outlets WHERE id = ?");
                $stmt->execute([$filter_outlet]);
                echo e($stmt->fetchColumn());
                ?>
            <?php else: ?>
                <br>Konsolidasi Semua Outlet
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- MAIN: Laba Rugi Summary -->
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-calculator me-1"></i> Ringkasan Laba Rugi
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" style="font-size:0.95rem;">
                    <tbody>
                        <tr>
                            <td>Omzet Penjualan</td>
                            <td class="text-end money" style="color:#2E7D5B; font-weight:700;">
                                <?= rupiah($omzet) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>HPP (Bahan Baku)</td>
                            <td class="text-end money" style="color:#A83232;">
                                ( <?= rupiah($hpp) ?> )
                            </td>
                        </tr>
                        <tr style="background:#FAF9F5; border-top:2px solid var(--sand);">
                            <td><strong>Laba Kotor</strong></td>
                            <td class="text-end money"><strong><?= rupiah($gross_profit) ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="text-muted small pt-3 pb-1">Dikurangi Pengeluaran:</td>
                        </tr>
                        <?php if (empty($expense_by_category)): ?>
                            <tr>
                                <td class="text-muted small ps-4">Tidak ada pengeluaran</td>
                                <td class="text-end money text-muted">Rp 0</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expense_by_category as $ec): ?>
                                <tr>
                                    <td class="ps-4"><?= e($ec['category']) ?></td>
                                    <td class="text-end money" style="color:#A83232;">
                                        ( <?= rupiah($ec['total']) ?> )
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <tr style="border-top:1px dashed var(--sand);">
                            <td><strong>Total Pengeluaran</strong></td>
                            <td class="text-end money" style="color:#A83232;"><strong>( <?= rupiah($total_expense) ?> )</strong></td>
                        </tr>
                        <tr style="background:#000; color:#fff; border-top:2px solid #000;">
                            <td style="padding:14px;"><strong style="font-size:1.05rem;">LABA BERSIH</strong></td>
                            <td class="text-end money" style="padding:14px;">
                                <strong style="font-size:1.25rem; color:<?= $net_profit >= 0 ? '#4ade80' : '#f87171' ?>;">
                                    <?= rupiah($net_profit) ?>
                                </strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-percent me-1"></i> Margin
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="text-muted small text-uppercase">Total Order</div>
                    <div class="money" style="font-size:1.75rem; font-weight:700;">
                        <?= number_format((int)$sales['total_order']) ?>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-muted small text-uppercase">Gross Margin</div>
                    <div class="money" style="font-size:1.5rem; font-weight:700; color:<?= $gross_margin_pct >= 50 ? '#2E7D5B' : ($gross_margin_pct >= 30 ? '#B8860B' : '#A83232') ?>;">
                        <?= number_format($gross_margin_pct, 1) ?>%
                    </div>
                    <small class="text-muted">(Laba Kotor ÷ Omzet)</small>
                </div>

                <div class="mb-0">
                    <div class="text-muted small text-uppercase">Net Margin</div>
                    <div class="money" style="font-size:1.5rem; font-weight:700; color:<?= $net_margin_pct >= 20 ? '#2E7D5B' : ($net_margin_pct >= 10 ? '#B8860B' : '#A83232') ?>;">
                        <?= number_format($net_margin_pct, 1) ?>%
                    </div>
                    <small class="text-muted">(Laba Bersih ÷ Omzet)</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Perbandingan per Outlet -->
<?php if ($filter_outlet === 0 && !empty($by_outlet)): ?>
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-shop me-1"></i> Perbandingan Laba Rugi per Outlet
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Outlet</th>
                    <th class="text-center">Order</th>
                    <th class="text-end">Omzet</th>
                    <th class="text-end">HPP</th>
                    <th class="text-end">Laba Kotor</th>
                    <th class="text-end">Pengeluaran</th>
                    <th class="text-end">Laba Bersih</th>
                    <th class="text-end">Net Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_order_all = 0;
                $total_omzet_all = 0;
                $total_hpp_all = 0;
                $total_gross_all = 0;
                $total_exp_all = 0;
                $total_net_all = 0;
                foreach ($by_outlet as $b):
                    $total_order_all += $b['order'];
                    $total_omzet_all += $b['omzet'];
                    $total_hpp_all += $b['hpp'];
                    $total_gross_all += $b['gross'];
                    $total_exp_all += $b['expense'];
                    $total_net_all += $b['net'];
                    $m = $b['omzet'] > 0 ? ($b['net'] / $b['omzet'] * 100) : 0;
                ?>
                    <tr>
                        <td><strong><?= e($b['nama']) ?></strong></td>
                        <td class="text-center money"><?= $b['order'] ?></td>
                        <td class="text-end money"><?= rupiah($b['omzet']) ?></td>
                        <td class="text-end money" style="color:#A83232;"><?= rupiah($b['hpp']) ?></td>
                        <td class="text-end money"><?= rupiah($b['gross']) ?></td>
                        <td class="text-end money" style="color:#A83232;"><?= rupiah($b['expense']) ?></td>
                        <td class="text-end money" style="color:<?= $b['net'] >= 0 ? '#2E7D5B' : '#A83232' ?>;">
                            <strong><?= rupiah($b['net']) ?></strong>
                        </td>
                        <td class="text-end money"><?= number_format($m, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
                <tr style="background:#FAF9F5; font-weight:700;">
                    <td>TOTAL</td>
                    <td class="text-center money"><?= $total_order_all ?></td>
                    <td class="text-end money"><?= rupiah($total_omzet_all) ?></td>
                    <td class="text-end money" style="color:#A83232;"><?= rupiah($total_hpp_all) ?></td>
                    <td class="text-end money"><?= rupiah($total_gross_all) ?></td>
                    <td class="text-end money" style="color:#A83232;"><?= rupiah($total_exp_all) ?></td>
                    <td class="text-end money" style="color:<?= $total_net_all >= 0 ? '#2E7D5B' : '#A83232' ?>;">
                        <?= rupiah($total_net_all) ?>
                    </td>
                    <td class="text-end money"><?= number_format($total_omzet_all > 0 ? ($total_net_all / $total_omzet_all * 100) : 0, 1) ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Print Style -->
<style>
@media print {
    .sidebar, .topbar, form, .btn, .alert, #export-bar { display: none !important; }
    .print-only { display: block !important; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 2px solid #000; }
    .main-wrapper { margin-left: 0 !important; }
    .content { padding: 0 !important; }
    body { background: #fff !important; font-size: 11px !important; }
    .card { break-inside: avoid; page-break-inside: avoid; border: 1px solid #ccc !important; box-shadow: none !important; margin-bottom: 12px !important; }
    .card-header { background: #f5f5f5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .money { font-size: 11px !important; }
    .table-responsive { overflow: visible !important; }
    tr { page-break-inside: avoid; }
}
</style>

<script>
function exportCSV() {
    const rows = [];
    rows.push(['LAPORAN LABA RUGI']);
    rows.push(['Periode', '<?= $date_from ?> s/d <?= $date_to ?>']);
    rows.push([]);
    rows.push(['RINGKASAN']);
    rows.push(['Omzet', <?= $omzet ?>]);
    rows.push(['HPP', <?= $hpp ?>]);
    rows.push(['Laba Kotor', <?= $gross_profit ?>]);
    rows.push(['Total Pengeluaran', <?= $total_expense ?>]);
    rows.push(['Laba Bersih', <?= $net_profit ?>]);
    rows.push(['Gross Margin %', <?= round($gross_margin_pct, 2) ?>]);
    rows.push(['Net Margin %', <?= round($net_margin_pct, 2) ?>]);
    rows.push([]);
    rows.push(['PENGELUARAN PER KATEGORI']);
    rows.push(['Kategori', 'Jumlah', 'Total']);
    <?php foreach ($expense_by_category as $ec): ?>
        rows.push(['<?= addslashes($ec['category']) ?>', <?= (int)$ec['jumlah'] ?>, <?= (float)$ec['total'] ?>]);
    <?php endforeach; ?>
    rows.push([]);

    <?php if (!empty($by_outlet)): ?>
    rows.push(['PER OUTLET']);
    rows.push(['Outlet', 'Order', 'Omzet', 'HPP', 'Laba Kotor', 'Pengeluaran', 'Laba Bersih']);
    <?php foreach ($by_outlet as $b): ?>
        rows.push([
            '<?= addslashes($b['nama']) ?>',
            <?= $b['order'] ?>,
            <?= $b['omzet'] ?>,
            <?= $b['hpp'] ?>,
            <?= $b['gross'] ?>,
            <?= $b['expense'] ?>,
            <?= $b['net'] ?>
        ]);
    <?php endforeach; ?>
    <?php endif; ?>

    const csv = rows.map(r => r.map(c => typeof c === 'string' && c.includes(',') ? '"' + c.replace(/"/g, '""') + '"' : c).join(',')).join('\n');
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'laba-rugi-<?= $date_from ?>-<?= $date_to ?>.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>