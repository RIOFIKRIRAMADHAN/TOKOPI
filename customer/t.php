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
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                -webkit-tap-highlight-color: transparent;
            }

            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
                background: linear-gradient(165deg, #1A0F06 0%, #2D1B0A 50%, #4A3418 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                position: relative;
                overflow: hidden;
            }

            /* Ornamen background */
            body::before,
            body::after {
                content: '';
                position: absolute;
                border-radius: 50%;
                pointer-events: none;
                z-index: 0;
            }

            body::before {
                width: 350px;
                height: 350px;
                background: radial-gradient(circle, rgba(225, 220, 201, .12) 0%, transparent 70%);
                top: -100px;
                right: -100px;
            }

            body::after {
                width: 300px;
                height: 300px;
                background: radial-gradient(circle, rgba(184, 133, 58, .15) 0%, transparent 70%);
                bottom: -80px;
                left: -100px;
            }

            /* Pattern garis diagonal */
            .bg-pattern {
                position: absolute;
                inset: 0;
                background-image: linear-gradient(45deg, transparent 47%, rgba(225, 220, 201, .03) 48%, rgba(225, 220, 201, .03) 52%, transparent 53%);
                background-size: 30px 30px;
                pointer-events: none;
                z-index: 0;
            }

            .card-scan {
                background: #fff;
                border-radius: 32px;
                padding: 40px 32px;
                width: 100%;
                max-width: 400px;
                text-align: center;
                box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);
                position: relative;
                z-index: 10;
            }

            .icon-scan {
                width: 88px;
                height: 88px;
                background: linear-gradient(135deg, #FAF6EC 0%, #E1DCC9 100%);
                color: #2D1B0A;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                margin-bottom: 24px;
                box-shadow: 0 8px 24px rgba(45, 27, 10, 0.1);
            }

            .title {
                font-family: 'Playfair Display', serif;
                font-size: 1.8rem;
                font-weight: 700;
                color: #2D1B0A;
                margin-bottom: 12px;
                letter-spacing: -0.02em;
            }

            .desc {
                font-size: 0.9rem;
                color: #6B5B4A;
                line-height: 1.6;
                margin-bottom: 32px;
            }

            .steps {
                background: #FAF9F5;
                border: 1px solid #EBE6DA;
                border-radius: 20px;
                padding: 20px;
                margin-bottom: 32px;
                text-align: left;
            }

            .step {
                display: flex;
                align-items: flex-start;
                gap: 16px;
                padding: 12px 0;
            }

            .step:not(:last-child) {
                border-bottom: 1px dashed #EBE6DA;
            }

            .step-num {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background: #2D1B0A;
                color: #fff;
                font-size: 0.8rem;
                font-weight: 700;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .step-text {
                font-size: 0.88rem;
                color: #4A3418;
                line-height: 1.5;
                font-weight: 500;
                padding-top: 4px;
            }

            .btn-primary {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #2D1B0A 0%, #1A0F06 100%);
                color: #fff;
                text-decoration: none;
                border-radius: 16px;
                font-weight: 700;
                font-size: 0.95rem;
                transition: all 0.25s ease;
                box-shadow: 0 8px 24px rgba(45, 27, 10, 0.3);
                border: none;
            }

            .btn-primary:active {
                transform: scale(0.98);
            }

            .btn-outline {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                padding: 16px;
                background: transparent;
                color: #6B5B4A;
                text-decoration: none;
                border-radius: 16px;
                font-weight: 600;
                font-size: 0.9rem;
                transition: all 0.25s ease;
                margin-top: 8px;
            }

            .btn-outline:active {
                background: #FAF9F5;
            }

            @media (min-width: 768px) {
                body {
                    background: #1A1A1A;
                    /* Biar sama dengan yang lain */
                }
            }
        </style>
        <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
    </head>

    <body>
        <div class="bg-pattern"></div>

        <div class="card-scan">
            <div class="icon-scan"><i class="bi bi-qr-code-scan"></i></div>
            <h1 class="title">Scan QR Dulu Yuk</h1>
            <p class="desc">Untuk mulai memesan, kamu perlu scan QR code yang ada di meja.</p>

            <div class="steps">
                <div class="step">
                    <span class="step-num">1</span>
                    <span class="step-text">Arahkan kamera HP ke QR code di meja</span>
                </div>
                <div class="step">
                    <span class="step-num">2</span>
                    <span class="step-text">Tunggu link terbuka secara otomatis</span>
                </div>
                <div class="step">
                    <span class="step-num">3</span>
                    <span class="step-text">Pilih menu favoritmu dan pesan tanpa antre</span>
                </div>
            </div>

            <a href="/tokopi/customer/home.php" class="btn-primary">
                <i class="bi bi-arrow-left"></i> Kembali ke Home
            </a>
            <a href="/tokopi/company/index.php" class="btn-outline">
                Lihat-lihat menu dulu <i class="bi bi-arrow-right"></i>
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
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                -webkit-tap-highlight-color: transparent;
            }

            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
                background: linear-gradient(165deg, #1A0F06 0%, #2D1B0A 50%, #4A3418 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                position: relative;
                overflow: hidden;
            }

            /* Ornamen background */
            body::before,
            body::after {
                content: '';
                position: absolute;
                border-radius: 50%;
                pointer-events: none;
                z-index: 0;
            }

            body::before {
                width: 350px;
                height: 350px;
                background: radial-gradient(circle, rgba(168, 50, 50, .15) 0%, transparent 70%);
                top: -100px;
                right: -100px;
            }

            body::after {
                width: 300px;
                height: 300px;
                background: radial-gradient(circle, rgba(184, 133, 58, .15) 0%, transparent 70%);
                bottom: -80px;
                left: -100px;
            }

            .bg-pattern {
                position: absolute;
                inset: 0;
                background-image: linear-gradient(45deg, transparent 47%, rgba(225, 220, 201, .03) 48%, rgba(225, 220, 201, .03) 52%, transparent 53%);
                background-size: 30px 30px;
                pointer-events: none;
                z-index: 0;
            }

            .card-error {
                background: #fff;
                border-radius: 32px;
                padding: 40px 32px;
                width: 100%;
                max-width: 400px;
                text-align: center;
                box-shadow: 0 24px 64px rgba(0, 0, 0, 0.3);
                position: relative;
                z-index: 10;
            }

            .icon-error {
                width: 88px;
                height: 88px;
                background: #FEF2F2;
                color: #991B1B;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                margin-bottom: 24px;
                box-shadow: 0 8px 24px rgba(168, 50, 50, 0.1);
            }

            .title {
                font-family: 'Playfair Display', serif;
                font-size: 1.8rem;
                font-weight: 700;
                color: #2D1B0A;
                margin-bottom: 12px;
                letter-spacing: -0.02em;
            }

            .desc {
                font-size: 0.9rem;
                color: #6B5B4A;
                line-height: 1.6;
                margin-bottom: 32px;
            }

            .btn-primary {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #2D1B0A 0%, #1A0F06 100%);
                color: #fff;
                text-decoration: none;
                border-radius: 16px;
                font-weight: 700;
                font-size: 0.95rem;
                transition: all 0.25s ease;
                box-shadow: 0 8px 24px rgba(45, 27, 10, 0.3);
                border: none;
            }

            .btn-primary:active {
                transform: scale(0.98);
            }
        </style>
    </head>

    <body>
        <div class="bg-pattern"></div>

        <div class="card-error">
            <div class="icon-error"><i class="bi bi-x-octagon"></i></div>
            <h1 class="title">QR Tidak Valid</h1>
            <p class="desc">QR code ini sudah tidak aktif atau tidak dikenali. Silakan minta bantuan staf kami untuk QR yang baru.</p>
            <a href="/tokopi/customer/t.php" class="btn-primary">
                <i class="bi bi-arrow-clockwise"></i> Coba Lagi
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
