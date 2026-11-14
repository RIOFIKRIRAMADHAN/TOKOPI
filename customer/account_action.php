<?php
// customer/account_action.php
require_once __DIR__ . '/../config/bootstrap.php';

// HAPUS BLOKIR MEJA
// if (empty($_SESSION['customer_table_id'])) { redirect('/tokopi/customer/t.php'); }

$action = $_GET['action'] ?? '';

// Ambil tenant_id (Bisa dari outlet meja, atau fallback ke tenant pertama jika dari luar)
$outlet_id = (int)($_SESSION['customer_outlet_id'] ?? 0);
$tenant_id = 0;

if ($outlet_id > 0) {
    $stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
    $stmt->execute([$outlet_id]);
    $tenant_id = (int)$stmt->fetchColumn();
} else {
    // Fallback: Ambil tenant pertama (karena biasanya 1 coffee shop utama)
    $stmt = $pdo->query("SELECT id FROM tenants ORDER BY id LIMIT 1");
    $tenant_id = (int)$stmt->fetchColumn();
}

if (!$tenant_id) {
    flash_set('error', 'Outlet / Tenant tidak valid.');
    redirect('/tokopi/customer/login.php');
}

// ============================================
// REGISTER
// ============================================
if ($action === 'register') {
    verify_csrf();

    $nama             = trim($_POST['nama'] ?? '');
    $email            = strtolower(trim($_POST['email'] ?? ''));
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($nama === '' || strlen($nama) > 100) {
        flash_set('error', 'Nama wajib diisi (max 100 karakter).');
        redirect('/tokopi/customer/register.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'Email tidak valid.');
        redirect('/tokopi/customer/register.php');
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $phone)) {
        flash_set('error', 'Format no HP tidak valid.');
        redirect('/tokopi/customer/register.php');
    }
    if (strlen($password) < 6) {
        flash_set('error', 'Password minimal 6 karakter.');
        redirect('/tokopi/customer/register.php');
    }
    if ($password !== $password_confirm) {
        flash_set('error', 'Konfirmasi password tidak cocok.');
        redirect('/tokopi/customer/register.php');
    }

    $stmt = $pdo->prepare("SELECT id FROM customers WHERE tenant_id = ? AND email = ?");
    $stmt->execute([$tenant_id, $email]);
    if ($stmt->fetch()) {
        flash_set('error', 'Email sudah terdaftar. Silakan login.');
        redirect('/tokopi/customer/login.php');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO customers (tenant_id, nama, email, phone, password)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $tenant_id,
            $nama,
            $email,
            $phone ?: null,
            password_hash($password, PASSWORD_BCRYPT),
        ]);
        $customer_id = (int)$pdo->lastInsertId();

        // Auto-login
        $_SESSION['customer_account_id']    = $customer_id;
        $_SESSION['customer_account_name']  = $nama;
        $_SESSION['customer_account_email'] = $email;

        flash_set('success', 'Selamat datang, ' . $nama . '! Akun berhasil dibuat.');
        
        // REDIRECT PINTAR
        if (!empty($_SESSION['customer_table_id'])) {
            redirect('/tokopi/customer/home.php');
        } else {
            redirect('/tokopi/customer/profile.php');
        }

    } catch (PDOException $e) {
        flash_set('error', 'Gagal mendaftar: ' . $e->getMessage());
        redirect('/tokopi/customer/register.php');
    }
}

// ============================================
// LOGIN
// ============================================
elseif ($action === 'login') {
    verify_csrf();

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        flash_set('error', 'Email dan password wajib diisi.');
        redirect('/tokopi/customer/login.php');
    }

    $stmt = $pdo->prepare("
        SELECT id, nama, email, password
        FROM customers
        WHERE tenant_id = ? AND email = ? AND is_active = 1
    ");
    $stmt->execute([$tenant_id, $email]);
    $customer = $stmt->fetch();

    if (!$customer || !password_verify($password, $customer['password'])) {
        flash_set('error', 'Email atau password salah.');
        redirect('/tokopi/customer/login.php');
    }

    $_SESSION['customer_account_id']    = (int)$customer['id'];
    $_SESSION['customer_account_name']  = $customer['nama'];
    $_SESSION['customer_account_email'] = $customer['email'];

    flash_set('success', 'Selamat datang kembali, ' . $customer['nama'] . '!');
    
    // REDIRECT PINTAR
    if (!empty($_SESSION['customer_table_id'])) {
        redirect('/tokopi/customer/home.php');
    } else {
        redirect('/tokopi/customer/profile.php');
    }
}

else {
    redirect('/tokopi/customer/login.php');
}