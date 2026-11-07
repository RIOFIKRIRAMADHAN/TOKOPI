<?php
// customer/api_my_vouchers.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/bootstrap.php';

// Cek login customer
$customer_id = $_SESSION['customer_account_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['success' => true, 'vouchers' => []]);
    exit;
}

// Ambil voucher aktif (UNUSED + belum expired)
$stmt = $pdo->prepare("
    SELECT r.id, r.reward_id, r.points_used, r.expires_at, r.reward_snapshot,
           rw.nama AS reward_nama, rw.type AS reward_type, rw.value AS reward_value
    FROM customer_redemptions r
    JOIN rewards rw ON rw.id = r.reward_id
    WHERE r.customer_id = ?
      AND r.status = 'UNUSED'
      AND r.expires_at > NOW()
      AND (r.order_id IS NULL OR r.order_id = 0)
    ORDER BY r.expires_at ASC
");
$stmt->execute([$customer_id]);
$rows = $stmt->fetchAll();

$vouchers = [];
foreach ($rows as $row) {
    $snapshot = json_decode($row['reward_snapshot'] ?? '{}', true);
    $vouchers[] = [
        'id'            => (int)$row['id'],
        'voucher_code'  => $snapshot['voucher_code'] ?? '-',
        'reward_nama'   => $row['reward_nama'],
        'reward_type'   => $row['reward_type'],
        'reward_value'  => (float)$row['reward_value'],
        'expires_at'    => $row['expires_at'],
        'expires_at_fmt'=> date('d M Y', strtotime($row['expires_at'])),
    ];
}

echo json_encode([
    'success'  => true,
    'vouchers' => $vouchers,
]);
