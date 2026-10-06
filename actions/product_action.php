cat > actions/product_action.php << 'EOF'
<?php
// actions/product_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

// Cek limit akun free (50 produk, sesuai kode lama Anda)
function cek_limit_produk(PDO $pdo, int $tenant_id, int $tambahan = 1) {
    $stmt = $pdo->prepare("SELECT status_langganan FROM tenants WHERE id = ?");
    $stmt->execute([$tenant_id]);
    $status = (int)$stmt->fetchColumn();

    if ($status === 1) return true; // premium, unlimited

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $total = (int)$stmt->fetchColumn();

    return ($total + $tambahan) <= 50;
}

// ============================================
// TAMBAH
// ============================================
if ($action === 'add') {
    verify_csrf();

    $nama        = trim($_POST['nama'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($nama === '' || $category_id <= 0) {
        flash_set('error', 'Nama dan kategori wajib diisi.');
        redirect('/tokopi/views/products.php');
    }

    // Cek limit
    if (!cek_limit_produk($pdo, $tenant_id)) {
        flash_set('error', 'Akun Free maksimal 50 produk. Upgrade ke Premium untuk unlimited.');
        redirect('/tokopi/views/products.php');
    }

    // Cek kategori milik tenant
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$category_id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Kategori tidak valid.');
        redirect('/tokopi/views/products.php');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (tenant_id, category_id, nama, description, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $category_id, $nama, $description, $is_active]);
        $product_id = $pdo->lastInsertId();

        flash_set('success', 'Produk "' . $nama . '" berhasil ditambahkan. Silakan tambahkan varian.');
        redirect('/tokopi/views/product_detail.php?id=' . $product_id);
    } catch (PDOException $e) {
        flash_set('error', 'Gagal menambah produk: ' . $e->getMessage());
        redirect('/tokopi/views/products.php');
    }
}

// ============================================
// EDIT
// ============================================
elseif ($action === 'edit') {
    verify_csrf();

    $id          = (int)($_POST['id'] ?? 0);
    $nama        = trim($_POST['nama'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0 || $nama === '' || $category_id <= 0) {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/products.php');
    }

    // Cek milik tenant
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Produk tidak ditemukan.');
        redirect('/tokopi/views/products.php');
    }

    try {
        $stmt = $pdo->prepare("UPDATE products SET nama = ?, category_id = ?, description = ?, is_active = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$nama, $category_id, $description, $is_active, $id, $tenant_id]);
        flash_set('success', 'Produk berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update produk: ' . $e->getMessage());
    }

    redirect('/tokopi/views/products.php');
}

// ============================================
// HAPUS
// ============================================
elseif ($action === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    // Cek milik tenant
    $stmt = $pdo->prepare("SELECT nama FROM products WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Produk tidak ditemukan.');
        redirect('/tokopi/views/products.php');
    }

    try {
        // Cek ada order item yang refer ke varian produk ini
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM order_items oi
            JOIN product_variants pv ON pv.id = oi.variant_id
            WHERE pv.product_id = ?
        ");
        $stmt->execute([$id]);
        $jml_order = (int)$stmt->fetchColumn();

        if ($jml_order > 0) {
            flash_set('error', 'Tidak bisa hapus: produk ini sudah pernah terjual di ' . $jml_order . ' transaksi. Nonaktifkan saja.');
            redirect('/tokopi/views/products.php');
        }

        // Hapus (varian & resep otomatis kehapus lewat CASCADE)
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenant_id]);
        flash_set('success', 'Produk "' . $existing['nama'] . '" berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus produk: ' . $e->getMessage());
    }

    redirect('/tokopi/views/products.php');
}

else {
    redirect('/tokopi/views/products.php');
}
