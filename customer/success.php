<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$outlet_id = (int)$_SESSION['customer_outlet_id'];
$table_id  = (int)$_SESSION['customer_table_id'];
$order_id  = (int)($_GET['order_id'] ?? 0);

$order = null;
$earned_points = 0;

if ($order_id > 0) {
    // Retry up to 3x kalau poin belum ke-award (race condition)
    $attempts = 0;
    while ($attempts < 3) {
        $stmt = $pdo->prepare("
            SELECT o.*, t.nama AS table_nama
            FROM orders o
            LEFT JOIN `tables` t ON t.id = o.table_id
            WHERE o.id = ? AND o.table_id = ? AND o.outlet_id = ?
        ");
        $stmt->execute([$order_id, $table_id, $outlet_id]);
        $order = $stmt->fetch();

        if ($order && !empty($order['customer_account_id'])) {
            $stmt = $pdo->prepare("
                SELECT points FROM customer_points
                WHERE order_id = ? AND type = 'EARN'
                LIMIT 1
            ");
            $stmt->execute([$order_id]);
            $earned_points = (int)$stmt->fetchColumn();

            if ($earned_points > 0) break;
        } else {
            break;
        }

        $attempts++;
        if ($attempts < 3) usleep(300000); // 0.3 detik
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#412D15">
    <title>Pesanan Diterima · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --sand: #E1DCC9;
            --sand-light: #F3F0E6;
            --sand-50: #FAF6EC;
            --bg: #EDEAE3;
            --text-muted: #8a8478;
            --green: #2E7D5B;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        .container-c {
            max-width: 400px;
            margin: 0 auto;
            padding: 60px 24px 40px;
            text-align: center;
        }

        .icon-check {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #dcfce7;
            color: var(--green);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(46, 125, 91, .25);
        }

        .title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            color: var(--espresso);
            margin: 0 0 8px;
            font-weight: 700;
        }

        .subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .order-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px 20px;
            box-shadow: 0 4px 20px rgba(65, 45, 21, .08);
            margin-bottom: 20px;
        }

        .order-no {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--espresso);
            margin-bottom: 4px;
        }

        .order-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
        }

        .customer-name {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--espresso);
            padding: 12px 0;
            border-top: 1px dashed var(--sand);
            border-bottom: 1px dashed var(--sand);
            margin-bottom: 16px;
        }

        .customer-name-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .table-info {
            font-size: 0.85rem;
            color: var(--espresso);
            font-weight: 600;
        }

        .info-box {
            background: var(--sand-50);
            border-radius: 16px;
            padding: 16px;
            text-align: left;
            font-size: 0.82rem;
            color: var(--espresso);
            line-height: 1.6;
        }

        .info-box strong {
            display: block;
            margin-bottom: 6px;
        }

        .info-box ul {
            padding-left: 18px;
            margin: 6px 0 0;
        }

        .info-box li {
            margin-bottom: 4px;
        }

        .btn-secondary-c {
            display: block;
            margin-top: 24px;
            padding: 14px;
            background: #fff;
            color: var(--espresso);
            border: 2px solid var(--sand);
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .btn-primary-c {
            display: block;
            width: 100%;
            padding: 16px;
            background: var(--espresso);
            color: #fff;
            border: none;
            border-radius: 14px;
            text-decoration: none;
            text-align: center;
            font-weight: 700;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(65, 45, 21, .2);
            transition: 0.15s;
        }

        .btn-primary-c:active {
            transform: scale(0.98);
        }
    </style>
</head>

<body>

    <div class="container-c">
        <div class="icon-check">
            <i class="bi bi-check-lg"></i>
        </div>

        <h1 class="title">Pesanan Diterima!</h1>
        <p class="subtitle">Tunjukkan nama Anda ke barista atau tunggu dipanggil</p>

        <?php if ($order): ?>
            <div class="order-card">
                <div class="order-no"><?= htmlspecialchars($order['order_no']) ?></div>
                <div class="order-label">Nomor Pesanan</div>

                <div class="customer-name-label">Atas Nama</div>
                <div class="customer-name"><?= htmlspecialchars($order['customer_name'] ?? '-') ?></div>

                <div class="table-info">
                    <i class="bi bi-geo-alt-fill me-1"></i>
                    <?= htmlspecialchars($order['table_nama'] ?? 'Meja') ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($earned_points > 0): ?>
            <div class="info-box my-3" style="background:#fef3c7; color:#78350f; border-left: 4px solid #f59e0b;">
                <strong>🎁 Kamu dapat <?= $earned_points ?> poin!</strong><br>
                Poin otomatis masuk ke akunmu. Tukar dengan reward menarik di halaman <strong>Rewards</strong>.
            </div>
        <?php endif; ?>
        <a href="/tokopi/customer/track.php?order_id=<?= $order_id ?>" class="btn-primary-c" style="margin-bottom:12px;">
            <i class="bi bi-geo-alt-fill me-1"></i> Lacak Pesanan
        </a>

        <a href="/tokopi/customer/menu.php" class="btn-secondary-c">
            <i class="bi bi-arrow-left me-1"></i> Pesan Lagi
        </a>
    </div>

</body>

</html>