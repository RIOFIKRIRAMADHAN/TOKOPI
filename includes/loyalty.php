<?php
// includes/loyalty.php
// Helper untuk loyalty: award points, update tier, dsb

/**
 * Award poin ke customer setelah order PAID.
 * Idempotent: kalau sudah pernah di-award untuk order ini, skip.
 */
function award_points_for_order(PDO $pdo, int $order_id): array
{
    $result = ['awarded' => false, 'points' => 0, 'reason' => ''];

    // Ambil order
    $stmt = $pdo->prepare("
        SELECT id, outlet_id, customer_account_id, grand_total, status
        FROM orders
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $result['reason'] = 'Order tidak ditemukan';
        return $result;
    }

    // Skip kalau tidak ada customer (guest)
    if (empty($order['customer_account_id'])) {
        $result['reason'] = 'Guest order (tanpa akun)';
        return $result;
    }

    // Skip kalau order belum PAID
    if (!in_array($order['status'], ['PAID', 'PREPARING', 'READY', 'COMPLETED'], true)) {
        $result['reason'] = 'Order belum PAID';
        return $result;
    }

    $customer_id = (int)$order['customer_account_id'];
    $grand_total = (float)$order['grand_total'];

    // Idempotency: cek apakah sudah pernah dapat poin dari order ini
    $stmt = $pdo->prepare("
        SELECT id FROM customer_points
        WHERE order_id = ? AND type = 'EARN'
        LIMIT 1
    ");
    $stmt->execute([$order_id]);
    if ($stmt->fetch()) {
        $result['reason'] = 'Poin sudah pernah diberikan';
        return $result;
    }

    // Ambil customer
    $stmt = $pdo->prepare("SELECT id, tenant_id, total_spent, total_orders FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        $result['reason'] = 'Customer tidak ditemukan';
        return $result;
    }

    // Hitung poin: floor(grand_total / 10000)
    $points = (int) floor($grand_total / 10000);

    if ($points <= 0) {
        $result['reason'] = 'Total terlalu kecil (min Rp 10.000)';
        return $result;
    }

    $tenant_id = (int)$customer['tenant_id'];

    // Update customer: points, total_spent, total_orders
    $new_total_spent = (float)$customer['total_spent'] + $grand_total;
    $new_total_orders = (int)$customer['total_orders'] + 1;
    $new_tier = hitung_tier($new_total_spent);

    $pdo->prepare("
        UPDATE customers
        SET points = points + ?,
            total_spent = ?,
            total_orders = ?,
            tier = ?
        WHERE id = ?
    ")->execute([$points, $new_total_spent, $new_total_orders, $new_tier, $customer_id]);

    // Catat di ledger
    $pdo->prepare("
        INSERT INTO customer_points (customer_id, tenant_id, order_id, type, points, note)
        VALUES (?, ?, ?, 'EARN', ?, ?)
    ")->execute([
        $customer_id,
        $tenant_id,
        $order_id,
        $points,
        'Poin dari order Rp ' . number_format($grand_total, 0, ',', '.'),
    ]);

    $result['awarded'] = true;
    $result['points'] = $points;
    $result['reason'] = 'Berhasil';

    return $result;
}

/**
 * Hitung tier berdasarkan total spending.
 */
function hitung_tier(float $total_spent): string
{
    if ($total_spent >= 2000000) return 'GOLD';
    if ($total_spent >= 500000)  return 'SILVER';
    return 'BRONZE';
}