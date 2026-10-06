<?php
// api/pos_checkout.php
// Endpoint AJAX untuk proses checkout POS
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

// Ambil shift aktif kasir (kalau ada)
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

// Kalau owner akses POS tanpa outlet, fallback ke outlet pertama
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

// Baca input JSON
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload tidak valid.']);
    exit;
}

$items             = $input['items'] ?? [];
$payment_method    = $input['payment_method'] ?? 'CASH';
$cash_received     = (float)($input['cash_received'] ?? 0);
$client_request_id = trim($input['client_request_id'] ?? '');
$channel           = $input['channel'] ?? 'DINE_IN';

// Validasi
if (empty($items) || !is_array($items)) {
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

// Idempotency: cek client_request_id sudah diproses?
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

    // 2. Hitung subtotal, HPP, dan bangun breakdown bahan
    $subtotal   = 0;
    $cost_total = 0;
    $lines      = [];

    foreach ($items as $it) {
        $vid = (int)$it['variant_id'];
        $qty = (int)$it['qty'];

        if ($qty <= 0) throw new Exception('Qty harus lebih dari 0.');
        if ($qty > 999) throw new Exception('Qty terlalu besar.');

        $v = $variants_map[$vid];
        $harga = (float)$v['harga_jual'];
        $line_total = $harga * $qty;

        // Ambil resep & bahan untuk HPP
        $stmt = $pdo->prepare("
            SELECT r.id AS recipe_id, r.yield_qty
            FROM recipes r
            WHERE r.variant_id = ?
        ");
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
                $bahan_qty = ((float)$ri['qty'] / $yield_qty);
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
        } elseif ((float)$v['hpp_manual'] > 0) {
            // Tidak ada resep → pakai HPP manual
            $unit_cost = (float)$v['hpp_manual'];
        }

        $cost_line = $unit_cost * $qty;
        $subtotal   += $line_total;
        $cost_total += $cost_line;

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

    $grand_total = $subtotal; // Belum ada tax/discount

    // ============================================
    // CEK STOK — hard block
    // ============================================
    $stok_error = [];
    foreach ($lines as $l) {
        foreach ($l['cost_breakdown'] as $bahan) {
            $qty_needed = $bahan['qty'] * $l['qty'];

            $stmt = $pdo->prepare("
                SELECT COALESCE(qty, 0) FROM stocks
                WHERE outlet_id = ? AND ingredient_id = ?
            ");
            $stmt->execute([$outlet_id, $bahan['ingredient_id']]);
            $current = (float)$stmt->fetchColumn();

            if ($current < $qty_needed) {
                $stok_error[] = sprintf(
                    '%s: butuh %s %s, tersedia %s %s',
                    $bahan['nama'],
                    number_format($qty_needed, 2, ',', '.'),
                    $bahan['unit'],
                    number_format($current, 2, ',', '.'),
                    $bahan['unit']
                );
            }
        }
    }

    if (!empty($stok_error)) {
        throw new Exception('Stok tidak cukup: ' . implode('; ', array_unique($stok_error)));
    }

    // 3. Validasi cash
    if ($payment_method === 'CASH' && $cash_received < $grand_total) {
        throw new Exception('Uang diterima kurang dari total.');
    }

    // 4. Generate order_no pakai sequence
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT code FROM outlets WHERE id = ?");
    $stmt->execute([$outlet_id]);
    $outlet_code = $stmt->fetchColumn();

    // Upsert sequence
    $pdo->prepare("
        INSERT INTO order_sequences (outlet_id, date, last_number)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE last_number = last_number + 1
    ")->execute([$outlet_id, $today]);

    $stmt = $pdo->prepare("SELECT last_number FROM order_sequences WHERE outlet_id = ? AND date = ?");
    $stmt->execute([$outlet_id, $today]);
    $seq = (int)$stmt->fetchColumn();

    $order_no = sprintf('%s-%s-%04d', $outlet_code, date('Ymd'), $seq);

    // 5. Insert order
    $stmt = $pdo->prepare("
        INSERT INTO orders
        (order_no, outlet_id, shift_id, cashier_id, channel, status, subtotal, grand_total,
         cost_total, client_request_id, paid_at)
        VALUES (?, ?, ?, ?, ?, 'PAID', ?, ?, ?, ?, NOW())
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

    // 6. Insert order items
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

    // 7. Insert payment
    if ($payment_method === 'CASH') {
        $change = $cash_received - $grand_total;
        $stmt = $pdo->prepare("
            INSERT INTO payments
            (order_id, method, amount, status, cash_received, change_given, paid_at)
            VALUES (?, 'CASH', ?, 'SETTLED', ?, ?, NOW())
        ");
        $stmt->execute([$order_id, $grand_total, $cash_received, $change]);
    }

    // 8. Potong stok (hanya bahan, karena stok hanya untuk bahan)
    foreach ($lines as $l) {
        foreach ($l['cost_breakdown'] as $bahan) {
            $qty_deduct = $bahan['qty'] * $l['qty'];

            // Update stocks (upsert)
            $pdo->prepare("
                INSERT INTO stocks (outlet_id, ingredient_id, qty, reorder_point)
                VALUES (?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE qty = qty - ?
            ")->execute([$outlet_id, $bahan['ingredient_id'], -$qty_deduct, $qty_deduct]);

            // Insert stock movement ledger
            $pdo->prepare("
                INSERT INTO stock_movements
                (outlet_id, ingredient_id, qty, type, ref_type, ref_id, created_by_id)
                VALUES (?, ?, ?, 'SALE_OUT', 'ORDER', ?, ?)
            ")->execute([
                $outlet_id,
                $bahan['ingredient_id'],
                -$qty_deduct,
                (string)$order_id,
                $user_id
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'  => true,
        'order_id' => $order_id,
        'order_no' => $order_no,
        'total'    => $grand_total,
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
