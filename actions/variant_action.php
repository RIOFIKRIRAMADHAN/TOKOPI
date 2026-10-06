<?php
// actions/variant_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'gudang']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

// Helper: pastikan produk milik tenant
function produk_milik_tenant(PDO $pdo, int $product_id, int $tenant_id)
{
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$product_id, $tenant_id]);
    return (bool)$stmt->fetch();
}

// ============================================
// TAMBAH VARIAN
// ============================================
if ($action === 'add') {
    verify_csrf();

    $product_id  = (int)($_POST['product_id'] ?? 0);
    $nama        = trim($_POST['nama'] ?? '');
    $temperature = $_POST['temperature'] ?? null;
    $size        = trim($_POST['size'] ?? '');
    $sku         = strtoupper(trim($_POST['sku'] ?? ''));
    $harga_jual  = (float)($_POST['harga_jual'] ?? 0);
    $hpp_manual  = (float)($_POST['hpp_manual'] ?? 0);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($product_id <= 0 || $nama === '' || $sku === '' || $harga_jual < 0) {
        flash_set('error', 'Data varian tidak lengkap.');
        redirect('/tokopi/views/product_detail.php?id=' . $product_id);
    }
    if ($temperature !== null && !in_array($temperature, ['HOT', 'ICED'], true)) {
        $temperature = null;
    }
    if ($size === '') $size = null;

    // Cek produk milik tenant
    if (!produk_milik_tenant($pdo, $product_id, $tenant_id)) {
        flash_set('error', 'Produk tidak ditemukan.');
        redirect('/tokopi/views/products.php');
    }

    // Cek SKU unik
    $stmt = $pdo->prepare("SELECT id FROM product_variants WHERE sku = ?");
    $stmt->execute([$sku]);
    if ($stmt->fetch()) {
        flash_set('error', 'SKU "' . $sku . '" sudah dipakai. Gunakan SKU lain.');
        redirect('/tokopi/views/product_detail.php?id=' . $product_id);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, sku, nama, temperature, size, harga_jual, hpp_manual, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $sku, $nama, $temperature, $size, $harga_jual, $hpp_manual, $is_active]);
        flash_set('success', 'Varian "' . $nama . '" berhasil ditambahkan.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal menambah varian: ' . $e->getMessage());
    }

    redirect('/tokopi/views/product_detail.php?id=' . $product_id);
}

// ============================================
// EDIT VARIAN
// ============================================
elseif ($action === 'edit') {
    verify_csrf();

    $id          = (int)($_POST['id'] ?? 0);
    $product_id  = (int)($_POST['product_id'] ?? 0);
    $nama        = trim($_POST['nama'] ?? '');
    $temperature = $_POST['temperature'] ?? null;
    $size        = trim($_POST['size'] ?? '');
    $sku         = strtoupper(trim($_POST['sku'] ?? ''));
    $harga_jual  = (float)($_POST['harga_jual'] ?? 0);
    $hpp_manual  = (float)($_POST['hpp_manual'] ?? 0);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0 || $nama === '' || $sku === '') {
        flash_set('error', 'Data varian tidak lengkap.');
        redirect('/tokopi/views/product_detail.php?id=' . $product_id);
    }
    if ($temperature !== null && !in_array($temperature, ['HOT', 'ICED'], true)) {
        $temperature = null;
    }
    if ($size === '') $size = null;

    // Cek varian milik produk tenant
    $stmt = $pdo->prepare("
        SELECT v.id FROM product_variants v
        JOIN products p ON p.id = v.product_id
        WHERE v.id = ? AND p.tenant_id = ?
    ");
    $stmt->execute([$id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Varian tidak ditemukan.');
        redirect('/tokopi/views/products.php');
    }

    // Cek SKU unik (kecuali dirinya sendiri)
    $stmt = $pdo->prepare("SELECT id FROM product_variants WHERE sku = ? AND id != ?");
    $stmt->execute([$sku, $id]);
    if ($stmt->fetch()) {
        flash_set('error', 'SKU "' . $sku . '" sudah dipakai varian lain.');
        redirect('/tokopi/views/product_detail.php?id=' . $product_id);
    }

    try {
        $stmt = $pdo->prepare("UPDATE product_variants SET sku = ?, nama = ?, temperature = ?, size = ?, harga_jual = ?, hpp_manual = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$sku, $nama, $temperature, $size, $harga_jual, $hpp_manual, $is_active, $id]);
        flash_set('success', 'Varian berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update varian: ' . $e->getMessage());
    }

    redirect('/tokopi/views/product_detail.php?id=' . $product_id);
}

// ============================================
// HAPUS VARIAN
// ============================================
elseif ($action === 'delete') {
    verify_csrf();

    $id         = (int)($_POST['id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);

    // Cek varian milik tenant
    $stmt = $pdo->prepare("
        SELECT v.id, v.nama FROM product_variants v
        JOIN products p ON p.id = v.product_id
        WHERE v.id = ? AND p.tenant_id = ?
    ");
    $stmt->execute([$id, $tenant_id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Varian tidak ditemukan.');
        redirect('/tokopi/views/products.php');
    }

    try {
        // Cek apakah varian pernah dipakai di order
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE variant_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            flash_set('error', 'Varian ini sudah pernah terjual. Nonaktifkan saja daripada dihapus.');
            redirect('/tokopi/views/product_detail.php?id=' . $product_id);
        }

        $stmt = $pdo->prepare("DELETE FROM product_variants WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Varian "' . $existing['nama'] . '" berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus varian: ' . $e->getMessage());
    }

    redirect('/tokopi/views/product_detail.php?id=' . $product_id);
} else {
    redirect('/tokopi/views/products.php');
}
