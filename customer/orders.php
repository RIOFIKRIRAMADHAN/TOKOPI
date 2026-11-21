<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Blokir redirect DIHAPUS. User bisa lihat riwayat order (meski kosong kalau tanpa meja).

$outlet_id = (int)($_SESSION['customer_outlet_id'] ?? 0);
$table_id  = (int)($_SESSION['customer_table_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Order Saya · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --sand: #E1DCC9;
            --sand-light: #F3F0E6;
            --bg: #EDEAE3;
            --text-muted: #8a8478;
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
            padding: 20px 22px;
            border-bottom: 1px solid #f5f2ea;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--espresso);
            margin: 0;
        }

        .page-header p {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 4px 0 0;
        }

        .orders-container {
            padding: 16px 20px;
        }

        .order-card {
            background: #fff;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 6px rgba(65, 45, 21, .04);
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .order-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 10px;
            border-bottom: 1px dashed #f0ede5;
            margin-bottom: 10px;
        }

        .order-no {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--espresso);
        }

        .order-date {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .order-status {
            font-size: 0.6rem;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .status-PAID {
            background: #dcfce7;
            color: #166534;
        }

        .status-PREPARING {
            background: #fef3c7;
            color: #92400e;
        }

        .status-READY {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-COMPLETED {
            background: #e5e7eb;
            color: #374151;
        }

        .status-PENDING_PAYMENT {
            background: #f1f5f9;
            color: #475569;
        }

        .status-CANCELLED {
            background: #fee2e2;
            color: #991b1b;
        }

        .order-items {
            font-size: 0.78rem;
            color: #666;
            line-height: 1.45;
        }

        .order-items div {
            margin-bottom: 2px;
        }

        .order-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px dashed #f0ede5;
        }

        .order-total-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .order-total-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--espresso);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3.5rem;
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
            margin-top: 20px;
            padding: 12px 24px;
            background: var(--espresso);
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 68px;
            background: #fff;
            border-top: 1px solid #f5f2ea;
            display: flex;
            align-items: center;
            box-shadow: 0 -4px 20px rgba(65, 45, 21, .06);
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

        .nav-item.active {
            color: var(--espresso);
        }
    </style>
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>

<body>

    <div class="page-header">
        <h1>Order Saya</h1>
        <p>Riwayat pesanan di meja ini</p>
    </div>

    <div class="orders-container" id="orders-container">
        <div class="empty-state">
            <i class="bi bi-hourglass"></i>
            <p>Memuat...</p>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="/tokopi/customer/home.php" class="nav-item">
            <i class="bi bi-house-door"></i>Home
        </a>
        <a href="/tokopi/customer/menu.php" class="nav-item">
            <i class="bi bi-cup-hot"></i>Menu
        </a>
        <a href="/tokopi/customer/orders.php" class="nav-item active">
            <i class="bi bi-receipt"></i>Order
        </a>
        <a href="/tokopi/customer/profile.php" class="nav-item">
            <i class="bi bi-person"></i>Profile
        </a>
    </div>

    <script>
        function rupiah(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s == null ? '' : s;
            return d.innerHTML;
        }

        function formatDate(s) {
            if (!s) return '-';
            const d = new Date(s.replace(' ', 'T'));
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const hour = String(d.getHours()).padStart(2, '0');
            const min = String(d.getMinutes()).padStart(2, '0');
            return `${day}/${month} ${hour}:${min}`;
        }

        async function loadOrders() {
            try {
                const res = await fetch('/tokopi/customer/api/my_orders.php');
                const data = await res.json();

                const container = document.getElementById('orders-container');

                if (!data.success || !data.orders || data.orders.length === 0) {
                    container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-receipt"></i>
                    <h3>Belum ada pesanan</h3>
                    <p>Yuk pesan menu favoritmu!</p>
                    <a href="/tokopi/customer/menu.php">Pesan Sekarang</a>
                </div>
            `;
                    return;
                }

                let html = '';
                data.orders.forEach(o => {
                    const itemsHtml = o.items.slice(0, 3).map(it =>
                        `<div>${it.qty}× ${escapeHtml(it.product_nama)} · ${escapeHtml(it.variant_nama)}${it.modifiers ? ` <span style="color:var(--text-muted)">(${escapeHtml(it.modifiers)})</span>` : ''}</div>`
                    ).join('');
                    const more = o.items.length > 3 ? `<div style="color:#999;">+${o.items.length - 3} item lagi</div>` : '';

                    html += `
                <div class="order-card">
                    <div class="order-head">
                        <div>
                            <div class="order-no">${escapeHtml(o.order_no)}</div>
                            <div class="order-date">${formatDate(o.created_at)}</div>
                        </div>
                        <span class="order-status status-${o.status}">${o.status.replace('_', ' ')}</span>
                    </div>
                    <div class="order-items">
                        ${itemsHtml}
                        ${more}
                    </div>
                    <div class="order-total">
                        <span class="order-total-label">Total</span>
                        <span class="order-total-value">${rupiah(o.grand_total)}</span>
                    </div>
                    ${o.discount_total > 0 ? `
                        <div class="order-total" style="border-top:none; padding-top:0; margin-top:4px;">
                            <span class="order-total-label" style="color:#2E7D5B;">Diskon Voucher</span>
                            <span class="order-total-value" style="color:#2E7D5B; font-size:0.85rem;">− ${rupiah(o.discount_total)}</span>
                        </div>
                    ` : ''}
                </div>
            `;
                });
                container.innerHTML = html;

            } catch (e) {
                console.error(e);
            }
        }

        loadOrders();
        setInterval(loadOrders, 10000);
    </script>

</body>

</html>