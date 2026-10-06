
<?php
// actions/stock_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();
$user_id   = current_user_id();

if ($action === 'adjust') {
    verify_csrf();

    $outlet_id     = (int)($_POST['outlet_id'] ?? 0);
    $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
    $mode          = $_POST['mode'] ?? 'add';
    $qty           = (float)($_POST['qty'] ?? 0);
    $note          = trim($_POST['note'] ?? '');

    $redirect = '/tokopi/views/stock.php?outlet=' . $outlet_id;

    // Validasi
    if ($outlet_id <= 0 || $ingredient_id <= 0 || $qty <= 0) {
        flash_set('error', 'Data tidak valid.');
        redirect($redirect);
    }
    if (!in_array($mode, ['add','subtract','set'], true)) {
        flash_set('error', 'Mode penyesuaian tidak valid.');
        redirect($redirect);
    }
    if ($note === '') $note = null;

    // Cek outlet milik tenant
    $stmt = $pdo->prepare("SELECT id FROM outlets WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$outlet_id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Outlet tidak valid.');
        redirect($redirect);
    }

    // Cek ingredient milik tenant
    $stmt = $pdo->prepare("SELECT id, nama, base_unit FROM ingredients WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$ingredient_id, $tenant_id]);
    $ing = $stmt->fetch();
    if (!$ing) {
        flash_set('error', 'Bahan tidak ditemukan.');
        redirect($redirect);
    }

    try {
        $pdo->beginTransaction();

        // Ambil stok saat ini (lock)
        $stmt = $pdo->prepare("SELECT qty FROM stocks WHERE outlet_id = ? AND ingredient_id = ? FOR UPDATE");
        $stmt->execute([$outlet_id, $ingredient_id]);
        $current = $stmt->fetchColumn();
        $current = $current === false ? 0 : (float)$current;

        // Hitung delta
        if ($mode === 'add') {
            $delta = $qty;
            $new_qty = $current + $qty;
            $movement_type = 'ADJUSTMENT';
        } elseif ($mode === 'subtract') {
            if ($qty > $current) {
                throw new Exception('Jumlah pengurangan (' . $qty . ') melebihi stok saat ini (' . $current . ').');
            }
            $delta = -$qty;
            $new_qty = $current - $qty;
            $movement_type = 'WASTE';
        } else { // set
            $delta = $qty - $current;
            $new_qty = $qty;
            $movement_type = 'ADJUSTMENT';
        }

        // Upsert stocks
        $stmt = $pdo->prepare("
            INSERT INTO stocks (outlet_id, ingredient_id, qty, reorder_point)
            VALUES (?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE qty = ?
        ");
        $stmt->execute([$outlet_id, $ingredient_id, $new_qty, $new_qty]);

        // Insert ledger
        $stmt = $pdo->prepare("
            INSERT INTO stock_movements
            (outlet_id, ingredient_id, qty, type, ref_type, ref_id, note, created_by_id)
            VALUES (?, ?, ?, ?, 'MANUAL', NULL, ?, ?)
        ");
        $stmt->execute([
            $outlet_id, $ingredient_id, $delta, $movement_type,
            $note ?: ('Adjust ' . $mode . ': ' . number_format($qty, 2) . ' ' . $ing['base_unit']),
            $user_id
        ]);

        $pdo->commit();

        $label = $mode === 'add' ? 'ditambah' : ($mode === 'subtract' ? 'dikurangi' : 'di-set');
        flash_set('success', 'Stok ' . $ing['nama'] . ' berhasil ' . $label . '. Stok baru: ' . number_format($new_qty, 2) . ' ' . $ing['base_unit']);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('error', 'Gagal adjust stok: ' . $e->getMessage());
    }

    redirect($redirect);
}

else {
    redirect('/tokopi/views/stock.php');
}
