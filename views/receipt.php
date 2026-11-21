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

// Mode: struk | kitchen
$mode = $_GET['mode'] ?? 'struk';
$is_kitchen = ($mode === 'kitchen');

// Kalau kitchen mode, potong data pelanggan (privasi)
// Tapi tetap tampilkan nama (untuk panggil)

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
    <title><?= $is_kitchen ? 'Tiket Dapur' : 'Struk' ?> - <?= e($order['order_no']) ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f0f0f0;
            font-family: 'Courier New', 'JetBrains Mono', monospace;
            font-size: 11px;
            line-height: 1.35;
            color: #000;
        }

        body {
            padding: 20px 0;
        }

        .struk {
            width: 76mm;
            margin: 0 auto;
            background: #fff;
            padding: 12px 10px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .big {
            font-size: 13px;
        }

        .huge {
            font-size: 15px;
        }

        .small {
            font-size: 10px;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0;
            height: 0;
        }

        .line-solid {
            border-top: 1px solid #000;
            margin: 6px 0;
            height: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .kitchen-ticket {
            font-size: 12px;
        }

        .kitchen-ticket .item-name {
            font-size: 14px;
        }

        td,
        th {
            padding: 1px 0;
            vertical-align: top;
            border: none;
        }

        .item-name {
            padding-top: 4px;
            font-weight: bold;
        }

        .item-detail {
            color: #333;
            font-size: 10px;
        }

        .footer-note {
            font-size: 10px;
            color: #333;
            margin-top: 8px;
        }

        /* Toolbar (hanya muncul di layar, tidak saat print) */
        .btn-toolbar {
            width: 76mm;
            margin: 16px auto 0;
            display: flex;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 10px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-family: -apple-system, sans-serif;
            font-size: 13px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            color: #fff;
        }

        .btn-print {
            background: #412D15;
        }

        .btn-print:hover {
            background: #2a1d0e;
        }

        .btn-back {
            background: #6c757d;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        /* ============================================
           PRINT MODE — Thermal 80mm
           ============================================ */
        @media print {
            @page {
                size: 80mm auto;
                margin: 2mm;
            }

            html,
            body {
                width: 80mm;
                margin: 0;
                padding: 0;
                background: #fff;
                font-size: 10px;
            }

            body {
                padding: 0;
            }

            .struk {
                width: 76mm;
                max-width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }

            .btn-toolbar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="struk <?= $is_kitchen ? 'kitchen-ticket' : '' ?>">
        <?php if (!$is_kitchen): ?>
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
        <?php else: ?>
            <div class="center">
                <div class="bold big" style="font-size:16px;">TIKET DAPUR</div>
                <div style="font-size:14px; font-weight:bold;"><?= e($order['outlet_nama']) ?></div>
            </div>
        <?php endif; ?>

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
            <?php if ($order['customer_name']): ?>
                <tr>
                    <td>Nama</td>
                    <td class="right bold"><?= e($order['customer_name']) ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($order['table_code']): ?>
                <tr>
                    <td>Meja</td>
                    <td class="right bold"><?= e($order['table_code']) ?></td>
                </tr>
            <?php endif; ?>
            <?php if (!$is_kitchen): ?>
                <tr>
                    <td>Kasir</td>
                    <td class="right"><?= e($order['cashier_nama'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td>Channel</td>
                    <td class="right"><?= e($order['channel']) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="line"></div>

        <?php foreach ($items as $it): ?>
            <div class="item-name bold"><?= e($it['product_nama']) ?></div>
            <div class="item-detail"><?= e($it['variant_nama']) ?></div>
            <?php foreach ((json_decode((string)($it['modifiers'] ?? ''), true) ?: []) as $m): ?>
                <div class="item-detail">+ <?= e($m['nama']) ?><?= !$is_kitchen && (float)$m['price'] > 0 ? ' (' . number_format((float)$m['price'], 0, ',', '.') . ')' : '' ?></div>
            <?php endforeach; ?>
            <?php if (!empty($it['note'])): ?>
                <div class="item-detail bold">Catatan: <?= e($it['note']) ?></div>
            <?php endif; ?>
            <table>
                <tr>
                    <td><?= (int)$it['qty'] ?> x <?= number_format((float)$it['harga_satuan'], 0, ',', '.') ?></td>
                    <td class="right"><?= number_format((float)$it['line_total'], 0, ',', '.') ?></td>
                </tr>
            </table>
        <?php endforeach; ?>
        <?php if (!$is_kitchen): ?>
            <div class="line"></div>

            <?php if (!empty($order['discount_total']) && $order['discount_total'] > 0): ?>
                <table>
                    <tr>
                        <td>Subtotal</td>
                        <td class="right">Rp <?= number_format((float)$order['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Diskon</td>
                        <td class="right">− Rp <?= number_format((float)$order['discount_total'], 0, ',', '.') ?></td>
                    </tr>
                </table>
            <?php endif; ?>

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
                <div>Terima kasih</div>
                <div>Selamat menikmati!</div>
                <div style="margin-top:6px;">*** <?= e($order['order_no']) ?> ***</div>
            </div>
        <?php else: ?>
            <div class="line-solid"></div>
            <div class="center footer-note" style="font-size: 10px;">
                *** <?= e($order['order_no']) ?> ***
            </div>
        <?php endif; ?>

        <div class="btn-toolbar">
            <a href="/tokopi/views/pos.php" class="btn btn-back" style="text-decoration:none; text-align:center;">
                ← Kembali ke POS
            </a>
            <button class="btn btn-print" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Print</button>
        </div>

        <script>
            // Auto-print kalau dipanggil dengan ?print=1
            <?php if (!empty($_GET['print'])): ?>
                window.addEventListener('load', () => setTimeout(() => window.print(), 300));
            <?php endif; ?>
        </script>
</body>

</html>