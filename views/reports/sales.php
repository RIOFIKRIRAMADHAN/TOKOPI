<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();
require_role(['owner','manager','finance']);

$page_title = 'Laporan Penjualan';
$active_menu = 'reports_sales';
$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

// Filter
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // default awal bulan
$date_to   = $_GET['date_to']   ?? date('Y-m-d');
$filter_outlet = is_role('owner') ? (int)($_GET['outlet'] ?? 0) : (int)$outlet_id;

// Validasi tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $date_to   = date('Y-m-d');
if (strtotime($date_from) > strtotime($date_to)) {
    $tmp = $date_from; $date_from = $date_to; $date_to = $tmp;
}

// SQL outlet filter
$outlet_sql = '';
$outlet_params = [];
if ($filter_outlet > 0) {
    $outlet_sql = " AND o.outlet_id = ? ";
    $outlet_params[] = $filter_outlet;
}

// Tenant filter (SELALU)
$tenant_filter_sql = " AND ou.tenant_id = ? ";
$tenant_param = [$tenant_id];

// Ambil outlets
$stmt = $pdo->prepare("SELECT id, code, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
$stmt->execute([$tenant_id]);
$outlets = $stmt->fetchAll();

// ============================================
// KPI SUMMARY
// ============================================
$sql = "
    SELECT
        COUNT(*) AS total_order,
        COALESCE(SUM(o.grand_total), 0) AS total_omzet,
        COALESCE(SUM(o.cost_total), 0) AS total_hpp,
        COALESCE(SUM(o.discount_total), 0) AS total_diskon
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND DATE(o.created_at) BETWEEN ? AND ?
      $tenant_filter_sql
      $outlet_sql
";
$params = array_merge([$date_from, $date_to], $tenant_param, $outlet_params);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$summary = $stmt->fetch();

// Periode pembanding: panjang sama, tepat sebelum periode terpilih
$__days    = (int)round((strtotime($date_to) - strtotime($date_from)) / 86400) + 1;
$prev_to   = date('Y-m-d', strtotime($date_from . ' -1 day'));
$prev_from = date('Y-m-d', strtotime($prev_to . ' -' . ($__days - 1) . ' days'));
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$prev_from, $prev_to], $tenant_param, $outlet_params));
$prev = $stmt->fetch();
$prev_omzet  = (float)$prev['total_omzet'];
$prev_profit = $prev_omzet - (float)$prev['total_hpp'];
$prev_orders = (int)$prev['total_order'];

$total_omzet = (float)$summary['total_omzet'];
$total_hpp   = (float)$summary['total_hpp'];
$total_profit = $total_omzet - $total_hpp;
$margin_pct = $total_omzet > 0 ? ($total_profit / $total_omzet * 100) : 0;

// ============================================
// BREAKDOWN PER HARI
// ============================================
$sql = "
    SELECT
        DATE(o.created_at) AS tanggal,
        COUNT(*) AS total_order,
        COALESCE(SUM(o.grand_total), 0) AS omzet,
        COALESCE(SUM(o.cost_total), 0) AS hpp
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND DATE(o.created_at) BETWEEN ? AND ?
      $tenant_filter_sql
      $outlet_sql
    GROUP BY DATE(o.created_at)
    ORDER BY tanggal ASC
";
$params = array_merge([$date_from, $date_to], $tenant_param, $outlet_params);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daily = $stmt->fetchAll();

// ============================================
// BREAKDOWN PER METODE BAYAR
// ============================================
$sql = "
    SELECT
        p.method,
        COUNT(DISTINCT p.order_id) AS total_order,
        COALESCE(SUM(p.amount), 0) AS total
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND p.status = 'SETTLED'
      AND DATE(o.created_at) BETWEEN ? AND ?
      $tenant_filter_sql
      $outlet_sql
    GROUP BY p.method
    ORDER BY total DESC
";
$params = array_merge([$date_from, $date_to], $tenant_param, $outlet_params);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$by_payment = $stmt->fetchAll();

