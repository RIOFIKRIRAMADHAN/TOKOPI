<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$outlet_id = (int)$_SESSION['customer_outlet_id'];
$table_id  = (int)$_SESSION['customer_table_id'];
$order_id  = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die('Order ID tidak valid.');
}

// Cek order milik meja ini / customer ini
$stmt = $pdo->prepare("
    SELECT o.*, t.nama AS table_nama
    FROM orders o
    LEFT JOIN `tables` t ON t.id = o.table_id
    WHERE o.id = ? AND o.outlet_id = ?
");
$stmt->execute([$order_id, $outlet_id]);
$order = $stmt->fetch();

if (!$order) {
    die('Pesanan tidak ditemukan.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Lacak Pesanan · Tokopi</title>
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
            --yellow: #facc15;
            --blue: #60a5fa;
            --red: #A83232;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body {
            margin: 0; padding: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            padding-bottom: 40px;
        }

        .page-header {
            background: #fff;
            padding: 16px 22px 18px;
            border-bottom: 1px solid #f5f2ea;
            position: sticky;
            top: 0; z-index: 50;
        }
        .page-header .back {
            display: inline-flex;
            align-items: center; justify-content: center;
            width: 36px; height: 36px;
            background: var(--sand-light);
            border-radius: 10px;
            color: var(--espresso);
            font-size: 1rem; text-decoration: none;
            margin-bottom: 10px;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem; color: var(--espresso); margin: 0;
        }
        .page-header p {
            font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0;
        }

        /* TIMELINE */
        .track-container {
            padding: 30px 20px;
            max-width: 440px; margin: 0 auto;
        }
        .order-info {
            text-align: center;
            margin-bottom: 40px;
        }
        .order-info .no {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.2rem; font-weight: 800; color: var(--espresso);
        }
        .order-info .name {
            font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;
        }

        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 14px; top: 10px; bottom: 10px;
            width: 4px;
            background: #e5e7eb;
            border-radius: 4px;
        }

        .step {
            position: relative;
            margin-bottom: 30px;
        }
        .step:last-child { margin-bottom: 0; }

        .step-icon {
            position: absolute;
            left: -40px; top: 0;
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #e5e7eb;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; color: #d1d5db;
            z-index: 2;
            transition: 0.3s;
        }
        .step-content {
            background: #fff;
            padding: 16px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(65,45,21,.04);
            border: 1px solid #f0ede5;
            opacity: 0.6;
            transition: 0.3s;
        }
        .step-title {
            font-weight: 800;
            color: var(--espresso);
            font-size: 0.95rem;
            margin-bottom: 4px;
        }
        .step-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* STATUS: PAID (Lunas) */
        .step.paid .step-icon { border-color: var(--green); background: var(--green); color: #fff; }
        .step.paid .step-content { opacity: 1; border-color: var(--green); }

        /* STATUS: PREPARING (Dibuat) */
        .step.preparing .step-icon { border-color: var(--yellow); background: var(--yellow); color: #713f12; animation: pulse 2s infinite; }
        .step.preparing .step-content { opacity: 1; border-color: var(--yellow); box-shadow: 0 4px 12px rgba(250, 204, 21, 0.15); }

        /* STATUS: READY (Siap) */
        .step.ready .step-icon { border-color: var(--blue); background: var(--blue); color: #fff; }
        .step.ready .step-content { opacity: 1; border-color: var(--blue); }

        /* STATUS: COMPLETED (Selesai) */
        .step.completed .step-icon { border-color: var(--espresso); background: var(--espresso); color: #fff; }
        .step.completed .step-content { opacity: 1; border-color: var(--espresso); }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(250, 204, 21, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(250, 204, 21, 0); }
            100% { box-shadow: 0 0 0 0 rgba(250, 204, 21, 0); }
        }

        .btn-home {
            display: block;
            margin-top: 40px;
            width: 100%;
            padding: 16px;
            background: var(--espresso);
            color: #fff;
            border-radius: 14px;
            text-decoration: none;
            text-align: center;
            font-weight: 700;
            font-size: 0.95rem;
        }
    </style>
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>
<body>

<div class="page-header">
    <a href="/tokopi/customer/success.php?order_id=<?= $order_id ?>" class="back">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1>Lacak Pesanan</h1>
    <p>Status pesanan kamu secara live</p>
</div>

<div class="track-container">
    
    <div class="order-info">
        <div class="no"><?= htmlspecialchars($order['order_no']) ?></div>
        <div class="name">Atas nama: <strong><?= htmlspecialchars($order['customer_name'] ?? '-') ?></strong></div>
        <div class="name" style="margin-top:4px;">Meja: <?= htmlspecialchars($order['table_nama'] ?? '') ?></div>
    </div>

    <div class="timeline" id="timeline">
        <!-- Step 1: Pesanan Diterima / Lunas -->
        <div class="step" id="step-paid">
            <div class="step-icon"><i class="bi bi-check-lg"></i></div>
            <div class="step-content">
                <div class="step-title">Pesanan Diterima</div>
                <div class="step-desc">Pembayaran berhasil. Pesanan sedang diteruskan ke barista.</div>
            </div>
        </div>

        <!-- Step 2: Sedang Dibuat -->
        <div class="step" id="step-preparing">
            <div class="step-icon"><i class="bi bi-cup-hot"></i></div>
            <div class="step-content">
                <div class="step-title">Sedang Dibuat</div>
                <div class="step-desc">Barista kami sedang menyiapkan pesanan kamu.</div>
            </div>
        </div>

        <!-- Step 3: Siap Diambil -->
        <div class="step" id="step-ready">
            <div class="step-icon"><i class="bi bi-bell"></i></div>
            <div class="step-content">
                <div class="step-title">Siap Diambil</div>
                <div class="step-desc">Pesanan sudah siap! Silakan ambil di counter.</div>
            </div>
        </div>

        <!-- Step 4: Selesai -->
        <div class="step" id="step-completed">
            <div class="step-icon"><i class="bi bi-bag-check"></i></div>
            <div class="step-content">
                <div class="step-title">Selesai</div>
                <div class="step-desc">Pesanan telah diserahkan. Selamat menikmati!</div>
            </div>
        </div>
    </div>

    <a href="/tokopi/customer/menu.php" class="btn-home">
        Pesan Lagi
    </a>

</div>

<script>
const ORDER_ID = <?= $order_id ?>;
let pollInterval = null;

function updateTimeline(status) {
    // Reset semua step
    const steps = ['paid', 'preparing', 'ready', 'completed'];
    steps.forEach(s => document.getElementById('step-' + s).className = 'step');

    // Tandai step yang sudah lewat
    if (status === 'PAID') {
        document.getElementById('step-paid').classList.add('paid');
    } 
    else if (status === 'PREPARING') {
        document.getElementById('step-paid').classList.add('paid');
        document.getElementById('step-preparing').classList.add('preparing');
    } 
    else if (status === 'READY') {
        document.getElementById('step-paid').classList.add('paid');
        document.getElementById('step-preparing').classList.add('preparing');
        document.getElementById('step-ready').classList.add('ready');
    } 
    else if (status === 'COMPLETED') {
        document.getElementById('step-paid').classList.add('paid');
        document.getElementById('step-preparing').classList.add('preparing');
        document.getElementById('step-ready').classList.add('ready');
        document.getElementById('step-completed').classList.add('completed');
    }
    else if (status === 'CANCELLED') {
        alert('Pesanan dibatalkan oleh kasir.');
        window.location.href = '/tokopi/customer/menu.php';
    }
}

async function checkStatus() {
    try {
        const res = await fetch('/tokopi/customer/api/my_orders.php');
        const data = await res.json();

        if (data.success && data.orders) {
            const currentOrder = data.orders.find(o => o.id === ORDER_ID);
            if (currentOrder) {
                updateTimeline(currentOrder.status);
                
                // Kalau sudah COMPLETED, stop polling
                if (currentOrder.status === 'COMPLETED') {
                    clearInterval(pollInterval);
                }
            }
        }
    } catch (e) {
        console.error('Polling error:', e);
    }
}

// Init
updateTimeline('<?= $order['status'] ?>'); // Load status awal dari PHP
pollInterval = setInterval(checkStatus, 5000); // Polling tiap 5 detik
</script>

</body>
</html>
