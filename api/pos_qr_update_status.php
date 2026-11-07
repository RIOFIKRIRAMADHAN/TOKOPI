<?php
// api/pos_qr_update_status.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/bootstrap.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

if (!in_array(current_user_role(), ['owner','manager','kasir'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

$input = json_decode(file_get_contents('php://input'), true);
$order_id   = (int)($input['order_id'] ?? 0);
$new_status = $input['status'] ?? '';

$valid = ['PREPARING', 'READY', 'COMPLETED'];

if ($order_id <= 0 || !in_array($new_status, $valid, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.id, o.status, o.source
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE o.id = ? AND ou.tenant_id = ? AND o.outlet_id = ?
");
$stmt->execute([$order_id, $tenant_id, $outlet_id]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan.']);
    exit;
}

if ($order['source'] !== 'QR') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Hanya order QR yang bisa diupdate di sini.']);
    exit;
}

$valid_transitions = [
    'PAID'      => ['PREPARING'],
    'PREPARING' => ['READY'],
    'READY'     => ['COMPLETED'],
];

if (!isset($valid_transitions[$order['status']]) ||
    !in_array($new_status, $valid_transitions[$order['status']], true)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => "Tidak bisa ubah dari {$order['status']} ke {$new_status}."
    ]);
    exit;
}

try {
    if ($new_status === 'COMPLETED') {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'COMPLETED', completed_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
    }

    echo json_encode(['success' => true, 'status' => $new_status]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
