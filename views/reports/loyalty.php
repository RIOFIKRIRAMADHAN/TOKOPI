

<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_login();
require_role(['owner','manager','finance']);

$page_title = 'Laporan Loyalty';
$active_menu = 'reports_loyalty';
$tenant_id = current_tenant_id();

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to))   $date_to   = date('Y-m-d');

// Ringkasan
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_member,
        SUM(CASE WHEN tier = 'BRONZE' THEN 1 ELSE 0 END) AS bronze,
        SUM(CASE WHEN tier = 'SILVER' THEN 1 ELSE 0 END) AS silver,
        SUM(CASE WHEN tier = 'GOLD' THEN 1 ELSE 0 END) AS gold,
        COALESCE(SUM(points), 0) AS total_points,
        COALESCE(SUM(total_spent), 0) AS total_spent_all
    FROM customers WHERE tenant_id = ?
");
$stmt->execute([$tenant_id]);
$summary = $stmt->fetch();

// Poin dipakai (redeem)
$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'EARN' THEN points ELSE 0 END), 0) AS total_earned,
        COALESCE(SUM(CASE WHEN type = 'REDEEM' THEN ABS(points) ELSE 0 END), 0) AS total_redeemed,
        COUNT(DISTINCT CASE WHEN type = 'EARN' THEN customer_id END) AS member_earning
    FROM customer_points
    WHERE tenant_id = ? AND DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$tenant_id, $date_from, $date_to]);
$points_stats = $stmt->fetch();

