<?php
// actions/user_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner']);

$action = $_GET['action'] ?? '';
$tenant_id = current_tenant_id();

// ============================================
// TAMBAH USER
// ============================================
if ($action === 'add') {
    verify_csrf();

    $nama      = trim($_POST['nama'] ?? '');
    $role      = $_POST['role'] ?? '';
    $outlet_id = (int)($_POST['outlet_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $valid_roles = ['kasir','manager','barista','gudang','finance'];

    if ($nama === '' || !in_array($role, $valid_roles, true) || $outlet_id <= 0) {
        flash_set('error', 'Data tidak lengkap.');
        redirect('/tokopi/views/users.php');
    }

    // Cek outlet milik tenant
    $stmt = $pdo->prepare("SELECT id FROM outlets WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$outlet_id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Outlet tidak valid.');
        redirect('/tokopi/views/users.php');
    }

    try {
        if ($role === 'kasir') {
            $username = strtolower(trim($_POST['username'] ?? ''));
            $pin      = trim($_POST['pin'] ?? '');

            if ($username === '' || !preg_match('/^[a-z0-9_]+$/', $username)) {
                flash_set('error', 'Username hanya boleh huruf kecil, angka, underscore.');
                redirect('/tokopi/views/users.php');
            }
            if (!preg_match('/^[0-9]{4,6}$/', $pin)) {
                flash_set('error', 'PIN harus 4–6 digit angka.');
                redirect('/tokopi/views/users.php');
            }

            // Cek username unik
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                flash_set('error', 'Username "' . $username . '" sudah dipakai.');
                redirect('/tokopi/views/users.php');
            }

            $stmt = $pdo->prepare("INSERT INTO users (tenant_id, outlet_id, nama, username, pin, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $tenant_id, $outlet_id, $nama,
                $username,
                password_hash($pin, PASSWORD_BCRYPT),
                $role, $is_active
            ]);
        } else {
            $email    = strtolower(trim($_POST['email'] ?? ''));
            $password = $_POST['password'] ?? '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash_set('error', 'Email tidak valid.');
                redirect('/tokopi/views/users.php');
            }
            if (strlen($password) < 6) {
                flash_set('error', 'Password minimal 6 karakter.');
                redirect('/tokopi/views/users.php');
            }

            // Cek email unik
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                flash_set('error', 'Email "' . $email . '" sudah dipakai.');
                redirect('/tokopi/views/users.php');
            }

            $stmt = $pdo->prepare("INSERT INTO users (tenant_id, outlet_id, nama, email, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $tenant_id, $outlet_id, $nama,
                $email,
                password_hash($password, PASSWORD_BCRYPT),
                $role, $is_active
            ]);
        }

        flash_set('success', ucfirst($role) . ' "' . $nama . '" berhasil ditambahkan.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal menambah pengguna: ' . $e->getMessage());
    }

    redirect('/tokopi/views/users.php');
}

