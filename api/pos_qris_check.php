<?php
// api/pos_qris_check.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/bootstrap.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'UNAUTHORIZED']);
    exit;
}

$tenant_id = current_tenant_id();
$order_id  = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'INVALID']);
    exit;
}

// Ambil order + pastikan milik tenant
$stmt = $pdo->prepare("
    SELECT o.id, o.order_no, o.status
    FROM orders o
    JOIN outlets ou ON ou.id = o.outlet_id
    WHERE o.id = ? AND ou.tenant_id = ?
");
$stmt->execute([$order_id, $tenant_id]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['status' => 'NOT_FOUND']);
    exit;
}

// Kalau sudah PAID, langsung return
if ($order['status'] === 'PAID') {
    echo json_encode([
        'status'   => 'PAID',
        'order_no' => $order['order_no'],
    ]);
    exit;
}

// Kalau CANCELLED / VOID
if (in_array($order['status'], ['CANCELLED', 'VOID'], true)) {
    echo json_encode(['status' => $order['status']]);
    exit;
}

// Masih PENDING_PAYMENT → cek ke Midtrans
$stmt = $pdo->prepare("
    SELECT provider_ref, status, raw_payload
    FROM payments
    WHERE order_id = ? AND method = 'QRIS'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

if (!$payment) {
    echo json_encode(['status' => 'PENDING_PAYMENT', 'reason' => 'no_payment_record']);
    exit;
}

// Ambil Midtrans order_id dari raw_payload
$raw = json_decode($payment['raw_payload'] ?? '{}', true);
$midtrans_order_id = $raw['order_id'] ?? null;

if (!$midtrans_order_id) {
    echo json_encode(['status' => 'PENDING_PAYMENT', 'reason' => 'no_midtrans_order_id']);
    exit;
}

// Cek ke Midtrans
try {
    midtrans_init();
    $status = \Midtrans\Transaction::status($midtrans_order_id);
    $tx_status = $status->transaction_status ?? '';
    $fraud_status = $status->fraud_status ?? '';

    // Update payment raw_payload dengan status terbaru
    $pdo->prepare("UPDATE payments SET raw_payload = ? WHERE order_id = ? AND method = 'QRIS'")
        ->execute([json_encode($status, JSON_UNESCAPED_UNICODE), $order_id]);

    if (in_array($tx_status, ['capture', 'settlement'], true)) {
        // Kalau capture & fraud challenge, belum settled
        if ($tx_status === 'capture' && $fraud_status === 'challenge') {
            echo json_encode(['status' => 'PENDING_PAYMENT', 'reason' => 'fraud_challenge']);
            exit;
        }

        // Update order → PAID
        $pdo->beginTransaction();

        $pdo->prepare("
            UPDATE payments
            SET status = 'SETTLED', paid_at = NOW()
            WHERE order_id = ? AND method = 'QRIS' AND status != 'SETTLED'
        ")->execute([$order_id]);

        $pdo->prepare("
            UPDATE orders
            SET status = 'PAID', paid_at = NOW()
            WHERE id = ? AND status = 'PENDING_PAYMENT'
        ")->execute([$order_id]);

        // Potong stok (idempotent)
        potong_stok_qris_check($pdo, $order_id);

        $pdo->commit();

        // Ambil order_no
        $stmt = $pdo->prepare("SELECT order_no FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order_no = $stmt->fetchColumn();

        echo json_encode([
            'status'   => 'PAID',
            'order_no' => $order_no,
        ]);
        exit;
    }

    if (in_array($tx_status, ['expire', 'cancel', 'deny'], true)) {
        $pdo->beginTransaction();

        $final_status = $tx_status === 'expire' ? 'EXPIRED' : 'FAILED';
        $pdo->prepare("UPDATE payments SET status = ? WHERE order_id = ? AND method = 'QRIS'")
            ->execute([$final_status, $order_id]);

        $pdo->prepare("UPDATE orders SET status = 'CANCELLED' WHERE id = ? AND status = 'PENDING_PAYMENT'")
            ->execute([$order_id]);

        $pdo->commit();

        echo json_encode(['status' => 'CANCELLED', 'reason' => $tx_status]);
        exit;
    }

    // Masih pending di Midtrans
    echo json_encode([
        'status'          => 'PENDING_PAYMENT',
        'midtrans_status' => $tx_status,
    ]);
    exit;

} catch (\Exception $e) {
    // Midtrans error (mis. 404 transaction not found) → tetap PENDING
    echo json_encode([
        'status'  => 'PENDING_PAYMENT',
        'reason'  => 'midtrans_error',
        'message' => $e->getMessage(),
    ]);
    exit;
}

// ============================================
// Helper: potong stok (idempotent)
// ============================================
function potong_stok_qris_check(PDO $pdo, int $order_id): void
{
    // Cek apakah sudah pernah dipotong
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE ref_type = 'ORDER' AND ref_id = ?");
    $stmt->execute([(string)$order_id]);
    if ((int)$stmt->fetchColumn() > 0) return;

    $stmt = $pdo->prepare("SELECT outlet_id, cashier_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) return;

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
            ")->execute([$order['outlet_id'], $bahan['ingredient_id'], -$qty_deduct, $qty_deduct]);

            $pdo->prepare("
                INSERT INTO stock_movements
                (outlet_id, ingredient_id, qty, type, ref_type, ref_id, created_by_id)
                VALUES (?, ?, ?, 'SALE_OUT', 'ORDER', ?, ?)
            ")->execute([
                $order['outlet_id'],
                $bahan['ingredient_id'],
                -$qty_deduct,
                (string)$order_id,
                $order['cashier_id']
            ]);
        }
    }
}