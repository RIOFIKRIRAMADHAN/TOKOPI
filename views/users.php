<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner']);

$page_title = 'Pengguna';
$active_menu = 'users';
$tenant_id = current_tenant_id();

// Filter role & outlet
$filter_role   = $_GET['role'] ?? '';
$filter_outlet = (int)($_GET['outlet'] ?? 0);

$where = ["u.tenant_id = ?"];
$params = [$tenant_id];

if (in_array($filter_role, ['owner','manager','kasir','barista','gudang','finance'], true)) {
    $where[] = "u.role = ?";
    $params[] = $filter_role;
}
if ($filter_outlet > 0) {
    $where[] = "u.outlet_id = ?";
    $params[] = $filter_outlet;
}

$sql = "
    SELECT u.*, o.nama AS outlet_nama
    FROM users u
    LEFT JOIN outlets o ON o.id = u.outlet_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY
        FIELD(u.role, 'owner','manager','kasir','barista','gudang','finance'),
        u.nama ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Outlet untuk dropdown
$stmt = $pdo->prepare("SELECT id, code, nama FROM outlets WHERE tenant_id = ? ORDER BY id");
$stmt->execute([$tenant_id]);
$outlets = $stmt->fetchAll();

// Stats
$stmt = $pdo->prepare("
    SELECT role, COUNT(*) AS total
    FROM users
    WHERE tenant_id = ?
    GROUP BY role
");
$stmt->execute([$tenant_id]);
$stats = [];
foreach ($stmt->fetchAll() as $row) {
    $stats[$row['role']] = (int)$row['total'];
}

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

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Kasir</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:var(--espresso);"><?= $stats['kasir'] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Manager</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:var(--espresso);"><?= $stats['manager'] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Gudang</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:var(--espresso);"><?= $stats['gudang'] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Total</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:var(--espresso);">
                <?= array_sum($stats) ?>
            </div>
        </div>
    </div>
</div>

<!-- Filter + Tambah -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <select name="role" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">Semua Role</option>
            <option value="kasir"   <?= $filter_role === 'kasir'   ? 'selected' : '' ?>>Kasir</option>
            <option value="manager" <?= $filter_role === 'manager' ? 'selected' : '' ?>>Manager</option>
            <option value="barista" <?= $filter_role === 'barista' ? 'selected' : '' ?>>Barista</option>
            <option value="gudang"  <?= $filter_role === 'gudang'  ? 'selected' : '' ?>>Gudang</option>
            <option value="finance" <?= $filter_role === 'finance' ? 'selected' : '' ?>>Finance</option>
        </select>
        <select name="outlet" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <option value="">Semua Outlet</option>
            <?php foreach ($outlets as $o): ?>
                <option value="<?= (int)$o['id'] ?>" <?= $filter_outlet === (int)$o['id'] ? 'selected' : '' ?>>
                    <?= e($o['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filter_role || $filter_outlet): ?>
            <a href="/tokopi/views/users.php" class="btn btn-sm btn-outline-secondary">Reset</a>
        <?php endif; ?>
    </form>

    <button class="btn btn-espresso" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pengguna
    </button>
</div>

<div class="card">
    <div class="d-flex justify-content-end p-2 border-bottom"><div class="table-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" class="form-control form-control-sm" placeholder="Cari pengguna…" aria-label="Cari pengguna" data-table-filter="#tbl_users"></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tbl_users" data-enhance data-page-size="25">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Nama</th>
                    <th width="150">Login</th>
                    <th width="130">Role</th>
                    <th width="200">Outlet</th>
                    <th width="110" class="text-center">Status</th>
                    <th data-nosort width="140" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Tidak ada pengguna dengan filter ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= e($u['nama']) ?></strong>
                                <?php if ($u['role'] === 'owner'): ?>
                                    <span class="badge bg-dark ms-1">Owner</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['email']): ?>
                                    <div style="font-size:.85rem;"><?= e($u['email']) ?></div>
                                    <small class="text-muted">Email + Password</small>
                                <?php elseif ($u['username']): ?>
                                    <code><?= e($u['username']) ?></code>
                                    <div><small class="text-muted">Username + PIN</small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $role_class = [
                                    'owner'   => 'bg-dark',
                                    'manager' => 'bg-primary',
                                    'kasir'   => 'bg-success',
                                    'barista' => 'bg-info text-dark',
                                    'gudang'  => 'bg-warning text-dark',
                                    'finance' => 'bg-secondary',
                                ];
                                ?>
                                <span class="badge <?= $role_class[$u['role']] ?? 'bg-secondary' ?>">
                                    <?= e(ucfirst($u['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'owner'): ?>
                                    <span class="text-muted small">Semua Outlet</span>
                                <?php elseif ($u['outlet_nama']): ?>
                                    <?= e($u['outlet_nama']) ?>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($u['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($u['role'] !== 'owner'): ?>
                                    <button class="btn btn-sm btn-outline-dark"
                                            onclick='openEdit(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning"
                                            onclick='openResetPassword(<?= (int)$u['id'] ?>, "<?= e($u['nama']) ?>", "<?= e($u['role']) ?>")'>
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="confirmDelete(<?= (int)$u['id'] ?>, '<?= e($u['nama']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="/tokopi/actions/user_action.php?action=add" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" required placeholder="contoh: Budi Santoso">
                </div>

                <div class="mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" id="add_role" class="form-select" required onchange="toggleAddFields()">
                        <option value="">— Pilih Role —</option>
                        <option value="kasir">Kasir (login pakai username + PIN)</option>
                        <option value="manager">Manager (login pakai email + password)</option>
                        <option value="barista">Barista (login pakai email + password)</option>
                        <option value="gudang">Gudang (login pakai email + password)</option>
                        <option value="finance">Finance (login pakai email + password)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Outlet <span class="text-danger">*</span></label>
                    <select name="outlet_id" class="form-select" required>
                        <option value="">— Pilih Outlet —</option>
                        <?php foreach ($outlets as $o): ?>
                            <option value="<?= (int)$o['id'] ?>"><?= e($o['nama']) ?> (<?= e($o['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Setiap pengguna hanya bisa akses 1 outlet.</small>
                </div>

                <!-- Field KHUSUS KASIR -->
                <div id="add_fields_kasir" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control"
                                   placeholder="contoh: kasir4"
                                   pattern="[a-z0-9_]+"
                                   title="Huruf kecil, angka, underscore">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">PIN <span class="text-danger">*</span></label>
                            <input type="text" name="pin" class="form-control"
                                   inputmode="numeric" pattern="[0-9]{4,6}"
                                   placeholder="4–6 digit angka">
                        </div>
                    </div>
                </div>

                <!-- Field KHUSUS NON-KASIR -->
                <div id="add_fields_email" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   placeholder="contoh: budi@tokopi.test">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" minlength="6"
                                   placeholder="minimal 6 karakter">
                        </div>
                    </div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="add_active" checked>
                    <label class="form-check-label" for="add_active">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/user_action.php?action=edit" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="role" id="edit_role_hidden">
            <div class="modal-header">
                <h5 class="modal-title">Edit Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" id="edit_role_display" class="form-control" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">Outlet <span class="text-danger">*</span></label>
                    <select name="outlet_id" id="edit_outlet_id" class="form-select" required>
                        <?php foreach ($outlets as $o): ?>
                            <option value="<?= (int)$o['id'] ?>"><?= e($o['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="edit_field_login">
                    <div class="mb-3">
                        <label class="form-label" id="edit_login_label">Username / Email</label>
                        <input type="text" name="login_id" id="edit_login_id" class="form-control">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah.</small>
                    </div>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                    <label class="form-check-label" for="edit_is_active">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL RESET PASSWORD -->
<div class="modal fade" id="modalReset" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/user_action.php?action=reset_password" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="reset_id">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password / PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Reset <span id="reset_label">Password</span> untuk <strong id="reset_nama"></strong>:</p>
                <div class="mb-3">
                    <label class="form-label" id="reset_input_label">Password Baru</label>
                    <input type="text" name="new_password" class="form-control" required minlength="4">
                    <small class="text-muted" id="reset_hint"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning">Reset</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HAPUS -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog">
        <form action="/tokopi/actions/user_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="hapus_id">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin menghapus pengguna <strong id="hapus_nama"></strong>?</p>
                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Pengguna yang sudah punya riwayat transaksi tidak bisa dihapus.
                    Nonaktifkan saja.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAddFields() {
    const role = document.getElementById('add_role').value;
    const kasirFields = document.getElementById('add_fields_kasir');
    const emailFields = document.getElementById('add_fields_email');

    if (role === 'kasir') {
        kasirFields.style.display = 'block';
        emailFields.style.display = 'none';
        document.querySelector('#add_fields_kasir input[name=username]').required = true;
        document.querySelector('#add_fields_kasir input[name=pin]').required = true;
        document.querySelector('#add_fields_email input[name=email]').required = false;
        document.querySelector('#add_fields_email input[name=password]').required = false;
    } else if (role) {
        kasirFields.style.display = 'none';
        emailFields.style.display = 'block';
        document.querySelector('#add_fields_kasir input[name=username]').required = false;
        document.querySelector('#add_fields_kasir input[name=pin]').required = false;
        document.querySelector('#add_fields_email input[name=email]').required = true;
        document.querySelector('#add_fields_email input[name=password]').required = true;
    } else {
        kasirFields.style.display = 'none';
        emailFields.style.display = 'none';
    }
}

function openEdit(data) {
    document.getElementById('edit_id').value           = data.id;
    document.getElementById('edit_role_hidden').value  = data.role;
    document.getElementById('edit_nama').value         = data.nama;
    document.getElementById('edit_role_display').value = data.role.charAt(0).toUpperCase() + data.role.slice(1);
    document.getElementById('edit_outlet_id').value    = data.outlet_id || '';
    document.getElementById('edit_is_active').checked  = data.is_active == 1;

    const isKasir = data.role === 'kasir';
    document.getElementById('edit_login_label').textContent = isKasir ? 'Username' : 'Email';
    document.getElementById('edit_login_id').value = isKasir ? (data.username || '') : (data.email || '');

    new bootstrap.Modal('#modalEdit').show();
}

function openResetPassword(id, nama, role) {
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_nama').textContent = nama;

    const isKasir = role === 'kasir';
    document.getElementById('reset_label').textContent      = isKasir ? 'PIN' : 'Password';
    document.getElementById('reset_input_label').textContent = isKasir ? 'PIN Baru' : 'Password Baru';
    document.getElementById('reset_hint').textContent = isKasir
        ? '4–6 digit angka.'
        : 'Minimal 6 karakter.';

    new bootstrap.Modal('#modalReset').show();
}

function confirmDelete(id, nama) {
    document.getElementById('hapus_id').value = id;
    document.getElementById('hapus_nama').textContent = nama;
    new bootstrap.Modal('#modalHapus').show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
