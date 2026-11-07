<?php
// actions/customer_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','finance']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

if ($action === 'adjust_points') {
    verify_csrf();

    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $mode        = $_POST['mode'] ?? 'add';
    $amount      = (int)($_POST['amount'] ?? 0);
    $note        = trim($_POST['note'] ?? '');

    if ($customer_id <= 0 || $amount <= 0 || !in_array($mode, ['add', 'subtract', 'set'], true) || $note === '') {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/customer_detail.php?id=' . $customer_id);
    }

    $stmt = $pdo->prepare("SELECT points FROM customers WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$customer_id, $tenant_id]);
    $current = $stmt->fetchColumn();

    if ($current === false) {
        flash_set('error', 'Customer tidak ditemukan.');
        redirect('/tokopi/views/customers.php');
    }

    $current = (int)$current;

    try {
        $pdo->beginTransaction();

        if ($mode === 'add') {
            $delta = $amount;
        } elseif ($mode === 'subtract') {
            if ($amount > $current) throw new Exception('Jumlah pengurangan melebihi poin saat ini.');
            $delta = -$amount;
        } else {
            $delta = $amount - $current;
        }

        $pdo->prepare("UPDATE customers SET points = points + ? WHERE id = ?")
            ->execute([$delta, $customer_id]);

        $pdo->prepare("
            INSERT INTO customer_points (customer_id, tenant_id, type, points, note, created_at)
            VALUES (?, ?, 'ADJUST', ?, ?, NOW())
        ")->execute([$customer_id, $tenant_id, $delta, $note]);

        $pdo->commit();
        flash_set('success', 'Poin berhasil di-adjust (' . ($delta > 0 ? '+' : '') . $delta . ').');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('error', 'Gagal adjust poin: ' . $e->getMessage());
    }

    redirect('/tokopi/views/customer_detail.php?id=' . $customer_id);
}

else {
    redirect('/tokopi/views/customers.php');
}
