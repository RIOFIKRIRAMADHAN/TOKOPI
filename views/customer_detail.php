<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','finance']);

$tenant_id   = current_tenant_id();
$customer_id = (int)($_GET['id'] ?? 0);

if ($customer_id <= 0) {
    flash_set('error', 'Customer tidak valid.');
    redirect('/tokopi/views/customers.php');
}

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND tenant_id = ?");
$stmt->execute([$customer_id, $tenant_id]);
$customer = $stmt->fetch();

if (!$customer) {
    flash_set('error', 'Customer tidak ditemukan.');
    redirect('/tokopi/views/customers.php');
}

// Ledger poin (50 terakhir)
$stmt = $pdo->prepare("
    SELECT cp.*, o.order_no
    FROM customer_points cp
    LEFT JOIN orders o ON o.id = cp.order_id
    WHERE cp.customer_id = ?
    ORDER BY cp.created_at DESC
    LIMIT 50
");
$stmt->execute([$customer_id]);
$ledger = $stmt->fetchAll();

// Riwayat order (20 terakhir)
$stmt = $pdo->prepare("
    SELECT o.id, o.order_no, o.status, o.grand_total, o.created_at, ou.nama AS outlet_nama
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE o.customer_account_id = ?
    ORDER BY o.id DESC
    LIMIT 20
");
$stmt->execute([$customer_id]);
$orders = $stmt->fetchAll();

// Voucher (semua)
$stmt = $pdo->prepare("
    SELECT r.*, rw.nama AS reward_nama
    FROM customer_redemptions r
    JOIN rewards rw ON rw.id = r.reward_id
    WHERE r.customer_id = ?
    ORDER BY r.id DESC
    LIMIT 20
");
$stmt->execute([$customer_id]);
$vouchers = $stmt->fetchAll();

$page_title = 'Detail Member: ' . $customer['nama'];
$active_menu = 'customers';
$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="/tokopi/views/customers.php" class="text-decoration-none small" style="color: var(--espresso);">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Pelanggan
    </a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Header -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex gap-3 align-items-center">
                <div style="width:60px;height:60px;background:var(--sand);color:var(--espresso);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;">
                    <?= strtoupper(substr($customer['nama'], 0, 1)) ?>
                </div>
                <div>
                    <h4 class="mb-1"><?= e($customer['nama']) ?></h4>
                    <div class="text-muted small">
                        <i class="bi bi-envelope me-1"></i><?= e($customer['email']) ?>
                        <?php if ($customer['phone']): ?>
                            · <i class="bi bi-telephone me-1"></i><?= e($customer['phone']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="small mt-1">Bergabung: <?= date('d M Y', strtotime($customer['created_at'])) ?></div>
                </div>
            </div>
            <div class="text-end">
                <?php $tier_icon = '<i class="bi bi-award-fill" style="color:' . ($customer['tier'] === 'GOLD' ? '#C9A227' : ($customer['tier'] === 'SILVER' ? '#9AA0A6' : '#A9713F')) . '" aria-hidden="true"></i>'; ?>
                <span class="badge bg-dark" style="font-size:0.85rem; padding:6px 14px;"><?= $tier_icon ?> <?= e($customer['tier']) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Poin</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);"><?= number_format((int)$customer['points']) ?></div>
            <button class="btn btn-sm btn-outline-dark mt-2" data-bs-toggle="modal" data-bs-target="#modalAdjust">
                <i class="bi bi-sliders me-1"></i> Adjust
            </button>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Total Belanja</div>
            <div class="money" style="font-size:1.4rem; font-weight:700; color:var(--espresso);"><?= rupiah($customer['total_spent']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Total Order</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);"><?= number_format((int)$customer['total_orders']) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Voucher Aktif</div>
            <div class="money" style="font-size:1.75rem; font-weight:700; color:var(--espresso);">
                <?php
                $count = 0;
                foreach ($vouchers as $v) {
                    if ($v['status'] === 'UNUSED') $count++;
                }
                echo $count;
                ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Ledger Poin -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-star me-1"></i> Riwayat Poin</div>
            <div class="table-responsive" style="max-height:400px;">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th class="text-end">Poin</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ledger)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Belum ada riwayat.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ledger as $l):
                                $pts = (int)$l['points'];
                                $color = $pts > 0 ? '#2E7D5B' : '#A83232';
                            ?>
                                <tr>
                                    <td style="font-size:0.75rem;"><?= date('d/m H:i', strtotime($l['created_at'])) ?></td>
                                    <td><span class="badge bg-light text-dark" style="font-size:0.65rem;"><?= e($l['type']) ?></span></td>
                                    <td class="text-end money" style="color:<?= $color ?>; font-weight:700;"><?= $pts > 0 ? '+' : '' ?><?= $pts ?></td>
                                    <td style="font-size:0.75rem;"><?= e($l['note'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Voucher -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-ticket-perforated me-1"></i> Voucher</div>
            <div class="table-responsive" style="max-height:400px;">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Reward</th>
                            <th>Status</th>
                            <th>Berlaku s/d</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vouchers)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">Belum ada voucher.</td></tr>
                        <?php else: ?>
                            <?php foreach ($vouchers as $v):
                                $cls = $v['status'] === 'UNUSED' ? 'bg-success' : ($v['status'] === 'RESERVED' ? 'bg-warning text-dark' : 'bg-secondary');
                            ?>
                                <tr>
                                    <td style="font-size:0.8rem;"><?= e($v['reward_nama']) ?></td>
                                    <td><span class="badge <?= $cls ?>" style="font-size:0.65rem;"><?= e($v['status']) ?></span></td>
                                    <td style="font-size:0.75rem;"><?= $v['expires_at'] ? date('d/m/Y', strtotime($v['expires_at'])) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Riwayat Order -->
<div class="card mt-3">
    <div class="card-header"><i class="bi bi-receipt me-1"></i> Riwayat Order (20 terakhir)</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Order No</th>
                    <th>Outlet</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada order.</td></tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td style="font-size:0.8rem;"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                            <td><code style="font-size:0.75rem;"><?= e($o['order_no']) ?></code></td>
                            <td style="font-size:0.8rem;"><?= e($o['outlet_nama']) ?></td>
                            <td><span class="badge bg-light text-dark" style="font-size:0.65rem;"><?= e($o['status']) ?></span></td>
                            <td class="text-end money" style="font-size:0.8rem;"><?= rupiah($o['grand_total']) ?></td>
                            <td><a href="/tokopi/views/receipt.php?order_id=<?= (int)$o['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark"><i class="bi bi-receipt"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Adjust Poin -->
<div class="modal fade" id="modalAdjust" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/customer_action.php?action=adjust_points" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="customer_id" value="<?= (int)$customer_id ?>">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Poin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">Poin saat ini: <strong><?= number_format((int)$customer['points']) ?></strong></div>
                <div class="mb-3">
                    <label class="form-label">Mode</label>
                    <select name="mode" class="form-select" required>
                        <option value="add">Tambah poin</option>
                        <option value="subtract">Kurangi poin</option>
                        <option value="set">Set ke angka baru</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah Poin</label>
                    <input type="number" name="amount" class="form-control" required min="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan (wajib untuk audit)</label>
                    <input type="text" name="note" class="form-control" required placeholder="contoh: bonus ulang tahun">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