// ============================================
// BREAKDOWN PER OUTLET (kalau filter = semua)
// ============================================
$by_outlet = [];
if ($filter_outlet === 0) {
    $sql = "
        SELECT
            ou.nama AS outlet_nama,
            COUNT(o.id) AS total_order,
            COALESCE(SUM(o.grand_total), 0) AS omzet,
            COALESCE(SUM(o.cost_total), 0) AS hpp
        FROM outlets ou
        LEFT JOIN orders o ON o.outlet_id = ou.id
            AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
            AND DATE(o.created_at) BETWEEN ? AND ?
        WHERE ou.tenant_id = ? AND ou.is_active = 1
        GROUP BY ou.id, ou.nama
        ORDER BY omzet DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date_from, $date_to, $tenant_id]);
    $by_outlet = $stmt->fetchAll();
}

// ============================================
// TOP 10 PRODUK
// ============================================
$sql = "
    SELECT
        oi.product_nama,
        oi.variant_nama,
        SUM(oi.qty) AS total_qty,
        SUM(oi.line_total) AS total_revenue,
        SUM(oi.qty * oi.unit_cost_snapshot) AS total_hpp
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND DATE(o.created_at) BETWEEN ? AND ?
      $tenant_filter_sql
      $outlet_sql
    GROUP BY oi.variant_id, oi.product_nama, oi.variant_nama
    ORDER BY total_qty DESC
    LIMIT 10
";
$params = array_merge([$date_from, $date_to], $tenant_param, $outlet_params);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$top_products = $stmt->fetchAll();

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
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php
$report_actions = '<button type="button" class="btn btn-outline-espresso btn-sm" onclick="window.print()"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print</button>'
    . '<button type="button" class="btn btn-outline-espresso btn-sm" onclick="exportCSV()"><i class="bi bi-filetype-csv me-1" aria-hidden="true"></i>CSV</button>';
require __DIR__ . '/../../includes/report_filter.php';
?>

<!-- Print header (hanya muncul saat print) -->
<div class="print-only" style="display:none;">
    <h3 style="text-align:center;">Laporan Penjualan</h3>
    <p style="text-align:center;">
        Periode: <?= date('d/m/Y', strtotime($date_from)) ?> s/d <?= date('d/m/Y', strtotime($date_to)) ?>
        <?php if ($filter_outlet > 0): ?>
            <?php foreach ($outlets as $o): if ((int)$o['id'] === $filter_outlet): ?>
                <br>Outlet: <?= e($o['nama']) ?>
            <?php endif; endforeach; ?>
        <?php endif; ?>
    </p>
</div>

<!-- KPI -->
<?php
$aov = (int)$summary['total_order'] > 0 ? $total_omzet / (int)$summary['total_order'] : 0;
$prev_aov = $prev_orders > 0 ? $prev_omzet / $prev_orders : 0;
$cmp_label = 'vs ' . date('d/m', strtotime($prev_from)) . '–' . date('d/m', strtotime($prev_to));
?>
<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-label">Omzet</div>
        <div class="kpi-value"><?= rupiah($total_omzet) ?></div>
        <div class="kpi-foot"><?= delta_badge($total_omzet, $prev_omzet, true, $cmp_label) ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Total order</div>
        <div class="kpi-value"><?= number_format((int)$summary['total_order'], 0, ',', '.') ?></div>
        <div class="kpi-foot"><?= delta_badge((int)$summary['total_order'], $prev_orders, true, $cmp_label) ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Laba kotor</div>
        <div class="kpi-value" style="color:<?= $total_profit < 0 ? 'var(--adm-down)' : 'inherit' ?>"><?= rupiah($total_profit) ?></div>
        <div class="kpi-foot"><?= delta_badge($total_profit, $prev_profit) ?> <span>margin <?= number_format($margin_pct, 1, ',', '.') ?>%</span></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">HPP</div>
        <div class="kpi-value"><?= rupiah($total_hpp) ?></div>
        <div class="kpi-foot"><span><?= $total_omzet > 0 ? number_format($total_hpp / $total_omzet * 100, 1, ',', '.') : 0 ?>% dari omzet</span></div>
    </div>
    <div class="kpi">
        <div class="kpi-label">Rata-rata per order</div>
        <div class="kpi-value"><?= rupiah($aov) ?></div>
        <div class="kpi-foot"><?= delta_badge($aov, $prev_aov) ?></div>
    </div>
