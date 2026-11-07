<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner']);

$page_title = 'Pengaturan Toko';
$active_menu = 'settings';
$tenant_id = current_tenant_id();
$user_id   = current_user_id();

$stmt = $pdo->prepare("SELECT * FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$tenant = $stmt->fetch();

if (!$tenant) die('Tenant tidak ditemukan.');

$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shop me-1"></i> Profil Toko
            </div>
            <div class="card-body">
                <form action="/tokopi/actions/settings_action.php?action=update_tenant" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Nama Toko <span class="text-danger">*</span></label>
                        <input type="text" name="nama_toko" class="form-control" required
                               value="<?= e($tenant['nama_toko']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= e($tenant['email']) ?>">
                        <small class="text-muted">Email ini dipakai untuk login owner.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control"
                               value="<?= e($tenant['no_hp'] ?? '') ?>"
                               placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"><?= e($tenant['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status Langganan</label>
                        <div>
                            <?php if ($tenant['status_langganan'] == 1): ?>
                                <span class="badge bg-success">Premium</span>
                                <small class="text-muted ms-2">Produk unlimited</small>
                            <?php else: ?>
                                <span class="badge bg-secondary">Free</span>
                                <small class="text-muted ms-2">Maksimal 50 produk</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-espresso">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-key me-1"></i> Ganti Password
            </div>
            <div class="card-body">
                <form action="/tokopi/actions/settings_action.php?action=change_password" method="POST">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small class="text-muted">Minimal 6 karakter.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-espresso">
                        <i class="bi bi-shield-lock me-1"></i> Ganti Password
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-1"></i> Info Sistem
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <td class="text-muted">Tenant ID</td>
                        <td class="text-end money"><?= (int)$tenant_id ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Outlet</td>
                        <td class="text-end money">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM outlets WHERE tenant_id = ? AND is_active = 1");
                            $stmt->execute([$tenant_id]);
                            echo (int)$stmt->fetchColumn();
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Produk</td>
                        <td class="text-end money">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE tenant_id = ?");
                            $stmt->execute([$tenant_id]);
                            echo (int)$stmt->fetchColumn();
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat</td>
                        <td class="text-end"><?= date('d/m/Y', strtotime($tenant['created_at'])) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
