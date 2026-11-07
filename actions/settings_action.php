<?php
// actions/settings_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();
$user_id   = current_user_id();

// ============================================
// UPDATE PROFIL TOKO
// ============================================
if ($action === 'update_tenant') {
    verify_csrf();

    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');

    if ($nama_toko === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Nama toko dan email wajib valid.');
        redirect('/tokopi/views/settings.php');
    }

    if ($no_hp === '') $no_hp = null;
    if ($alamat === '') $alamat = null;

    // Cek email unik
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE email = ? AND id != ?");
    $stmt->execute([$email, $tenant_id]);
    if ($stmt->fetch()) {
        flash_set('error', 'Email sudah dipakai tenant lain.');
        redirect('/tokopi/views/settings.php');
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE tenants SET nama_toko = ?, email = ?, no_hp = ?, alamat = ? WHERE id = ?")
            ->execute([$nama_toko, $email, $no_hp, $alamat, $tenant_id]);

        // Update email owner juga
        $pdo->prepare("UPDATE users SET email = ? WHERE id = ? AND role = 'owner'")
            ->execute([$email, $user_id]);

        $_SESSION['user_name'] = $nama_toko;

        $pdo->commit();
        flash_set('success', 'Profil toko berhasil diupdate.');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('error', 'Gagal update: ' . $e->getMessage());
    }

    redirect('/tokopi/views/settings.php');
}

// ============================================
// GANTI PASSWORD
// ============================================
elseif ($action === 'change_password') {
    verify_csrf();

    $old_password     = $_POST['old_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($old_password === '' || $new_password === '' || $confirm_password === '') {
        flash_set('error', 'Semua field wajib diisi.');
        redirect('/tokopi/views/settings.php');
    }

    if (strlen($new_password) < 6) {
        flash_set('error', 'Password baru minimal 6 karakter.');
        redirect('/tokopi/views/settings.php');
    }

    if ($new_password !== $confirm_password) {
        flash_set('error', 'Konfirmasi password tidak cocok.');
        redirect('/tokopi/views/settings.php');
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $current_hash = $stmt->fetchColumn();

    if (!password_verify($old_password, $current_hash)) {
        flash_set('error', 'Password lama salah.');
        redirect('/tokopi/views/settings.php');
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($new_password, PASSWORD_BCRYPT), $user_id]);
        flash_set('success', 'Password berhasil diganti.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal ganti password: ' . $e->getMessage());
    }

    redirect('/tokopi/views/settings.php');
}

else {
    redirect('/tokopi/views/settings.php');
}
