<?php
// customer/api_my_orders.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session meja tidak ditemukan.']);
    exit;
}

$outlet_id           = (int)$_SESSION['customer_outlet_id'];
$customer_session_id = $_SESSION['customer_session_id'] ?? null;
$customer_account_id = $_SESSION['customer_account_id'] ?? null;

// Prioritas: login account > session meja
if ($customer_account_id) {
    // Customer login: ambil SEMUA riwayat lintas meja
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_no, o.status, o.grand_total, o.discount_total, o.created_at,
               (SELECT method FROM payments p WHERE p.order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method
        FROM orders o
        WHERE o.customer_account_id = ?
          AND o.source = 'QR'
        ORDER BY o.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$customer_account_id]);
} elseif ($customer_session_id) {
    // Guest: filter by session meja
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_no, o.status, o.grand_total, o.discount_total, o.created_at,
               (SELECT method FROM payments p WHERE p.order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method
        FROM orders o
        WHERE o.customer_session_id = ?
          AND o.outlet_id = ?
          AND o.source = 'QR'
        ORDER BY o.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$customer_session_id, $outlet_id]);
} else {
    echo json_encode(['success' => true, 'orders' => []]);
    exit;
}

$outlet_id           = (int)$_SESSION['customer_outlet_id'];
$customer_session_id = $_SESSION['customer_session_id'] ?? null;
$customer_account_id = $_SESSION['customer_account_id'] ?? null;

// Prioritas: login account > session meja
if ($customer_account_id) {
    // Customer login: ambil SEMUA riwayat lintas meja
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_no, o.status, o.grand_total, o.discount_total, o.created_at,
               (SELECT method FROM payments p WHERE p.order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method
        FROM orders o
        WHERE o.customer_account_id = ?
          AND o.source = 'QR'
        ORDER BY o.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$customer_account_id]);
} elseif ($customer_session_id) {
    // Guest: filter by session meja
    $stmt = $pdo->prepare("
        SELECT o.id, o.order_no, o.status, o.grand_total, o.discount_total, o.created_at,
               (SELECT method FROM payments p WHERE p.order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method
        FROM orders o
        WHERE o.customer_session_id = ?
          AND o.outlet_id = ?
          AND o.source = 'QR'
        ORDER BY o.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$customer_session_id, $outlet_id]);
} else {
    echo json_encode(['success' => true, 'orders' => []]);
    exit;
}

$orders_raw = $stmt->fetchAll();

$order_ids = array_column($orders_raw, 'id');
$items_by_order = [];
if (!empty($order_ids)) {
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT order_id, product_nama, variant_nama, qty, modifiers
        FROM order_items
        WHERE order_id IN ($placeholders)
        ORDER BY id
    ");
    $stmt->execute($order_ids);
    foreach ($stmt->fetchAll() as $row) {
        $oid = (int)$row['order_id'];
        if (!isset($items_by_order[$oid])) $items_by_order[$oid] = [];
        $items_by_order[$oid][] = [
            'product_nama' => $row['product_nama'],
            'variant_nama' => $row['variant_nama'],
            'qty'          => (int)$row['qty'],
            'modifiers'    => menu_options_label($row['modifiers']),
        ];
    }
}

$orders = [];
foreach ($orders_raw as $o) {
    $orders[] = [
        'id'             => (int)$o['id'],
        'order_no'       => $o['order_no'],
        'status'         => $o['status'],
        'grand_total'    => (float)$o['grand_total'],
        'discount_total' => (float)($o['discount_total'] ?? 0),
        'created_at'     => $o['created_at'],
        'payment_method' => $o['payment_method'],
        'items'          => $items_by_order[(int)$o['id']] ?? [],
    ];
}

echo json_encode([
    'success' => true,
    'orders'  => $orders,
]);
