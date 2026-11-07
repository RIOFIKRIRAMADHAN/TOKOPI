<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#412D15">
    <title>Pembayaran QRIS · Tokopi</title>
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
            --red: #A83232;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body {
            margin: 0; padding: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        .page-header {
            background: #fff;
            padding: 16px 22px 18px;
            border-bottom: 1px solid #f5f2ea;
            text-align: center;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--espresso);
            margin: 0;
        }
        .page-header p {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin: 4px 0 0;
        }

        .qris-box {
            max-width: 400px;
            margin: 20px auto;
            background: #fff;
            border-radius: 20px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(65,45,21,.08);
        }

        .amount {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--espresso);
            margin-bottom: 4px;
        }
        .order-no {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .qr-img {
            width: 100%;
            max-width: 260px;
            margin: 0 auto;
            display: block;
            border-radius: 12px;
            border: 1px solid #f0ede5;
        }

        .status-waiting {
            margin-top: 20px;
            padding: 12px;
            background: var(--sand-50);
            border-radius: 12px;
            color: var(--espresso);
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-waiting .spinner-border {
            width: 1rem;
            height: 1rem;
            margin-right: 6px;
            vertical-align: middle;
        }
        .status-paid {
            margin-top: 20px;
            padding: 16px;
            background: #dcfce7;
            border-radius: 12px;
            color: #166534;
            font-weight: 700;
        }
        .status-paid i {
            font-size: 2rem;
            display: block;
            margin-bottom: 6px;
        }

        .info-tip {
            margin: 20px auto;
            max-width: 400px;
            padding: 12px 16px;
            background: #fef3c7;
            border-radius: 12px;
            font-size: 0.75rem;
            color: #92400e;
            line-height: 1.5;
        }

        .btn-tes {
            display: block;
            max-width: 400px;
            margin: 20px auto;
            padding: 12px;
            background: transparent;
            color: var(--text-muted);
            border: 1px dashed #ccc;
            border-radius: 12px;
            font-size: 0.75rem;
            cursor: pointer;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1>Pembayaran QRIS</h1>
    <p>Scan QR di bawah untuk menyelesaikan pesanan</p>
</div>

<div class="qris-box">
    <div class="amount" id="amount">Rp 0</div>
    <div class="order-no" id="order-no">Loading...</div>

    <img src="" alt="QRIS" class="qr-img" id="qr-img">

    <div class="status-waiting" id="status-waiting">
        <span class="spinner-border spinner-border-sm"></span>
        Menunggu pembayaran...
    </div>

    <div class="status-paid" id="status-paid" style="display:none;">
        <i class="bi bi-check-circle-fill"></i>
        <div>Pembayaran diterima!</div>
        <div style="font-size:0.8rem; margin-top:6px;">Mengarahkan ke halaman sukses...</div>
    </div>
</div>

<div class="info-tip">
    <strong>💡 Cara bayar:</strong><br>
    1. Buka aplikasi e-wallet / m-banking<br>
    2. Scan QR di atas<br>
    3. Konfirmasi nominal & bayar
</div>

<button class="btn-tes" onclick="cekStatusManual()">
    <i class="bi bi-arrow-clockwise"></i> Sudah bayar? Cek status
</button>

<script>
const pending = sessionStorage.getItem('tokopi_pending_order');
if (!pending) {
    alert('Tidak ada pesanan pending. Redirect ke menu.');
    window.location.href = '/tokopi/customer/menu.php';
}

const orderData = JSON.parse(pending);
let pollInterval = null;

function rupiah(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

// Tampilkan info
document.getElementById('amount').textContent = rupiah(orderData.grand_total);
document.getElementById('order-no').textContent = orderData.order_no;
document.getElementById('qr-img').src = orderData.qr_url;

// Polling status
async function cekStatus() {
    try {
        const res = await fetch('/tokopi/customer/api/check_status.php?order_id=' + orderData.order_id);
        const data = await res.json();

        if (data.status === 'PAID') {
            clearInterval(pollInterval);
            document.getElementById('status-waiting').style.display = 'none';
            document.getElementById('status-paid').style.display = 'block';

            sessionStorage.removeItem('tokopi_pending_order');

            setTimeout(() => {
                window.location.href = '/tokopi/customer/success.php?order_id=' + orderData.order_id;
            }, 1200);
        } else if (data.status === 'CANCELLED') {
            clearInterval(pollInterval);
            alert('Pesanan dibatalkan. Silakan pesan ulang.');
            sessionStorage.removeItem('tokopi_pending_order');
            window.location.href = '/tokopi/customer/menu.php';
        }
    } catch (e) {
        // abaikan error, lanjut polling
    }
}

async function cekStatusManual() {
    await cekStatus();
}

// Mulai polling setiap 3 detik
pollInterval = setInterval(cekStatus, 3000);
cekStatus();

// Cek saat window kembali fokus
window.addEventListener('focus', cekStatus);
</script>

</body>
</html>
