<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'kasir']);

$page_title = 'POS / Kasir';
$active_menu = 'pos';
$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

// Kasir WAJIB punya outlet
if (!$outlet_id && is_role('kasir')) {
    die('Kasir belum di-assign ke outlet. Hubungi owner.');
}

// Kasir WAJIB buka shift dulu sebelum transaksi
if (is_role('kasir')) {
    $stmt = $pdo->prepare("SELECT id FROM shifts WHERE cashier_id = ? AND status = 'OPEN' LIMIT 1");
    $stmt->execute([current_user_id()]);
    if (!$stmt->fetch()) {
        flash_set('error', 'Buka shift dulu sebelum mulai transaksi.');
        redirect('/tokopi/views/shift.php');
    }
}

// Kalau owner akses POS tanpa pilih outlet, pilih outlet pertama
if (!$outlet_id) {
    $stmt = $pdo->prepare("SELECT id, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id LIMIT 1");
    $stmt->execute([$tenant_id]);
    $first_outlet = $stmt->fetch();
    if (!$first_outlet) {
        die('Belum ada outlet aktif.');
    }
    $outlet_id = $first_outlet['id'];
}

// Info outlet
$stmt = $pdo->prepare("SELECT id, nama FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$outlet = $stmt->fetch();

// Ambil daftar outlet untuk dropdown (khusus owner)
$outlets_list = [];
if (is_owner()) {
    $stmt = $pdo->prepare("SELECT id, code, nama FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
    $stmt->execute([$tenant_id]);
    $outlets_list = $stmt->fetchAll();
}

// Ambil kategori + varian yang aktif
$stmt = $pdo->prepare("
    SELECT c.id AS category_id, c.nama AS category_nama, c.sort_order
    FROM categories c
    WHERE c.tenant_id = ? AND c.is_active = 1
    ORDER BY c.sort_order, c.nama
");
$stmt->execute([$tenant_id]);
$categories = $stmt->fetchAll();

// Ambil semua varian aktif dengan produknya
$stmt = $pdo->prepare("
    SELECT
        v.id AS variant_id, v.nama AS variant_nama, v.sku, v.harga_jual, v.temperature, v.size,
        p.id AS product_id, p.nama AS product_nama, p.category_id
    FROM product_variants v
    JOIN products p ON p.id = v.product_id
    WHERE p.tenant_id = ? AND p.is_active = 1 AND v.is_active = 1
    ORDER BY p.nama, v.nama
");
$stmt->execute([$tenant_id]);
$all_variants = $stmt->fetchAll();

// Ambil stok semua bahan di outlet ini
$stmt = $pdo->prepare("
    SELECT i.id AS ingredient_id, COALESCE(s.qty, 0) AS qty
    FROM ingredients i
    LEFT JOIN stocks s ON s.ingredient_id = i.id AND s.outlet_id = ?
    WHERE i.tenant_id = ? AND i.is_active = 1
");
$stmt->execute([$outlet_id, $tenant_id]);
$stok_map = [];
foreach ($stmt->fetchAll() as $row) {
    $stok_map[(int)$row['ingredient_id']] = (float)$row['qty'];
}

// Ambil resep tiap varian
$stmt = $pdo->prepare("
    SELECT r.variant_id, ri.ingredient_id, ri.qty, r.yield_qty
    FROM recipes r
    JOIN recipe_items ri ON ri.recipe_id = r.id
    JOIN product_variants v ON v.id = r.variant_id
    JOIN products p ON p.id = v.product_id
    WHERE p.tenant_id = ?
");
$stmt->execute([$tenant_id]);
$recipe_map = [];
foreach ($stmt->fetchAll() as $row) {
    $vid = (int)$row['variant_id'];
    if (!isset($recipe_map[$vid])) $recipe_map[$vid] = [];
    $recipe_map[$vid][] = $row;
}

// Bangun resep per varian (untuk validasi stok di JS)
$recipe_js = [];
foreach ($recipe_map as $vid => $items) {
    $recipe_js[$vid] = [];
    foreach ($items as $ri) {
        $per_unit = (float)$ri['qty'] / max((float)$ri['yield_qty'], 1);
        $recipe_js[$vid][] = [
            'ingredient_id' => (int)$ri['ingredient_id'],
            'qty' => $per_unit,
        ];
    }
}

// Bangun stok per bahan (untuk validasi di JS)
$stok_js = [];
foreach ($stok_map as $iid => $qty) {
    $stok_js[$iid] = (float)$qty;
}

// Bangun nama bahan (untuk pesan error)
$stmt = $pdo->prepare("SELECT id, nama, base_unit FROM ingredients WHERE tenant_id = ? AND is_active = 1");
$stmt->execute([$tenant_id]);
$bahan_names = [];
foreach ($stmt->fetchAll() as $b) {
    $bahan_names[(int)$b['id']] = [
        'nama' => $b['nama'],
        'unit' => $b['base_unit'],
    ];
}

// Hitung max qty per varian
$max_qty_map = [];
foreach ($all_variants as $v) {
    $vid = (int)$v['variant_id'];
    $max = PHP_INT_MAX;

    if (!isset($recipe_map[$vid]) || empty($recipe_map[$vid])) {
        // Tidak ada resep → unlimited (pakai hpp_manual, tidak ada tracking stok)
        $max_qty_map[$vid] = 999;
        continue;
    }

    foreach ($recipe_map[$vid] as $ri) {
        $stok = $stok_map[(int)$ri['ingredient_id']] ?? 0;
        $per_unit = (float)$ri['qty'] / max((float)$ri['yield_qty'], 1);
        if ($per_unit <= 0) continue;
        $possible = (int)floor($stok / $per_unit);
        if ($possible < $max) $max = $possible;
    }
    $max_qty_map[$vid] = max(0, $max === PHP_INT_MAX ? 999 : $max);
}

// Kelompok per kategori
$variants_by_category = [];
foreach ($all_variants as $v) {
    $variants_by_category[$v['category_id']][] = $v;
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .pos-wrapper {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 1rem;
        height: calc(100vh - 130px);
    }

    @media (max-width: 992px) {
        .pos-wrapper {
            grid-template-columns: 1fr;
            height: auto;
        }
    }

    .product-panel {
        overflow-y: auto;
        padding-right: .5rem;
    }

    .cart-panel {
        background: #fff;
        border: 1px solid var(--sand);
        border-radius: 1rem;
        display: flex;
        flex-direction: column;
        max-height: 100%;
    }

    .cart-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--sand);
    }

    .cart-body {
        flex: 1;
        overflow-y: auto;
        padding: .5rem 1.25rem;
    }

    .cart-footer {
        padding: 1rem 1.25rem;
        border-top: 1px solid var(--sand);
        background: #FAF9F5;
        border-radius: 0 0 1rem 1rem;
    }

    .category-pills {
        display: flex;
        gap: .5rem;
        overflow-x: auto;
        padding-bottom: .5rem;
        margin-bottom: 1rem;
    }

    .category-pills::-webkit-scrollbar {
        height: 4px;
    }

    .category-pills::-webkit-scrollbar-thumb {
        background: var(--sand);
        border-radius: 4px;
    }

    .cat-pill {
        white-space: nowrap;
        padding: .5rem 1rem;
        background: #fff;
        border: 1px solid var(--sand);
        border-radius: 999px;
        cursor: pointer;
        font-size: .875rem;
        font-weight: 500;
        transition: .15s;
    }

    .cat-pill:hover {
        border-color: var(--espresso);
    }

    .cat-pill.active {
        background: var(--espresso);
        color: #fff;
        border-color: var(--espresso);
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: .75rem;
    }

    .product-card {
        background: #fff;
        border: 1px solid var(--sand);
        border-radius: .875rem;
        padding: .875rem;
        cursor: pointer;
        transition: .15s;
        display: flex;
        flex-direction: column;
        gap: .375rem;
    }

    .product-card:hover {
        border-color: var(--espresso);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(65, 45, 21, .08);
    }

    .product-card:active {
        transform: scale(.97);
    }

    .product-card .prod-name {
        font-weight: 600;
        font-size: .875rem;
        color: var(--espresso);
        line-height: 1.2;
    }

    .product-card .variant-name {
        font-size: .75rem;
        color: #6c757d;
    }

    .product-card .price {
        font-weight: 700;
        color: var(--espresso);
        font-family: 'JetBrains Mono', monospace;
        font-size: .875rem;
        margin-top: auto;
    }

    .cart-item {
        display: flex;
        gap: .75rem;
        padding: .75rem 0;
        border-bottom: 1px dashed var(--sand);
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .cart-item .info {
        flex: 1;
        min-width: 0;
    }

    .cart-item .name {
        font-weight: 600;
        font-size: .85rem;
        color: var(--espresso);
        line-height: 1.2;
    }

    .cart-item .variant {
        font-size: .7rem;
        color: #6c757d;
    }

    .qty-controls {
        display: flex;
        align-items: center;
        gap: .35rem;
        margin-top: .35rem;
    }

    .qty-btn {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        border: 1px solid var(--sand);
        background: #fff;
        font-weight: 700;
        font-size: .8rem;
        line-height: 1;
        color: var(--espresso);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .qty-btn:hover {
        background: var(--espresso);
        color: #fff;
        border-color: var(--espresso);
    }

    .qty-input {
        width: 40px;
        text-align: center;
        border: 1px solid var(--sand);
        border-radius: 6px;
        font-size: .8rem;
        padding: 2px;
    }

    .cart-item .subtotal {
        font-weight: 700;
        color: var(--espresso);
        font-family: 'JetBrains Mono', monospace;
        font-size: .85rem;
        white-space: nowrap;
    }

    .empty-cart {
        text-align: center;
        padding: 3rem 1rem;
        color: #adb5bd;
    }

    .grand-total {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 700;
        color: var(--espresso);
        font-size: 1.5rem;
    }

    .btn-checkout {
        background: var(--espresso);
        color: #fff;
        font-weight: 700;
        padding: .875rem;
        border-radius: .75rem;
        width: 100%;
        border: none;
        font-size: 1rem;
    }

    .btn-checkout:hover:not(:disabled) {
        background: #2a1d0e;
        color: #fff;
    }

    .btn-checkout:disabled {
        opacity: .5;
    }

    .product-card {
        position: relative;
    }

    .product-card-disabled {
        opacity: 0.5;
        cursor: not-allowed !important;
        filter: grayscale(0.7);
    }

    .product-card-disabled:hover {
        transform: none !important;
        border-color: var(--sand) !important;
        box-shadow: none !important;
    }

    .stock-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
        letter-spacing: 0.03em;
    }

    .stock-badge-habis {
        background: #A83232;
        color: #fff;
    }

    .stock-badge-terbatas {
        background: #B8860B;
        color: #fff;
    }
</style>

<div class="pos-wrapper">

    <!-- KIRI: PRODUK -->
    <div class="product-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Pilih Produk</h5>
                <?php if (is_owner() && !empty($outlets_list)): ?>
                    <div class="mt-1">
                        <label class="text-muted small me-2">Outlet aktif:</label>
                        <select class="form-select form-select-sm d-inline-block"
                            style="width: auto;"
                            onchange="switchOutlet(this.value)">
                            <?php foreach ($outlets_list as $o): ?>
                                <option value="<?= (int)$o['id'] ?>" <?= $outlet_id == $o['id'] ? 'selected' : '' ?>>
                                    <?= e($o['nama']) ?> (<?= e($o['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <small class="text-muted">Outlet: <strong><?= e($outlet['nama']) ?></strong></small>
                <?php endif; ?>
            </div>
        </div>

        <div class="category-pills">
            <div class="cat-pill active" data-cat="all" onclick="filterCategory('all', this)">
                Semua
            </div>
            <?php foreach ($categories as $c): ?>
                <div class="cat-pill" data-cat="<?= (int)$c['category_id'] ?>"
                    onclick="filterCategory(<?= (int)$c['category_id'] ?>, this)">
                    <?= e($c['category_nama']) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="product-grid" id="product-grid">
            <?php foreach ($all_variants as $v): ?>
                <?php
                $vid = (int)$v['variant_id'];
                $max_qty = $max_qty_map[$vid] ?? 0;
                $is_habis = $max_qty <= 0;
                $is_terbatas = !$is_habis && $max_qty <= 5;
                ?>
                <div class="product-card <?= $is_habis ? 'product-card-disabled' : '' ?>"
                    data-cat="<?= (int)$v['category_id'] ?>"
                    data-variant-id="<?= $vid ?>"
                    data-max-qty="<?= $max_qty ?>"
                    <?php if (!$is_habis): ?>
                    onclick='addToCart(<?= json_encode($v, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                    <?php endif; ?>>
                    <div class="prod-name"><?= e($v['product_nama']) ?></div>
                    <div class="variant-name">
                        <?= e($v['variant_nama']) ?>
                        <?php if ($v['temperature'] === 'ICED'): ?>
                            <i class="bi bi-snow text-info"></i>
                        <?php elseif ($v['temperature'] === 'HOT'): ?>
                            <i class="bi bi-fire text-danger"></i>
                        <?php endif; ?>
                    </div>
                    <div class="price"><?= rupiah($v['harga_jual']) ?></div>

                    <?php if ($is_habis): ?>
                        <div class="stock-badge stock-badge-habis">HABIS</div>
                    <?php elseif ($is_terbatas): ?>
                        <div class="stock-badge stock-badge-terbatas">SISA <?= $max_qty ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (empty($all_variants)): ?>
                <div class="text-muted text-center py-5" style="grid-column: 1 / -1;">
                    <i class="bi bi-inbox" style="font-size:2rem;"></i>
                    <p class="mt-2">Belum ada produk aktif.<br>Tambahkan produk & varian dulu di menu Produk.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- KANAN: CART -->
    <div class="cart-panel">
        <div class="cart-header d-flex justify-content-between align-items-center">
            <div>
                <strong style="color: var(--espresso);">
                    <i class="bi bi-cart3 me-1"></i> Keranjang
                </strong>
                <div class="small text-muted" id="cart-count">0 item</div>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="clearCart()" id="btn-clear" style="display:none;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="cart-body" id="cart-body">
            <div class="empty-cart">
                <i class="bi bi-cart-x" style="font-size:2.5rem;"></i>
                <p class="mt-2 mb-0">Keranjang kosong</p>
                <small>Klik produk untuk menambahkan</small>
            </div>
        </div>

        <div class="cart-footer">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Total</span>
                <span class="grand-total" id="grand-total">Rp 0</span>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Metode Bayar</label>
                <select class="form-select form-select-sm" id="payment-method">
                    <option value="CASH">💵 Tunai</option>
                    <option value="QRIS">📱 QRIS (Midtrans)</option>
                </select>
            </div>

            <button class="btn-checkout" onclick="openCheckout()" id="btn-checkout" disabled>
                <i class="bi bi-check-circle me-1"></i> Bayar
            </button>
        </div>
    </div>
</div>

<!-- MODAL CASH PAYMENT -->
<div class="modal fade" id="modalCash" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pembayaran Tunai</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Total Belanja</span>
                    <span class="grand-total" id="cash-total">Rp 0</span>
                </div>

                <label class="form-label">Uang Diterima</label>
                <div class="input-group mb-3">
                    <span class="input-group-text">Rp</span>
                    <input type="number" id="cash-input" class="form-control form-control-lg"
                        min="0" step="1000" placeholder="0" autocomplete="off">
                </div>

                <div class="d-flex gap-2 flex-wrap mb-3" id="quick-cash">
                    <!-- Quick cash buttons di-inject via JS -->
                </div>

                <div class="p-3 rounded" style="background:#FAF9F5;">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Kembalian</span>
                        <span class="money fw-bold" id="cash-change" style="font-size:1.25rem; color:#2E7D5B;">Rp 0</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-espresso" onclick="submitCash()" id="btn-submit-cash" disabled>
                    <i class="bi bi-check-lg me-1"></i> Konfirmasi Bayar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL QRIS PAYMENT -->
<div class="modal fade" id="modalQris" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-qr-code me-1"></i> Pembayaran QRIS
                </h5>
                <button type="button" class="btn-close" onclick="cancelQris()"></button>
            </div>
            <div class="modal-body text-center">
                <div class="d-flex justify-content-between align-items-center mb-3 px-3">
                    <span class="text-muted">Total</span>
                    <span class="grand-total" id="qris-total">Rp 0</span>
                </div>

                <div id="qris-loading" class="py-5">
                    <div class="spinner-border text-dark" role="status"></div>
                    <p class="text-muted mt-3 mb-0">Menghubungi Midtrans...</p>
                </div>

                <div id="qris-ready" style="display:none;">
                    <div class="qr-container mx-auto mb-3">
                        <img id="qris-image" src="" alt="QRIS" style="max-width:280px; width:100%;">
                    </div>

                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Scan QRIS dengan aplikasi apapun</strong>
                        <br>GoPay, OVO, Dana, ShopeePay, LinkAja, m-Banking, dll.
                    </div>

                    <div id="qris-status-waiting">
                        <div class="spinner-border spinner-border-sm text-dark me-2" role="status"></div>
                        <span class="text-muted">Menunggu pembayaran...</span>
                    </div>

                    <div id="qris-status-paid" style="display:none;">
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            <strong>Pembayaran diterima!</strong>
                            <br><small>Mengarahkan ke struk...</small>
                        </div>
                    </div>
                </div>

                <div id="qris-error" style="display:none;">
                    <div class="alert alert-danger" id="qris-error-msg"></div>
                </div>

                <div id="qris-order-info" class="small text-muted mt-3" style="display:none;">
                    Order: <strong id="qris-order-no"></strong>
                </div>
                <!-- <div id="qris-dev-tools" class="mt-3" style="display:none;">
                    <hr>
                    <small class="text-muted">🧪 Dev Tools (localhost only)</small>
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-success flex-fill" onclick="devMarkPaid()">
                            ✅ Mark PAID (Mock)
                        </button>
                        <button type="button" class="btn btn-sm btn-danger flex-fill" onclick="devMarkCancel()">
                            ❌ Mark CANCELLED
                        </button>
                    </div>
                </div> -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="cancelQris()">
                    Batalkan
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .qr-container {
        max-width: 320px;
        padding: 16px;
        background: #fff;
        border: 2px solid var(--sand);
        border-radius: 1rem;
    }
</style>

<script>
    // Data dari server untuk validasi stok di keranjang
    const RECIPE_MAP = <?= json_encode($recipe_js, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const STOK_MAP = <?= json_encode($stok_js, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const BAHAN_INFO = <?= json_encode($bahan_names, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    // =========================================
    // CART STATE (in-memory)
    // =========================================
    let cart = {}; // { variant_id: { variant_id, product_nama, variant_nama, sku, harga_jual, qty } }
    // =========================================
    // QRIS PAYMENT
    // =========================================
    let modalQris = null;
    let qrisPollInterval = null;
    let qrisOrderId = null;

    async function openQris() {
        const items = Object.values(cart).map(it => ({
            variant_id: it.variant_id,
            qty: it.qty
        }));

        if (items.length === 0) return;

        // Reset modal state
        document.getElementById('qris-loading').style.display = 'block';
        document.getElementById('qris-ready').style.display = 'none';
        document.getElementById('qris-error').style.display = 'none';
        document.getElementById('qris-order-info').style.display = 'none';
        document.getElementById('qris-status-waiting').style.display = 'block';
        document.getElementById('qris-status-paid').style.display = 'none';

        if (!modalQris) modalQris = new bootstrap.Modal('#modalQris');
        modalQris.show();

        const total = Object.values(cart).reduce((s, it) => s + it.harga_jual * it.qty, 0);
        document.getElementById('qris-total').textContent = rupiah(total);

        try {
            const res = await fetch('/tokopi/api/pos_qris_create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    items: items,
                    payment_method: 'QRIS',
                    client_request_id: crypto.randomUUID(),
                    channel: 'DINE_IN'
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                showQrisError(data.message || 'Gagal membuat QRIS');
                return;
            }

            qrisOrderId = data.order_id;

            document.getElementById('qris-loading').style.display = 'none';
            document.getElementById('qris-ready').style.display = 'block';
            document.getElementById('qris-order-info').style.display = 'block';
            document.getElementById('qris-order-no').textContent = data.order_no;
            // document.getElementById('qris-dev-tools').style.display = 'block';
            document.getElementById('qris-image').src = data.qr_url;
            // Auto-copy QR string ke clipboard untuk testing sandbox
            // if (data.qr_string) {
            //     navigator.clipboard.writeText(data.qr_string).then(() => {
            //         console.log('✅ QR string copied to clipboard:', data.qr_string);
            //     }).catch(err => {
            //         console.log('❌ Clipboard error, manual copy:', data.qr_string);
            //     });
            // }
            document.getElementById('qris-total').textContent = rupiah(data.grand_total);

            // Mulai polling
            startQrisPolling(qrisOrderId);

        } catch (e) {
            showQrisError('Error: ' + e.message);
        }
    }

    function showQrisError(msg) {
        document.getElementById('qris-loading').style.display = 'none';
        document.getElementById('qris-ready').style.display = 'none';
        document.getElementById('qris-error').style.display = 'block';
        document.getElementById('qris-error-msg').textContent = msg;
    }

    function startQrisPolling(orderId) {
        if (qrisPollInterval) clearInterval(qrisPollInterval);

        qrisPollInterval = setInterval(async () => {
            try {
                const res = await fetch('/tokopi/api/pos_qris_check.php?order_id=' + orderId);
                const data = await res.json();

                if (data.status === 'PAID') {
                    if (qrisPollInterval) {
                        clearInterval(qrisPollInterval);
                        qrisPollInterval = null;
                    }

                    document.getElementById('qris-status-waiting').style.display = 'none';
                    document.getElementById('qris-status-paid').style.display = 'block';

                    cart = {};

                    setTimeout(() => {
                        modalQris.hide();
                        window.location.href = '/tokopi/views/receipt.php?order_id=' + qrisOrderId + '&autoprint=1';
                    }, 1200);
                } else if (data.status === 'CANCELLED' || data.status === 'EXPIRED') {
                    clearInterval(qrisPollInterval);
                    qrisPollInterval = null;
                    showQrisError('QRIS sudah ' + data.status.toLowerCase() + '. Silakan buat ulang.');
                }
            } catch (e) {
                // Abaikan error sementara, biarkan polling lanjut
            }
        }, 1500);
    }

    async function cancelQris() {
        if (qrisOrderId) {
            if (!confirm('Batalkan pembayaran QRIS ini? Order akan di-cancel.')) return;

            try {
                await fetch('/tokopi/api/pos_qris_cancel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: qrisOrderId
                    })
                });
            } catch (e) {}
        }

        if (qrisPollInterval) {
            clearInterval(qrisPollInterval);
            qrisPollInterval = null;
        }
        qrisOrderId = null;

        if (modalQris) modalQris.hide();
    }

    // function devMarkPaid() {
    //     if (!qrisOrderId) return;
    //     if (!confirm('DEV: Mark order ini PAID + potong stok?')) return;
    //     window.open('/tokopi/dev/mock_pay.php?order_id=' + qrisOrderId, '_blank');
    // }

    // function devMarkCancel() {
    //     if (!qrisOrderId) return;
    //     if (!confirm('DEV: Batalkan order ini?')) return;
    //     window.location.href = '/tokopi/dev/mock_pay.php?order_id=' + qrisOrderId;
    // }

    function switchOutlet(outletId) {
        if (!outletId) return;

        // Kalau cart tidak kosong, konfirmasi dulu
        if (Object.keys(cart).length > 0) {
            if (!confirm('Keranjang masih ada isi. Ganti outlet akan mengosongkan keranjang. Lanjutkan?')) {
                // Balikkan dropdown ke pilihan sebelumnya
                location.reload();
                return;
            }
        }

        window.location.href = '/tokopi/actions/switch_outlet.php?outlet_id=' + outletId + '&redirect=' + encodeURIComponent('/tokopi/views/pos.php');
    }
    // =========================================
    // VALIDASI STOK AGREGAT
    // =========================================

    /**
     * Hitung total kebutuhan tiap bahan di keranjang saat ini.
     * Return: { ingredient_id: qty_total }
     */
    function hitungKebutuhanBahan(cartData) {
        const kebutuhan = {};
        for (const vid in cartData) {
            const qty_order = cartData[vid].qty;
            const resep = RECIPE_MAP[vid] || [];
            for (const item of resep) {
                const iid = item.ingredient_id;
                kebutuhan[iid] = (kebutuhan[iid] || 0) + (item.qty * qty_order);
            }
        }
        return kebutuhan;
    }

    /**
     * Cek apakah keranjang masih muat stok.
     * Return: { ok: true } atau { ok: false, message: "..." }
     */
    function cekStokKeranjang(cartData) {
        const kebutuhan = hitungKebutuhanBahan(cartData);

        for (const iid in kebutuhan) {
            const butuh = kebutuhan[iid];
            const tersedia = STOK_MAP[iid] ?? 0;

            // Untuk hitungan pecahan, toleransi 0.0001
            if (butuh > tersedia + 0.0001) {
                const info = BAHAN_INFO[iid] || {
                    nama: 'Bahan',
                    unit: ''
                };
                return {
                    ok: false,
                    message: `Stok ${info.nama} tidak cukup. Butuh ${formatQty(butuh)} ${info.unit}, tersedia ${formatQty(tersedia)} ${info.unit}.`,
                };
            }
        }
        return {
            ok: true
        };
    }

    /**
     * Cek apakah menambah 1 qty varian tertentu akan melanggar stok.
     */
    function bisaTambah(variantId) {
        const test = JSON.parse(JSON.stringify(cart));
        if (test[variantId]) {
            test[variantId].qty++;
        } else {
            test[variantId] = {
                qty: 1
            };
        }

        // Butuh RECIPE_MAP untuk varian itu, kalau tidak ada resep → unlimited
        if (!RECIPE_MAP[variantId] || RECIPE_MAP[variantId].length === 0) {
            return {
                ok: true
            };
        }

        return cekStokKeranjang(test);
    }

    function formatQty(n) {
        // Kalau integer, tampilkan tanpa desimal
        if (Number.isInteger(n)) return n.toLocaleString('id-ID');
        return n.toLocaleString('id-ID', {
            maximumFractionDigits: 2
        });
    }

    // =========================================
    // RENDER
    // =========================================
    function renderCart() {
        const body = document.getElementById('cart-body');
        const count = document.getElementById('cart-count');
        const btnClear = document.getElementById('btn-clear');
        const btnCheckout = document.getElementById('btn-checkout');

        const items = Object.values(cart);

        if (items.length === 0) {
            body.innerHTML = `
            <div class="empty-cart">
                <i class="bi bi-cart-x" style="font-size:2.5rem;"></i>
                <p class="mt-2 mb-0">Keranjang kosong</p>
                <small>Klik produk untuk menambahkan</small>
            </div>`;
            count.textContent = '0 item';
            document.getElementById('grand-total').textContent = 'Rp 0';
            btnClear.style.display = 'none';
            btnCheckout.disabled = true;
            return;
        }

        let total = 0;
        let totalQty = 0;
        let html = '';

        items.forEach(it => {
            const subtotal = it.harga_jual * it.qty;
            total += subtotal;
            totalQty += it.qty;

            html += `
            <div class="cart-item">
                <div class="info">
                    <div class="name">${escapeHtml(it.product_nama)}</div>
                    <div class="variant">${escapeHtml(it.variant_nama)}</div>
                    <div class="qty-controls">
                        <button class="qty-btn" onclick="changeQty(${it.variant_id}, -1)">−</button>
                        <input class="qty-input" type="number" min="1" value="${it.qty}"
                               onchange="setQty(${it.variant_id}, this.value)">
                        <button class="qty-btn" onclick="changeQty(${it.variant_id}, 1)">+</button>
                        <button class="qty-btn ms-2" style="color:#A83232;"
                                onclick="removeItem(${it.variant_id})" title="Hapus">×</button>
                    </div>
                </div>
                <div class="subtotal">${rupiah(subtotal)}</div>
            </div>
        `;
        });

        body.innerHTML = html;
        count.textContent = totalQty + ' item';
        document.getElementById('grand-total').textContent = rupiah(total);
        btnClear.style.display = 'inline-block';
        btnCheckout.disabled = false;
    }

    // =========================================
    // CART ACTIONS
    // =========================================
    function addToCart(v) {
        const id = v.variant_id;

        // Ambil max qty dari DOM
        const card = document.querySelector(`.product-card[data-variant-id="${id}"]`);
        const maxQty = card ? parseInt(card.dataset.maxQty) : 0;

        const currentQty = cart[id] ? cart[id].qty : 0;

        if (maxQty <= 0) {
            alert('Stok habis untuk produk ini.');
            return;
        }
        if (currentQty + 1 > maxQty) {
            alert(`Stok terbatas. Maksimal ${maxQty} untuk produk ini.`);
            return;
        }

        if (cart[id]) {
            cart[id].qty++;
        } else {
            cart[id] = {
                variant_id: id,
                product_nama: v.product_nama,
                variant_nama: v.variant_nama,
                sku: v.sku,
                harga_jual: parseFloat(v.harga_jual),
                qty: 1
            };
        }
        renderCart();
    }

    function changeQty(id, delta) {
        if (!cart[id]) return;

        // Kalau menambah, cek validasi agregat
        if (delta > 0) {
            const check = bisaTambah(id);
            if (!check.ok) {
                alert(check.message);
                return;
            }
        }

        cart[id].qty += delta;
        if (cart[id].qty <= 0) delete cart[id];
        renderCart();
    }

    function setQty(id, val) {
        if (!cart[id]) return;
        const q = parseInt(val) || 1;

        // Validasi: cek apakah qty baru masih muat
        const test = JSON.parse(JSON.stringify(cart));
        test[id].qty = q;

        const check = cekStokKeranjang(test);
        if (!check.ok) {
            alert(check.message);
            renderCart(); // render ulang untuk kembalikan nilai input
            return;
        }

        cart[id].qty = Math.max(1, q);
        renderCart();
    }

    function removeItem(id) {
        delete cart[id];
        renderCart();
    }

    function clearCart() {
        if (!confirm('Kosongkan keranjang?')) return;
        cart = {};
        renderCart();
    }

    // =========================================
    // FILTER KATEGORI
    // =========================================
    function filterCategory(catId, el) {
        document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');

        document.querySelectorAll('.product-card').forEach(card => {
            if (catId === 'all' || card.dataset.cat == catId) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // =========================================
    // CHECKOUT
    // =========================================
    let modalCash = null;

    function openCheckout() {
        const items = Object.values(cart);
        if (items.length === 0) return;

        // Validasi akhir sebelum checkout
        const check = cekStokKeranjang(cart);
        if (!check.ok) {
            alert('Tidak bisa checkout:\n\n' + check.message);
            return;
        }

        const method = document.getElementById('payment-method').value;

        if (method === 'QRIS') {
            openQris();
        } else {
            openCash();
        }
    }

    function openCash() {
        const items = Object.values(cart);
        const total = items.reduce((s, it) => s + it.harga_jual * it.qty, 0);
        document.getElementById('cash-total').textContent = rupiah(total);
        document.getElementById('cash-input').value = '';
        document.getElementById('cash-change').textContent = 'Rp 0';
        document.getElementById('btn-submit-cash').disabled = true;

        const quick = document.getElementById('quick-cash');
        const options = [total, 20000, 50000, 100000].filter(v => v >= total);
        const uniq = [...new Set(options)].slice(0, 4);
        quick.innerHTML = uniq.map(v =>
            `<button type="button" class="btn btn-sm btn-outline-dark" onclick="setCash(${v})">${rupiah(v)}</button>`
        ).join('');

        if (!modalCash) modalCash = new bootstrap.Modal('#modalCash');
        modalCash.show();

        setTimeout(() => document.getElementById('cash-input').focus(), 300);
    }

    function setCash(val) {
        document.getElementById('cash-input').value = val;
        updateChange();
    }

    function updateChange() {
        const items = Object.values(cart);
        const total = items.reduce((s, it) => s + it.harga_jual * it.qty, 0);
        const input = parseFloat(document.getElementById('cash-input').value) || 0;
        const change = input - total;

        const el = document.getElementById('cash-change');
        if (change >= 0) {
            el.textContent = rupiah(change);
            el.style.color = '#2E7D5B';
            document.getElementById('btn-submit-cash').disabled = false;
        } else {
            el.textContent = '− ' + rupiah(-change);
            el.style.color = '#A83232';
            document.getElementById('btn-submit-cash').disabled = true;
        }
    }

    document.getElementById('cash-input').addEventListener('input', updateChange);

    // =========================================
    // SUBMIT
    // =========================================
    async function submitCash() {
        const items = Object.values(cart).map(it => ({
            variant_id: it.variant_id,
            qty: it.qty
        }));
        const cash = parseFloat(document.getElementById('cash-input').value) || 0;

        const btn = document.getElementById('btn-submit-cash');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

        try {
            const res = await fetch('/tokopi/api/pos_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    items: items,
                    payment_method: 'CASH',
                    cash_received: cash,
                    client_request_id: crypto.randomUUID(),
                    channel: 'DINE_IN'
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Konfirmasi Bayar';
                return;
            }

            // Clear cart & redirect ke struk
            cart = {};
            modalCash.hide();

            // Buka struk di tab baru, lalu reload POS
            window.open('/tokopi/views/receipt.php?order_id=' + data.order_id, '_blank');
            window.location.href = '/tokopi/views/pos.php?success=' + encodeURIComponent(data.order_no);
        } catch (e) {
            alert('Error: ' + e.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Konfirmasi Bayar';
        }
    }

    // =========================================
    // HELPERS
    // =========================================
    function rupiah(n) {
        return 'Rp ' + Math.round(n).toLocaleString('id-ID');
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // =========================================
    // INIT
    // =========================================
    renderCart();

    // Auto-refresh info stok saat window fokus
    window.addEventListener('focus', () => {});
</script>

<?php if (!empty($_GET['success'])): ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const notif = document.createElement('div');
            notif.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notif.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 320px;';
            notif.innerHTML = '<i class="bi bi-check-circle me-1"></i> Transaksi <strong><?= e($_GET['success']) ?></strong> berhasil!' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.body.appendChild(notif);
            setTimeout(() => notif.remove(), 5000);
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>