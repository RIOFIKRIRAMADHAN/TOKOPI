<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','gudang']);

$page_title = 'Resep';
$active_menu = 'recipes';
$tenant_id = current_tenant_id();

// Ambil semua varian + produk + jumlah resep items + HPP (dihitung di query)
$stmt = $pdo->prepare("
    SELECT
        v.id AS variant_id,
        v.sku,
        v.nama AS variant_nama,
        v.harga_jual,
        p.id AS product_id,
        p.nama AS product_nama,
        c.nama AS kategori_nama,
        r.id AS recipe_id,
        r.yield_qty,
        (SELECT COUNT(*) FROM recipe_items ri WHERE ri.recipe_id = r.id) AS total_bahan,
        (SELECT COALESCE(SUM(ri.qty * i.avg_cost), 0)
            FROM recipe_items ri
            JOIN ingredients i ON i.id = ri.ingredient_id
            WHERE ri.recipe_id = r.id
        ) AS hpp_total
    FROM product_variants v
    JOIN products p ON p.id = v.product_id
    JOIN categories c ON c.id = p.category_id
    LEFT JOIN recipes r ON r.variant_id = v.id
    WHERE p.tenant_id = ? AND p.is_active = 1 AND v.is_active = 1
    ORDER BY p.nama ASC, v.nama ASC
");
$stmt->execute([$tenant_id]);
$variants = $stmt->fetchAll();

// Stats
$total_varian = count($variants);
$sudah_resep = 0;
$belum_resep = 0;
foreach ($variants as $v) {
    if ($v['recipe_id']) $sudah_resep++;
    else $belum_resep++;
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

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Total Varian</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:var(--espresso);"><?= $total_varian ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Sudah Ada Resep</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:#2E7D5B;"><?= $sudah_resep ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-muted small text-uppercase">Belum Ada Resep</div>
            <div class="money" style="font-size:1.5rem; font-weight:700; color:#A83232;"><?= $belum_resep ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Daftar Resep per Varian</span>
        <small class="text-muted">Klik "Kelola Resep" untuk mengatur bahan</small>
    </div>
    <div class="d-flex justify-content-end p-2 border-bottom"><div class="table-search"><i class="bi bi-search" aria-hidden="true"></i><input type="search" class="form-control form-control-sm" placeholder="Cari resep…" aria-label="Cari resep" data-table-filter="#tbl_recipes"></div></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tbl_recipes" data-enhance data-page-size="25">
            <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Produk / Varian</th>
                    <th width="140">Kategori</th>
                    <th width="160" class="text-end">Harga Jual</th>
                    <th width="160" class="text-end">HPP</th>
                    <th width="140" class="text-end">Margin</th>
                    <th width="130" class="text-center">Status</th>
                    <th data-nosort width="140" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($variants)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Belum ada varian produk. Buat produk & varian dulu di menu Produk.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($variants as $i => $v): ?>
                        <?php
                            $harga = (float)$v['harga_jual'];
                            $hpp = (float)$v['hpp_total'];
                            $margin = $harga - $hpp;
                            $margin_pct = $harga > 0 ? ($margin / $harga) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <div style="font-weight:600; color: var(--espresso);"><?= e($v['product_nama']) ?></div>
                                <small class="text-muted"><?= e($v['variant_nama']) ?> · <code><?= e($v['sku']) ?></code></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark"><?= e($v['kategori_nama']) ?></span>
                            </td>
                            <td class="text-end money"><?= rupiah($harga) ?></td>
                            <td class="text-end money">
                                <?php if ($v['recipe_id']): ?>
                                    <?= rupiah($hpp) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end money">
                                <?php if ($v['recipe_id'] && $harga > 0): ?>
                                    <div><?= rupiah($margin) ?></div>
                                    <small class="<?= $margin_pct >= 50 ? 'text-success' : ($margin_pct >= 30 ? 'text-warning' : 'text-danger') ?>">
                                        <?= number_format($margin_pct, 1) ?>%
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($v['recipe_id'] && (int)$v['total_bahan'] > 0): ?>
                                    <span class="badge bg-success"><?= (int)$v['total_bahan'] ?> bahan</span>
                                <?php elseif ($v['recipe_id']): ?>
                                    <span class="badge bg-warning text-dark">Resep kosong</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Belum diset</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/tokopi/views/recipe_detail.php?variant_id=<?= (int)$v['variant_id'] ?>"
                                   class="btn btn-sm btn-espresso">
                                    <i class="bi bi-journal-text me-1"></i>
                                    <?= $v['recipe_id'] ? 'Kelola' : 'Buat' ?> Resep
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>