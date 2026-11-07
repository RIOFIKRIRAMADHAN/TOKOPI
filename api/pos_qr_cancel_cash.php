<?php
// api/pos_qr_cancel_cash.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/bootstrap.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login.']);
    exit;
}

if (!in_array(current_user_role(), ['owner', 'manager', 'kasir'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

$input = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($input['order_id'] ?? 0);
$reason = trim($input['reason'] ?? 'Dibatalkan kasir');

if ($order_id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Order ID tidak valid.']);
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

if ($order['status'] !== 'PENDING_PAYMENT') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Order ini tidak bisa dibatalkan.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $pdo->prepare("UPDATE orders SET status = 'CANCELLED', note = ? WHERE id = ?")
        ->execute([$reason, $order_id]);

    $pdo->prepare("UPDATE payments SET status = 'FAILED' WHERE order_id = ? AND method = 'CASH'")
        ->execute([$order_id]);
    $pdo->prepare("
        UPDATE customer_redemptions
        SET order_id = NULL
        WHERE order_id = ? AND status IN ('UNUSED', 'RESERVED')
    ")->execute([$order_id]);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Order dibatalkan.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
