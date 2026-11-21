<?php
// views/modifiers.php — Opsi Menu (gula, susu, tambahan, dll.)
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'gudang']);

$page_title  = 'Opsi Menu';
$active_menu = 'modifiers';
$tenant_id   = current_tenant_id();

$st = $pdo->prepare("SELECT * FROM modifier_groups WHERE tenant_id = ? ORDER BY sort_order, id");
$st->execute([$tenant_id]);
$groups = $st->fetchAll();

$options = []; $links = [];
if ($groups) {
    $ids = array_column($groups, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT * FROM modifier_options WHERE group_id IN ($in) ORDER BY sort_order, id");
    $st->execute($ids);
    foreach ($st->fetchAll() as $o) $options[(int)$o['group_id']][] = $o;
    $st = $pdo->prepare("SELECT group_id, product_id FROM product_modifier_groups WHERE group_id IN ($in)");
    $st->execute($ids);
    foreach ($st->fetchAll() as $l) $links[(int)$l['group_id']][] = (int)$l['product_id'];
}

$st = $pdo->prepare("SELECT p.id, p.nama, c.nama AS kategori FROM products p JOIN categories c ON c.id = p.category_id WHERE p.tenant_id = ? AND p.is_active = 1 ORDER BY c.sort_order, c.nama, p.nama");
$st->execute([$tenant_id]);
$products = $st->fetchAll();
$products_by_cat = [];
foreach ($products as $p) $products_by_cat[$p['kategori']][] = $p;
$product_name = array_column($products, 'nama', 'id');

$success = flash_get('success');
$error   = flash_get('error');
require_once __DIR__ . '/../includes/header.php';

$scope_label = ['ALL' => '', 'HOT' => 'Hanya varian Hot', 'ICED' => 'Hanya varian Iced'];
$rule = function ($g) {
    $max = (int)$g['max_select'];
    if ((int)$g['is_required']) return $max > 1 ? "Wajib · pilih 1–$max" : 'Wajib · pilih 1';
    return $max > 1 ? "Opsional · maks $max" : 'Opsional · pilih 1';
};
?>

<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div><?php endif; ?>

<div class="page-head">
    <div>
        <h5 class="mb-0">Opsi Menu</h5>
        <small class="text-muted">Pilihan yang muncul saat pelanggan memesan, misalnya tingkat gula, jenis susu, atau tambahan shot. Harga tambahan otomatis ikut terhitung.</small>
    </div>
    <button class="btn btn-espresso" type="button" onclick="openGroup(null)"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Tambah Grup Opsi</button>
</div>

<?php if (!$groups): ?>
    <div class="card"><div class="empty"><i class="bi bi-ui-checks" aria-hidden="true"></i>Belum ada grup opsi. Mulai dengan "Tingkat gula" atau "Pilihan susu".</div></div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($groups as $g): $gid = (int)$g['id']; $opts = $options[$gid] ?? []; $pids = $links[$gid] ?? []; ?>
        <div class="col-xl-4 col-md-6">
            <div class="card h-100 <?= (int)$g['is_active'] ? '' : 'opacity-75' ?>">
                <div class="card-header d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div><?= e($g['nama']) ?> <?php if (!(int)$g['is_active']): ?><span class="badge bg-secondary ms-1">Nonaktif</span><?php endif; ?></div>
                        <small class="text-muted fw-normal"><?= e($rule($g)) ?></small>
                        <?php if (!empty($scope_label[$g['applies_to'] ?? 'ALL'])): ?>
                            <span class="badge <?= $g['applies_to'] === 'ICED' ? 'bg-info-subtle text-info-emphasis' : 'bg-danger-subtle text-danger-emphasis' ?> ms-1">
                                <i class="bi <?= $g['applies_to'] === 'ICED' ? 'bi-snow' : 'bi-cup-hot' ?>" aria-hidden="true"></i> <?= e($scope_label[$g['applies_to']]) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="button" class="btn btn-sm btn-outline-espresso" aria-label="Edit <?= e($g['nama']) ?>"
                            onclick='openGroup(<?= json_encode(["group" => $g, "options" => $opts, "products" => $pids], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?>)'><i class="bi bi-pencil" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" aria-label="Hapus <?= e($g['nama']) ?>"
                            onclick="confirmDeleteGroup(<?= $gid ?>, <?= e(json_encode($g['nama'])) ?>, <?= count($pids) ?>)"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($opts as $o): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2 <?= (int)$o['is_active'] ? '' : 'text-muted' ?>">
                            <span>
                                <?= e($o['nama']) ?>
                                <?php if ((int)$o['is_default']): ?><span class="badge bg-light text-dark border ms-1">default</span><?php endif; ?>
                                <?php if (!(int)$o['is_active']): ?><span class="badge bg-secondary ms-1">habis</span><?php endif; ?>
                            </span>
                            <span class="money small"><?= (float)$o['price_delta'] > 0 ? '+' . rupiah($o['price_delta']) : 'gratis' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="card-body pt-2 pb-3 small text-muted">
                    <i class="bi bi-cup-hot me-1" aria-hidden="true"></i>
                    <?php if ($pids): ?>
                        Dipakai di <?= count($pids) ?> produk:
                        <?= e(implode(', ', array_slice(array_map(function ($id) use ($product_name) { return $product_name[$id] ?? '?'; }, $pids), 0, 4))) ?><?= count($pids) > 4 ? ', …' : '' ?>
                    <?php else: ?>
                        Belum dipasang ke produk mana pun.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- MODAL GRUP -->
<div class="modal fade" id="modalGroup" tabindex="-1" aria-labelledby="modalGroupTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form action="/tokopi/actions/modifier_action.php?action=save" method="POST" class="modal-content" id="groupForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="g_id">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGroupTitle">Grup Opsi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="g_nama">Nama grup <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="g_nama" name="nama" maxlength="100" required placeholder="contoh: Tingkat gula">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="g_scope">Berlaku untuk</label>
                        <select class="form-select" id="g_scope" name="applies_to" aria-describedby="g_scope_help">
                            <option value="ALL">Semua varian</option>
                            <option value="ICED">Hanya varian Iced</option>
                            <option value="HOT">Hanya varian Hot</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="g_max">Maks pilihan</label>
                        <input type="number" class="form-control" id="g_max" name="max_select" min="1" max="10" value="1">
                    </div>
                    <div class="col-md-3 d-flex flex-column justify-content-end gap-1">
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="g_req" name="is_required" value="1"><label class="form-check-label" for="g_req">Wajib dipilih</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="g_active" name="is_active" value="1" checked><label class="form-check-label" for="g_active">Aktif</label></div>
                    </div>
                </div>

                <div class="form-text" id="g_scope_help">Contoh: "Tingkat es" → Hanya varian Iced, jadi tidak muncul saat pelanggan memilih Hot. Varian tanpa suhu (makanan) hanya mendapat grup "Semua varian".</div>

                <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                    <strong class="small text-uppercase text-muted">Pilihan</strong>
                    <button type="button" class="btn btn-sm btn-outline-espresso" onclick="addOptionRow()"><i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah pilihan</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-1">
                        <thead><tr><th>Nama pilihan</th><th style="width:190px;">Tambahan harga</th><th class="text-center" style="width:80px;">Default</th><th class="text-center" style="width:80px;">Tersedia</th><th style="width:44px;"></th></tr></thead>
                        <tbody id="optRows"></tbody>
                    </table>
                </div>
                <div class="form-text">Isi 0 untuk pilihan gratis. "Default" = otomatis terpilih untuk grup wajib. Matikan "Tersedia" bila bahannya sedang habis.</div>

                <div class="mt-4 mb-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <strong class="small text-uppercase text-muted">Pasang ke produk</strong>
                    <span class="small"><a href="#" onclick="toggleAllProducts(true);return false;">Pilih semua</a> · <a href="#" onclick="toggleAllProducts(false);return false;">Kosongkan</a></span>
                </div>
                <div class="row g-2" id="productChecks">
                    <?php foreach ($products_by_cat as $cat => $list): ?>
                        <div class="col-md-4">
                            <div class="small fw-bold mb-1"><?= e($cat) ?></div>
                            <?php foreach ($list as $p): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="product_ids[]" value="<?= (int)$p['id'] ?>" id="gp_<?= (int)$p['id'] ?>">
                                    <label class="form-check-label small" for="gp_<?= (int)$p['id'] ?>"><?= e($p['nama']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-espresso">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HAPUS -->
<div class="modal fade" id="modalDelGroup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/tokopi/actions/modifier_action.php?action=delete" method="POST" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="dg_id">
            <div class="modal-header"><h5 class="modal-title">Hapus grup opsi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <p class="mb-2">Hapus <strong id="dg_nama"></strong>?</p>
                <p class="small text-muted mb-0" id="dg_info"></p>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Hapus</button></div>
        </form>
    </div>
</div>

<script>
let optSeq = 0;
function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
function addOptionRow(o) {
    o = o || { id: 0, nama: '', price_delta: 0, is_active: 1, is_default: 0 };
    const k = 'k' + (optSeq++);
    const tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="hidden" name="options[' + k + '][id]" value="' + (+o.id || 0) + '">' +
        '<input type="text" class="form-control form-control-sm" name="options[' + k + '][nama]" maxlength="100" value="' + esc(o.nama) + '" placeholder="contoh: Oat milk" aria-label="Nama pilihan"></td>' +
        '<td><div class="input-group input-group-sm"><span class="input-group-text">+Rp</span><input type="number" class="form-control text-end" style="min-width:90px" name="options[' + k + '][price]" min="0" step="500" value="' + (Math.round(+o.price_delta) || 0) + '" aria-label="Tambahan harga"></div></td>' +
        '<td class="text-center"><input class="form-check-input" type="radio" name="default_option" value="' + k + '" ' + (+o.is_default ? 'checked' : '') + ' aria-label="Jadikan default"></td>' +
        '<td class="text-center"><input class="form-check-input" type="checkbox" name="options[' + k + '][is_active]" value="1" ' + (+o.is_active ? 'checked' : '') + ' aria-label="Tersedia"></td>' +
        '<td><button type="button" class="btn btn-sm btn-link text-danger p-1" aria-label="Hapus pilihan" onclick="this.closest(\'tr\').remove()"><i class="bi bi-x-lg"></i></button></td>';
    document.getElementById('optRows').appendChild(tr);
    if (!o.nama) tr.querySelector('input[type=text]').focus();
}
function toggleAllProducts(on) { document.querySelectorAll('#productChecks input').forEach(c => c.checked = on); }
function openGroup(data) {
    const g = data ? data.group : null;
    document.getElementById('modalGroupTitle').textContent = g ? 'Edit grup opsi' : 'Tambah grup opsi';
    document.getElementById('g_id').value = g ? g.id : '';
    document.getElementById('g_nama').value = g ? g.nama : '';
    document.getElementById('g_max').value = g ? g.max_select : 1;
    document.getElementById('g_scope').value = g && g.applies_to ? g.applies_to : 'ALL';
    document.getElementById('g_req').checked = g ? +g.is_required === 1 : false;
    document.getElementById('g_active').checked = g ? +g.is_active === 1 : true;
    document.getElementById('optRows').innerHTML = '';
    (data ? data.options : [{}, {}]).forEach(o => addOptionRow(o && o.nama !== undefined ? o : undefined));
    const pids = new Set((data ? data.products : []).map(Number));
    document.querySelectorAll('#productChecks input').forEach(c => c.checked = pids.has(+c.value));
    new bootstrap.Modal('#modalGroup').show();
    setTimeout(() => document.getElementById('g_nama').focus(), 300);
}
function confirmDeleteGroup(id, nama, count) {
    document.getElementById('dg_id').value = id;
    document.getElementById('dg_nama').textContent = nama;
    document.getElementById('dg_info').textContent = (count ? 'Grup ini dilepas dari ' + count + ' produk. ' : '') + 'Riwayat pesanan lama tetap menampilkan opsi yang dipilih saat itu.';
    new bootstrap.Modal('#modalDelGroup').show();
}
// Grup wajib sebaiknya punya default
document.getElementById('groupForm').addEventListener('submit', function (e) {
    const names = [...document.querySelectorAll('#optRows input[type=text]')].filter(i => i.value.trim());
    if (!names.length) { e.preventDefault(); alert('Tambahkan minimal satu pilihan.'); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
