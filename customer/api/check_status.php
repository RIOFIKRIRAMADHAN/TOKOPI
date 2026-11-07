<?php
// customer/api_check_status.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'UNAUTHORIZED']);
    exit;
}

$table_id = (int)$_SESSION['customer_table_id'];
$outlet_id = (int)$_SESSION['customer_outlet_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'INVALID']);
    exit;
}

// Cek order milik meja ini
$stmt = $pdo->prepare("
    SELECT o.id, o.order_no, o.status, o.outlet_id
    FROM orders o
    WHERE o.id = ? AND o.table_id = ? AND o.outlet_id = ?
");
$stmt->execute([$order_id, $table_id, $outlet_id]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    echo json_encode(['status' => 'NOT_FOUND']);
    exit;
}

// Kalau sudah PAID / COMPLETED / READY, langsung return
if (in_array($order['status'], ['PAID', 'PREPARING', 'READY', 'COMPLETED'], true)) {
    // Idempotent award
    try {
        $pdo->beginTransaction();
        award_points_for_order($pdo, $order_id);
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Award on check_status failed: ' . $e->getMessage());
    }

    echo json_encode([
        'status' => 'PAID',
        'order_no' => $order['order_no'],
        'order_status' => $order['status'],
    ]);
    exit;
}

if (in_array($order['status'], ['CANCELLED', 'VOID'], true)) {
    echo json_encode(['status' => 'CANCELLED']);
    exit;
}

// Cek ke Midtrans
$stmt = $pdo->prepare("
    SELECT provider_ref, status, raw_payload
    FROM payments
    WHERE order_id = ? AND method = 'QRIS'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

if (!$payment) {
    echo json_encode(['status' => 'PENDING']);
    exit;
}

$raw = json_decode($payment['raw_payload'] ?? '{}', true);
$midtrans_order_id = $raw['order_id'] ?? null;

// Kalau payment method CASH, cek status di DB saja
if ($payment['method'] === 'CASH') {
    if ($payment['status'] === 'SETTLED') {
        echo json_encode([
            'status' => 'PAID',
            'order_no' => $order['order_no'],
        ]);
    } else if ($payment['status'] === 'FAILED') {
        echo json_encode(['status' => 'CANCELLED']);
    } else {
        echo json_encode(['status' => 'PENDING']);
    }
    exit;
}

if ($midtrans_order_id) {
    try {
        midtrans_init();
        $status = \Midtrans\Transaction::status($midtrans_order_id);
        $tx_status = $status->transaction_status ?? '';

        $pdo->prepare("UPDATE payments SET raw_payload = ? WHERE order_id = ? AND method = 'QRIS'")
            ->execute([json_encode($status, JSON_UNESCAPED_UNICODE), $order_id]);

                if (in_array($tx_status, ['capture', 'settlement'], true)) {
            $pdo->beginTransaction();

            $pdo->prepare("
                UPDATE payments SET status = 'SETTLED', paid_at = NOW()
                WHERE order_id = ? AND method = 'QRIS' AND status != 'SETTLED'
            ")->execute([$order_id]);

            $pdo->prepare("
                UPDATE orders SET status = 'PAID', paid_at = NOW()
                WHERE id = ? AND status = 'PENDING_PAYMENT'
            ")->execute([$order_id]);

            // Mark voucher USED
            $pdo->prepare("
                UPDATE customer_redemptions
                SET status = 'USED', used_at = NOW()
                WHERE order_id = ? AND status IN ('UNUSED', 'RESERVED')
            ")->execute([$order_id]);

            // Potong stok
            potong_stok_customer($pdo, $order_id, $outlet_id, $table_id);

            // Award poin loyalty
            award_points_for_order($pdo, $order_id);

            $pdo->commit();

            echo json_encode([
                'status' => 'PAID',
                'order_no' => $order['order_no'],
            ]);
            exit;
        }

        if (in_array($tx_status, ['expire', 'cancel', 'deny'], true)) {
            $pdo->beginTransaction();
            $final = $tx_status === 'expire' ? 'EXPIRED' : 'FAILED';
            $pdo->prepare("UPDATE payments SET status = ? WHERE order_id = ? AND method = 'QRIS'")
                ->execute([$final, $order_id]);
            $pdo->prepare("UPDATE orders SET status = 'CANCELLED' WHERE id = ? AND status = 'PENDING_PAYMENT'")
                ->execute([$order_id]);
            $pdo->commit();

            echo json_encode(['status' => 'CANCELLED']);
            exit;
        }

        echo json_encode(['status' => 'PENDING', 'midtrans_status' => $tx_status]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'PENDING', 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['status' => 'PENDING']);
exit;

// Helper
function potong_stok_customer(PDO $pdo, int $order_id, int $outlet_id, int $table_id): void
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
                VALUES (?, ?, ?, 'SALE_OUT', 'ORDER', ?, NULL)
            ")->execute([$outlet_id, $bahan['ingredient_id'], -$qty_deduct, (string)$order_id]);
        }
    }
}
