<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','kasir']);

$tenant_id = current_tenant_id();
$shift_id  = (int)($_GET['id'] ?? 0);

if ($shift_id <= 0) die('Shift tidak valid.');

$stmt = $pdo->prepare("
    SELECT s.*, u.nama AS cashier_nama, o.nama AS outlet_nama
    FROM shifts s
    JOIN users u ON u.id = s.cashier_id
    JOIN outlets o ON o.id = s.outlet_id
    WHERE s.id = ? AND o.tenant_id = ?
");
$stmt->execute([$shift_id, $tenant_id]);
$shift = $stmt->fetch();

if (!$shift) die('Shift tidak ditemukan.');

$stmt = $pdo->prepare("
    SELECT o.*,
           (SELECT method FROM payments p WHERE p.order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method
    FROM orders o
    WHERE o.shift_id = ?
    ORDER BY o.created_at ASC
");
$stmt->execute([$shift_id]);
$orders = $stmt->fetchAll();

$total_omzet = 0; $cash_total = 0; $qris_total = 0;
foreach ($orders as $o) {
    if ($o['status'] !== 'PAID') continue;
    $total_omzet += (float)$o['grand_total'];
    if ($o['payment_method'] === 'CASH') $cash_total += (float)$o['grand_total'];
    if ($o['payment_method'] === 'QRIS') $qris_total += (float)$o['grand_total'];
}

$page_title = 'Detail Shift #' . $shift_id;
$active_menu = 'shift';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="/tokopi/views/shift.php" class="text-decoration-none small" style="color: var(--espresso);">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Shift
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between">
            <div>
                <h4 class="mb-1">Shift #<?= (int)$shift['id'] ?></h4>
                <div class="text-muted small">
                    <?= e($shift['outlet_nama']) ?> · Kasir: <strong><?= e($shift['cashier_nama']) ?></strong>
                </div>
            </div>
            <?php if ($shift['status'] === 'OPEN'): ?>
                <span class="badge bg-success" style="height:fit-content;">Masih Aktif</span>
            <?php else: ?>
                <span class="badge bg-secondary" style="height:fit-content;">Ditutup</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card p-3">
        <div class="text-muted small text-uppercase">Saldo Awal</div>
        <div class="money" style="font-size:1.25rem; font-weight:700;"><?= rupiah($shift['opening_cash']) ?></div>
    </div></div>
    <div class="col-md-3"><div class="card p-3">
        <div class="text-muted small text-uppercase">Total Omzet</div>
        <div class="money" style="font-size:1.25rem; font-weight:700; color:#2E7D5B;"><?= rupiah($total_omzet) ?></div>
    </div></div>
    <div class="col-md-3"><div class="card p-3">
        <div class="text-muted small text-uppercase">Cash / QRIS</div>
        <div class="money" style="font-size:1rem; font-weight:700;"><?= rupiah($cash_total) ?> / <?= rupiah($qris_total) ?></div>
    </div></div>
    <div class="col-md-3"><div class="card p-3">
        <div class="text-muted small text-uppercase">Selisih</div>
        <?php
        $diff = (float)$shift['difference'];
        $color = abs($diff) < 1 ? '#6c757d' : ($diff > 0 ? '#2E7D5B' : '#A83232');
        ?>
        <div class="money" style="font-size:1.25rem; font-weight:700; color:<?= $color ?>;">
            <?= $diff > 0 ? '+' : '' ?><?= rupiah($diff) ?>
        </div>
    </div></div>
</div>

<?php if ($shift['note']): ?>
    <div class="alert alert-info small">
        <i class="bi bi-chat-left-text me-1"></i> <strong>Catatan:</strong> <?= e($shift['note']) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="bi bi-receipt me-1"></i> Order (<?= count($orders) ?>)</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr>
                <th>Waktu</th><th>Order No</th><th>Bayar</th><th>Status</th>
                <th class="text-end">Total</th><th></th>
            </tr></thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada order.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td style="font-size:.8rem;"><?= date('H:i:s', strtotime($o['created_at'])) ?></td>
                            <td><code><?= e($o['order_no']) ?></code></td>
                            <td style="font-size:.8rem;"><?= e($o['payment_method'] ?? '-') ?></td>
                            <td>
                                <?php
                                $status_class = [
                                    'PAID' => 'bg-success', 'PENDING_PAYMENT' => 'bg-warning text-dark',
                                    'CANCELLED' => 'bg-secondary', 'COMPLETED' => 'bg-success',
                                ];
                                ?>
                                <span class="badge <?= $status_class[$o['status']] ?? 'bg-secondary' ?>"><?= e($o['status']) ?></span>
                            </td>
                            <td class="text-end money"><?= rupiah($o['grand_total']) ?></td>
                            <td><a href="/tokopi/views/receipt.php?order_id=<?= (int)$o['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-receipt"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
