<?php
// api/pos_qr_orders.php
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

if (!$outlet_id) {
    echo json_encode(['success' => false, 'message' => 'Outlet tidak valid.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.id, o.order_no, o.status, o.grand_total, o.discount_total,
       o.customer_name, o.customer_phone, o.created_at,
       t.nama AS table_nama, t.code AS table_code,
       (SELECT method FROM payments p WHERE p.order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method,
       (SELECT status FROM payments p WHERE p.order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_status,
       r.id AS redemption_id,
       r.points_used,
       rw.nama AS voucher_nama,
       rw.type AS voucher_type
FROM orders o
LEFT JOIN `tables` t ON t.id = o.table_id
LEFT JOIN customer_redemptions r ON r.order_id = o.id
LEFT JOIN rewards rw ON rw.id = r.reward_id
JOIN outlets ou ON ou.id = o.outlet_id
    WHERE ou.tenant_id = ?
      AND o.outlet_id = ?
      AND o.source = 'QR'
      AND o.status IN ('PAID', 'PREPARING', 'READY')
    ORDER BY
        FIELD(o.status, 'PAID', 'PREPARING', 'READY', 'PENDING_PAYMENT'),
        o.created_at ASC
");
$stmt->execute([$tenant_id, $outlet_id]);
$orders_raw = $stmt->fetchAll();

$order_ids = array_column($orders_raw, 'id');
$items_by_order = [];
if (!empty($order_ids)) {
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT order_id, product_nama, variant_nama, qty, note, line_total
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
            'note'         => $row['note'],
            'line_total'   => (float)$row['line_total'],
        ];
    }
}

$orders = [];
$stats = ['PAID' => 0, 'PREPARING' => 0, 'READY' => 0, 'PENDING_PAYMENT' => 0];

foreach ($orders_raw as $o) {
    $stats[$o['status']] = ($stats[$o['status']] ?? 0) + 1;
    $orders[] = [
        'id'             => (int)$o['id'],
        'order_no'       => $o['order_no'],
        'status'         => $o['status'],
        'grand_total'    => (float)$o['grand_total'],
        'customer_name'  => $o['customer_name'],
        'customer_phone' => $o['customer_phone'],
        'created_at'     => $o['created_at'],
        'table_nama'     => $o['table_nama'],
        'table_code'     => $o['table_code'],
        'payment_method' => $o['payment_method'],
        'payment_status' => $o['payment_status'],
        'items'          => $items_by_order[(int)$o['id']] ?? [],
        'voucher' => $o['redemption_id'] ? [
            'nama' => $o['voucher_nama'],
            'type' => $o['voucher_type'],
            'points_used' => (int)$o['points_used'],
            'discount' => (float)$o['discount_total'],
        ] : null,
    ];
}

echo json_encode([
    'success' => true,
    'stats'   => $stats,
    'orders'  => $orders,
]);
