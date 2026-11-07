<?php
// customer/api_checkout.php
// Endpoint checkout dari customer QR ordering
header('Content-Type: application/json');

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/bootstrap.php';

// Cek session meja
if (empty($_SESSION['customer_table_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session meja tidak ditemukan.']);
    exit;
}

$table_id  = (int)$_SESSION['customer_table_id'];
$outlet_id = (int)$_SESSION['customer_outlet_id'];

$customer_session_id = $_SESSION['customer_session_id'] ?? null;

if (!$customer_session_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session tidak valid. Scan ulang QR.']);
    exit;
}

// Ambil tenant_id dari outlet
$stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$tenant_id = (int)$stmt->fetchColumn();

if ($tenant_id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Outlet tidak valid.']);
    exit;
}

// Ambil payload
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload tidak valid.']);
    exit;
}

$customer_name     = trim($input['customer_name'] ?? '');
$customer_phone    = trim($input['customer_phone'] ?? '');
$payment_method    = $input['payment_method'] ?? 'CASH';
$items             = $input['items'] ?? [];
$client_request_id = trim($input['client_request_id'] ?? '');
$voucher_code = trim($input['voucher_code'] ?? '');

if ($customer_name === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nama wajib diisi.']);
    exit;
}
if (empty($items)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong.']);
    exit;
}
if ($client_request_id === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'client_request_id wajib.']);
    exit;
}
if (!in_array($payment_method, ['CASH', 'QRIS'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Metode bayar tidak valid.']);
    exit;
}
if (strlen($customer_name) > 100 || strlen($customer_phone) > 30) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Data pelanggan terlalu panjang.']);
    exit;
}

// Idempotency
$stmt = $pdo->prepare("SELECT id, order_no FROM orders WHERE client_request_id = ?");
$stmt->execute([$client_request_id]);
$existing = $stmt->fetch();
if ($existing) {
    echo json_encode([
        'success'  => true,
        'order_id' => (int)$existing['id'],
        'order_no' => $existing['order_no'],
        'duplicate' => true,
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Ambil & validasi varian
    $variant_ids = array_column($items, 'variant_id');
    $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));

    $stmt = $pdo->prepare("
        SELECT v.id, v.nama AS variant_nama, v.harga_jual, v.sku,
               p.nama AS product_nama, p.tenant_id
        FROM product_variants v
        JOIN products p ON p.id = v.product_id
        WHERE v.id IN ($placeholders)
          AND p.tenant_id = ?
          AND v.is_active = 1
          AND p.is_active = 1
    ");
    $params = $variant_ids;
    $params[] = $tenant_id;
    $stmt->execute($params);
    $variants_map = [];
    foreach ($stmt->fetchAll() as $v) {
        $variants_map[(int)$v['id']] = $v;
    }

    if (count($variants_map) !== count(array_unique($variant_ids))) {
        throw new Exception('Ada varian yang tidak valid.');
    }

    // ============================================
    // CEK VOUCHER (kalau ada)
    // ============================================
    $voucher = null;
    if ($voucher_code !== '') {
        // Cek customer login
        $customer_account_id = $_SESSION['customer_account_id'] ?? null;
        if (!$customer_account_id) {
            throw new Exception('Voucher hanya bisa dipakai kalau login.');
        }

        // Cari voucher dengan code ini milik customer ini
        // Kunci row untuk cegah double-submit
        $stmt = $pdo->prepare("
            SELECT r.*, rw.nama AS reward_nama, rw.type AS reward_type, rw.value AS reward_value
            FROM customer_redemptions r
            JOIN rewards rw ON rw.id = r.reward_id
            WHERE r.customer_id = ?
              AND r.status = 'UNUSED'
              AND r.expires_at > NOW()
              AND (r.order_id IS NULL OR r.order_id = 0)
              AND JSON_EXTRACT(r.reward_snapshot, '$.voucher_code') = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$customer_account_id, $voucher_code]);
        $voucher = $stmt->fetch();

        if (!$voucher) {
            // Cek apakah voucher ada tapi sedang dipakai order lain
            $stmt = $pdo->prepare("
                SELECT r.id, r.status, r.order_id
                FROM customer_redemptions r
                WHERE r.customer_id = ?
                  AND r.status IN ('UNUSED', 'RESERVED')
                  AND JSON_EXTRACT(r.reward_snapshot, '$.voucher_code') = ?
                LIMIT 1
            ");
            $stmt->execute([$customer_account_id, $voucher_code]);
            $existing = $stmt->fetch();

            if ($existing && $existing['order_id']) {
                throw new Exception('Voucher sedang dipakai di order lain. Selesaikan atau batalkan order itu dulu.');
            }

            throw new Exception('Voucher tidak valid atau sudah dipakai.');
        }

        // Skip FREE_ITEM dulu
        if ($voucher['reward_type'] === 'FREE_ITEM') {
            throw new Exception('Voucher FREE_ITEM belum didukung. Hubungi kasir.');
        }
    }

    // Hitung HPP + breakdown + cek stok
    $subtotal = 0;
    $cost_total = 0;
    $lines = [];

    foreach ($items as $it) {
        $vid = (int)$it['variant_id'];
        $qty = (int)$it['qty'];
        $note = trim($it['note'] ?? '');

        if ($qty <= 0 || $qty > 99) throw new Exception('Qty tidak valid.');
        if (strlen($note) > 200) throw new Exception('Catatan terlalu panjang.');

        $v = $variants_map[$vid];
        $harga = (float)$v['harga_jual'];
        $line_total = $harga * $qty;

        // Ambil resep
        $stmt = $pdo->prepare("SELECT id AS recipe_id, yield_qty FROM recipes WHERE variant_id = ?");
        $stmt->execute([$vid]);
        $recipe = $stmt->fetch();

        $unit_cost = 0;
        $breakdown = [];

        if ($recipe) {
            $stmt = $pdo->prepare("
                SELECT ri.ingredient_id, ri.qty, ri.unit, i.nama, i.avg_cost
                FROM recipe_items ri
                JOIN ingredients i ON i.id = ri.ingredient_id
                WHERE ri.recipe_id = ?
            ");
            $stmt->execute([$recipe['recipe_id']]);
            $yield_qty = max((float)$recipe['yield_qty'], 1);

            foreach ($stmt->fetchAll() as $ri) {
                $bahan_qty = (float)$ri['qty'] / $yield_qty;
                $bahan_cost = $bahan_qty * (float)$ri['avg_cost'];
                $unit_cost += $bahan_cost;
                $breakdown[] = [
                    'ingredient_id' => (int)$ri['ingredient_id'],
                    'nama'          => $ri['nama'],
                    'qty'           => $bahan_qty,
                    'unit'          => $ri['unit'],
                    'cost'          => $bahan_cost,
                ];
            }
        }

        // Cek stok
        foreach ($breakdown as $b) {
            $butuh = $b['qty'] * $qty;
            $stmt = $pdo->prepare("SELECT COALESCE(qty, 0) FROM stocks WHERE outlet_id = ? AND ingredient_id = ?");
            $stmt->execute([$outlet_id, $b['ingredient_id']]);
            $tersedia = (float)$stmt->fetchColumn();

            if ($tersedia < $butuh) {
                throw new Exception("Stok {$b['nama']} tidak cukup. Tersedia " . number_format($tersedia, 2) . " {$b['unit']}.");
            }
        }

        $subtotal += $line_total;
        $cost_total += $unit_cost * $qty;

        $lines[] = [
            'variant_id'         => $vid,
            'product_nama'       => $v['product_nama'],
            'variant_nama'       => $v['variant_nama'],
            'qty'                => $qty,
            'harga_satuan'       => $harga,
            'line_total'         => $line_total,
            'unit_cost_snapshot' => $unit_cost,
            'cost_breakdown'     => $breakdown,
            'note'               => $note ?: null,
        ];
    }

    // Hitung diskon dari voucher
    $discount_total = 0;
    if ($voucher) {
        if ($voucher['reward_type'] === 'DISCOUNT_NOMINAL') {
            $discount_total = min((float)$voucher['reward_value'], $subtotal);
        } elseif ($voucher['reward_type'] === 'DISCOUNT_PERCENT') {
            $discount_total = floor($subtotal * (float)$voucher['reward_value'] / 100);
        }
    }

    $grand_total = $subtotal - $discount_total;

    // Generate order_no
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT code FROM outlets WHERE id = ?");
    $stmt->execute([$outlet_id]);
    $outlet_code = $stmt->fetchColumn();

    $pdo->prepare("
        INSERT INTO order_sequences (outlet_id, date, last_number)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE last_number = last_number + 1
    ")->execute([$outlet_id, $today]);

    $stmt = $pdo->prepare("SELECT last_number FROM order_sequences WHERE outlet_id = ? AND date = ?");
    $stmt->execute([$outlet_id, $today]);
    $seq = (int)$stmt->fetchColumn();

    $order_no = sprintf('%s-%s-Q%04d', $outlet_code, date('Ymd'), $seq);

    // Status: QRIS → PENDING_PAYMENT, CASH → PENDING_PAYMENT juga (kasir konfirmasi)
    $status = 'PENDING_PAYMENT';

    // Insert order
    $customer_account_id_val = $_SESSION['customer_account_id'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO orders
        (order_no, outlet_id, cashier_id, shift_id, channel, source, table_id,
         customer_name, customer_phone, customer_session_id, customer_account_id,
         status, subtotal, discount_total, grand_total, cost_total,
         client_request_id)
        VALUES (?, ?, NULL, NULL, 'QR_ORDER', 'QR', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order_no,
        $outlet_id,
        $table_id,
        $customer_name,
        $customer_phone ?: null,
        $customer_session_id,
        $customer_account_id_val,
        $status,
        $subtotal,
        $discount_total,
        $grand_total,
        $cost_total,
        $client_request_id,
    ]);
    $order_id = (int)$pdo->lastInsertId();

    // Kalau pakai voucher, tandai voucher sebagai RESERVED
    // Status: RESERVED = sedang dipakai di order ini
    if ($voucher) {
        $pdo->prepare("
            UPDATE customer_redemptions
            SET order_id = ?, status = 'RESERVED'
            WHERE id = ?
        ")->execute([$order_id, $voucher['id']]);
    }

    // Insert order items
    $stmt_item = $pdo->prepare("
        INSERT INTO order_items
        (order_id, variant_id, product_nama, variant_nama, qty, harga_satuan, line_total,
         unit_cost_snapshot, cost_breakdown, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($lines as $l) {
        $stmt_item->execute([
            $order_id,
            $l['variant_id'],
            $l['product_nama'],
            $l['variant_nama'],
            $l['qty'],
            $l['harga_satuan'],
            $l['line_total'],
            $l['unit_cost_snapshot'],
            json_encode($l['cost_breakdown'], JSON_UNESCAPED_UNICODE),
            $l['note'],
        ]);
    }

    // Kalau QRIS, generate QR via Midtrans
    $qr_url = null;
    $qr_string = null;

    if ($payment_method === 'QRIS') {
        $server_key = midtrans_server_key();
        if ($server_key === '') {
            throw new Exception('Midtrans Server Key belum di-set.');
        }

        midtrans_init();

        $midtrans_order_id = $order_no . '-' . time();

        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id'     => $midtrans_order_id,
                'gross_amount' => (int)round($grand_total),
            ],
            'qris' => [
                'acquirer' => midtrans_qris_acquirer(),
            ],
            'custom_field1' => (string)$order_id,
            'custom_field2' => (string)$outlet_id,
        ];

        $charge = \Midtrans\CoreApi::charge($params);

        foreach (($charge->actions ?? []) as $action) {
            if (isset($action->name) && $action->name === 'generate-qr-code') {
                $qr_url = $action->url;
                break;
            }
        }
        $qr_string = $charge->qr_string ?? null;

        if (!$qr_url) {
            throw new Exception('Midtrans tidak mengembalikan QR URL.');
        }

        // Insert payment
        $stmt = $pdo->prepare("
            INSERT INTO payments
            (order_id, method, amount, status, provider, provider_ref, qr_string, qr_url, raw_payload)
            VALUES (?, 'QRIS', ?, 'PENDING', 'midtrans', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $grand_total,
            $charge->transaction_id,
            $qr_string,
            $qr_url,
            json_encode($charge, JSON_UNESCAPED_UNICODE),
        ]);
    } else {
        // CASH → payment PENDING, tunggu kasir konfirmasi
        $stmt = $pdo->prepare("
            INSERT INTO payments (order_id, method, amount, status)
            VALUES (?, 'CASH', ?, 'PENDING')
        ");
        $stmt->execute([$order_id, $grand_total]);
    }

    $pdo->commit();

    echo json_encode([
        'success'     => true,
        'order_id'    => $order_id,
        'order_no'    => $order_no,
        'grand_total' => $grand_total,
        'qr_url'      => $qr_url,
        'qr_string'   => $qr_string,
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
