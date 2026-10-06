<?php
// actions/ingredient_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

// ============================================
// TAMBAH
// ============================================
if ($action === 'add') {
    verify_csrf();

    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $nama      = trim($_POST['nama'] ?? '');
    $base_unit = $_POST['base_unit'] ?? 'G';
    $category  = trim($_POST['category'] ?? '');
    $avg_cost  = (float)($_POST['avg_cost'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($code === '' || $nama === '') {
        flash_set('error', 'Kode dan nama bahan wajib diisi.');
        redirect('/tokopi/views/ingredients.php');
    }
    if (!in_array($base_unit, ['G','ML','PCS'], true)) {
        $base_unit = 'G';
    }
    if ($category === '') $category = null;

    // Cek kode unik per tenant
    $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE tenant_id = ? AND code = ?");
    $stmt->execute([$tenant_id, $code]);
    if ($stmt->fetch()) {
        flash_set('error', 'Kode "' . $code . '" sudah dipakai.');
        redirect('/tokopi/views/ingredients.php');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO ingredients (tenant_id, code, nama, base_unit, category, avg_cost, last_cost, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $code, $nama, $base_unit, $category, $avg_cost, $avg_cost, $is_active]);
        flash_set('success', 'Bahan "' . $nama . '" berhasil ditambahkan.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal menambah bahan: ' . $e->getMessage());
    }

    redirect('/tokopi/views/ingredients.php');
}

// ============================================
// EDIT
// ============================================
elseif ($action === 'edit') {
    verify_csrf();

    $id        = (int)($_POST['id'] ?? 0);
    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $nama      = trim($_POST['nama'] ?? '');
    $base_unit = $_POST['base_unit'] ?? 'G';
    $category  = trim($_POST['category'] ?? '');
    $avg_cost  = (float)($_POST['avg_cost'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0 || $code === '' || $nama === '') {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/ingredients.php');
    }
    if (!in_array($base_unit, ['G','ML','PCS'], true)) {
        $base_unit = 'G';
    }
    if ($category === '') $category = null;

    // Cek milik tenant
    $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Bahan tidak ditemukan.');
        redirect('/tokopi/views/ingredients.php');
    }

    // Cek kode unik kecuali dirinya
    $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE tenant_id = ? AND code = ? AND id != ?");
    $stmt->execute([$tenant_id, $code, $id]);
    if ($stmt->fetch()) {
        flash_set('error', 'Kode "' . $code . '" sudah dipakai bahan lain.');
        redirect('/tokopi/views/ingredients.php');
    }

    try {
        $stmt = $pdo->prepare("UPDATE ingredients SET code = ?, nama = ?, base_unit = ?, category = ?, avg_cost = ?, is_active = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$code, $nama, $base_unit, $category, $avg_cost, $is_active, $id, $tenant_id]);
        flash_set('success', 'Bahan berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update bahan: ' . $e->getMessage());
    }

    redirect('/tokopi/views/ingredients.php');
}

// ============================================
// HAPUS
// ============================================
elseif ($action === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama FROM ingredients WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Bahan tidak ditemukan.');
        redirect('/tokopi/views/ingredients.php');
    }

    try {
        // Cek dipakai resep
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM recipe_items WHERE ingredient_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            flash_set('error', 'Tidak bisa hapus: bahan ini masih dipakai di resep. Hapus dari resep dulu.');
            redirect('/tokopi/views/ingredients.php');
        }

        // Cek ada stock movement
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE ingredient_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            flash_set('error', 'Tidak bisa hapus: bahan ini punya riwayat stok. Nonaktifkan saja.');
            redirect('/tokopi/views/ingredients.php');
        }

        $stmt = $pdo->prepare("DELETE FROM ingredients WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenant_id]);
        flash_set('success', 'Bahan "' . $existing['nama'] . '" berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus bahan: ' . $e->getMessage());
    }

    redirect('/tokopi/views/ingredients.php');
}

else {
    redirect('/tokopi/views/ingredients.php');
}