</div>

<!-- Grafik -->
<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2">
                <span><i class="bi bi-graph-up me-1" aria-hidden="true"></i> Tren harian</span>
                <span class="legend-inline"><span style="--c:var(--chart-1)">Omzet</span><span style="--c:var(--chart-2)">Laba kotor</span><span style="--c:var(--chart-3)">Order</span></span>
            </div>
            <div class="card-body">
                <?php if (empty($daily)): ?>
                    <div class="empty"><i class="bi bi-graph-up" aria-hidden="true"></i>Tidak ada transaksi pada periode ini.</div>
                <?php else: ?>
                    <div class="chart-box"><canvas id="chDaily" role="img" aria-label="Grafik omzet, laba, dan order per hari"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-credit-card me-1" aria-hidden="true"></i> Komposisi metode bayar</div>
            <div class="card-body">
                <?php if (empty($by_payment)): ?>
                    <div class="empty"><i class="bi bi-credit-card" aria-hidden="true"></i>Belum ada pembayaran.</div>
                <?php else: ?>
                    <div class="chart-box"><canvas id="chPay" role="img" aria-label="Grafik komposisi metode pembayaran"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Perbandingan Outlet (kalau semua) -->
<?php if ($filter_outlet === 0 && !empty($by_outlet)): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-shop me-1"></i> Perbandingan Outlet</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Outlet</th>
                    <th class="text-center">Order</th>
                    <th class="text-end">Omzet</th>
                    <th class="text-end">HPP</th>
                    <th class="text-end">Profit</th>
                    <th class="text-end">Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($by_outlet as $b):
                    $profit = (float)$b['omzet'] - (float)$b['hpp'];
                    $margin = (float)$b['omzet'] > 0 ? $profit / (float)$b['omzet'] * 100 : 0;
                ?>
                    <tr>
                        <td><strong><?= e($b['outlet_nama']) ?></strong></td>
                        <td class="text-center money"><?= (int)$b['total_order'] ?></td>
                        <td class="text-end money"><?= rupiah($b['omzet']) ?></td>
                        <td class="text-end money" style="color:#A83232;"><?= rupiah($b['hpp']) ?></td>
                        <td class="text-end money" style="color:#2E7D5B;"><?= rupiah($profit) ?></td>
                        <td class="text-end money"><?= number_format($margin, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Breakdown -->
<div class="row g-3 mb-3">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calendar3 me-1"></i> Per Hari</div>
            <div class="table-responsive" style="max-height: 400px;">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-center">Order</th>
                            <th class="text-end">Omzet</th>
                            <th class="text-end">HPP</th>
                            <th class="text-end">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daily)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data.</td></tr>
                        <?php else: ?>
                            <?php
                            $sum_o = 0; $sum_omzet = 0; $sum_hpp = 0;
                            foreach ($daily as $d):
                                $profit = (float)$d['omzet'] - (float)$d['hpp'];
                                $sum_o += (int)$d['total_order'];
                                $sum_omzet += (float)$d['omzet'];
                                $sum_hpp += (float)$d['hpp'];
                            ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($d['tanggal'])) ?></td>
                                    <td class="text-center money"><?= (int)$d['total_order'] ?></td>
                                    <td class="text-end money"><?= rupiah($d['omzet']) ?></td>
                                    <td class="text-end money" style="color:#A83232;"><?= rupiah($d['hpp']) ?></td>
                                    <td class="text-end money" style="color:<?= $profit >= 0 ? '#2E7D5B' : '#A83232' ?>;"><?= rupiah($profit) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="background:#FAF9F5; font-weight:700;">
                                <td>TOTAL</td>
                                <td class="text-center money"><?= $sum_o ?></td>
                                <td class="text-end money"><?= rupiah($sum_omzet) ?></td>
                                <td class="text-end money" style="color:#A83232;"><?= rupiah($sum_hpp) ?></td>
                                <td class="text-end money" style="color:#2E7D5B;"><?= rupiah($sum_omzet - $sum_hpp) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-credit-card me-1"></i> Per Metode Bayar</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Metode</th>
                            <th class="text-center">Order</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($by_payment)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data.</td></tr>
                        <?php else: ?>
                            <?php foreach ($by_payment as $p):
                                $pct = $total_omzet > 0 ? ((float)$p['total'] / $total_omzet * 100) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <?php
                                        $icon = '<i class="bi ' . ($p['method'] === 'CASH' ? 'bi-cash-coin' : ($p['method'] === 'QRIS' ? 'bi-qr-code' : 'bi-credit-card')) . '" aria-hidden="true"></i>';
                                        ?>
                                        <?= $icon ?> <strong><?= e($p['method']) ?></strong>
                                    </td>
                                    <td class="text-center money"><?= (int)$p['total_order'] ?></td>
                                    <td class="text-end money"><?= rupiah($p['total']) ?></td>
                                    <td class="text-end money text-muted"><?= number_format($pct, 1) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Top Produk -->
