<?php
// customer/t.php
// Handler ketika pelanggan scan QR di meja
// URL: /tokopi/customer/t.php?token=xxx
// URL tanpa token = Landing page "Scan QR Dulu Yuk"

require_once __DIR__ . '/../config/bootstrap.php';

$token = trim($_GET['token'] ?? '');

// ============================================================
// JIKA TANPA TOKEN → TAMPILKAN LANDING PAGE "SCAN QR DULU"
// ============================================================
if ($token === '') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Scan QR Dulu · Tokopi</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
        <link href="/tokopi/assets/css/app.css" rel="stylesheet">
    </head>
    <body class="scan-page">
        <div class="card-scan">
            <div class="icon-scan"><i class="bi bi-qr-code-scan"></i></div>
            <h1 class="title">Scan QR Dulu Yuk</h1>
            <p class="desc">Untuk memesan, kamu perlu scan QR code di meja terlebih dahulu.</p>

            <div class="steps">
                <div class="step">
                    <span class="step-num">1</span>
                    <span>Arahkan kamera HP ke QR code di meja</span>
                </div>
                <div class="step">
                    <span class="step-num">2</span>
                    <span>Tunggu link terbuka otomatis</span>
                </div>
                <div class="step">
                    <span class="step-num">3</span>
                    <span>Pilih menu dan pesan tanpa antre</span>
                </div>
            </div>

            <a href="/tokopi/customer/home.php" class="btn-espresso">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Home
            </a>
            <a href="/tokopi/company/index.php" class="btn-back-link">
                Lihat-lihat menu dulu →
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// JIKA ADA TOKEN → VALIDASI DAN SET SESSION
// ============================================================

// Cari meja berdasarkan token
$stmt = $pdo->prepare("
    SELECT t.*, o.nama AS outlet_nama, o.tenant_id, o.timezone
    FROM `tables` t
    JOIN outlets o ON o.id = t.outlet_id
    WHERE t.qr_token = ? AND t.is_active = 1 AND o.is_active = 1
    LIMIT 1
");
$stmt->execute([$token]);
$table = $stmt->fetch();

// Kalau token tidak valid / meja tidak aktif → tampilkan error page yang ramah
if (!$table) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>QR Tidak Valid · Tokopi</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
        <link href="/tokopi/assets/css/app.css" rel="stylesheet">
    </head>
    <body class="scan-page">
        <div class="card-error">
            <div class="icon-error"><i class="bi bi-x-octagon"></i></div>
            <h1 class="title">QR Tidak Valid</h1>
            <p class="desc">QR code ini sudah tidak aktif atau tidak dikenali. Silakan minta bantuan staf kami untuk QR yang baru.</p>
            <a href="/tokopi/customer/t.php" class="btn-espresso">
                <i class="bi bi-arrow-clockwise me-1"></i> Coba Lagi
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// VALIDASI SUKSES → SET SESSION MEJA
// ============================================================
$new_table_id = (int)$table['id'];
$old_table_id = (int)($_SESSION['customer_table_id'] ?? 0);

// Kalau pindah meja (table_id beda), reset session ID
// Biar pelanggan baru di meja ini tidak lihat riwayat orang sebelumnya
if ($new_table_id !== $old_table_id || empty($_SESSION['customer_session_id'])) {
    $_SESSION['customer_session_id'] = bin2hex(random_bytes(16));
}

$_SESSION['customer_outlet_id']     = (int)$table['outlet_id'];
$_SESSION['customer_outlet_nama']   = $table['outlet_nama'];
$_SESSION['customer_table_id']      = $new_table_id;
$_SESSION['customer_table_code']    = $table['code'];
$_SESSION['customer_table_nama']    = $table['nama'];
$_SESSION['customer_token']         = $token;
$_SESSION['customer_session_start'] = time();

// Redirect ke home
redirect('/tokopi/customer/home.php');