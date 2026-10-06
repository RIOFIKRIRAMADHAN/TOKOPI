<?php
// actions/outlet_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

// ============================================
// TAMBAH
// ============================================
if ($action === 'add') {
    verify_csrf();

    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $nama      = trim($_POST['nama'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($code === '' || $nama === '') {
        flash_set('error', 'Kode dan nama outlet wajib diisi.');
        redirect('/tokopi/views/outlets.php');
    }
    if ($alamat === '') $alamat = null;
    if ($no_hp === '')  $no_hp = null;

    // Cek kode unik per tenant
    $stmt = $pdo->prepare("SELECT id FROM outlets WHERE tenant_id = ? AND code = ?");
    $stmt->execute([$tenant_id, $code]);
    if ($stmt->fetch()) {
        flash_set('error', 'Kode outlet "' . $code . '" sudah dipakai.');
        redirect('/tokopi/views/outlets.php');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO outlets (tenant_id, code, nama, alamat, no_hp, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $code, $nama, $alamat, $no_hp, $is_active]);
        flash_set('success', 'Outlet "' . $nama . '" berhasil ditambahkan.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal menambah outlet: ' . $e->getMessage());
    }

    redirect('/tokopi/views/outlets.php');
}

// ============================================
// EDIT
// ============================================
elseif ($action === 'edit') {
    verify_csrf();

    $id        = (int)($_POST['id'] ?? 0);
    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $nama      = trim($_POST['nama'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0 || $code === '' || $nama === '') {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/outlets.php');
    }
    if ($alamat === '') $alamat = null;
    if ($no_hp === '')  $no_hp = null;

    // Cek milik tenant
    $stmt = $pdo->prepare("SELECT id FROM outlets WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Outlet tidak ditemukan.');
        redirect('/tokopi/views/outlets.php');
    }

    // Cek kode unik kecuali dirinya
    $stmt = $pdo->prepare("SELECT id FROM outlets WHERE tenant_id = ? AND code = ? AND id != ?");
    $stmt->execute([$tenant_id, $code, $id]);
    if ($stmt->fetch()) {
        flash_set('error', 'Kode outlet "' . $code . '" sudah dipakai outlet lain.');
        redirect('/tokopi/views/outlets.php');
    }

    try {
        $stmt = $pdo->prepare("UPDATE outlets SET code = ?, nama = ?, alamat = ?, no_hp = ?, is_active = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$code, $nama, $alamat, $no_hp, $is_active, $id, $tenant_id]);
        flash_set('success', 'Outlet berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update outlet: ' . $e->getMessage());
    }

    redirect('/tokopi/views/outlets.php');
}

// ============================================
// HAPUS
// ============================================
elseif ($action === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama FROM outlets WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Outlet tidak ditemukan.');
        redirect('/tokopi/views/outlets.php');
    }

    // Cek minimal 1 outlet
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM outlets WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    if ((int)$stmt->fetchColumn() <= 1) {
        flash_set('error', 'Tidak bisa hapus: minimal harus ada 1 outlet.');
        redirect('/tokopi/views/outlets.php');
    }

    // Cek ada order
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE outlet_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        flash_set('error', 'Tidak bisa hapus: outlet ini sudah punya riwayat transaksi. Nonaktifkan saja.');
        redirect('/tokopi/views/outlets.php');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM outlets WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenant_id]);
        flash_set('success', 'Outlet "' . $existing['nama'] . '" berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus outlet: ' . $e->getMessage());
    }

    redirect('/tokopi/views/outlets.php');
}

else {
    redirect('/tokopi/views/outlets.php');
}
