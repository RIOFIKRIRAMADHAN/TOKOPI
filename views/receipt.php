<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

$tenant_id = current_tenant_id();
$order_id  = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die('Order ID tidak valid.');
}

// Ambil order + relasi
$stmt = $pdo->prepare("
    SELECT o.*, u.nama AS cashier_nama, ou.nama AS outlet_nama, ou.alamat AS outlet_alamat, ou.no_hp AS outlet_hp
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    LEFT JOIN users u ON u.id = o.cashier_id
    WHERE o.id = ? AND ou.tenant_id = ?
");
$stmt->execute([$order_id, $tenant_id]);
$order = $stmt->fetch();

if (!$order) {
    die('Order tidak ditemukan.');
}

// Item
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Payment
$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

// Tenant info
$stmt = $pdo->prepare("SELECT nama_toko FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?= e($order['order_no']) ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', 'JetBrains Mono', monospace;
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }

        .struk {
            width: 300px;
            margin: 0 auto;
            background: #fff;
            padding: 16px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed #333;
            margin: 8px 0;
        }

        .line-solid {
            border-top: 1px solid #333;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 1px 0;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .big {
            font-size: 14px;
        }

        .huge {
            font-size: 16px;
        }

        .item-name {
            padding-top: 4px;
        }

        .item-detail {
            color: #333;
        }

        .footer-note {
            font-size: 11px;
            color: #555;
            margin-top: 8px;
        }

        .btn-toolbar {
            width: 300px;
            margin: 16px auto 0;
            display: flex;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-family: -apple-system, sans-serif;
            font-size: 13px;
        }

        .btn-print {
            background: #412D15;
            color: #fff;
        }

        .btn-print:hover {
            background: #2a1d0e;
        }

        .btn-back {
            background: #e5e5e5;
            color: #333;
        }

        .btn-back:hover {
            background: #ccc;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .struk {
                box-shadow: none;
                width: 100%;
                max-width: 300px;
            }

            .btn-toolbar {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="struk">
        <div class="center">
            <div class="bold big"><?= e($tenant['nama_toko']) ?></div>
            <div><?= e($order['outlet_nama']) ?></div>
            <?php if ($order['outlet_alamat']): ?>
                <div style="font-size:11px;"><?= e($order['outlet_alamat']) ?></div>
            <?php endif; ?>
            <?php if ($order['outlet_hp']): ?>
                <div style="font-size:11px;">Telp: <?= e($order['outlet_hp']) ?></div>
            <?php endif; ?>
        </div>

        <div class="line"></div>

        <table>
            <tr>
                <td>No</td>
                <td class="right"><?= e($order['order_no']) ?></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td class="right"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td class="right"><?= e($order['cashier_nama'] ?? '-') ?></td>
            </tr>
            <tr>
                <td>Channel</td>
                <td class="right"><?= e($order['channel']) ?></td>
            </tr>
        </table>

        <div class="line"></div>

        <?php foreach ($items as $it): ?>
            <div class="item-name bold"><?= e($it['product_nama']) ?></div>
            <div class="item-detail"><?= e($it['variant_nama']) ?></div>
            <table>
                <tr>
                    <td><?= (int)$it['qty'] ?> x <?= number_format((float)$it['harga_satuan'], 0, ',', '.') ?></td>
                    <td class="right"><?= number_format((float)$it['line_total'], 0, ',', '.') ?></td>
                </tr>
            </table>
        <?php endforeach; ?>

        <div class="line"></div>

        <table>
            <tr class="bold big">
                <td>TOTAL</td>
                <td class="right">Rp <?= number_format((float)$order['grand_total'], 0, ',', '.') ?></td>
            </tr>
            <?php if ($payment): ?>
                <tr>
                    <td><?= e($payment['method']) ?></td>
                    <td class="right">Rp <?= number_format((float)$payment['amount'], 0, ',', '.') ?></td>
                </tr>
                <?php if ($payment['cash_received']): ?>
                    <tr>
                        <td>Tunai</td>
                        <td class="right">Rp <?= number_format((float)$payment['cash_received'], 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($payment['change_given']): ?>
                    <tr>
                        <td>Kembali</td>
                        <td class="right">Rp <?= number_format((float)$payment['change_given'], 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </table>

        <div class="line-solid"></div>

        <div class="center footer-note">
            <div>Terima kasih 🙏</div>
            <div>Selamat menikmati!</div>
            <div style="margin-top:6px;">*** <?= e($order['order_no']) ?> ***</div>
        </div>
    </div>

    <div class="btn-toolbar">
        <a href="/tokopi/views/pos.php" class="btn btn-back" style="text-decoration:none; text-align:center;">
            ← Kembali ke POS
        </a>
        <button class="btn btn-print" onclick="window.print()">🖨️ Print</button>
    </div>

    <script>
        // Auto-print kalau dipanggil dengan ?print=1
        <?php if (!empty($_GET['print'])): ?>
            window.addEventListener('load', () => setTimeout(() => window.print(), 300));
        <?php endif; ?>
    </script>
</body>

</html>
EOF