// Top 10 member
$stmt = $pdo->prepare("
    SELECT nama, email, tier, points, total_spent, total_orders
    FROM customers
    WHERE tenant_id = ?
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute([$tenant_id]);
$top_members = $stmt->fetchAll();

// Top 5 voucher
$stmt = $pdo->prepare("
    SELECT rw.nama AS reward_nama, COUNT(*) AS redeemed_count, SUM(r.points_used) AS total_points
    FROM customer_redemptions r
    JOIN rewards rw ON rw.id = r.reward_id
    WHERE DATE(r.redeemed_at) BETWEEN ? AND ?
      AND rw.tenant_id = ?
    GROUP BY rw.id, rw.nama
    ORDER BY redeemed_count DESC
    LIMIT 5
");
$stmt->execute([$date_from, $date_to, $tenant_id]);
$top_rewards = $stmt->fetchAll();

// Tren poin harian (earn vs redeem)
$stmt = $pdo->prepare("
    SELECT DATE(created_at) AS tanggal,
           COALESCE(SUM(CASE WHEN type = 'EARN' THEN points ELSE 0 END), 0) AS earned,
           COALESCE(SUM(CASE WHEN type = 'REDEEM' THEN ABS(points) ELSE 0 END), 0) AS redeemed
    FROM customer_points
    WHERE tenant_id = ? AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at) ORDER BY tanggal
");
$stmt->execute([$tenant_id, $date_from, $date_to]);
$points_daily = $stmt->fetchAll();

$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<?php $show_outlet = false; require __DIR__ . '/../../includes/report_filter.php'; ?>

<?php
$redeem_rate = (int)$points_stats['total_earned'] > 0 ? (int)$points_stats['total_redeemed'] / (int)$points_stats['total_earned'] * 100 : 0;
?>
<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-people" aria-hidden="true"></i> Total member</div>
        <div class="kpi-value"><?= number_format((int)$summary['total_member'], 0, ',', '.') ?></div>
        <div class="kpi-foot"><span><?= (int)$points_stats['member_earning'] ?> aktif di periode ini</span></div>
    </div>
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-coin" aria-hidden="true"></i> Poin beredar</div>
        <div class="kpi-value"><?= number_format((int)$summary['total_points'], 0, ',', '.') ?></div>
        <div class="kpi-foot"><span>kewajiban poin saat ini</span></div>
    </div>
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-plus-circle" aria-hidden="true"></i> Poin diberikan</div>
        <div class="kpi-value">+<?= number_format((int)$points_stats['total_earned'], 0, ',', '.') ?></div>
        <div class="kpi-foot"><span>periode terpilih</span></div>
    </div>
    <div class="kpi">
        <div class="kpi-label"><i class="bi bi-gift" aria-hidden="true"></i> Poin ditukar</div>
        <div class="kpi-value">−<?= number_format((int)$points_stats['total_redeemed'], 0, ',', '.') ?></div>
        <div class="kpi-foot"><span>redeem rate <?= number_format($redeem_rate, 1, ',', '.') ?>%</span></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2">
                <span><i class="bi bi-graph-up me-1" aria-hidden="true"></i> Poin diberikan vs ditukar</span>
                <span class="legend-inline"><span style="--c:var(--chart-1)">Diberikan</span><span style="--c:var(--chart-3)">Ditukar</span></span>
            </div>
            <div class="card-body">
                <?php if (empty($points_daily)): ?>
                    <div class="empty"><i class="bi bi-graph-up" aria-hidden="true"></i>Belum ada aktivitas poin di periode ini.</div>
                <?php else: ?>
                    <div class="chart-box"><canvas id="chPoints" role="img" aria-label="Grafik poin diberikan dan ditukar per hari"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-award me-1" aria-hidden="true"></i> Sebaran tier member</div>
            <div class="card-body">
                <?php if ((int)$summary['total_member'] === 0): ?>
                    <div class="empty"><i class="bi bi-people" aria-hidden="true"></i>Belum ada member.</div>
                <?php else: ?>
                    <div class="chart-box sm"><canvas id="chTier" role="img" aria-label="Grafik jumlah member per tier"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Top Members -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-trophy me-1"></i> Top 10 Member (by Total Belanja)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Tier</th>
                            <th class="text-end">Poin</th>
                            <th class="text-end">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_members)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada member.</td></tr>
                        <?php else: ?>
                            <?php foreach ($top_members as $i => $m): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($m['nama']) ?></td>
                                    <td><span class="badge bg-light text-dark" style="font-size:0.65rem;"><?= e($m['tier']) ?></span></td>
                                    <td class="text-end money"><?= number_format((int)$m['points']) ?></td>
                                    <td class="text-end money"><?= rupiah($m['total_spent']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Rewards -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-gift me-1"></i> Top 5 Reward (Periode)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Reward</th>
                            <th class="text-end">Ditukar</th>
                            <th class="text-end">Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_rewards)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada penukaran di periode ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($top_rewards as $i => $r): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($r['reward_nama']) ?></td>
                                    <td class="text-end money"><?= (int)$r['redeemed_count'] ?>x</td>
                                    <td class="text-end money"><?= number_format((int)$r['total_points']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/tokopi/assets/js/admin-charts.js?v=20261121b"></script>
<script>
<?php if (!empty($points_daily)): ?>
new Chart(document.getElementById('chPoints'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(function ($d) { return date('d/m', strtotime($d['tanggal'])); }, $points_daily)) ?>,
        datasets: [
            { label: 'Diberikan', data: <?= json_encode(array_map(function ($d) { return (int)$d['earned']; }, $points_daily)) ?>, borderColor: TK_COLORS[0], backgroundColor: 'rgba(45,27,10,.08)', fill: true, tension: 0, pointRadius: 2 },
            { label: 'Ditukar', data: <?= json_encode(array_map(function ($d) { return (int)$d['redeemed']; }, $points_daily)) ?>, borderColor: TK_COLORS[2], tension: 0, pointRadius: 2 }
        ]
    },
    options: { interaction: { mode: 'index', intersect: false }, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
});
<?php endif; ?>
<?php if ((int)$summary['total_member'] > 0): ?>
new Chart(document.getElementById('chTier'), {
    type: 'bar',
    data: {
        labels: ['Bronze', 'Silver', 'Gold'],
        datasets: [{ data: [<?= (int)$summary['bronze'] ?>, <?= (int)$summary['silver'] ?>, <?= (int)$summary['gold'] ?>], backgroundColor: ['#A9713F', '#9AA0A6', '#C9A227'], borderRadius: 5, maxBarThickness: 48 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