<div class="card">
    <div class="card-header"><i class="bi bi-trophy me-1"></i> Top 10 Produk Terlaris</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th>Produk</th>
                    <th class="text-center">Terjual</th>
                    <th class="text-end">Revenue</th>
                    <th class="text-end">HPP</th>
                    <th class="text-end">Profit</th>
                    <th class="text-end">Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_products)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Belum ada penjualan.</td></tr>
                <?php else: ?>
                    <?php foreach ($top_products as $i => $p):
                        $rev = (float)$p['total_revenue'];
                        $hpp = (float)$p['total_hpp'];
                        $profit = $rev - $hpp;
                        $margin = $rev > 0 ? $profit / $rev * 100 : 0;
                    ?>
                        <tr>
                            <td>
                                <?php if ($i < 3): ?>
                                    <span class="badge bg-warning text-dark"><?= $i + 1 ?></span>
                                <?php else: ?>
                                    <span class="text-muted"><?= $i + 1 ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($p['product_nama']) ?></strong>
                                <div class="small text-muted"><?= e($p['variant_nama']) ?></div>
                            </td>
                            <td class="text-center money"><strong><?= (int)$p['total_qty'] ?></strong></td>
                            <td class="text-end money"><?= rupiah($rev) ?></td>
                            <td class="text-end money" style="color:#A83232;"><?= rupiah($hpp) ?></td>
                            <td class="text-end money" style="color:#2E7D5B;"><?= rupiah($profit) ?></td>
                            <td class="text-end money"><?= number_format($margin, 1) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Print style -->
<style>
@media print {
    /* Sembunyikan navigasi & elemen interaktif */
    .sidebar,
    .topbar,
    form,
    .btn,
    .alert,
    #export-bar {
        display: none !important;
    }

    /* Tampilkan header print */
    .print-only {
        display: block !important;
    }

    /* Reset layout */
    .main-wrapper {
        margin-left: 0 !important;
    }
    .content {
        padding: 0 !important;
    }
    body {
        background: #fff !important;
        font-size: 11px !important;
    }

    /* Card tidak pecah antar halaman */
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
        border: 1px solid #ccc !important;
        box-shadow: none !important;
        margin-bottom: 12px !important;
    }
    .card-header {
        background: #f5f5f5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Angka lebih kecil */
    .money {
        font-size: 11px !important;
    }

    /* Badge background tetap tercetak */
    .badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Table tidak overflow */
    .table-responsive {
        overflow: visible !important;
        max-height: none !important;
    }

    /* Hindari page break setelah judul */
    h4, h5 {
        page-break-after: avoid;
    }

    /* Row table tidak terpotong antar halaman */
    tr {
        page-break-inside: avoid;
    }
}
</style>

