<?php
// actions/table_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

// TAMBAH MEJA
if ($action === 'add') {
    verify_csrf();

    $outlet_id = (int)($_POST['outlet_id'] ?? 0);
    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $nama      = trim($_POST['nama'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($outlet_id <= 0 || $code === '' || $nama === '') {
        flash_set('error', 'Kode, nama, dan outlet wajib diisi.');
        redirect('/tokopi/views/tables.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM outlets WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$outlet_id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Outlet tidak valid.');
        redirect('/tokopi/views/tables.php');
    }

    if (is_role('manager') && $outlet_id != current_outlet_id()) {
        flash_set('error', 'Anda hanya bisa kelola meja di outlet Anda.');
        redirect('/tokopi/views/tables.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM `tables` WHERE outlet_id = ? AND code = ?");
    $stmt->execute([$outlet_id, $code]);
    if ($stmt->fetch()) {
        flash_set('error', 'Kode meja "' . $code . '" sudah dipakai di outlet ini.');
        redirect('/tokopi/views/tables.php?outlet=' . $outlet_id);
    }

    try {
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("
            INSERT INTO `tables` (outlet_id, code, nama, qr_token, is_active)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$outlet_id, $code, $nama, $token, $is_active]);
        flash_set('success', 'Meja "' . $nama . '" berhasil ditambahkan.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal tambah meja: ' . $e->getMessage());
    }

    redirect('/tokopi/views/tables.php?outlet=' . $outlet_id);
}

// EDIT MEJA
elseif ($action === 'edit') {
    verify_csrf();

    $id        = (int)($_POST['id'] ?? 0);
    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $nama      = trim($_POST['nama'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0 || $code === '' || $nama === '') {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/tables.php');
    }

    $stmt = $pdo->prepare("
        SELECT t.id, t.outlet_id
        FROM `tables` t
        JOIN outlets o ON o.id = t.outlet_id
        WHERE t.id = ? AND o.tenant_id = ?
    ");
    $stmt->execute([$id, $tenant_id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        flash_set('error', 'Meja tidak ditemukan.');
        redirect('/tokopi/views/tables.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM `tables` WHERE outlet_id = ? AND code = ? AND id != ?");
    $stmt->execute([$existing['outlet_id'], $code, $id]);
    if ($stmt->fetch()) {
        flash_set('error', 'Kode meja "' . $code . '" sudah dipakai.');
        redirect('/tokopi/views/tables.php?outlet=' . $existing['outlet_id']);
    }

    try {
        $stmt = $pdo->prepare("UPDATE `tables` SET code = ?, nama = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$code, $nama, $is_active, $id]);
        flash_set('success', 'Meja berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update meja: ' . $e->getMessage());
    }

    redirect('/tokopi/views/tables.php?outlet=' . $existing['outlet_id']);
}

// ROTATE TOKEN
elseif ($action === 'rotate') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT t.id, t.outlet_id
        FROM `tables` t
        JOIN outlets o ON o.id = t.outlet_id
        WHERE t.id = ? AND o.tenant_id = ?
    ");
    $stmt->execute([$id, $tenant_id]);
    $table = $stmt->fetch();

    if (!$table) {
        flash_set('error', 'Meja tidak ditemukan.');
        redirect('/tokopi/views/tables.php');
    }

    try {
        $new_token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("UPDATE `tables` SET qr_token = ? WHERE id = ?");
        $stmt->execute([$new_token, $id]);
        flash_set('success', 'Token QR berhasil di-rotate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal rotate token: ' . $e->getMessage());
    }

    redirect('/tokopi/views/tables.php?outlet=' . $table['outlet_id']);
}

// HAPUS MEJA
elseif ($action === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT t.id, t.outlet_id, t.nama
        FROM `tables` t
        JOIN outlets o ON o.id = t.outlet_id
        WHERE t.id = ? AND o.tenant_id = ?
    ");
    $stmt->execute([$id, $tenant_id]);
    $table = $stmt->fetch();

    if (!$table) {
        flash_set('error', 'Meja tidak ditemukan.');
        redirect('/tokopi/views/tables.php');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM `tables` WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Meja "' . $table['nama'] . '" berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus meja: ' . $e->getMessage());
    }

    redirect('/tokopi/views/tables.php?outlet=' . $table['outlet_id']);
}

else {
    redirect('/tokopi/views/tables.php');
}
