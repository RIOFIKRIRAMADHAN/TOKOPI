<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager']);

$tenant_id = current_tenant_id();
$outlet_id = (int)($_GET['outlet'] ?? 0);

if ($outlet_id <= 0) die('Outlet tidak valid.');

$stmt = $pdo->prepare("SELECT * FROM outlets WHERE id = ? AND tenant_id = ?");
$stmt->execute([$outlet_id, $tenant_id]);
$outlet = $stmt->fetch();
if (!$outlet) die('Outlet tidak ditemukan.');

$stmt = $pdo->prepare("SELECT * FROM `tables` WHERE outlet_id = ? AND is_active = 1 ORDER BY code");
$stmt->execute([$outlet_id]);
$tables = $stmt->fetchAll();

if (empty($tables)) die('Belum ada meja aktif di outlet ini.');

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
          . '://' . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Print QR Meja · <?= e($outlet['nama']) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; padding: 20px; background: #f0f0f0; }
        .toolbar { text-align: center; margin-bottom: 20px; }
        .toolbar button, .toolbar a {
            padding: 12px 24px; background: #412D15; color: #fff;
            border: none; border-radius: 8px; font-weight: 600;
            cursor: pointer; margin: 0 4px;
            text-decoration: none; display: inline-block;
        }
        .toolbar a { background: #666; }
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .qr-card {
            background: #fff; border: 2px dashed #412D15;
            border-radius: 16px; padding: 20px;
            text-align: center; page-break-inside: avoid;
        }
        .qr-card h3 {
            font-family: 'Playfair Display', serif;
            color: #412D15; font-size: 1.5rem; margin: 0 0 4px;
        }
        .qr-card .outlet-name { color: #999; font-size: 0.75rem; margin-bottom: 12px; }
        .qr-card img { width: 100%; max-width: 220px; height: auto; }
        .qr-card .table-code {
            font-size: 1.75rem; font-weight: 700;
            color: #412D15; margin-top: 12px; letter-spacing: 0.05em;
        }
        .qr-card .table-name { color: #999; font-size: 0.85rem; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .qr-grid { gap: 10px; }
            .qr-card { page-break-inside: avoid; padding: 12px; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <a href="/tokopi/views/tables.php?outlet=<?= (int)$outlet_id ?>">← Kembali</a>
    <button onclick="window.print()">Print Semua</button>
</div>

<h2 style="text-align:center; color:#412D15;">
    QR Code Meja — <?= e($outlet['nama']) ?>
</h2>
<p style="text-align:center; color:#999; margin-bottom: 24px;">
    Cetak, potong, dan tempel di meja masing-masing
</p>

<div class="qr-grid">
    <?php foreach ($tables as $t): ?>
        <?php
        $qr_url = $base_url . '/tokopi/customer/t.php?token=' . urlencode($t['qr_token']);
        $qr_image = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_url);
        ?>
        <div class="qr-card">
            <h3>Tokopi</h3>
            <div class="outlet-name"><?= e($outlet['nama']) ?></div>
            <img src="<?= e($qr_image) ?>" alt="QR <?= e($t['code']) ?>">
            <div class="table-code"><?= e($t['code']) ?></div>
            <div class="table-name"><?= e($t['nama']) ?></div>
            <div style="font-size:0.7rem; color:#999; margin-top:8px;">Scan untuk pesan</div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
