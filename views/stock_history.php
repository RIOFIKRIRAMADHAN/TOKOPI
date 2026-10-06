<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$tenant_id     = current_tenant_id();
$outlet_id     = (int)($_GET['outlet_id'] ?? current_outlet_id());
$ingredient_id = (int)($_GET['ingredient_id'] ?? 0);

if ($outlet_id <= 0 || $ingredient_id <= 0) {
    die('Parameter tidak lengkap.');
}

// Cek ingredient milik tenant
$stmt = $pdo->prepare("
    SELECT i.nama, i.code, i.base_unit, o.nama AS outlet_nama
    FROM ingredients i
    JOIN outlets o ON o.id = ?
    WHERE i.id = ? AND i.tenant_id = ? AND o.tenant_id = ?
");
$stmt->execute([$outlet_id, $ingredient_id, $tenant_id, $tenant_id]);
$info = $stmt->fetch();

if (!$info) die('Data tidak ditemukan.');

// Ambil stok saat ini
$stmt = $pdo->prepare("SELECT qty FROM stocks WHERE outlet_id = ? AND ingredient_id = ?");
$stmt->execute([$outlet_id, $ingredient_id]);
$current_qty = (float)$stmt->fetchColumn();

// Ambil movements
$stmt = $pdo->prepare("
    SELECT sm.*, u.nama AS user_nama
    FROM stock_movements sm
    LEFT JOIN users u ON u.id = sm.created_by_id
    WHERE sm.outlet_id = ? AND sm.ingredient_id = ?
    ORDER BY sm.created_at DESC, sm.id DESC
    LIMIT 200
");
$stmt->execute([$outlet_id, $ingredient_id]);
$movements = $stmt->fetchAll();

$page_title = 'Riwayat Stok: ' . $info['nama'];
$active_menu = 'stock';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="/tokopi/views/stock.php?outlet=<?= (int)$outlet_id ?>" class="text-decoration-none small" style="color: var(--espresso);">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Stok
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h4 class="mb-1"><?= e($info['nama']) ?> <code style="font-size:.8rem;"><?= e($info['code']) ?></code></h4>
        <div class="text-muted small">
            Outlet: <strong><?= e($info['outlet_nama']) ?></strong>
            · Unit: <strong><?= e($info['base_unit']) ?></strong>
            · Stok Sekarang:
            <strong style="color: var(--espresso);"><?= number_format($current_qty, 2, ',', '.') ?> <?= e($info['base_unit']) ?></strong>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Riwayat Pergerakan Stok (200 terakhir)</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th width="160">Waktu</th>
                    <th width="140">Tipe</th>
                    <th width="140" class="text-end">Qty</th>
                    <th>Referensi</th>
                    <th>Catatan</th>
                    <th width="180">Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movements)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada pergerakan stok.</td></tr>
                <?php else: ?>
                    <?php foreach ($movements as $m): ?>
                        <?php
                        $qty = (float)$m['qty'];
                        $type_class = [
                            'PURCHASE_IN'  => 'bg-success',
                            'SALE_OUT'     => 'bg-primary',
                            'ADJUSTMENT'   => 'bg-info text-dark',
                            'TRANSFER_IN'  => 'bg-success',
                            'TRANSFER_OUT' => 'bg-warning text-dark',
                            'WASTE'        => 'bg-danger',
                            'OPENING'      => 'bg-secondary',
                            'RETURN_IN'    => 'bg-success',
                            'RETURN_OUT'   => 'bg-warning text-dark',
                        ];
                        ?>
                        <tr>
                            <td style="font-size:.8rem;"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                            <td>
                                <span class="badge <?= $type_class[$m['type']] ?? 'bg-secondary' ?>">
                                    <?= e($m['type']) ?>
                                </span>
                            </td>
                            <td class="text-end money">
                                <strong style="color: <?= $qty >= 0 ? '#2E7D5B' : '#A83232' ?>;">
                                    <?= $qty >= 0 ? '+' : '' ?><?= number_format($qty, 2, ',', '.') ?>
                                </strong>
                            </td>
                            <td style="font-size:.8rem;">
                                <?php if ($m['ref_type'] === 'ORDER'): ?>
                                    <a href="/tokopi/views/receipt.php?order_id=<?= (int)$m['ref_id'] ?>" target="_blank" style="color: var(--espresso);">
                                        Order #<?= e($m['ref_id']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted"><?= e($m['ref_type'] ?? '—') ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.85rem;"><?= e($m['note'] ?? '—') ?></td>
                            <td style="font-size:.8rem;"><?= e($m['user_nama'] ?? 'System') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
