<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$outlet_id = (int)$_SESSION['customer_outlet_id'];
$table_id  = (int)$_SESSION['customer_table_id'];
$order_id  = (int)($_GET['order_id'] ?? 0);

$order = null;
if ($order_id > 0) {
    $stmt = $pdo->prepare("
        SELECT o.*, t.nama AS table_nama
        FROM orders o
        LEFT JOIN `tables` t ON t.id = o.table_id
        WHERE o.id = ? AND o.table_id = ? AND o.outlet_id = ?
    ");
    $stmt->execute([$order_id, $table_id, $outlet_id]);
    $order = $stmt->fetch();
}

if (!$order) {
    die('Order tidak ditemukan.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Bayar di Kasir · Tokopi</title>
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
            --yellow: #B8860B;
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
        }

        .container-c {
            max-width: 440px;
            margin: 0 auto;
            padding: 40px 24px 40px;
        }

        .icon-box {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #fef3c7;
            color: #92400e;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(184, 134, 11, .2);
        }

        .title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--espresso);
            margin: 0 0 8px;
            font-weight: 700;
        }

        .subtitle {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 28px;
            line-height: 1.55;
        }

        .order-card {
            background: #fff;
            border-radius: 20px;
            padding: 22px 20px;
            box-shadow: 0 4px 20px rgba(65, 45, 21, .08);
            margin-bottom: 20px;
            text-align: center;
        }

        .order-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 4px;
        }

        .order-no {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--espresso);
            margin-bottom: 16px;
            word-break: break-all;
        }

        .name-box {
            background: var(--sand-light);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }

        .name-label {
            font-size: 0.68rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 2px;
        }

        .name-value {
            font-family: 'Playfair Display', serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--espresso);
            line-height: 1.1;
        }

        .amount-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 2px;
        }

        .amount-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--espresso);
        }

        .info-box {
            background: var(--sand-50);
            border-radius: 16px;
            padding: 16px 18px;
            text-align: left;
            font-size: 0.82rem;
            color: var(--espresso);
            line-height: 1.65;
            margin-bottom: 16px;
        }

        .info-box strong {
            display: block;
            margin-bottom: 6px;
            font-size: 0.88rem;
        }

        .info-box ol {
            padding-left: 20px;
            margin: 6px 0 0;
        }

        .info-box ol li {
            margin-bottom: 4px;
        }

        .status-waiting {
            padding: 14px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            color: #78350f;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .status-waiting .spinner-border {
            width: 1rem;
            height: 1rem;
            margin-right: 6px;
            vertical-align: middle;
        }

        .status-paid {
            padding: 16px;
            background: #dcfce7;
            border-radius: 12px;
            color: #166534;
            font-weight: 700;
            text-align: center;
        }

        .status-paid i {
            font-size: 2rem;
            display: block;
            margin-bottom: 6px;
        }
    </style>
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>

<body>

    <div class="container-c" style="text-align:center;">
        <div class="icon-box">
            <i class="bi bi-cash-coin"></i>
        </div>

        <h1 class="title">Bayar di Kasir</h1>
        <p class="subtitle">Tunjukkan nomor pesanan ini ke kasir untuk menyelesaikan pembayaran</p>

        <div class="order-card">
            <div class="order-label">Nomor Pesanan</div>
            <div class="order-no"><?= htmlspecialchars($order['order_no']) ?></div>

            <div class="name-box">
                <div class="name-label">Atas Nama</div>
                <div class="name-value"><?= htmlspecialchars($order['customer_name'] ?? '-') ?></div>
            </div>

            <div class="amount-label">Total yang Harus Dibayar</div>
            <div class="amount-value">Rp <?= number_format((float)$order['grand_total'], 0, ',', '.') ?></div>
        </div>

        <div class="info-box">
            <strong><i class="bi bi-info-circle" aria-hidden="true"></i> Cara bayar:</strong>
            <ol>
                <li>Datang ke kasir</li>
                <li>Sebutkan nomor pesanan <strong><?= htmlspecialchars($order['order_no']) ?></strong></li>
                <li>Bayar tunai sejumlah total</li>
                <li>Kembali ke meja, tunggu pesanan dipanggil</li>
            </ol>
        </div>

        <div class="status-waiting" id="status-waiting">
            <span class="spinner-border spinner-border-sm"></span>
            Menunggu konfirmasi kasir...
        </div>

        <div class="status-paid" id="status-paid" style="display:none;">
            <i class="bi bi-check-circle-fill"></i>
            <div>Pembayaran dikonfirmasi!</div>
            <div style="font-size:0.8rem; margin-top:6px;">Mengarahkan ke halaman sukses...</div>
        </div>
    </div>

    <script>
        const ORDER_ID = <?= (int)$order_id ?>;
        let pollInterval = null;

        async function cekStatus() {
            try {
                const res = await fetch('/tokopi/customer/api/check_status.php?order_id=' + ORDER_ID);
                const data = await res.json();

                if (data.status === 'PAID') {
                    clearInterval(pollInterval);
                    document.getElementById('status-waiting').style.display = 'none';
                    document.getElementById('status-paid').style.display = 'block';

                    setTimeout(() => {
                        window.location.href = '/tokopi/customer/track.php?order_id=' + ORDER_ID;
                    }, 1200);
                } else if (data.status === 'CANCELLED') {
                    clearInterval(pollInterval);
                    alert('Pesanan dibatalkan. Silakan hubungi kasir.');
                    window.location.href = '/tokopi/customer/menu.php';
                }
            } catch (e) {
                // Lanjut polling
            }
        }

        pollInterval = setInterval(cekStatus, 3000);
        cekStatus();

        window.addEventListener('focus', cekStatus);
    </script>

</body>

</html>