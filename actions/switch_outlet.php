<?php
// actions/switch_outlet.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();

// Hanya owner yang bisa ganti outlet
if (!is_owner()) {
    http_response_code(403);
    flash_set('error', 'Hanya owner yang bisa ganti outlet.');
    redirect('/tokopi/views/pos.php');
}

$outlet_id = (int)($_GET['outlet_id'] ?? 0);
$redirect  = $_GET['redirect'] ?? '/tokopi/views/pos.php';

// Validasi outlet milik tenant
$tenant_id = current_tenant_id();
$stmt = $pdo->prepare("SELECT id, nama FROM outlets WHERE id = ? AND tenant_id = ? AND is_active = 1");
$stmt->execute([$outlet_id, $tenant_id]);
$outlet = $stmt->fetch();

if (!$outlet) {
    flash_set('error', 'Outlet tidak valid.');
    redirect($redirect);
}

// Set active outlet di session
set_active_outlet($outlet_id);

flash_set('success', 'Outlet aktif: ' . $outlet['nama']);
redirect($redirect);
