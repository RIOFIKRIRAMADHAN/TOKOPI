<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager']);

$tenant_id = current_tenant_id();
$table_id  = (int)($_GET['id'] ?? 0);

if ($table_id <= 0) die('ID meja tidak valid.');

$stmt = $pdo->prepare("
    SELECT t.*, o.nama AS outlet_nama
    FROM `tables` t
    JOIN outlets o ON o.id = t.outlet_id
    WHERE t.id = ? AND o.tenant_id = ?
");
$stmt->execute([$table_id, $tenant_id]);
$table = $stmt->fetch();

if (!$table) die('Meja tidak ditemukan.');

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
          . '://' . $_SERVER['HTTP_HOST'];
$qr_url = $base_url . '/tokopi/customer/t.php?token=' . urlencode($table['qr_token']);
$qr_image = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($qr_url);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR <?= e($table['nama']) ?> · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f0f0; padding: 30px 20px; font-family: system-ui, sans-serif; }
        .qr-card {
            max-width: 400px; margin: 0 auto; background: #fff;
            border-radius: 20px; padding: 30px; text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,.1);
        }
        .qr-card h3 {
            font-family: 'Playfair Display', serif;
            color: #412D15; font-size: 1.75rem; margin-bottom: 4px;
        }
        .qr-card .subtitle { color: #999; font-size: 0.85rem; margin-bottom: 20px; }
        .qr-card img { max-width: 100%; border-radius: 12px; background: #fff; padding: 12px; border: 1px solid #eee; }
        .qr-card .token-box {
            background: #FAF9F5; padding: 8px 12px; border-radius: 8px;
            font-size: 0.7rem; margin-top: 16px;
            word-break: break-all; color: #999;
        }
        .qr-card .btn-group-custom { margin-top: 24px; display: flex; gap: 8px; }
        .qr-card .btn-group-custom a, .qr-card .btn-group-custom button {
            flex: 1; padding: 12px; border: none; border-radius: 10px;
            font-weight: 600; cursor: pointer; text-decoration: none;
            text-align: center; font-size: 1rem;
        }
        .btn-print-qr { background: #412D15; color: #fff; }
        .btn-back-qr { background: #e5e5e5; color: #333; }
        @media print {
            body { background: #fff; padding: 0; }
            .qr-card { box-shadow: none; max-width: 100%; padding: 20px; }
            .btn-group-custom, .token-box { display: none !important; }
        }
    </style>
</head>
<body>

<div class="qr-card">
    <h3>Tokopi</h3>
    <div class="subtitle"><?= e($table['outlet_nama']) ?></div>

    <img src="<?= e($qr_image) ?>" alt="QR <?= e($table['code']) ?>">

    <div style="margin-top:16px;">
        <div style="font-size:1.5rem; font-weight:700; color:#412D15;"><?= e($table['nama']) ?></div>
        <div style="color:#999; font-size:0.85rem;">Scan QR di atas untuk pesan dari meja ini</div>
    </div>

    <div class="token-box">Token: <?= e($table['qr_token']) ?></div>

    <div class="btn-group-custom">
        <a href="/tokopi/views/tables.php?outlet=<?= (int)$table['outlet_id'] ?>" class="btn-back-qr">
            ← Kembali
        </a>
        <button class="btn-print-qr" onclick="window.print()">
            <i class="bi bi-printer" aria-hidden="true"></i> Print QR
        </button>
    </div>
</div>

</body>
</html>
