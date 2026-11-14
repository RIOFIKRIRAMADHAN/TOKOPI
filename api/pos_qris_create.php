<?php
// api/pos_qris_create.php
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
$user_id   = current_user_id();

// Ambil shift aktif kasir (wajib untuk kasir)
$shift_id = null;
if (is_role('kasir')) {
    $stmt = $pdo->prepare("SELECT id FROM shifts WHERE cashier_id = ? AND status = 'OPEN' LIMIT 1");
    $stmt->execute([$user_id]);
    $shift_id = $stmt->fetchColumn() ?: null;
    if (!$shift_id) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Buka shift dulu sebelum transaksi.']);
        exit;
    }
}

if (!$outlet_id) {
    $stmt = $pdo->prepare("SELECT id FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id LIMIT 1");
    $stmt->execute([$tenant_id]);
    $outlet_id = (int)$stmt->fetchColumn();
}
if ($outlet_id <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Outlet tidak valid.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload tidak valid.']);
    exit;
}

$items             = $input['items'] ?? [];
$client_request_id = trim($input['client_request_id'] ?? '');
$channel           = $input['channel'] ?? 'DINE_IN';

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

// Idempotency
$stmt = $pdo->prepare("
    SELECT o.id, o.order_no, p.qr_url, p.qr_string
    FROM orders o
    LEFT JOIN payments p ON p.order_id = o.id AND p.method = 'QRIS'
    WHERE o.client_request_id = ?
");
$stmt->execute([$client_request_id]);
$existing = $stmt->fetch();
if ($existing) {
    echo json_encode([
        'success'      => true,
        'order_id'     => (int)$existing['id'],
        'order_no'     => $existing['order_no'],
        'qr_url'       => $existing['qr_url'],
        'qr_string'    => $existing['qr_string'],
        'grand_total'  => 0,
        'duplicate'    => true,
    ]);
    exit;
}

// Cek konfigurasi Midtrans
$server_key = midtrans_server_key();
if ($server_key === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Midtrans Server Key belum di-set di .env']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ambil & validasi varian
    $variant_ids = array_column($items, 'variant_id');
    $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));

    $stmt = $pdo->prepare("
        SELECT v.id, v.nama AS variant_nama, v.harga_jual, v.hpp_manual, v.sku,
               p.nama AS product_nama, p.tenant_id
        FROM product_variants v
        JOIN products p ON p.id = v.product_id
        WHERE v.id IN ($placeholders) AND p.tenant_id = ? AND v.is_active = 1 AND p.is_active = 1
    ");
    $params = $variant_ids;
    $params[] = $tenant_id;
    $stmt->execute($params);
    $variants_map = [];
    foreach ($stmt->fetchAll() as $v) {
        $variants_map[(int)$v['id']] = $v;
    }

    if (count($variants_map) !== count(array_unique($variant_ids))) {
        throw new Exception('Ada varian yang tidak valid atau sudah nonaktif.');
    }

    // 2. Hitung subtotal + HPP + kumpulkan kebutuhan stok
    $subtotal = 0;
    $cost_total = 0;
    $lines = [];
    $stock_needs = []; // ingredient_id => total_qty_needed

    foreach ($items as $it) {
        $vid = (int)$it['variant_id'];
        $qty = (int)$it['qty'];
        if ($qty <= 0 || $qty > 999) throw new Exception('Qty tidak valid.');

        $v = $variants_map[$vid];
        $harga = (float)$v['harga_jual'];
        $line_total = $harga * $qty;

        // Resep
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
                $bahan_qty = ((float)$ri['qty'] / $yield_qty) * $qty; // total untuk qty ini
                $bahan_cost = $bahan_qty * (float)$ri['avg_cost'];
                $unit_cost += $bahan_cost / $qty; // per unit

                // Akumulasikan kebutuhan stok per bahan
                $iid = (int)$ri['ingredient_id'];
                if (!isset($stock_needs[$iid])) {
                    $stock_needs[$iid] = [
                        'nama' => $ri['nama'],
                        'unit' => $ri['unit'],
                        'qty' => 0,
                    ];
                }
                $stock_needs[$iid]['qty'] += $bahan_qty;

                $breakdown[] = [
                    'ingredient_id' => $iid,
                    'nama'          => $ri['nama'],
                    'qty'           => $bahan_qty / $qty, // per unit
                    'unit'          => $ri['unit'],
                    'cost'          => $bahan_cost / $qty, // per unit
                ];
            }
        } elseif ((float)$v['hpp_manual'] > 0) {
            $unit_cost = (float)$v['hpp_manual'];
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
        ];
    }

    $grand_total = $subtotal;

    // ============================================
    // CEK STOK ATOMIC — SELECT FOR UPDATE di dalam transaksi
    // ============================================
    if (!empty($stock_needs)) {
        $ingredient_ids = array_keys($stock_needs);
        $placeholders = implode(',', array_fill(0, count($ingredient_ids), '?'));

        // Lock rows untuk mencegah race condition
        $stmt = $pdo->prepare("
            SELECT ingredient_id, COALESCE(qty, 0) AS current_qty
            FROM stocks
            WHERE outlet_id = ? AND ingredient_id IN ($placeholders)
            FOR UPDATE
        ");
        $params = array_merge([$outlet_id], $ingredient_ids);
        $stmt->execute($params);
        $current_stocks = [];
        foreach ($stmt->fetchAll() as $row) {
            $current_stocks[(int)$row['ingredient_id']] = (float)$row['current_qty'];
        }

        // Cek kecukupan
        $stok_error = [];
        foreach ($stock_needs as $iid => $need) {
            $available = $current_stocks[$iid] ?? 0;
            if ($available < $need['qty']) {
                $stok_error[] = sprintf(
                    '%s: butuh %s %s, tersedia %s %s',
                    $need['nama'],
                    number_format($need['qty'], 2, ',', '.'),
                    $need['unit'],
                    number_format($available, 2, ',', '.'),
                    $need['unit']
                );
            }
        }

        if (!empty($stok_error)) {
            throw new Exception('Stok tidak cukup: ' . implode('; ', array_unique($stok_error)));
        }

        // Kurangi stok (atomic decrement)
        foreach ($stock_needs as $iid => $need) {
            $stmt = $pdo->prepare("
                UPDATE stocks SET qty = qty - ? WHERE outlet_id = ? AND ingredient_id = ?
            ");
            $stmt->execute([$need['qty'], $outlet_id, $iid]);

            // Catat stock movement
            $stmt = $pdo->prepare("
                INSERT INTO stock_movements (outlet_id, ingredient_id, type, qty, reference_type, reference_id, note)
                VALUES (?, ?, 'OUT', ?, 'ORDER', 0, ?)
            ");
            $stmt->execute([$outlet_id, $iid, $need['qty'], "Pesanan QRIS (pending)"]);
        }
    }

    // 3. Generate order_no
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

    $order_no = sprintf('%s-%s-%04d', $outlet_code, date('Ymd'), $seq);

    // 4. Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders
        (order_no, outlet_id, shift_id, cashier_id, channel, status, subtotal, grand_total,
         cost_total, client_request_id)
        VALUES (?, ?, ?, ?, ?, 'PENDING_PAYMENT', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order_no,
        $outlet_id,
        $shift_id,
        $user_id,
        $channel,
        $subtotal,
        $grand_total,
        $cost_total,
        $client_request_id
    ]);
    $order_id = (int)$pdo->lastInsertId();

    // Update stock movements dengan order_id yang benar
    if (!empty($stock_needs)) {
        foreach ($stock_needs as $iid => $need) {
            $stmt = $pdo->prepare("
                UPDATE stock_movements SET reference_id = ?
                WHERE outlet_id = ? AND ingredient_id = ? AND reference_type = 'ORDER' AND reference_id = 0
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$order_id, $outlet_id, $iid]);
        }
    }

    // 5. Insert order items
    $stmt_item = $pdo->prepare("
        INSERT INTO order_items
        (order_id, variant_id, product_nama, variant_nama, qty, harga_satuan, line_total,
         unit_cost_snapshot, cost_breakdown)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            json_encode($l['cost_breakdown'], JSON_UNESCAPED_UNICODE)
        ]);
    }

    // 6. Panggil Midtrans Core API untuk generate QRIS
    midtrans_init();

    // Midtrans order_id harus unik — kita append timestamp supaya bisa retry
    $midtrans_order_id = $order_no . '-' . time();

    $params = [
        'payment_type' => 'qris',
        'transaction_details' => [
            'order_id'     => $midtrans_order_id,
            'gross_amount' => (int) round($grand_total),
        ],
        'qris' => [
            'acquirer' => midtrans_qris_acquirer(),
        ],
        'custom_field1' => (string) $order_id,
        'custom_field2' => (string) $outlet_id,
    ];

    $charge = \Midtrans\CoreApi::charge($params);

    // Ambil QR URL
    $qr_url = null;
    $qr_string = $charge->qr_string ?? null;
    if (!empty($charge->actions)) {
        foreach ($charge->actions as $action) {
            if (isset($action->name) && $action->name === 'generate-qr-code') {
                $qr_url = $action->url;
                break;
            }
        }
    }

    if (!$qr_url) {
        throw new Exception('Midtrans tidak mengembalikan QR URL. Response: ' . json_encode($charge));
    }

    // 7. Insert payment
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
        json_encode($charge, JSON_UNESCAPED_UNICODE)
    ]);

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