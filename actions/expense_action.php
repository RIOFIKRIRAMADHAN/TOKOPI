<?php
// actions/expense_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','finance']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();
$user_id   = current_user_id();

// ============================================
// TAMBAH
// ============================================
if ($action === 'add') {
    verify_csrf();

    $tanggal   = $_POST['tanggal'] ?? '';
    $category  = trim($_POST['category'] ?? '');
    $outlet_id = (int)($_POST['outlet_id'] ?? 0);
    $amount    = (float)($_POST['amount'] ?? 0);
    $note      = trim($_POST['note'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        flash_set('error', 'Tanggal tidak valid.');
        redirect('/tokopi/views/expenses.php');
    }
    if ($category === '' || $amount <= 0) {
        flash_set('error', 'Kategori dan jumlah wajib diisi.');
        redirect('/tokopi/views/expenses.php');
    }
    if ($note === '') $note = null;

    // outlet_id = 0 → NULL (biaya pusat)
    $outlet_val = $outlet_id > 0 ? $outlet_id : null;

    // Kalau outlet_id diisi, pastikan milik tenant
    if ($outlet_val !== null) {
        $stmt = $pdo->prepare("SELECT id FROM outlets WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$outlet_val, $tenant_id]);
        if (!$stmt->fetch()) {
            flash_set('error', 'Outlet tidak valid.');
            redirect('/tokopi/views/expenses.php');
        }
    }

    // Manager hanya boleh input untuk outlet-nya sendiri
    if (is_role('manager')) {
        $outlet_val = current_outlet_id();
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO expenses (tenant_id, outlet_id, tanggal, category, amount, note, created_by_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tenant_id, $outlet_val, $tanggal, $category, $amount, $note, $user_id]);
        flash_set('success', 'Pengeluaran ' . $category . ' sebesar ' . rupiah($amount) . ' berhasil dicatat.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal menambah pengeluaran: ' . $e->getMessage());
    }

    redirect('/tokopi/views/expenses.php');
}

// ============================================
// EDIT
// ============================================
elseif ($action === 'edit') {
    verify_csrf();

    $id        = (int)($_POST['id'] ?? 0);
    $tanggal   = $_POST['tanggal'] ?? '';
    $category  = trim($_POST['category'] ?? '');
    $outlet_id = (int)($_POST['outlet_id'] ?? 0);
    $amount    = (float)($_POST['amount'] ?? 0);
    $note      = trim($_POST['note'] ?? '');

    if ($id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || $category === '' || $amount <= 0) {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/expenses.php');
    }
    if ($note === '') $note = null;

    // Cek milik tenant
    $stmt = $pdo->prepare("SELECT id FROM expenses WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Pengeluaran tidak ditemukan.');
        redirect('/tokopi/views/expenses.php');
    }

    // Manager hanya boleh edit untuk outlet-nya
    $outlet_val = $outlet_id > 0 ? $outlet_id : null;
    if (is_role('manager')) {
        $outlet_val = current_outlet_id();
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE expenses
            SET outlet_id = ?, tanggal = ?, category = ?, amount = ?, note = ?
            WHERE id = ? AND tenant_id = ?
        ");
        $stmt->execute([$outlet_val, $tanggal, $category, $amount, $note, $id, $tenant_id]);
        flash_set('success', 'Pengeluaran berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update: ' . $e->getMessage());
    }

    redirect('/tokopi/views/expenses.php');
}

// ============================================
// HAPUS
// ============================================
elseif ($action === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT category FROM expenses WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        flash_set('error', 'Pengeluaran tidak ditemukan.');
        redirect('/tokopi/views/expenses.php');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenant_id]);
        flash_set('success', 'Pengeluaran "' . $existing['category'] . '" berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus: ' . $e->getMessage());
    }

    redirect('/tokopi/views/expenses.php');
}

else {
    redirect('/tokopi/views/expenses.php');
}
