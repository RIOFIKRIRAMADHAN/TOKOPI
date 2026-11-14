<?php
// customer/password_update.php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_account_id'])) {
    redirect('/tokopi/customer/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tokopi/customer/profile_edit.php');
}

verify_csrf();

$customer_id      = (int)$_SESSION['customer_account_id'];
$old_password     = $_POST['old_password'] ?? '';
$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($old_password === '' || $new_password === '' || $confirm_password === '') {
    flash_set('error', 'Semua kolom password wajib diisi.');
    redirect('/tokopi/customer/profile_edit.php');
}

if ($new_password !== $confirm_password) {
    flash_set('error', 'Konfirmasi password baru tidak cocok.');
    redirect('/tokopi/customer/profile_edit.php');
}

if (strlen($new_password) < 6) {
    flash_set('error', 'Password baru minimal 6 karakter.');
    redirect('/tokopi/customer/profile_edit.php');
}

$stmt = $pdo->prepare("SELECT password FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$current_hash = $stmt->fetchColumn();

if (!password_verify($old_password, $current_hash)) {
    flash_set('error', 'Password saat ini salah.');
    redirect('/tokopi/customer/profile_edit.php');
}

try {
    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE customers SET password = ? WHERE id = ?");
    $stmt->execute([$new_hash, $customer_id]);

    flash_set('success', 'Password berhasil diperbarui.');
    redirect('/tokopi/customer/profile_edit.php');

} catch (Throwable $e) {
    error_log('[customer_password_update] ' . $e->getMessage());
    flash_set('error', 'Terjadi kesalahan sistem.');
    redirect('/tokopi/customer/profile_edit.php');
}