<?php
// actions/auth_action.php
require_once __DIR__ . '/../config/bootstrap.php';

$action = $_GET['action'] ?? '';

// ============================================
// RATE LIMIT HELPER (file-based, simple)
// ============================================
function checkRateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 60): bool {
    $file = sys_get_temp_dir() . '/ratelimit_' . md5($key) . '.json';
    $now = time();

    $data = ['attempts' => [], 'blocked_until' => 0];
    if (file_exists($file)) {
        $json = @file_get_contents($file);
        if ($json) $data = json_decode($json, true) ?: $data;
    }

    // Check if blocked
    if ($data['blocked_until'] > $now) {
        return false;
    }

    // Clean old attempts
    $data['attempts'] = array_filter($data['attempts'], fn($t) => $now - $t < $windowSeconds);

    if (count($data['attempts']) >= $maxAttempts) {
        $data['blocked_until'] = $now + $windowSeconds;
        @file_put_contents($file, json_encode($data));
        return false;
    }

    // Add attempt
    $data['attempts'][] = $now;
    @file_put_contents($file, json_encode($data));
    return true;
}

function clearRateLimit(string $key): void {
    $file = sys_get_temp_dir() . '/ratelimit_' . md5($key) . '.json';
    @unlink($file);
}

// ============================================
// REGISTER TENANT BARU
// ============================================
if ($action === 'register') {
    verify_csrf();

    $nama_toko = trim($_POST['nama_toko'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $no_hp     = trim($_POST['no_hp'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Rate limit by IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit("register:$ip", 3, 300)) {
        flash_set('error', 'Terlalu banyak percobaan. Coba lagi nanti.');
        redirect('/tokopi/register.php');
    }

    // Validasi
    $errors = [];
    if ($nama_toko === '') $errors[] = 'Nama toko wajib diisi.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($password !== $password_confirm) $errors[] = 'Konfirmasi password tidak cocok.';

    // Cek email unik
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = 'Email sudah digunakan.';

    if (!empty($errors)) {
        $_SESSION['old'] = $_POST;
        flash_set('error', implode(' ', $errors));
        redirect('/tokopi/register.php');
    }

    try {
        $pdo->beginTransaction();

        // 1. Buat tenant
        $stmt = $pdo->prepare("INSERT INTO tenants (nama_toko, email, password, alamat, no_hp, status_langganan) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([$nama_toko, $email, password_hash($password, PASSWORD_BCRYPT), $alamat, $no_hp]);
        $tenant_id = $pdo->lastInsertId();

        // 2. Buat outlet pusat
        $stmt = $pdo->prepare("INSERT INTO outlets (tenant_id, code, nama, alamat, no_hp) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, 'PUSAT', $nama_toko . ' - Pusat', $alamat, $no_hp]);

        // 3. Buat user owner
        $stmt = $pdo->prepare("INSERT INTO users (tenant_id, outlet_id, nama, email, password, role) VALUES (?, NULL, ?, ?, ?, 'owner')");
        $stmt->execute([$tenant_id, $nama_toko, $email, password_hash($password, PASSWORD_BCRYPT)]);

        // 4. Buat kategori default
        $stmt = $pdo->prepare("INSERT INTO categories (tenant_id, nama, slug, sort_order) VALUES (?, ?, ?, ?)");
        $defaults = [
            ['Promo & Combo', 'promo-combo', 1],
            ['Baru',          'baru',        2],
            ['Makanan',       'makanan',     3],
            ['Coffee',        'coffee',      4],
            ['Non Coffee',    'non-coffee',  5],
        ];
        foreach ($defaults as $c) {
            $stmt->execute([$tenant_id, $c[0], $c[1], $c[2]]);
        }

        $pdo->commit();

        clearRateLimit("register:$ip");
        flash_set('success', 'Registrasi berhasil! Silakan login.');
        redirect('/tokopi/login.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        flash_set('error', 'Gagal registrasi: ' . $e->getMessage());
        redirect('/tokopi/register.php');
    }
}

// ============================================
// LOGIN OWNER / MANAGER (email + password)
// ============================================
elseif ($action === 'login_owner') {
    verify_csrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Rate limit by email + IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateKey = "login_owner:$email:$ip";
    if (!checkRateLimit($rateKey, 5, 60)) {
        flash_set('error', 'Terlalu banyak percobaan login. Tunggu 1 menit.');
        redirect('/tokopi/login.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('owner','manager','finance','gudang','barista') AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        flash_set('error', 'Email atau password salah.');
        redirect('/tokopi/login.php');
    }

    // Success - clear rate limit, regenerate session
    clearRateLimit($rateKey);
    session_regenerate_id(true);

    // Set session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['nama'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['tenant_id'] = $user['tenant_id'];
    $_SESSION['outlet_id'] = $user['outlet_id'];

    // Update last login (opsional, kalau nanti ada kolomnya)

    redirect('/tokopi/views/dashboard.php');
}

// ============================================
// LOGIN KASIR (username + PIN)
// ============================================
elseif ($action === 'login_kasir') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $pin      = trim($_POST['pin'] ?? '');

    // Rate limit by username + IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateKey = "login_kasir:$username:$ip";
    if (!checkRateLimit($rateKey, 5, 60)) {
        flash_set('error', 'Terlalu banyak percobaan login. Tunggu 1 menit.');
        redirect('/tokopi/login.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'kasir' AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pin, $user['pin'])) {
        flash_set('error', 'Username atau PIN salah.');
        redirect('/tokopi/login.php');
    }

    // Success - clear rate limit, regenerate session
    clearRateLimit($rateKey);
    session_regenerate_id(true);

    // Set session
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_name'] = $user['nama'];
    $_SESSION['role']      = 'kasir';
    $_SESSION['tenant_id'] = $user['tenant_id'];
    $_SESSION['outlet_id'] = $user['outlet_id'];

    redirect('/tokopi/views/pos.php');
}

else {
    redirect('/tokopi/login.php');
}