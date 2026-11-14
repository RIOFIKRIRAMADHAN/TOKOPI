<?php
// company/contact_submit.php
require_once __DIR__ . '/../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tokopi/company/contact.php');
}

verify_csrf();

// 1. Cek Honeypot (Kalau diisi, anggap bot. Silent reject, tapi redirect ke success biar bot ga sadar)
if (!empty($_POST['website_url'])) {
    flash_set('success', 'Pesan Anda berhasil dikirim! Kami akan segera membalas.');
    redirect('/tokopi/company/contact.php');
}

// 2. Tangkap Data
$nama    = trim($_POST['nama'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$ip      = $_SERVER['REMOTE_ADDR'] ?? null;

// 3. Validasi
$errors = [];
if ($nama === '') $errors[] = 'Nama wajib diisi.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
if ($subject === '') $errors[] = 'Subjek wajib diisi.';
if ($message === '') $errors[] = 'Pesan wajib diisi.';

if (!empty($errors)) {
    flash_set('error', implode(' ', $errors));
    redirect('/tokopi/company/contact.php');
}

// 4. Simpan ke DB
try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (nama, email, phone, subject, message, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nama, $email, $phone ?: null, $subject, $message, $ip]);

    flash_set('success', 'Pesan Anda berhasil dikirim! Kami akan segera membalas.');
    redirect('/tokopi/company/contact.php');

} catch (Throwable $e) {
    error_log('[contact_submit] ' . $e->getMessage());
    flash_set('error', 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.');
    redirect('/tokopi/company/contact.php');
}