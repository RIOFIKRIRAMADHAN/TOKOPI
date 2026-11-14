<?php
// customer/profile_update.php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_account_id'])) {
    redirect('/tokopi/customer/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tokopi/customer/profile_edit.php');
}

verify_csrf();

$customer_id = (int)$_SESSION['customer_account_id'];
$nama        = trim($_POST['nama'] ?? '');
$phone       = trim($_POST['phone'] ?? '');

if ($nama === '') {
    flash_set('error', 'Nama tidak boleh kosong.');
    redirect('/tokopi/customer/profile_edit.php');
}

if ($phone !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) {
    flash_set('error', 'Format nomor HP tidak valid.');
    redirect('/tokopi/customer/profile_edit.php');
}

$avatar_path = null;

// ---------- Proses Upload Avatar ----------
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];

    if ($file['size'] > 2 * 1024 * 1024) {
        flash_set('error', 'Ukuran foto maksimal 2MB.');
        redirect('/tokopi/customer/profile_edit.php');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!array_key_exists($mime, $allowed)) {
        flash_set('error', 'Format foto harus JPG, PNG, atau WEBP.');
        redirect('/tokopi/customer/profile_edit.php');
    }

    $ext      = $allowed[$mime];
    $filename = 'customer_' . $customer_id . '_' . time() . '.' . $ext;
    $upload_dir = __DIR__ . '/../uploads/avatars/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        $avatar_path = 'uploads/avatars/' . $filename;
    } else {
        flash_set('error', 'Gagal mengupload foto.');
        redirect('/tokopi/customer/profile_edit.php');
    }
}

// ---------- Update DB ----------
try {
    $sql = "UPDATE customers SET nama = ?, phone = ?";
    $params = [$nama, $phone ?: null];

    if ($avatar_path) {
        $sql .= ", avatar_path = ?";
        $params[] = $avatar_path;
    }

    $sql .= " WHERE id = ?";
    $params[] = $customer_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['customer_account_name'] = $nama;

    flash_set('success', 'Profil berhasil diperbarui.');
    redirect('/tokopi/customer/profile_edit.php');

} catch (Throwable $e) {
    error_log('[customer_profile_update] ' . $e->getMessage());
    flash_set('error', 'Terjadi kesalahan sistem.');
    redirect('/tokopi/customer/profile_edit.php');
}