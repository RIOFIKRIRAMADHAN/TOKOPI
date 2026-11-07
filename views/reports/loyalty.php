

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

$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($date_from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($date_to) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-espresso btn-sm"><i class="bi bi-search me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Total Member</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);"><?= number_format((int)$summary['total_member']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Poin Beredar</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);"><?= number_format((int)$summary['total_points']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Poin Diberikan</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:#2E7D5B;">+<?= number_format((int)$points_stats['total_earned']) ?></div>
            <small class="text-muted"><?= (int)$points_stats['member_earning'] ?> member aktif</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Poin Ditukar</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:#A83232;">−<?= number_format((int)$points_stats['total_redeemed']) ?></div>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