// ============================================
// EDIT USER
// ============================================
elseif ($action === 'edit') {
    verify_csrf();

    $id        = (int)($_POST['id'] ?? 0);
    $nama      = trim($_POST['nama'] ?? '');
    $outlet_id = (int)($_POST['outlet_id'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $login_id  = trim($_POST['login_id'] ?? '');

    if ($id <= 0 || $nama === '' || $outlet_id <= 0) {
        flash_set('error', 'Data tidak lengkap.');
        redirect('/tokopi/views/users.php');
    }

    // Ambil user & cek milik tenant
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $user = $stmt->fetch();
    if (!$user) {
        flash_set('error', 'Pengguna tidak ditemukan.');
        redirect('/tokopi/views/users.php');
    }
    if ($user['role'] === 'owner') {
        flash_set('error', 'Owner tidak bisa diedit dari sini.');
        redirect('/tokopi/views/users.php');
    }

    // Cek outlet milik tenant
    $stmt = $pdo->prepare("SELECT id FROM outlets WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$outlet_id, $tenant_id]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Outlet tidak valid.');
        redirect('/tokopi/views/users.php');
    }

    try {
        if ($user['role'] === 'kasir') {
            if ($login_id !== '') {
                $login_id = strtolower($login_id);
                if (!preg_match('/^[a-z0-9_]+$/', $login_id)) {
                    flash_set('error', 'Username tidak valid.');
                    redirect('/tokopi/views/users.php');
                }
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$login_id, $id]);
                if ($stmt->fetch()) {
                    flash_set('error', 'Username sudah dipakai.');
                    redirect('/tokopi/views/users.php');
                }
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, outlet_id = ?, username = ?, is_active = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$nama, $outlet_id, $login_id, $is_active, $id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, outlet_id = ?, is_active = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$nama, $outlet_id, $is_active, $id, $tenant_id]);
            }
        } else {
            if ($login_id !== '') {
                $login_id = strtolower($login_id);
                if (!filter_var($login_id, FILTER_VALIDATE_EMAIL)) {
                    flash_set('error', 'Email tidak valid.');
                    redirect('/tokopi/views/users.php');
                }
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$login_id, $id]);
                if ($stmt->fetch()) {
                    flash_set('error', 'Email sudah dipakai.');
                    redirect('/tokopi/views/users.php');
                }
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, outlet_id = ?, email = ?, is_active = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$nama, $outlet_id, $login_id, $is_active, $id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nama = ?, outlet_id = ?, is_active = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$nama, $outlet_id, $is_active, $id, $tenant_id]);
            }
        }

        flash_set('success', 'Pengguna berhasil diupdate.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal update pengguna: ' . $e->getMessage());
    }

    redirect('/tokopi/views/users.php');
}

// ============================================
// RESET PASSWORD / PIN
// ============================================
elseif ($action === 'reset_password') {
    verify_csrf();

    $id           = (int)($_POST['id'] ?? 0);
    $new_password = trim($_POST['new_password'] ?? '');

    if ($id <= 0 || $new_password === '') {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/users.php');
    }

    // Ambil user
    $stmt = $pdo->prepare("SELECT id, nama, role FROM users WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] === 'owner') {
        flash_set('error', 'Pengguna tidak ditemukan.');
        redirect('/tokopi/views/users.php');
    }

    try {
        if ($user['role'] === 'kasir') {
            if (!preg_match('/^[0-9]{4,6}$/', $new_password)) {
                flash_set('error', 'PIN harus 4–6 digit angka.');
                redirect('/tokopi/views/users.php');
            }
            $stmt = $pdo->prepare("UPDATE users SET pin = ? WHERE id = ? AND tenant_id = ?");
            $stmt->execute([password_hash($new_password, PASSWORD_BCRYPT), $id, $tenant_id]);
            flash_set('success', 'PIN kasir berhasil direset.');
        } else {
            if (strlen($new_password) < 6) {
                flash_set('error', 'Password minimal 6 karakter.');
                redirect('/tokopi/views/users.php');
            }
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND tenant_id = ?");
            $stmt->execute([password_hash($new_password, PASSWORD_BCRYPT), $id, $tenant_id]);
            flash_set('success', 'Password berhasil direset.');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Gagal reset: ' . $e->getMessage());
    }

    redirect('/tokopi/views/users.php');
}

// ============================================
// HAPUS
// ============================================
elseif ($action === 'delete') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    $stmt = $pdo->prepare("SELECT nama, role FROM users WHERE id = ? AND tenant_id = ?");
    $stmt->execute([$id, $tenant_id]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] === 'owner') {
        flash_set('error', 'Pengguna tidak ditemukan.');
        redirect('/tokopi/views/users.php');
    }

    // Cek kalau user pernah jadi kasir di order
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE cashier_id = ?");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        flash_set('error', 'Tidak bisa hapus: pengguna ini sudah punya riwayat transaksi. Nonaktifkan saja.');
        redirect('/tokopi/views/users.php');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenant_id]);
        flash_set('success', 'Pengguna "' . $user['nama'] . '" berhasil dihapus.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal hapus pengguna: ' . $e->getMessage());
    }

    redirect('/tokopi/views/users.php');
}

else {
    redirect('/tokopi/views/users.php');
}