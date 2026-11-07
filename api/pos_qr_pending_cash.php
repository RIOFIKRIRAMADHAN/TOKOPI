<?php
// api/pos_qr_pending_cash.php
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
           r.id AS redemption_id,
           r.points_used,
           rw.nama AS voucher_nama,
           rw.type AS voucher_type
    FROM orders o
    LEFT JOIN `tables` t ON t.id = o.table_id
    LEFT JOIN customer_redemptions r ON r.order_id = o.id
    LEFT JOIN rewards rw ON rw.id = r.reward_id
    JOIN outlets ou ON ou.id = o.outlet_id
    JOIN payments p ON p.order_id = o.id AND p.method = 'CASH'
    WHERE ou.tenant_id = ?
      AND o.outlet_id = ?
      AND o.source = 'QR'
      AND o.status = 'PENDING_PAYMENT'
      AND p.status = 'PENDING'
    ORDER BY o.created_at ASC
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
foreach ($orders_raw as $o) {
    $orders[] = [
        'id'             => (int)$o['id'],
        'order_no'       => $o['order_no'],
        'status'         => $o['status'],
        'grand_total'    => (float)$o['grand_total'],
        'discount_total' => (float)($o['discount_total'] ?? 0),
        'customer_name'  => $o['customer_name'],
        'customer_phone' => $o['customer_phone'],
        'created_at'     => $o['created_at'],
        'table_nama'     => $o['table_nama'],
        'table_code'     => $o['table_code'],
        'voucher'        => $o['redemption_id'] ? [
            'nama'        => $o['voucher_nama'],
            'type'        => $o['voucher_type'],
            'points_used' => (int)$o['points_used'],
            'discount'    => (float)$o['discount_total'],
        ] : null,
        'items'          => $items_by_order[(int)$o['id']] ?? [],
    ];
}

echo json_encode([
    'success' => true,
    'total'   => count($orders),
    'orders'  => $orders,
]);
