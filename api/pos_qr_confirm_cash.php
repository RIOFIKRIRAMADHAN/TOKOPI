<?php
// api/pos_qr_confirm_cash.php
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
$user_id   = current_user_id();

$input = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($input['order_id'] ?? 0);

if ($order_id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Order ID tidak valid.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.id, o.status, o.source, o.cashier_id, o.shift_id
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
    echo json_encode(['success' => false, 'message' => 'Hanya order QR yang bisa dikonfirmasi di sini.']);
    exit;
}

if ($order['status'] !== 'PENDING_PAYMENT') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Order ini sudah diproses sebelumnya.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Cari shift aktif kasir
    $stmt = $pdo->prepare("SELECT id FROM shifts WHERE cashier_id = ? AND status = 'OPEN' LIMIT 1");
    $stmt->execute([$user_id]);
    $shift_id = $stmt->fetchColumn() ?: null;

    // Update payment → SETTLED
    $pdo->prepare("
        UPDATE payments
        SET status = 'SETTLED', paid_at = NOW()
        WHERE order_id = ? AND method = 'CASH' AND status = 'PENDING'
    ")->execute([$order_id]);

    // Update order → PAID + isi cashier_id & shift_id
    $pdo->prepare("
        UPDATE orders
        SET status = 'PAID', paid_at = NOW(), cashier_id = ?, shift_id = ?
        WHERE id = ?
    ")->execute([$user_id, $shift_id, $order_id]);

    // Potong stok
    $pdo->prepare("
        UPDATE customer_redemptions
        SET status = 'USED', used_at = NOW()
        WHERE order_id = ? AND status IN ('UNUSED', 'RESERVED')
    ")->execute([$order_id]);

    // Potong stok
    potong_stok_cash_qr($pdo, $order_id, $outlet_id, $user_id);

    // Award poin loyalty (kalau customer login)
    award_points_for_order($pdo, $order_id);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Pembayaran cash dikonfirmasi.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Helper
function potong_stok_cash_qr(PDO $pdo, int $order_id, int $outlet_id, int $user_id): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE ref_type = 'ORDER' AND ref_id = ?");
    $stmt->execute([(string)$order_id]);
    if ((int)$stmt->fetchColumn() > 0) return;

    $stmt = $pdo->prepare("SELECT qty, cost_breakdown FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $it) {
        $breakdown = json_decode($it['cost_breakdown'], true) ?: [];
        $qty_order = (int)$it['qty'];

        foreach ($breakdown as $bahan) {
            $qty_deduct = $bahan['qty'] * $qty_order;

            $pdo->prepare("
                INSERT INTO stocks (outlet_id, ingredient_id, qty, reorder_point)
                VALUES (?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE qty = qty - ?
            ")->execute([$outlet_id, $bahan['ingredient_id'], -$qty_deduct, $qty_deduct]);

            $pdo->prepare("
                INSERT INTO stock_movements
                (outlet_id, ingredient_id, qty, type, ref_type, ref_id, created_by_id)
                VALUES (?, ?, ?, 'SALE_OUT', 'ORDER', ?, ?)
            ")->execute([$outlet_id, $bahan['ingredient_id'], -$qty_deduct, (string)$order_id, $user_id]);
        }
    }
}
