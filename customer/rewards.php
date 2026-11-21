<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$outlet_id   = (int)$_SESSION['customer_outlet_id'];
$table_nama  = $_SESSION['customer_table_nama'];
$outlet_nama = $_SESSION['customer_outlet_nama'];
$is_logged_in = !empty($_SESSION['customer_account_id']);

// Ambil tenant_id
$stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$tenant_id = (int)$stmt->fetchColumn();

// Kalau login → ambil data customer & points
$customer = null;
$voucher_active = 0;
if ($is_logged_in) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_SESSION['customer_account_id']]);
    $customer = $stmt->fetch();

    // Hitung voucher aktif (belum dipakai & belum expired)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM customer_redemptions
        WHERE customer_id = ? AND status = 'UNUSED' AND expires_at > NOW()
    ");
    $stmt->execute([$_SESSION['customer_account_id']]);
    $voucher_active = (int)$stmt->fetchColumn();
}

// Ambil daftar reward aktif
$stmt = $pdo->prepare("
    SELECT r.*,
           v.nama AS variant_nama,
           v.harga_jual AS variant_harga,
           p.nama AS product_nama
    FROM rewards r
    LEFT JOIN product_variants v ON v.id = r.variant_id
    LEFT JOIN products p ON p.id = v.product_id
    WHERE r.tenant_id = ? AND r.is_active = 1
    ORDER BY r.points_cost ASC
");
$stmt->execute([$tenant_id]);
$rewards = $stmt->fetchAll();

$success = flash_get('success');
$error = flash_get('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Rewards · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --sand: #E1DCC9;
            --sand-light: #F3F0E6;
            --bg: #EDEAE3;
            --text-muted: #8a8478;
            --green: #2E7D5B;
            --red: #A83232;
            --gold: #B8860B;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body {
            margin: 0; padding: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        .page-header {
            background: #fff;
            padding: 16px 22px 18px;
            border-bottom: 1px solid #f5f2ea;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .page-header .back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--sand-light);
            border-radius: 10px;
            color: var(--espresso);
            font-size: 1rem;
            text-decoration: none;
            margin-bottom: 10px;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--espresso);
            margin: 0;
        }
        .page-header p {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin: 3px 0 0;
        }

        /* POINTS CARD */
        .points-card {
            background: linear-gradient(160deg, #1a1a1a 0%, #2a1d0e 60%, #3d2a15 100%);
            color: #fff;
            padding: 24px 22px;
            border-radius: 20px;
            margin: 16px 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(65,45,21,.2);
        }
        .points-card::after {
            content: '';
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 8rem;
            opacity: 0.1;
        }
        .points-label {
            font-size: 0.72rem;
            color: var(--sand);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        .points-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 2.25rem;
            font-weight: 800;
            line-height: 1;
            margin: 6px 0 12px;
            position: relative;
            z-index: 1;
        }
        .points-value small {
            font-size: 0.9rem;
            opacity: 0.7;
            font-weight: 500;
        }
        .progress-bar-c {
            height: 8px;
            background: rgba(255,255,255,.15);
            border-radius: 999px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        .progress-fill {
            height: 100%;
            background: var(--sand);
            border-radius: 999px;
            transition: width 0.3s;
        }
        .progress-text {
            font-size: 0.7rem;
            color: var(--sand);
            margin-top: 8px;
            position: relative;
            z-index: 1;
        }

        /* VOUCHER BANNER */
        .voucher-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 20px 16px;
            padding: 14px 16px;
            background: #fff;
            border-radius: 14px;
            border-left: 4px solid var(--gold);
            box-shadow: 0 2px 8px rgba(65,45,21,.05);
            text-decoration: none;
            color: inherit;
        }
        .voucher-icon {
            width: 40px; height: 40px;
            background: #FEF3C7;
            color: var(--gold);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .voucher-info { flex: 1; min-width: 0; }
        .voucher-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--espresso);
        }
        .voucher-desc {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .voucher-arrow { color: #ccc; }

        /* SECTION */
        .section-head {
            padding: 8px 22px 12px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }
        .section-head h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--espresso);
            margin: 0;
        }

        /* REWARD LIST */
        .rewards-list { padding: 0 20px 20px; }

        .reward-card {
            display: flex;
            gap: 14px;
            padding: 16px;
            background: #fff;
            border-radius: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(65,45,21,.05);
            align-items: center;
            transition: 0.15s;
        }
        .reward-card.locked { opacity: 0.6; }
        .reward-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--sand-light), var(--sand));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }
        .reward-info { flex: 1; min-width: 0; }
        .reward-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--espresso);
            line-height: 1.25;
            margin-bottom: 3px;
        }
        .reward-desc {
            font-size: 0.72rem;
            color: var(--text-muted);
            line-height: 1.35;
            margin-bottom: 6px;
        }
        .reward-cost {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #FEF3C7;
            color: #92400e;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
        }
        .reward-action {
            flex-shrink: 0;
        }
        .btn-redeem {
            padding: 10px 18px;
            background: var(--espresso);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.78rem;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-redeem:disabled {
            background: #ddd;
            color: #999;
            cursor: not-allowed;
        }
        .btn-redeem:active:not(:disabled) {
            transform: scale(0.96);
        }

        /* GUEST MODE */
        .guest-card {
            margin: 20px;
            padding: 40px 24px;
            background: #fff;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(65,45,21,.08);
        }
        .guest-icon {
            font-size: 3.5rem;
            margin-bottom: 16px;
        }
        .guest-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            color: var(--espresso);
            margin: 0 0 10px;
        }
        .guest-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 24px;
        }
        .btn-primary-c {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--espresso);
            color: #fff;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
            margin-bottom: 10px;
        }
        .btn-secondary-c {
            display: block;
            width: 100%;
            padding: 14px;
            background: transparent;
            color: var(--espresso);
            border: 1.5px solid var(--sand);
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
        }

        /* ALERTS */
        .alert-c {
            margin: 0 20px 16px;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.82rem;
            line-height: 1.5;
        }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }

        /* EMPTY */
        .empty-rewards {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .empty-rewards i {
            font-size: 3rem;
            color: var(--sand);
            display: block;
            margin-bottom: 12px;
        }

        /* BOTTOM NAV */
        .bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 68px;
            background: #fff;
            border-top: 1px solid #f5f2ea;
            display: flex;
            align-items: center;
            box-shadow: 0 -4px 20px rgba(65,45,21,.06);
        }
        .nav-item {
            flex: 1;
            text-align: center;
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            padding: 6px 0;
        }
        .nav-item i {
            display: block;
            font-size: 1.3rem;
            margin-bottom: 2px;
        }
        .nav-item.active { color: var(--espresso); }

        /* MODAL */
        .modal-custom {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            z-index: 999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-custom.show { display: flex; }
        .modal-content-c {
            background: #fff;
            border-radius: 20px;
            padding: 24px 22px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .modal-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }
        .modal-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            color: var(--espresso);
            margin-bottom: 8px;
        }
        .modal-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
        }
        .modal-actions button {
            flex: 1;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.85rem;
            font-family: inherit;
            cursor: pointer;
            border: none;
        }
        .btn-cancel {
            background: var(--sand-light);
            color: var(--espresso);
        }
        .btn-confirm {
            background: var(--espresso);
            color: #fff;
        }
    </style>
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>
<body>

