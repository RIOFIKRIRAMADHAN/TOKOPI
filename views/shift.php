<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'kasir']);

$page_title = 'Shift Kasir';
$active_menu = 'shift';
$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();
$user_id   = current_user_id();

if (!$outlet_id) {
    die('Anda belum di-assign ke outlet.');
}

$stmt = $pdo->prepare("SELECT nama FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$outlet = $stmt->fetch();

// Cek apakah ada shift OPEN untuk kasir ini
$stmt = $pdo->prepare("
    SELECT s.*, u.nama AS cashier_nama
    FROM shifts s
    JOIN users u ON u.id = s.cashier_id
    WHERE s.cashier_id = ? AND s.status = 'OPEN'
    ORDER BY s.id DESC LIMIT 1
");
$stmt->execute([$user_id]);
$active_shift = $stmt->fetch();

// Riwayat shift CLOSED
$stmt = $pdo->prepare("
    SELECT s.*, u.nama AS cashier_nama
    FROM shifts s
    JOIN users u ON u.id = s.cashier_id
    WHERE s.outlet_id = ? AND s.status = 'CLOSED'
    ORDER BY s.id DESC
    LIMIT 30
");
$stmt->execute([$outlet_id]);
$history = $stmt->fetchAll();

$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../includes/header.php';
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

<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-shop me-1"></i> Outlet:
                <?php if (is_owner()): ?>
                    <select class="form-select form-select-sm d-inline-block ms-2"
                        style="width: auto;"
                        onchange="window.location.href='/tokopi/actions/switch_outlet.php?outlet_id=' + this.value + '&redirect=' + encodeURIComponent('/tokopi/views/shift.php')">
                        <?php
                        $stmt = $pdo->prepare("SELECT id, code, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
                        $stmt->execute([$tenant_id]);
                        foreach ($stmt->fetchAll() as $o):
                        ?>
                            <option value="<?= (int)$o['id'] ?>" <?= $outlet_id == $o['id'] ? 'selected' : '' ?>>
                                <?= e($o['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <?= e($outlet['nama']) ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($active_shift): ?>
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-success me-2">AKTIF</span>
                        <small class="text-muted">Sejak <?= date('d/m/Y H:i', strtotime($active_shift['opened_at'])) ?></small>
                    </div>

                    <?php
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) AS total_order, COALESCE(SUM(o.grand_total), 0) AS total_omzet
                        FROM orders o
                        WHERE o.shift_id = ? AND o.status = 'PAID'
                    ");
                    $stmt->execute([$active_shift['id']]);
                    $sum = $stmt->fetch();

                    $stmt = $pdo->prepare("
                        SELECT COALESCE(SUM(p.amount), 0)
                        FROM payments p
                        JOIN orders o ON o.id = p.order_id
                        WHERE o.shift_id = ? AND p.method = 'CASH' AND p.status = 'SETTLED'
                    ");
                    $stmt->execute([$active_shift['id']]);
                    $cash_in = (float)$stmt->fetchColumn();

                    $expected = (float)$active_shift['opening_cash'] + $cash_in;
                    ?>

                    <table class="table table-sm mb-3">
                        <tr>
                            <td class="text-muted">Kasir</td>
                            <td class="text-end"><strong><?= e($active_shift['cashier_nama']) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Saldo Awal</td>
                            <td class="text-end money"><?= rupiah($active_shift['opening_cash']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Order Sukses</td>
                            <td class="text-end money"><?= (int)$sum['total_order'] ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Omzet Shift</td>
                            <td class="text-end money"><?= rupiah($sum['total_omzet']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kas Masuk (Cash)</td>
                            <td class="text-end money text-success">+ <?= rupiah($cash_in) ?></td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Kas Seharusnya</strong></td>
                            <td class="text-end money"><strong style="color: var(--espresso);"><?= rupiah($expected) ?></strong></td>
                        </tr>
                    </table>

                    <button class="btn btn-espresso w-100" data-bs-toggle="modal" data-bs-target="#modalTutup">
                        <i class="bi bi-box-arrow-right me-1"></i> Tutup Shift
                    </button>
                    <a href="/tokopi/views/pos.php" class="btn btn-outline-dark w-100 mt-2">
                        <i class="bi bi-cash-register me-1"></i> Ke POS
                    </a>

                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-clock-history" style="font-size: 3rem; color: var(--sand);"></i>
                        <h5 class="mt-3">Belum Ada Shift Aktif</h5>
                        <p class="text-muted">Buka shift dulu sebelum mulai transaksi.</p>
                    </div>

                    <button class="btn btn-espresso w-100" data-bs-toggle="modal" data-bs-target="#modalBuka">
                        <i class="bi bi-play-circle me-1"></i> Buka Shift Baru
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-clock-history me-1"></i> Riwayat Shift
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kasir</th>
                                <th>Buka</th>
                                <th>Tutup</th>
                                <th class="text-end">Awal</th>
                                <th class="text-end">Akhir</th>
                                <th class="text-end">Selisih</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">Belum ada riwayat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $i => $h): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= e($h['cashier_nama']) ?></td>
                                        <td style="font-size:.8rem;"><?= date('d/m H:i', strtotime($h['opened_at'])) ?></td>
                                        <td style="font-size:.8rem;"><?= date('d/m H:i', strtotime($h['closed_at'])) ?></td>
                                        <td class="text-end money" style="font-size:.8rem;"><?= number_format((float)$h['opening_cash'], 0, ',', '.') ?></td>
                                        <td class="text-end money" style="font-size:.8rem;"><?= number_format((float)$h['closing_cash'], 0, ',', '.') ?></td>
                                        <td class="text-end money" style="font-size:.8rem;">
                                            <?php
                                            $diff = (float)$h['difference'];
                                            $color = abs($diff) < 1 ? 'text-muted' : ($diff > 0 ? 'text-success' : 'text-danger');
                                            ?>
                                            <span class="<?= $color ?>"><?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 0, ',', '.') ?></span>
                                        </td>
                                        <td><a href="/tokopi/views/shift_detail.php?id=<?= (int)$h['id'] ?>" class="btn btn-sm btn-outline-dark"><i class="bi bi-eye"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL BUKA -->
<div class="modal fade" id="modalBuka" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="/tokopi/actions/shift_action.php?action=open" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-play-circle me-1"></i> Buka Shift Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Masukkan jumlah uang tunai di kasir saat ini.
                </div>
                <div class="mb-3">
                    <label class="form-label">Saldo Awal Kas <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="opening_cash" class="form-control"
                            min="0" step="1000" value="200000" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="note" class="form-control" placeholder="contoh: shift pagi">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Buka Shift</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL TUTUP -->
<?php if ($active_shift): ?>
    <div class="modal fade" id="modalTutup" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="/tokopi/actions/shift_action.php?action=close" method="POST" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden" name="shift_id" value="<?= (int)$active_shift['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-right me-1"></i> Tutup Shift</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm mb-3">
                        <tr>
                            <td class="text-muted">Saldo Awal</td>
                            <td class="text-end money"><?= rupiah($active_shift['opening_cash']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kas Masuk</td>
                            <td class="text-end money text-success">+ <?= rupiah($cash_in) ?></td>
                        </tr>
                        <tr class="border-top">
                            <td><strong>Kas Seharusnya</strong></td>
                            <td class="text-end money"><strong><?= rupiah($expected) ?></strong></td>
                        </tr>
                    </table>

                    <div class="mb-3">
                        <label class="form-label">Kas Fisik Aktual <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="closing_cash" id="closing_cash" class="form-control"
                                min="0" step="1000" value="<?= (int)$expected ?>" required autofocus>
                        </div>
                    </div>

                    <div class="p-3 rounded" id="diff-box" style="background:#FAF9F5;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Selisih</span>
                            <span class="money fw-bold" id="diff-display" style="font-size:1.25rem; color: #2E7D5B;">Rp 0</span>
                        </div>
                        <small class="text-muted" id="diff-note">Sesuai. Bagus!</small>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="note" class="form-control" rows="2"
                            placeholder="Wajib kalau ada selisih"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-espresso">Tutup Shift</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const expectedCash = <?= (float)$expected ?>;
        const closingInput = document.getElementById('closing_cash');
        const diffDisplay = document.getElementById('diff-display');
        const diffNote = document.getElementById('diff-note');
        const diffBox = document.getElementById('diff-box');

        function rupiahJS(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        }

        function updateDiff() {
            const closing = parseFloat(closingInput.value) || 0;
            const diff = closing - expectedCash;

            if (Math.abs(diff) < 1) {
                diffDisplay.textContent = 'Rp 0';
                diffDisplay.style.color = '#2E7D5B';
                diffNote.textContent = 'Sesuai. Bagus!';
                diffBox.style.background = '#F0F9F4';
            } else if (diff > 0) {
                diffDisplay.textContent = '+ ' + rupiahJS(diff);
                diffDisplay.style.color = '#2E7D5B';
                diffNote.textContent = 'Lebih ' + rupiahJS(diff) + ' dari seharusnya.';
                diffBox.style.background = '#F0F9F4';
            } else {
                diffDisplay.textContent = '− ' + rupiahJS(-diff);
                diffDisplay.style.color = '#A83232';
                diffNote.textContent = 'Kurang ' + rupiahJS(-diff) + ' dari seharusnya. Jelaskan di catatan.';
                diffBox.style.background = '#FDF2F2';
            }
        }

        if (closingInput) {
            closingInput.addEventListener('input', updateDiff);
            updateDiff();
        }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>