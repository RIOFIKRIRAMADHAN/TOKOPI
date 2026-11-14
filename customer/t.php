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
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Scan QR Dulu · Tokopi</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
            body {
                background: linear-gradient(160deg, #1a1a1a 0%, #2a1d0e 60%, #3d2a15 100%);
                color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
                min-height: 100vh; display: flex; align-items: center; justify-content: center;
                padding: 30px; margin: 0; overflow: hidden;
            }
            body::before {
                content: ''; position: fixed; top: -100px; right: -100px;
                width: 350px; height: 350px;
                background: radial-gradient(circle, rgba(225,220,201,.15) 0%, transparent 70%);
                border-radius: 50%; pointer-events: none;
            }
            .card-scan {
                background: #fff; color: #1a1a1a; border-radius: 24px;
                padding: 40px 28px; max-width: 400px; width: 100%; text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.4); position: relative; z-index: 1;
            }
            .icon-scan {
                width: 88px; height: 88px; border-radius: 50%;
                background: linear-gradient(135deg, #F3F0E6 0%, #E1DCC9 100%);
                display: inline-flex; align-items: center; justify-content: center;
                font-size: 2.8rem; color: #412D15; margin-bottom: 24px;
                box-shadow: 0 8px 20px rgba(65,45,21,0.15);
            }
            .title {
                font-family: 'Playfair Display', serif; font-size: 1.6rem;
                font-weight: 700; color: #412D15; margin-bottom: 12px; line-height: 1.2;
            }
            .desc {
                font-size: 0.9rem; color: #8a8478; line-height: 1.6; margin-bottom: 28px;
            }
            .steps {
                background: #FAF9F5; border-radius: 12px; padding: 16px;
                margin-bottom: 24px; text-align: left;
            }
            .step {
                display: flex; gap: 12px; align-items: flex-start;
                font-size: 0.82rem; color: #412D15; font-weight: 500;
                padding: 6px 0;
            }
            .step:not(:last-child) { border-bottom: 1px dashed #ebe6da; }
            .step-num {
                width: 22px; height: 22px; border-radius: 50%;
                background: #412D15; color: #fff;
                display: flex; align-items: center; justify-content: center;
                font-size: 0.7rem; font-weight: 800; flex-shrink: 0;
            }
            .btn-espresso {
                background: #412D15; color: #fff; text-decoration: none;
                padding: 14px; border-radius: 12px; font-weight: 700; display: block;
                font-size: 0.95rem; transition: 0.15s;
            }
            .btn-espresso:active { transform: scale(0.97); }
            .btn-back-link {
                display: block; margin-top: 16px; color: #8a8478; text-decoration: none;
                font-size: 0.85rem; font-weight: 600;
            }
        </style>
    </head>
    <body>
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
        <style>
            * { box-sizing: border-box; }
            body {
                background: linear-gradient(160deg, #1a1a1a 0%, #2a1d0e 100%);
                color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
                min-height: 100vh; display: flex; align-items: center; justify-content: center;
                padding: 30px; margin: 0;
            }
            .card-error {
                background: #fff; color: #1a1a1a; border-radius: 24px;
                padding: 40px 28px; max-width: 400px; width: 100%; text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            }
            .icon-error {
                width: 88px; height: 88px; border-radius: 50%;
                background: #FEE2E2; color: #991B1B;
                display: inline-flex; align-items: center; justify-content: center;
                font-size: 2.8rem; margin-bottom: 24px;
            }
            .title { font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 700; color: #412D15; margin-bottom: 12px; }
            .desc { font-size: 0.9rem; color: #8a8478; line-height: 1.6; margin-bottom: 24px; }
            .btn-espresso {
                background: #412D15; color: #fff; text-decoration: none;
                padding: 14px; border-radius: 12px; font-weight: 700; display: block; transition: 0.15s;
            }
            .btn-espresso:active { transform: scale(0.97); }
        </style>
    </head>
    <body>
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