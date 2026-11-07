<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$is_logged_in = !empty($_SESSION['customer_account_id']);

if (!$is_logged_in) {
    redirect('/tokopi/customer/rewards.php');
}

$customer_id = (int)$_SESSION['customer_account_id'];

// Ambil semua voucher
$stmt = $pdo->prepare("
    SELECT r.*, rw.nama AS reward_nama, rw.type AS reward_type
    FROM customer_redemptions r
    JOIN rewards rw ON rw.id = r.reward_id
    WHERE r.customer_id = ?
    ORDER BY
        FIELD(r.status, 'UNUSED',  'RESERVED' ,'USED', 'EXPIRED'),
        r.redeemed_at DESC
");
$stmt->execute([$customer_id]);
$redemptions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Voucher Saya · Tokopi</title>
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

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
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

        .voucher-list {
            padding: 16px 20px;
        }

        .voucher-card {
            background: #fff;
            border-radius: 16px;
            margin-bottom: 14px;
            box-shadow: 0 2px 12px rgba(65, 45, 21, .06);
            overflow: hidden;
            position: relative;
        }

        .voucher-card.used {
            opacity: 0.5;
        }

        .voucher-card.expired {
            opacity: 0.4;
        }

        .voucher-header {
            padding: 16px 18px;
            background: linear-gradient(135deg, var(--espresso), #6b4a26);
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .voucher-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .voucher-status-badge {
            font-size: 0.6rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .status-UNUSED {
            background: #dcfce7;
            color: #166534;
        }

        .status-RESERVED {
            background: #fef3c7;
            color: #92400e;
        }

        .status-USED {
            background: #e5e7eb;
            color: #374151;
        }

        .status-EXPIRED {
            background: #fee2e2;
            color: #991b1b;
        }

        .voucher-body {
            padding: 16px 18px;
        }

        .voucher-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--espresso);
            letter-spacing: 0.15em;
            background: var(--sand-light);
            padding: 10px 16px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 10px;
            border: 2px dashed var(--sand);
        }

        .voucher-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.72rem;
            color: var(--text-muted);
        }

        .voucher-meta strong {
            color: var(--espresso);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--sand);
            display: block;
            margin-bottom: 12px;
        }

        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            color: var(--espresso);
            margin: 0 0 6px;
        }

        .empty-state a {
            display: inline-block;
            margin-top: 16px;
            padding: 12px 24px;
            background: var(--espresso);
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <div class="page-header">
        <a href="/tokopi/customer/rewards.php" class="back">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Voucher Saya</h1>
        <p>Tukar voucher di kasir saat pesan</p>
    </div>

    <?php if (empty($redemptions)): ?>
        <div class="empty-state">
            <i class="bi bi-ticket-perforated"></i>
            <h3>Belum ada voucher</h3>
            <p>Tukar poin kamu dengan reward menarik!</p>
            <a href="/tokopi/customer/rewards.php">Lihat Rewards</a>
        </div>
    <?php else: ?>
        <div class="voucher-list">
            <?php foreach ($redemptions as $v):
                $snapshot = json_decode($v['reward_snapshot'] ?? '{}', true);
                $voucher_code = $snapshot['voucher_code'] ?? '-';
                $expires_at = $v['expires_at'] ? date('d M Y', strtotime($v['expires_at'])) : '-';
            ?>
                <div class="voucher-card <?= strtolower($v['status']) ?>">
                    <div class="voucher-header">
                        <div class="voucher-name"><?= e($v['reward_nama']) ?></div>
                        <span class="voucher-status-badge status-<?= $v['status'] ?>">
                            <?php if ($v['status'] === 'UNUSED'): ?>
                                Aktif
                            <?php elseif ($v['status'] === 'RESERVED'): ?>
                                Sedang Dipakai
                            <?php elseif ($v['status'] === 'USED'): ?>
                                Sudah Dipakai
                            <?php else: ?>
                                Expired
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="voucher-body">
                        <div class="voucher-code"><?= e($voucher_code) ?></div>
                        <div class="voucher-meta">
                            <span>Berlaku s/d: <strong><?= e($expires_at) ?></strong></span>
                            <span><strong><?= (int)$v['points_used'] ?></strong> poin</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>

</html>