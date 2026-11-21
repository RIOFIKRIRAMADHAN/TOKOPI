<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'finance']);

$page_title = 'Dashboard';
$active_menu = 'dashboard';
$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

// Tentukan outlet scope
if (is_role('owner')) {
    $outlet_filter = (int)($_GET['outlet'] ?? 0);
    if ($outlet_filter > 0) {
        $outlet_sql = " AND o.outlet_id = " . $outlet_filter;
    } else {
        $outlet_sql = "";
    }
} else {
    $outlet_sql = " AND o.outlet_id = " . (int)$outlet_id;
    $outlet_filter = $outlet_id;
}

// Ambil daftar outlet untuk dropdown owner
$stmt = $pdo->prepare("SELECT id, code, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
$stmt->execute([$tenant_id]);
$outlets = $stmt->fetchAll();

// ============================================
// KPI HARI INI
// ============================================
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_order,
        COALESCE(SUM(o.grand_total), 0) AS omzet,
        COALESCE(SUM(o.cost_total), 0) AS hpp
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND DATE(o.created_at) = CURDATE()
      $outlet_sql
");
$stmt->execute([$tenant_id]);
$today = $stmt->fetch();

$today_omzet = (float)$today['omzet'];
$today_hpp   = (float)$today['hpp'];
$today_profit = $today_omzet - $today_hpp;
$today_orders = (int)$today['total_order'];

// ============================================
// KPI BULAN INI
// ============================================
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_order,
        COALESCE(SUM(o.grand_total), 0) AS omzet,
        COALESCE(SUM(o.cost_total), 0) AS hpp
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND MONTH(o.created_at) = MONTH(CURDATE())
      AND YEAR(o.created_at) = YEAR(CURDATE())
      $outlet_sql
");
$stmt->execute([$tenant_id]);
$month = $stmt->fetch();
$month_omzet  = (float)$month['omzet'];
$month_hpp    = (float)$month['hpp'];
$month_profit = $month_omzet - $month_hpp;

// ============================================
// MENU TERLARIS 7 HARI
// ============================================
$stmt = $pdo->prepare("
    SELECT
        oi.product_nama,
        oi.variant_nama,
        SUM(oi.qty) AS total_qty,
        SUM(oi.line_total) AS total_revenue
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
      $outlet_sql
    GROUP BY oi.variant_id, oi.product_nama, oi.variant_nama
    ORDER BY total_qty DESC
    LIMIT 8
");
$stmt->execute([$tenant_id]);
$top_products = $stmt->fetchAll();

// ============================================
// STOK KRITIS
// ============================================
$stmt = $pdo->prepare("
    SELECT
        i.nama AS ingredient_nama,
        i.base_unit,
        o.nama AS outlet_nama,
        s.qty,
        s.reorder_point
    FROM stocks s
    JOIN ingredients i ON i.id = s.ingredient_id
    JOIN outlets o ON o.id = s.outlet_id
    WHERE o.tenant_id = ?
      AND i.is_active = 1
      AND s.reorder_point > 0
      AND s.qty <= s.reorder_point
    ORDER BY (s.qty / NULLIF(s.reorder_point, 0)) ASC
    LIMIT 10
");
$stmt->execute([$tenant_id]);
$low_stocks = $stmt->fetchAll();

// ============================================
// PERBANDINGAN CABANG (HARI INI)
// ============================================
$stmt = $pdo->prepare("
    SELECT
        ou.id AS outlet_id,
        ou.nama AS outlet_nama,
        COUNT(o.id) AS total_order,
        COALESCE(SUM(o.grand_total), 0) AS omzet,
        COALESCE(SUM(o.cost_total), 0) AS hpp
    FROM outlets ou
    LEFT JOIN orders o ON o.outlet_id = ou.id
        AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
        AND DATE(o.created_at) = CURDATE()
    WHERE ou.tenant_id = ? AND ou.is_active = 1
    GROUP BY ou.id, ou.nama
    ORDER BY omzet DESC
");
$stmt->execute([$tenant_id]);
$branch_compare = $stmt->fetchAll();

// ============================================
// PEMBANDING: kemarin (s/d jam yang sama) & bulan lalu (s/d tanggal yang sama)
// ============================================
$cmp_sql = "
    SELECT COUNT(*) AS total_order,
           COALESCE(SUM(o.grand_total), 0) AS omzet,
           COALESCE(SUM(o.cost_total), 0) AS hpp
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND o.created_at >= ? AND o.created_at <= ?
      $outlet_sql
";
$stmt = $pdo->prepare($cmp_sql);
$stmt->execute([$tenant_id, date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d H:i:s', strtotime('-1 day'))]);
$yday = $stmt->fetch();
$yday_omzet  = (float)$yday['omzet'];
$yday_hpp    = (float)$yday['hpp'];
$yday_profit = $yday_omzet - $yday_hpp;
$yday_orders = (int)$yday['total_order'];

$lm_start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
$lm_days  = (int)date('t', strtotime('first day of last month'));
$lm_end   = date('Y-m-', strtotime('first day of last month')) . sprintf('%02d', min((int)date('j'), $lm_days)) . ' ' . date('H:i:s');
$stmt = $pdo->prepare($cmp_sql);
$stmt->execute([$tenant_id, $lm_start, $lm_end]);
$lm = $stmt->fetch();
$lm_omzet  = (float)$lm['omzet'];
$lm_profit = $lm_omzet - (float)$lm['hpp'];
$month_orders = (int)$month['total_order'];
$aov       = $month_orders > 0 ? $month_omzet / $month_orders : 0;
$lm_aov    = (int)$lm['total_order'] > 0 ? $lm_omzet / (int)$lm['total_order'] : 0;

// ============================================
// 14 HARI (7 hari ini vs 7 hari sebelumnya) untuk chart tren
// ============================================
$stmt = $pdo->prepare("
    SELECT DATE(o.created_at) AS tanggal, COALESCE(SUM(o.grand_total), 0) AS omzet, COUNT(*) AS n
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
      $outlet_sql
    GROUP BY DATE(o.created_at)
");
$stmt->execute([$tenant_id]);
$by_day = [];
foreach ($stmt->fetchAll() as $r) $by_day[$r['tanggal']] = (float)$r['omzet'];
$chart_labels = []; $chart_values = []; $chart_prev = [];
$hari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
for ($i = 6; $i >= 0; $i--) {
    $d  = date('Y-m-d', strtotime("-$i days"));
    $dp = date('Y-m-d', strtotime('-' . ($i + 7) . ' days'));
    $chart_labels[] = $hari[(int)date('w', strtotime($d))] . ' ' . date('d/m', strtotime($d));
    $chart_values[] = $by_day[$d] ?? 0;
    $chart_prev[]   = $by_day[$dp] ?? 0;
}
$week_total = array_sum($chart_values);
$prev_week_total = array_sum($chart_prev);

// ============================================
// JAM SIBUK HARI INI (order per jam)
// ============================================
$stmt = $pdo->prepare("
    SELECT HOUR(o.created_at) AS jam, COUNT(*) AS n
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.status IN ('PAID', 'PREPARING', 'READY', 'COMPLETED')
      AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      $outlet_sql
    GROUP BY HOUR(o.created_at)
");
$stmt->execute([$tenant_id]);
$hourly = array_fill(0, 24, 0);
foreach ($stmt->fetchAll() as $r) $hourly[(int)$r['jam']] = round((int)$r['n'] / 7, 1);
$open_hours = range(7, 22);
$hour_labels = array_map(function ($h) { return sprintf('%02d', $h); }, $open_hours);
$hour_values = array_map(function ($h) use ($hourly) { return $hourly[$h]; }, $open_hours);

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header + filter -->
<div class="page-head">
    <div>
        <h4 class="mb-0">Ringkasan <?= $outlet_filter ? 'Outlet' : 'Semua Outlet' ?></h4>
        <small class="text-muted">Data per <?= date('d M Y, H:i') ?> · pembanding: kemarin s/d jam yang sama</small>
    </div>
    <?php if (is_role('owner')): ?>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label for="fOutlet" class="small text-muted">Outlet</label>
            <select name="outlet" id="fOutlet" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="0" <?= $outlet_filter == 0 ? 'selected' : '' ?>>Semua Outlet</option>
                <?php foreach ($outlets as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= $outlet_filter == $o['id'] ? 'selected' : '' ?>><?= e($o['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<!-- KPI -->
<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-cash-stack" aria-hidden="true"></i> Omzet hari ini</div>
        <div class="kpi-value"><?= rupiah($today_omzet) ?></div>
        <div class="kpi-foot"><?= delta_badge($today_omzet, $yday_omzet, true, 'vs kemarin') ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-receipt" aria-hidden="true"></i> Order hari ini</div>
        <div class="kpi-value"><?= number_format($today_orders, 0, ',', '.') ?></div>
        <div class="kpi-foot"><?= delta_badge($today_orders, $yday_orders, true, 'vs kemarin') ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-graph-up" aria-hidden="true"></i> Laba kotor hari ini</div>
        <div class="kpi-value" style="color:<?= $today_profit < 0 ? 'var(--adm-down)' : 'inherit' ?>"><?= rupiah($today_profit) ?></div>
        <div class="kpi-foot">
            <?= delta_badge($today_profit, $yday_profit, true) ?>
            <span>margin <?= $today_omzet > 0 ? number_format($today_profit / $today_omzet * 100, 1, ',', '.') : '0' ?>% · HPP <?= rupiah($today_hpp) ?></span>
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-calendar3" aria-hidden="true"></i> Omzet bulan ini</div>
        <div class="kpi-value"><?= rupiah($month_omzet) ?></div>
        <div class="kpi-foot"><?= delta_badge($month_omzet, $lm_omzet, true, 'vs periode sama bln lalu') ?></div>
    </div>
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-basket" aria-hidden="true"></i> Rata-rata per order (bulan ini)</div>
        <div class="kpi-value"><?= rupiah($aov) ?></div>
        <div class="kpi-foot"><?= delta_badge($aov, $lm_aov, true, 'vs bln lalu') ?></div>
    </div>
</div>

<!-- Tren + Jam sibuk -->
<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-graph-up me-1" aria-hidden="true"></i> Omzet 7 hari terakhir</span>
                <span class="d-flex align-items-center gap-3">
                    <span class="legend-inline">
                        <span style="--c:var(--chart-1)">7 hari ini</span>
                        <span style="--c:#D8CFBF">7 hari sebelumnya</span>
                    </span>
                    <?= delta_badge($week_total, $prev_week_total) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="chart-box"><canvas id="chartOmzet" role="img" aria-label="Grafik omzet 7 hari terakhir dibanding 7 hari sebelumnya"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-clock me-1" aria-hidden="true"></i> Jam sibuk <small class="text-muted fw-normal">(rata-rata order/jam, 7 hari)</small></div>
            <div class="card-body">
                <div class="chart-box"><canvas id="chartHour" role="img" aria-label="Grafik rata-rata order per jam"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Menu terlaris -->
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-trophy me-1" aria-hidden="true"></i> Menu terlaris <small class="text-muted fw-normal">(7 hari)</small></div>
            <div class="card-body p-0">
                <?php if (empty($top_products)): ?>
                    <div class="empty"><i class="bi bi-cup" aria-hidden="true"></i>Belum ada penjualan 7 hari terakhir.</div>
                <?php else: $max_qty = max(array_map(function ($p) { return (int)$p['total_qty']; }, $top_products)); ?>
                    <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead><tr><th width="36">#</th><th>Produk</th><th class="text-end">Terjual</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                            <?php foreach ($top_products as $i => $p): ?>
                                <tr>
                                    <td class="num text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= e($p['product_nama']) ?></strong>
                                        <span class="small text-muted"> · <?= e($p['variant_nama']) ?></span>
                                        <div class="progress mt-1" style="height:4px;" aria-hidden="true">
                                            <div class="progress-bar" style="width:<?= $max_qty ? round($p['total_qty'] / $max_qty * 100) : 0 ?>%; background:var(--chart-2);"></div>
                                        </div>
                                    </td>
                                    <td class="text-end num"><strong><?= (int)$p['total_qty'] ?></strong></td>
                                    <td class="text-end money"><?= rupiah($p['total_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cabang -->
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-shop me-1" aria-hidden="true"></i> Cabang hari ini</div>
            <div class="card-body">
                <?php if (empty($branch_compare)): ?>
                    <div class="empty"><i class="bi bi-shop" aria-hidden="true"></i>Belum ada data.</div>
                <?php else: ?>
                    <div class="chart-box sm"><canvas id="chartBranch" role="img" aria-label="Grafik omzet per cabang hari ini"></canvas></div>
                    <table class="table table-sm mb-0 mt-2">
                        <thead><tr><th>Outlet</th><th class="text-end">Order</th><th class="text-end">Omzet</th></tr></thead>
                        <tbody>
                            <?php foreach ($branch_compare as $b): ?>
                                <tr>
                                    <td><?= e($b['outlet_nama']) ?></td>
                                    <td class="text-end num"><?= (int)$b['total_order'] ?></td>
                                    <td class="text-end money"><?= rupiah($b['omzet']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stok kritis -->
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Stok kritis</span>
                <?php if (!empty($low_stocks)): ?><span class="badge bg-danger-subtle text-danger-emphasis"><?= count($low_stocks) ?></span><?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($low_stocks)): ?>
                    <div class="empty"><i class="bi bi-check2-circle" aria-hidden="true"></i>Semua stok di atas batas minimum.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($low_stocks as $s):
                            $ratio = $s['reorder_point'] > 0 ? max(0, min(1, $s['qty'] / $s['reorder_point'])) : 0; ?>
                            <li class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="small"><?= e($s['ingredient_nama']) ?></strong>
                                    <span class="num small" style="color:var(--adm-down)"><?= number_format((float)$s['qty'], 0, ',', '.') ?> / <?= number_format((float)$s['reorder_point'], 0, ',', '.') ?> <?= e($s['base_unit']) ?></span>
                                </div>
                                <div class="small text-muted"><?= e($s['outlet_nama']) ?></div>
                                <div class="progress mt-1" style="height:4px;" aria-hidden="true"><div class="progress-bar bg-danger" style="width:<?= round($ratio * 100) ?>%"></div></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="p-2 text-end"><a href="/tokopi/views/stock.php" class="small">Kelola stok <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/tokopi/assets/js/admin-charts.js?v=20261121b"></script>
<script>
new Chart(document.getElementById('chartOmzet'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [
            { label: '7 hari ini', data: <?= json_encode($chart_values) ?>, backgroundColor: TK_COLORS[0], borderRadius: 6, maxBarThickness: 28 },
            { label: '7 hari sebelumnya', data: <?= json_encode($chart_prev) ?>, backgroundColor: '#D8CFBF', borderRadius: 6, maxBarThickness: 28 }
        ]
    },
    options: {
        plugins: { legend: { display: false }, tooltip: tkMoneyTooltip },
        scales: { y: tkMoneyAxis, x: { grid: { display: false } } }
    }
});
new Chart(document.getElementById('chartHour'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($hour_labels) ?>,
        datasets: [{ label: 'Order/jam', data: <?= json_encode($hour_values) ?>, backgroundColor: TK_COLORS[2], borderRadius: 3, maxBarThickness: 18 }]
    },
    options: {
        plugins: { legend: { display: false } },
        interaction: { mode: 'index', intersect: false },
        scales: { y: { beginAtZero: true }, x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } } }
    }
});
<?php if (!empty($branch_compare)): ?>
new Chart(document.getElementById('chartBranch'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(function ($b) { return preg_replace('/^.*?-\s*/', '', $b['outlet_nama']); }, $branch_compare)) ?>,
        datasets: [{ label: 'Omzet', data: <?= json_encode(array_map(function ($b) { return (float)$b['omzet']; }, $branch_compare)) ?>, backgroundColor: TK_COLORS[1], borderRadius: 5, maxBarThickness: 18 }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false }, tooltip: tkMoneyTooltip },
        scales: { x: tkMoneyAxis, y: { grid: { display: false } } }
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