<div class="page-header">
    <a href="/tokopi/customer/profile.php" class="back">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1>Rewards</h1>
    <p>Tukar poin dengan hadiah</p>
</div>

<?php if ($success): ?>
    <div class="alert-c alert-success" style="margin-top:16px;">
        <i class="bi bi-check-circle-fill me-1"></i>
        <?= e($success) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert-c alert-error" style="margin-top:16px;">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        <?= e($error) ?>
    </div>
<?php endif; ?>

<?php if (!$is_logged_in): ?>

    <!-- GUEST MODE -->
    <div class="guest-card">
        <div class="guest-icon"><i class="bi bi-gift" aria-hidden="true"></i></div>
        <div class="guest-title">Reward Eksklusif</div>
        <div class="guest-desc">
            Login atau daftar akun untuk melihat reward yang bisa kamu tukar dengan poin.
        </div>
        <a href="/tokopi/customer/register.php" class="btn-primary-c">
            <i class="bi bi-person-plus-fill me-1"></i> Daftar Gratis
        </a>
        <a href="/tokopi/customer/login.php" class="btn-secondary-c">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sudah Punya Akun
        </a>
    </div>

<?php else: ?>

    <!-- POINTS CARD -->
    <?php
    $points = (int)$customer['points'];
    // Cari reward termurah untuk hitung progress
    $min_cost = !empty($rewards) ? min(array_column($rewards, 'points_cost')) : 0;
    $progress = $min_cost > 0 ? min(100, round($points / $min_cost * 100)) : 0;
    ?>
    <div class="points-card">
        <div class="points-label">Poin Kamu</div>
        <div class="points-value"><?= number_format($points) ?> <small>poin</small></div>

        <?php if ($min_cost > 0 && $points < $min_cost): ?>
            <div class="progress-bar-c">
                <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
            </div>
            <div class="progress-text">
                <?= $min_cost - $points ?> poin lagi untuk reward pertama
            </div>
        <?php else: ?>
            <div class="progress-text" style="margin-top:0;">
                <i class="bi bi-check-circle-fill me-1"></i>
                Kamu bisa tukar reward sekarang!
            </div>
        <?php endif; ?>
    </div>

    <!-- VOUCHER BANNER -->
    <a href="/tokopi/customer/my_vouchers.php" class="voucher-banner">
        <div class="voucher-icon">
            <i class="bi bi-ticket-perforated-fill"></i>
        </div>
        <div class="voucher-info">
            <div class="voucher-title">Voucher Saya</div>
            <div class="voucher-desc">
                <?php if ($voucher_active > 0): ?>
                    <?= $voucher_active ?> voucher siap dipakai
                <?php else: ?>
                    Belum ada voucher aktif
                <?php endif; ?>
            </div>
        </div>
        <i class="bi bi-chevron-right voucher-arrow"></i>
    </a>

    <!-- REWARDS LIST -->
    <div class="section-head">
        <h3>Daftar Reward</h3>
    </div>

    <div class="rewards-list">
        <?php if (empty($rewards)): ?>
            <div class="empty-rewards">
                <i class="bi bi-gift"></i>
                <p>Belum ada reward tersedia.</p>
            </div>
        <?php else: ?>
            <?php foreach ($rewards as $r):
                $can_redeem = $points >= (int)$r['points_cost'];
                $icon = '<i class="bi ' . ($r['type'] === 'FREE_ITEM' ? 'bi-cup-hot' : ($r['type'] === 'DISCOUNT_NOMINAL' ? 'bi-cash-coin' : 'bi-percent')) . '" aria-hidden="true"></i>';
            ?>
                <div class="reward-card <?= !$can_redeem ? 'locked' : '' ?>">
                    <div class="reward-icon"><?= $icon ?></div>
                    <div class="reward-info">
                        <div class="reward-name"><?= e($r['nama']) ?></div>
                        <?php if ($r['deskripsi']): ?>
                            <div class="reward-desc"><?= e($r['deskripsi']) ?></div>
                        <?php endif; ?>
                        <span class="reward-cost">
                            <i class="bi bi-star-fill"></i>
                            <?= (int)$r['points_cost'] ?> poin
                        </span>
                    </div>
                    <div class="reward-action">
                        <button class="btn-redeem"
                                <?= !$can_redeem ? 'disabled' : '' ?>
                                onclick='openRedeem(<?= json_encode([
                                    "id" => (int)$r["id"],
                                    "nama" => $r["nama"],
                                    "points_cost" => (int)$r["points_cost"],
                                    "type" => $r["type"],
                                    "value" => (float)$r["value"],
                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                            <?= $can_redeem ? 'Tukar' : 'Kurang' ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php endif; ?>

