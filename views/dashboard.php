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
      AND o.status = 'PAID'
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
      AND o.status = 'PAID'
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
      AND o.status = 'PAID'
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
        AND o.status = 'PAID'
        AND DATE(o.created_at) = CURDATE()
    WHERE ou.tenant_id = ? AND ou.is_active = 1
    GROUP BY ou.id, ou.nama
    ORDER BY omzet DESC
");
$stmt->execute([$tenant_id]);
$branch_compare = $stmt->fetchAll();

// ============================================
// PENJUALAN 7 HARI (untuk chart)
// ============================================
$stmt = $pdo->prepare("
    SELECT
        DATE(o.created_at) AS tanggal,
        COALESCE(SUM(o.grand_total), 0) AS omzet
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.status = 'PAID'
      AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      $outlet_sql
    GROUP BY DATE(o.created_at)
    ORDER BY tanggal ASC
");
$stmt->execute([$tenant_id]);
$chart_data_raw = $stmt->fetchAll();

// Susun 7 hari terakhir (isi 0 kalau tidak ada)
$chart_labels = [];
$chart_values = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($date));
    $val = 0;
    foreach ($chart_data_raw as $row) {
        if ($row['tanggal'] === $date) {
            $val = (float)$row['omzet'];
            break;
        }
    }
    $chart_values[] = $val;
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header filter -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-0">Dashboard <?= $outlet_filter ? 'Outlet' : 'Semua Outlet' ?></h4>
        <small class="text-muted">Data per <?= date('d F Y') ?></small>
    </div>

    <?php if (is_role('owner')): ?>
        <form method="GET" class="d-flex gap-2">
            <select name="outlet" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="0">Semua Outlet</option>
                <?php foreach ($outlets as $o): ?>
                    <option value="<?= (int)$o['id'] ?>" <?= (int)$o['id'] == current_outlet_id() ? 'selected' : '' ?>>
                        <?= e($o['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 h-100">
            <div class="text-muted small text-uppercase">Omzet Hari Ini</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);">
                <?= rupiah($today_omzet) ?>
            </div>
            <small class="text-muted"><?= $today_orders ?> order</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 h-100">
            <div class="text-muted small text-uppercase">HPP Hari Ini</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:#A83232;">
                <?= rupiah($today_hpp) ?>
            </div>
            <small class="text-muted">
                <?= $today_omzet > 0 ? number_format($today_hpp / $today_omzet * 100, 1) : '0' ?>% dari omzet
            </small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 h-100">
            <div class="text-muted small text-uppercase">Profit Hari Ini</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:<?= $today_profit >= 0 ? '#2E7D5B' : '#A83232' ?>;">
                <?= rupiah($today_profit) ?>
            </div>
            <small class="text-muted">
                Margin <?= $today_omzet > 0 ? number_format($today_profit / $today_omzet * 100, 1) : '0' ?>%
            </small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 h-100">
            <div class="text-muted small text-uppercase">Omzet Bulan Ini</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:var(--espresso);">
                <?= rupiah($month_omzet) ?>
            </div>
            <small class="text-muted">
                Profit: <strong style="color:#2E7D5B;"><?= rupiah($month_profit) ?></strong>
            </small>
        </div>
    </div>
</div>

<!-- Chart + Perbandingan Cabang -->
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-graph-up me-1"></i> Omzet 7 Hari Terakhir
            </div>
            <div class="card-body">
                <div style="position: relative; height: 260px;">
                    <canvas id="chartOmzet"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-shop me-1"></i> Perbandingan Cabang (Hari Ini)
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Outlet</th>
                            <th class="text-center">Order</th>
                            <th class="text-end">Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($branch_compare)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Belum ada data.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($branch_compare as $b): ?>
                                <tr>
                                    <td><strong><?= e($b['outlet_nama']) ?></strong></td>
                                    <td class="text-center money"><?= (int)$b['total_order'] ?></td>
                                    <td class="text-end money"><?= rupiah($b['omzet']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Menu Terlaris + Stok Kritis -->
<div class="row g-3">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-trophy me-1"></i> Menu Terlaris (7 hari terakhir)
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Produk</th>
                            <th class="text-center">Terjual</th>
                            <th class="text-end">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_products)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Belum ada penjualan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_products as $i => $p): ?>
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
                                    <td class="text-end money"><?= rupiah($p['total_revenue']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-exclamation-triangle me-1 text-danger"></i> Stok Kritis
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Bahan</th>
                            <th>Outlet</th>
                            <th class="text-end">Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($low_stocks)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">Semua stok aman 👍</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($low_stocks as $s): ?>
                                <tr class="table-danger">
                                    <td>
                                        <strong><?= e($s['ingredient_nama']) ?></strong>
                                        <div class="small text-muted">Min: <?= number_format((float)$s['reorder_point'], 0) ?> <?= e($s['base_unit']) ?></div>
                                    </td>
                                    <td class="small"><?= e($s['outlet_nama']) ?></td>
                                    <td class="text-end money">
                                        <strong style="color:#A83232;"><?= number_format((float)$s['qty'], 2, ',', '.') ?></strong>
                                        <div class="small text-muted"><?= e($s['base_unit']) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('chartOmzet').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Omzet',
                data: <?= json_encode($chart_values) ?>,
                backgroundColor: '#412D15',
                borderRadius: 8,
                maxBarThickness: 40,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + Math.round(context.parsed.y).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                            return 'Rp ' + value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>