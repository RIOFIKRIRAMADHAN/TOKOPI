<?php
// actions/recipe_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

// Pastikan resep milik tenant (via variant → product → tenant)
function resep_milik_tenant(PDO $pdo, int $recipe_id, int $tenant_id) {
    $stmt = $pdo->prepare("
        SELECT r.id, r.variant_id FROM recipes r
        JOIN product_variants v ON v.id = r.variant_id
        JOIN products p ON p.id = v.product_id
        WHERE r.id = ? AND p.tenant_id = ?
    ");
    $stmt->execute([$recipe_id, $tenant_id]);
    return $stmt->fetch();
}

// Pastikan ingredient milik tenant
function ingredient_milik_tenant(PDO $pdo, int $ingredient_id, int $tenant_id) {
    $stmt = $pdo->prepare("SELECT id FROM ingredients WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$ingredient_id, $tenant_id]);
    return (bool)$stmt->fetch();
}

// ============================================
// TAMBAH ITEM RESEP
// ============================================
if ($action === 'add_item') {
    verify_csrf();

    $recipe_id     = (int)($_POST['recipe_id'] ?? 0);
    $variant_id    = (int)($_POST['variant_id'] ?? 0);
    $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
    $qty           = (float)($_POST['qty'] ?? 0);
    $note          = trim($_POST['note'] ?? '');

    $redirect = '/tokopi/views/recipe_detail.php?variant_id=' . $variant_id;

    if ($recipe_id <= 0 || $ingredient_id <= 0 || $qty <= 0) {
        flash_set('error', 'Data tidak lengkap atau jumlah tidak valid.');
        redirect($redirect);
    }
    if ($note === '') $note = null;

    if (!resep_milik_tenant($pdo, $recipe_id, $tenant_id)) {
        flash_set('error', 'Resep tidak ditemukan.');
        redirect('/tokopi/views/recipes.php');
    }
    if (!ingredient_milik_tenant($pdo, $ingredient_id, $tenant_id)) {
        flash_set('error', 'Bahan tidak valid.');
        redirect($redirect);
    }

    // Ambil unit dari bahan
    $stmt = $pdo->prepare("SELECT base_unit, nama FROM ingredients WHERE id = ?");
    $stmt->execute([$ingredient_id]);
    $ing = $stmt->fetch();
    $unit = $ing['base_unit'];

    // Cek duplikat
    $stmt = $pdo->prepare("SELECT id FROM recipe_items WHERE recipe_id = ? AND ingredient_id = ?");
    $stmt->execute([$recipe_id, $ingredient_id]);
    if ($stmt->fetch()) {
        flash_set('error', 'Bahan "' . $ing['nama'] . '" sudah ada di resep. Edit jumlahnya saja.');
        redirect($redirect);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO recipe_items (recipe_id, ingredient_id, qty, unit, note) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$recipe_id, $ingredient_id, $qty, $unit, $note]);
        flash_set('success', 'Bahan berhasil ditambahkan ke resep.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal menambah bahan: ' . $e->getMessage());
    }

    redirect($redirect);
}

// ============================================
// EDIT ITEM RESEP
// ============================================
elseif ($action === 'edit_item') {
    verify_csrf();

    $id         = (int)($_POST['id'] ?? 0);
    $recipe_id  = (int)($_POST['recipe_id'] ?? 0);
    $variant_id = (int)($_POST['variant_id'] ?? 0);
    $qty        = (float)($_POST['qty'] ?? 0);
    $note       = trim($_POST['note'] ?? '');

    $redirect = '/tokopi/views/recipe_detail.php?variant_id=' . $variant_id;

    if ($id <= 0 || $qty <= 0) {
        flash_set('error', 'Jumlah tidak valid.');
        redirect($redirect);
    }
    if ($note === '') $note = null;

    if (!resep_milik_tenant($pdo, $recipe_id, $tenant_id)) {
        flash_set('error', 'Resep tidak ditemukan.');
        redirect('/tokopi/views/recipes.php');
    }

    try {
        $stmt = $pdo->prepare("UPDATE recipe_items SET qty = ?, note = ? WHERE id = ? AND recipe_id = ?");
        $stmt->execute([$qty, $note, $id, $recipe_id]);
        flash_set('success', 'Bahan berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update bahan: ' . $e->getMessage());
    }

    redirect($redirect);
}

// ============================================
// HAPUS ITEM RESEP
// ============================================
elseif ($action === 'delete_item') {
    verify_csrf();

    $id         = (int)($_POST['id'] ?? 0);
    $recipe_id  = (int)($_POST['recipe_id'] ?? 0);
    $variant_id = (int)($_POST['variant_id'] ?? 0);

    $redirect = '/tokopi/views/recipe_detail.php?variant_id=' . $variant_id;

    if ($id <= 0) {
        flash_set('error', 'Data tidak valid.');
        redirect($redirect);
    }

    if (!resep_milik_tenant($pdo, $recipe_id, $tenant_id)) {
        flash_set('error', 'Resep tidak ditemukan.');
        redirect('/tokopi/views/recipes.php');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM recipe_items WHERE id = ? AND recipe_id = ?");
        $stmt->execute([$id, $recipe_id]);
        flash_set('success', 'Bahan berhasil dihapus dari resep.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus bahan: ' . $e->getMessage());
    }

    redirect($redirect);
}

else {
    redirect('/tokopi/views/recipes.php');
}