<?php
// customer/t.php
// Handler ketika pelanggan scan QR di meja
// URL: /tokopi/customer/t.php?token=xxx

require_once __DIR__ . '/../config/bootstrap.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    die('QR tidak valid: token kosong. Silakan scan ulang QR di meja Anda.');
}

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

if (!$table) {
    die('QR tidak valid atau meja sudah tidak aktif. Silakan tanya staf kami.');
}

// Simpan session pelanggan
$new_table_id = (int)$table['id'];
$old_table_id = (int)($_SESSION['customer_table_id'] ?? 0);

// Kalau pindah meja (table_id beda), reset session ID
// Biar pelanggan baru di meja ini tidak lihat riwayat orang sebelumnya
if ($new_table_id !== $old_table_id || empty($_SESSION['customer_session_id'])) {
    $_SESSION['customer_session_id'] = bin2hex(random_bytes(16));
}

$_SESSION['customer_outlet_id']  = (int)$table['outlet_id'];
$_SESSION['customer_outlet_nama'] = $table['outlet_nama'];
$_SESSION['customer_table_id']   = $new_table_id;
$_SESSION['customer_table_code'] = $table['code'];
$_SESSION['customer_table_nama'] = $table['nama'];
$_SESSION['customer_token']      = $token;
$_SESSION['customer_session_start'] = time();

// Redirect ke halaman menu
redirect('/tokopi/customer/home.php');
