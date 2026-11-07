<?php
// customer/reward_action.php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/bootstrap.php';

// Cek session meja
if (empty($_SESSION['customer_table_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session tidak valid.']);
    exit;
}

// Cek login akun
$customer_id = $_SESSION['customer_account_id'] ?? null;
if (!$customer_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Anda belum login.']);
    exit;
}

$outlet_id = (int)$_SESSION['customer_outlet_id'];

// Ambil tenant_id
$stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$tenant_id = (int)$stmt->fetchColumn();

// Baca payload
$input = json_decode(file_get_contents('php://input'), true);
$reward_id = (int)($input['reward_id'] ?? 0);

if ($reward_id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Reward tidak valid.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock customer row
    $stmt = $pdo->prepare("SELECT id, points FROM customers WHERE id = ? AND tenant_id = ? FOR UPDATE");
    $stmt->execute([$customer_id, $tenant_id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        throw new Exception('Akun pelanggan tidak ditemukan.');
    }

    // Ambil reward
    $stmt = $pdo->prepare("SELECT * FROM rewards WHERE id = ? AND tenant_id = ? AND is_active = 1");
    $stmt->execute([$reward_id, $tenant_id]);
    $reward = $stmt->fetch();

    if (!$reward) {
        throw new Exception('Reward tidak tersedia.');
    }

    $points_cost = (int)$reward['points_cost'];
    $current_points = (int)$customer['points'];

    if ($current_points < $points_cost) {
        throw new Exception('Poin tidak cukup. Butuh ' . $points_cost . ' poin, kamu punya ' . $current_points . ' poin.');
    }

    // Generate voucher code
    $voucher_code = strtoupper(bin2hex(random_bytes(4))); // 8 char

    // Insert redemption
    $stmt = $pdo->prepare("
        INSERT INTO customer_redemptions
        (customer_id, reward_id, points_used, reward_snapshot, status, expires_at)
        VALUES (?, ?, ?, ?, 'UNUSED', DATE_ADD(NOW(), INTERVAL 30 DAY))
    ");
    $stmt->execute([
        $customer_id,
        $reward_id,
        $points_cost,
        json_encode([
            'nama' => $reward['nama'],
            'deskripsi' => $reward['deskripsi'],
            'type' => $reward['type'],
            'value' => $reward['value'],
            'variant_id' => $reward['variant_id'],
            'voucher_code' => $voucher_code,
        ], JSON_UNESCAPED_UNICODE),
    ]);

    $redemption_id = (int)$pdo->lastInsertId();

    // Kurangi poin customer
    $pdo->prepare("UPDATE customers SET points = points - ? WHERE id = ?")
        ->execute([$points_cost, $customer_id]);

    // Catat di ledger
    $pdo->prepare("
        INSERT INTO customer_points (customer_id, tenant_id, type, points, note)
        VALUES (?, ?, 'REDEEM', ?, ?)
    ")->execute([
        $customer_id,
        $tenant_id,
        -$points_cost,
        'Tukar reward: ' . $reward['nama'] . ' (kode: ' . $voucher_code . ')',
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'redemption_id' => $redemption_id,
        'voucher_code' => $voucher_code,
        'message' => 'Voucher berhasil ditukar.',
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