<script>
function exportCSV() {
    const rows = [];
    rows.push(['Laporan Penjualan Tokopi']);
    rows.push(['Periode', '<?= $date_from ?> s/d <?= $date_to ?>']);
    rows.push([]);
    rows.push(['Ringkasan']);
    rows.push(['Total Order', <?= (int)$summary['total_order'] ?>]);
    rows.push(['Total Omzet', <?= $total_omzet ?>]);
    rows.push(['Total HPP', <?= $total_hpp ?>]);
    rows.push(['Total Profit', <?= $total_profit ?>]);
    rows.push(['Margin %', <?= round($margin_pct, 2) ?>]);
    rows.push([]);
    rows.push(['Per Hari']);
    rows.push(['Tanggal', 'Order', 'Omzet', 'HPP', 'Profit']);
    <?php foreach ($daily as $d): ?>
        rows.push(['<?= $d['tanggal'] ?>', <?= (int)$d['total_order'] ?>, <?= (float)$d['omzet'] ?>, <?= (float)$d['hpp'] ?>, <?= (float)$d['omzet'] - (float)$d['hpp'] ?>]);
    <?php endforeach; ?>
    rows.push([]);
    rows.push(['Top Produk']);
    rows.push(['Produk', 'Varian', 'Qty', 'Revenue', 'HPP', 'Profit']);
    <?php foreach ($top_products as $p): ?>
        rows.push([
            '<?= addslashes($p['product_nama']) ?>',
            '<?= addslashes($p['variant_nama']) ?>',
            <?= (int)$p['total_qty'] ?>,
            <?= (float)$p['total_revenue'] ?>,
            <?= (float)$p['total_hpp'] ?>,
            <?= (float)$p['total_revenue'] - (float)$p['total_hpp'] ?>
        ]);
    <?php endforeach; ?>

    const csv = rows.map(r => r.map(c => typeof c === 'string' && c.includes(',') ? '"' + c.replace(/"/g, '""') + '"' : c).join(',')).join('\n');
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'laporan-penjualan-<?= $date_from ?>-<?= $date_to ?>.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/tokopi/assets/js/admin-charts.js?v=20261121b"></script>
<script>
<?php if (!empty($daily)): ?>
new Chart(document.getElementById('chDaily'), {
    data: {
        labels: <?= json_encode(array_map(function ($d) { return date('d/m', strtotime($d['tanggal'])); }, $daily)) ?>,
        datasets: [
            { type: 'bar', label: 'Omzet', data: <?= json_encode(array_map(function ($d) { return (float)$d['omzet']; }, $daily)) ?>, backgroundColor: TK_COLORS[0], borderRadius: 4, maxBarThickness: 26, yAxisID: 'y', order: 2 },
            { type: 'line', label: 'Laba kotor', data: <?= json_encode(array_map(function ($d) { return (float)$d['omzet'] - (float)$d['hpp']; }, $daily)) ?>, borderColor: TK_COLORS[1], backgroundColor: TK_COLORS[1], tension: 0, pointRadius: 3, yAxisID: 'y', order: 1 },
            { type: 'line', label: 'Order', data: <?= json_encode(array_map(function ($d) { return (int)$d['total_order']; }, $daily)) ?>, borderColor: TK_COLORS[2], borderDash: [4, 4], pointRadius: 0, tension: 0, yAxisID: 'y2', order: 0 }
        ]
    },
    options: {
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + (c.dataset.yAxisID === 'y2' ? c.raw + ' order' : tkRp(c.raw)); } } } },
        scales: {
            y: tkMoneyAxis,
            y2: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { precision: 0 } },
            x: { grid: { display: false }, ticks: { maxTicksLimit: 12 } }
        }
    }
});
<?php endif; ?>
<?php if (!empty($by_payment)): ?>
new Chart(document.getElementById('chPay'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(function ($p) { return strtoupper($p['method']); }, $by_payment)) ?>,
        datasets: [{ data: <?= json_encode(array_map(function ($p) { return (float)$p['total']; }, $by_payment)) ?>, backgroundColor: TK_COLORS, borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
        cutout: '62%',
        plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: function (c) { var t = c.dataset.data.reduce(function (a, b) { return a + b; }, 0); return ' ' + c.label + ': ' + tkRp(c.raw) + ' (' + (t ? Math.round(c.raw / t * 100) : 0) + '%)'; } } }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