<!-- MODAL REDEEM -->
<div class="modal-custom" id="modalRedeem">
    <div class="modal-content-c">
        <div class="modal-icon"><i class="bi bi-gift" aria-hidden="true"></i></div>
        <div class="modal-title">Tukar Poin?</div>
        <div class="modal-desc">
            Tukar <strong id="redeem-cost"></strong> poin dengan <strong id="redeem-name"></strong>?
            <br><br>
            Voucher berlaku 30 hari dan hanya bisa dipakai 1x.
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Batal</button>
            <button class="btn-confirm" onclick="confirmRedeem()" id="btn-confirm-redeem">
                <i class="bi bi-check-lg me-1"></i> Tukar Sekarang
            </button>
        </div>
    </div>
</div>

<!-- BOTTOM NAV -->
<div class="bottom-nav">
    <a href="/tokopi/customer/home.php" class="nav-item">
        <i class="bi bi-house-door"></i>Home
    </a>
    <a href="/tokopi/customer/menu.php" class="nav-item">
        <i class="bi bi-cup-hot"></i>Menu
    </a>
    <a href="/tokopi/customer/orders.php" class="nav-item">
        <i class="bi bi-receipt"></i>Order
    </a>
    <a href="/tokopi/customer/profile.php" class="nav-item active">
        <i class="bi bi-person"></i>Profile
    </a>
</div>

<script>
let selectedReward = null;

function openRedeem(reward) {
    selectedReward = reward;
    document.getElementById('redeem-cost').textContent = reward.points_cost;
    document.getElementById('redeem-name').textContent = reward.nama;
    document.getElementById('modalRedeem').classList.add('show');
}

function closeModal() {
    document.getElementById('modalRedeem').classList.remove('show');
    selectedReward = null;
}

async function confirmRedeem() {
    if (!selectedReward) return;

    const btn = document.getElementById('btn-confirm-redeem');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

    try {
        const res = await fetch('/tokopi/customer/reward_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ reward_id: selectedReward.id })
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Tukar Sekarang';
            return;
        }

        // Sukses
        alert('Voucher berhasil ditukar!\n\nKode: ' + data.voucher_code + '\n\nLihat di "Voucher Saya"');
        window.location.href = '/tokopi/customer/my_vouchers.php';

    } catch (e) {
        alert('Error: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Tukar Sekarang';
    }
}
</script>

</body>
</html>
