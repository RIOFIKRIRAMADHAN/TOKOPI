<?php
// customer/account_action.php
require_once __DIR__ . '/../config/bootstrap.php';

// HAPUS BLOKIR MEJA
// if (empty($_SESSION['customer_table_id'])) { redirect('/tokopi/customer/t.php'); }

$action = $_GET['action'] ?? '';

// ============================================
// RATE LIMIT HELPER
// ============================================
function checkRateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 60): bool {
    $file = sys_get_temp_dir() . '/ratelimit_' . md5($key) . '.json';
    $now = time();

    $data = ['attempts' => [], 'blocked_until' => 0];
    if (file_exists($file)) {
        $json = @file_get_contents($file);
        if ($json) $data = json_decode($json, true) ?: $data;
    }

    if ($data['blocked_until'] > $now) return false;

    $data['attempts'] = array_filter($data['attempts'], fn($t) => $now - $t < $windowSeconds);

    if (count($data['attempts']) >= $maxAttempts) {
        $data['blocked_until'] = $now + $windowSeconds;
        @file_put_contents($file, json_encode($data));
        return false;
    }

    $data['attempts'][] = $now;
    @file_put_contents($file, json_encode($data));
    return true;
}

function clearRateLimit(string $key): void {
    $file = sys_get_temp_dir() . '/ratelimit_' . md5($key) . '.json';
    @unlink($file);
}

// ============================================
// TENANT ID VALIDATION (NO FALLBACK)
// ============================================
$outlet_id = (int)($_SESSION['customer_outlet_id'] ?? 0);
$tenant_id = 0;

if ($outlet_id > 0) {
    $stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ? AND is_active = 1");
    $stmt->execute([$outlet_id]);
    $tenant_id = (int)$stmt->fetchColumn();
}

// TIDAK ADA FALLBACK KE TENANT PERTAMA - ini celah keamanan multi-tenant
if (!$tenant_id) {
    flash_set('error', 'Sesi tidak valid. Silakan scan QR meja ulang.');
    redirect('/tokopi/customer/t.php');
}

// ============================================
// REGISTER
// ============================================
if ($action === 'register') {
    verify_csrf();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit("cust_register:$ip", 3, 300)) {
        flash_set('error', 'Terlalu banyak percobaan. Coba lagi nanti.');
        redirect('/tokopi/customer/register.php');
    }

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

        // Auto-login dengan session regeneration
        session_regenerate_id(true);
        clearRateLimit("cust_register:$ip");

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

    // Rate limit by email + IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateKey = "cust_login:$email:$ip";
    if (!checkRateLimit($rateKey, 5, 60)) {
        flash_set('error', 'Terlalu banyak percobaan login. Tunggu 1 menit.');
        redirect('/tokopi/customer/login.php');
    }

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

    // Success - clear rate limit, regenerate session
    clearRateLimit($rateKey);
    session_regenerate_id(true);

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