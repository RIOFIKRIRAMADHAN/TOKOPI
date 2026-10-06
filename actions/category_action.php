<?php
// actions/category_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

// Fungsi bantu: bikin slug unik untuk tenant
function make_unique_slug(PDO $pdo, int $tenant_id, string $nama, int $exclude_id = 0): string {
    $base = strtolower(trim($nama));
    $base = preg_replace('/[^a-z0-9]+/', '-', $base);
    $base = trim($base, '-');
    if ($base === '') $base = 'kategori';

    $slug = $base;
    $i = 2;
    while (true) {
        if ($exclude_id > 0) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE tenant_id = ? AND slug = ? AND id != ?");
            $stmt->execute([$tenant_id, $slug, $exclude_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE tenant_id = ? AND slug = ?");
            $stmt->execute([$tenant_id, $slug]);
        }
        if (!$stmt->fetch()) break;
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}

// ============================================
// TAMBAH
// ============================================
if ($action === 'add') {
    verify_csrf();

    $nama       = trim($_POST['nama'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if ($nama === '') {
        flash_set('error', 'Nama kategori wajib diisi.');
        redirect('/tokopi/views/categories.php');
    }

    try {
        $slug = make_unique_slug($pdo, $tenant_id, $nama);
        $stmt = $pdo->prepare("INSERT INTO categories (tenant_id, nama, slug, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $nama, $slug, $sort_order, $is_active]);
        flash_set('success', 'Kategori "' . $nama . '" berhasil ditambahkan.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal menambah kategori: ' . $e->getMessage());
    }

    redirect('/tokopi/views/categories.php');
}

// ============================================
// EDIT
// ============================================
elseif ($action === 'edit') {
    verify_csrf();

    $id         = (int)($_POST['id'] ?? 0);
    $nama       = trim($_POST['nama'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0 || $nama === '') {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/categories.php');
    }

    // Pastikan kategori milik tenant ini
    $stmt = $pdo->prepare("SELECT id, nama FROM categories WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Kategori tidak ditemukan.');
        redirect('/tokopi/views/categories.php');
    }

    try {
        $slug = make_unique_slug($pdo, $tenant_id, $nama, $id);
        $stmt = $pdo->prepare("UPDATE categories SET nama = ?, slug = ?, sort_order = ?, is_active = ? WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$nama, $slug, $sort_order, $is_active, $id, $tenant_id]);
        flash_set('success', 'Kategori berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update kategori: ' . $e->getMessage());
    }

    redirect('/tokopi/views/categories.php');
}

// ============================================
// HAPUS
// ============================================
elseif ($action === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/categories.php');
    }

    // Cek milik tenant
    $stmt = $pdo->prepare("SELECT nama FROM categories WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        flash_set('error', 'Kategori tidak ditemukan.');
        redirect('/tokopi/views/categories.php');
    }

    try {
        // Karena FK ON DELETE CASCADE di products.category_id,
        // hapus kategori otomatis hapus produk-produknya.
        // Kalau mau aman, cek dulu apakah ada produk:
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $jml = (int)$stmt->fetchColumn();

        if ($jml > 0) {
            flash_set('error', 'Tidak bisa hapus: masih ada ' . $jml . ' produk di kategori ini. Pindahkan atau hapus produknya dulu.');
            redirect('/tokopi/views/categories.php');
        }

        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenant_id]);
        flash_set('success', 'Kategori "' . $existing['nama'] . '" berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus kategori: ' . $e->getMessage());
    }

    redirect('/tokopi/views/categories.php');
}

else {
    redirect('/tokopi/views/categories.php');
}
