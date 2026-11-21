<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','finance']);

$page_title = 'Pelanggan (Member)';
$active_menu = 'customers';
$tenant_id = current_tenant_id();

// Filter
$filter_tier   = $_GET['tier'] ?? '';
$filter_search = trim($_GET['q'] ?? '');

$where = ["tenant_id = ?"];
$params = [$tenant_id];

if (in_array($filter_tier, ['BRONZE', 'SILVER', 'GOLD'], true)) {
    $where[] = "tier = ?";
    $params[] = $filter_tier;
}
if ($filter_search !== '') {
    $where[] = "(nama LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
    $params[] = "%$filter_search%";
}

$where_sql = implode(' AND ', $where);

// Stats
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN tier = 'BRONZE' THEN 1 ELSE 0 END) AS bronze,
        SUM(CASE WHEN tier = 'SILVER' THEN 1 ELSE 0 END) AS silver,
        SUM(CASE WHEN tier = 'GOLD' THEN 1 ELSE 0 END) AS gold,
        COALESCE(SUM(points), 0) AS total_points
    FROM customers WHERE tenant_id = ?
");
$stmt->execute([$tenant_id]);
$stats = $stmt->fetch();

// List
$stmt = $pdo->prepare("
    SELECT * FROM customers
    WHERE $where_sql
    ORDER BY total_spent DESC, id DESC
    LIMIT 200
");
$stmt->execute($params);
$customers = $stmt->fetchAll();

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

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Total Member</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);"><?= number_format((int)$stats['total']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Poin Beredar</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);"><?= number_format((int)$stats['total_points']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Tier Breakdown</div>
            <div style="font-size:0.85rem; margin-top:4px;">
                Bronze <?= (int)$stats['bronze'] ?> · Silver <?= (int)$stats['silver'] ?> · Gold <?= (int)$stats['gold'] ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Member Aktif</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);">
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT customer_id) FROM customer_points WHERE tenant_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                $stmt->execute([$tenant_id]);
                echo number_format((int)$stmt->fetchColumn());
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" value="<?= e($filter_search) ?>" placeholder="Nama, email, atau no HP">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Tier</label>
                <select name="tier" class="form-select form-select-sm">
                    <option value="">Semua Tier</option>
                    <option value="BRONZE" <?= $filter_tier === 'BRONZE' ? 'selected' : '' ?>>Bronze</option>
                    <option value="SILVER" <?= $filter_tier === 'SILVER' ? 'selected' : '' ?>>Silver</option>
                    <option value="GOLD" <?= $filter_tier === 'GOLD' ? 'selected' : '' ?>>Gold</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-espresso btn-sm flex-fill"><i class="bi bi-search me-1"></i> Filter</button>
                <a href="/tokopi/views/customers.php" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tbl_customers" data-enhance data-page-size="25">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th class="text-center">Tier</th>
                    <th class="text-end">Poin</th>
                    <th class="text-end">Total Belanja</th>
                    <th class="text-center">Order</th>
                    <th data-nosort width="100" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada pelanggan terdaftar.</td></tr>
                <?php else: ?>
                    <?php foreach ($customers as $i => $c): ?>
                        <?php
                        $tier_icon = '<i class="bi bi-award-fill" style="color:' . ($c['tier'] === 'GOLD' ? '#C9A227' : ($c['tier'] === 'SILVER' ? '#9AA0A6' : '#A9713F')) . '" aria-hidden="true"></i>';
                        $tier_class = $c['tier'] === 'GOLD' ? 'bg-warning text-dark' : ($c['tier'] === 'SILVER' ? 'bg-secondary' : 'bg-light text-dark');
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= e($c['nama']) ?></strong>
                                <?php if (!$c['is_active']): ?>
                                    <span class="badge bg-secondary ms-1">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:0.85rem;"><?= e($c['email']) ?></div>
                                <?php if ($c['phone']): ?>
                                    <small class="text-muted"><?= e($c['phone']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge <?= $tier_class ?>"><?= $tier_icon ?> <?= e($c['tier']) ?></span></td>
                            <td class="text-end money"><strong style="color:var(--espresso);"><?= number_format((int)$c['points']) ?></strong></td>
                            <td class="text-end money"><?= rupiah($c['total_spent']) ?></td>
                            <td class="text-center money"><?= (int)$c['total_orders'] ?></td>
                            <td class="text-end">
                                <a href="/tokopi/views/customer_detail.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
