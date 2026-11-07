
<?php
// actions/reward_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

if ($action === 'add') {
    verify_csrf();

    $nama        = trim($_POST['nama'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $points_cost = (int)($_POST['points_cost'] ?? 0);
    $type        = $_POST['type'] ?? '';
    $value       = (float)($_POST['value'] ?? 0);
    $variant_id  = (int)($_POST['variant_id'] ?? 0);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($nama === '' || $points_cost <= 0 || !in_array($type, ['FREE_ITEM', 'DISCOUNT_NOMINAL', 'DISCOUNT_PERCENT'], true)) {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/rewards_manage.php');
    }

    if ($type === 'FREE_ITEM') {
        $value = 0;
        if ($variant_id <= 0) {
            flash_set('error', 'Pilih produk untuk reward gratis.');
            redirect('/tokopi/views/rewards_manage.php');
        }
    } else {
        $variant_id = null;
        if ($value <= 0) {
            flash_set('error', 'Value harus lebih dari 0.');
            redirect('/tokopi/views/rewards_manage.php');
        }
        if ($type === 'DISCOUNT_PERCENT' && $value > 100) {
            flash_set('error', 'Diskon persen max 100%.');
            redirect('/tokopi/views/rewards_manage.php');
        }
    }

     // Cek duplikat: nama sama, atau kombinasi tipe+value+poin sama
    $stmt = $pdo->prepare("
        SELECT id FROM rewards
        WHERE tenant_id = ?
          AND (
            nama = ?
            OR (type = ? AND value = ? AND points_cost = ?)
          )
    ");
    $stmt->execute([$tenant_id, $nama, $type, $value, $points_cost]);
    if ($stmt->fetch()) {
        flash_set('error', 'Reward "' . $nama . '" sudah ada. Cek daftar reward.');
        redirect('/tokopi/views/rewards_manage.php');
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rewards (tenant_id, nama, deskripsi, points_cost, type, value, variant_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tenant_id, $nama, $deskripsi ?: null, $points_cost, $type, $value, $variant_id ?: null, $is_active]);
        flash_set('success', 'Reward "' . $nama . '" berhasil ditambahkan.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal: ' . $e->getMessage());
    }

    redirect('/tokopi/views/rewards_manage.php');
} elseif ($action === 'edit') {
    verify_csrf();

    $id          = (int)($_POST['id'] ?? 0);
    $nama        = trim($_POST['nama'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $points_cost = (int)($_POST['points_cost'] ?? 0);
    $type        = $_POST['type'] ?? '';
    $value       = (float)($_POST['value'] ?? 0);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if ($id <= 0 || $nama === '' || $points_cost <= 0) {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/rewards_manage.php');
    }

    // Cek milik tenant
    $stmt = $pdo->prepare("SELECT id FROM rewards WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Reward tidak ditemukan.');
        redirect('/tokopi/views/rewards_manage.php');
    }

    if ($type !== 'FREE_ITEM') {
        $variant_id = null;
    }

        // Cek duplikat nama (kecuali dirinya sendiri)
    $stmt = $pdo->prepare("
        SELECT id FROM rewards
        WHERE tenant_id = ? AND nama = ? AND id != ?
    ");
    $stmt->execute([$tenant_id, $nama, $id]);
    if ($stmt->fetch()) {
        flash_set('error', 'Reward dengan nama "' . $nama . '" sudah ada.');
        redirect('/tokopi/views/rewards_manage.php');
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE rewards
            SET nama = ?, deskripsi = ?, points_cost = ?, type = ?, value = ?, is_active = ?
            WHERE id = ? AND tenant_id = ?
        ");
        $stmt->execute([$nama, $deskripsi ?: null, $points_cost, $type, $value, $is_active, $id, $tenant_id]);
        flash_set('success', 'Reward berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal: ' . $e->getMessage());
    }

    redirect('/tokopi/views/rewards_manage.php');
} elseif ($action === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama FROM rewards WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $r = $stmt->fetch();
    if (!$r) {
        flash_set('error', 'Reward tidak ditemukan.');
        redirect('/tokopi/views/rewards_manage.php');
    }

    try {
        // Cek apakah ada voucher terkait
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_redemptions WHERE reward_id = ?");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) {
            // Ada voucher terkait → nonaktifkan saja
            $pdo->prepare("UPDATE rewards SET is_active = 0 WHERE id = ?")->execute([$id]);
            flash_set('success', 'Reward "' . $r['nama'] . '" dinonaktifkan (karena ada ' . $count . ' voucher terkait).');
        } else {
            // Tidak ada voucher → hapus permanen
            $pdo->prepare("DELETE FROM rewards WHERE id = ?")->execute([$id]);
            flash_set('success', 'Reward "' . $r['nama'] . '" berhasil dihapus.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Gagal: ' . $e->getMessage());
    }

    redirect('/tokopi/views/rewards_manage.php');
} else {
    redirect('/tokopi/views/rewards_manage.php');
}
