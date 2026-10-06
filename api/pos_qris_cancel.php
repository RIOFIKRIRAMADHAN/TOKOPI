<?php
// api/pos_qris_cancel.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/bootstrap.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

$tenant_id = current_tenant_id();

$input = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($input['order_id'] ?? 0);

if ($order_id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false]);
    exit;
}

// Cek order milik tenant & masih PENDING
$stmt = $pdo->prepare("
    SELECT o.id FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE o.id = ? AND ou.tenant_id = ? AND o.status = 'PENDING_PAYMENT'
");
$stmt->execute([$order_id, $tenant_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => true, 'note' => 'Order tidak bisa dibatalkan.']);
    exit;
}

try {
    // Update payment jadi FAILED
    $pdo->prepare("UPDATE payments SET status = 'FAILED' WHERE order_id = ? AND method = 'QRIS' AND status = 'PENDING'")
        ->execute([$order_id]);

    // Update order jadi CANCELLED
    $pdo->prepare("UPDATE orders SET status = 'CANCELLED' WHERE id = ?")
        ->execute([$order_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
