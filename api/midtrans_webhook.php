<?php
// api/midtrans_webhook.php
// Endpoint webhook dari Midtrans. TIDAK butuh login.
// URL: http://<domain>/tokopi/api/midtrans_webhook.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/bootstrap.php';

// Log raw payload untuk debugging
$raw = file_get_contents('php://input');
$payload = json_decode($raw, true) ?: [];

$log_dir = __DIR__ . '/../storage';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0775, true);
}
file_put_contents(
    $log_dir . '/midtrans_webhook.log',
    date('Y-m-d H:i:s') . ' | ' . $raw . PHP_EOL,
    FILE_APPEND
);

// 1. Validasi payload wajib
$order_id_midtrans = $payload['order_id'] ?? '';
$status_code       = $payload['status_code'] ?? '';
$gross_amount      = $payload['gross_amount'] ?? '';
$signature_key     = $payload['signature_key'] ?? '';
$server_key        = midtrans_server_key();

if (!$order_id_midtrans || !$status_code || !$gross_amount || !$signature_key) {
    http_response_code(400);
    echo json_encode(['message' => 'Payload tidak lengkap.']);
    exit;
}

// 2. Verifikasi signature SHA512
$expected = hash('sha512', $order_id_midtrans . $status_code . $gross_amount . $server_key);

if (!hash_equals($expected, $signature_key)) {
    http_response_code(403);
    echo json_encode(['message' => 'Invalid signature.']);
    exit;
}

// 3. Cari payment berdasarkan provider_ref (transaction_id)
$transaction_id = $payload['transaction_id'] ?? '';
if (!$transaction_id) {
    echo json_encode(['message' => 'OK']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, o.id AS order_id, o.outlet_id, o.status AS order_status
    FROM payments p
    JOIN orders o ON o.id = p.order_id
    WHERE p.provider_ref = ?
    LIMIT 1
");
$stmt->execute([$transaction_id]);
$payment = $stmt->fetch();

if (!$payment) {
    // Tidak dikenal — tetap return OK supaya Midtrans berhenti retry
    echo json_encode(['message' => 'OK']);
    exit;
}

// 4. Idempotent: kalau sudah final, tidak perlu proses lagi
if (in_array($payment['status'], ['SETTLED', 'REFUNDED'], true)) {
    echo json_encode(['message' => 'OK']);
    exit;
}

$tx_status = $payload['transaction_status'] ?? '';

try {
    $pdo->beginTransaction();

        if (in_array($tx_status, ['capture','settlement'], true)) {
        // Update payment
        $pdo->prepare("
            UPDATE payments
            SET status = 'SETTLED', paid_at = NOW(), raw_payload = ?
            WHERE id = ?
        ")->execute([json_encode($payload, JSON_UNESCAPED_UNICODE), $payment['id']]);

        // Update order
        $pdo->prepare("UPDATE orders SET status = 'PAID', paid_at = NOW() WHERE id = ?")
            ->execute([$payment['order_id']]);

        // Mark voucher USED
        $pdo->prepare("
            UPDATE customer_redemptions
            SET status = 'USED', used_at = NOW()
            WHERE order_id = ? AND status IN ('UNUSED', 'RESERVED')
        ")->execute([$payment['order_id']]);

        // Potong stok
        potong_stok_qris_webhook($pdo, (int)$payment['order_id'], (int)$payment['outlet_id']);

        // Award poin loyalty
        award_points_for_order($pdo, (int)$payment['order_id']);
    } elseif (in_array($tx_status, ['deny', 'cancel'], true)) {
        $pdo->prepare("UPDATE payments SET status = 'FAILED', raw_payload = ? WHERE id = ?")
            ->execute([json_encode($payload, JSON_UNESCAPED_UNICODE), $payment['id']]);
        $pdo->prepare("UPDATE orders SET status = 'CANCELLED' WHERE id = ?")
            ->execute([$payment['order_id']]);
    } elseif ($tx_status === 'expire') {
        $pdo->prepare("UPDATE payments SET status = 'EXPIRED', raw_payload = ? WHERE id = ?")
            ->execute([json_encode($payload, JSON_UNESCAPED_UNICODE), $payment['id']]);
        $pdo->prepare("UPDATE orders SET status = 'CANCELLED' WHERE id = ?")
            ->execute([$payment['order_id']]);
    } elseif ($tx_status === 'refund' || $tx_status === 'partial_refund') {
        $pdo->prepare("UPDATE payments SET status = 'REFUNDED', raw_payload = ? WHERE id = ?")
            ->execute([json_encode($payload, JSON_UNESCAPED_UNICODE), $payment['id']]);
    }
    // 'pending' → tidak ada aksi

    $pdo->commit();
    echo json_encode(['message' => 'OK']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    file_put_contents(
        $log_dir . '/midtrans_webhook.log',
        date('Y-m-d H:i:s') . ' | ERROR: ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(500);
    echo json_encode(['message' => 'Error processing webhook.']);
}

// ============================================
// Helper: potong stok berdasarkan cost_breakdown
// Idempotent — cek ledger dulu sebelum potong
// ============================================
function potong_stok_qris_webhook(PDO $pdo, int $order_id, int $outlet_id): void
{
    // Cek apakah sudah pernah dipotong
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE ref_type = 'ORDER' AND ref_id = ?");
    $stmt->execute([(string)$order_id]);
    if ((int)$stmt->fetchColumn() > 0) {
        return;
    }

    // Ambil cashier_id
    $stmt = $pdo->prepare("SELECT cashier_id FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $cashier_id = $stmt->fetchColumn();

    // Ambil items
    $stmt = $pdo->prepare("SELECT qty, cost_breakdown FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $it) {
        $breakdown = json_decode($it['cost_breakdown'], true) ?: [];
        $qty_order = (int)$it['qty'];

        foreach ($breakdown as $bahan) {
            $qty_deduct = $bahan['qty'] * $qty_order;

            // Upsert saldo stok
            $pdo->prepare("
                INSERT INTO stocks (outlet_id, ingredient_id, qty, reorder_point)
                VALUES (?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE qty = qty - ?
            ")->execute([$outlet_id, $bahan['ingredient_id'], -$qty_deduct, $qty_deduct]);

            // Ledger movement
            $pdo->prepare("
                INSERT INTO stock_movements
                (outlet_id, ingredient_id, qty, type, ref_type, ref_id, created_by_id)
                VALUES (?, ?, ?, 'SALE_OUT', 'ORDER', ?, ?)
            ")->execute([
                $outlet_id,
                $bahan['ingredient_id'],
                -$qty_deduct,
                (string)$order_id,
                $cashier_id
            ]);
        }
    }
}
