<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Tanpa meja: produk tetap bisa dilihat, tapi tombol pesan diganti ajakan scan meja.
$has_table   = !empty($_SESSION['customer_table_id']);
$outlet_id   = (int)($_SESSION['customer_outlet_id'] ?? 0);
$table_nama  = $_SESSION['customer_table_nama'] ?? '';
$outlet_nama = $_SESSION['customer_outlet_nama'] ?? '';

if ($outlet_id > 0) {
    $stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
    $stmt->execute([$outlet_id]);
    $tenant_id = (int)$stmt->fetchColumn();
} else {
    $tenant_id = (int)$pdo->query("SELECT id FROM tenants ORDER BY id LIMIT 1")->fetchColumn();
}

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    die('Produk tidak valid.');
}

$stmt = $pdo->prepare("
    SELECT p.*, c.nama AS kategori_nama
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.id = ? AND p.tenant_id = ? AND p.is_active = 1
");
$stmt->execute([$product_id, $tenant_id]);
$product = $stmt->fetch();

if (!$product) {
    die('Produk tidak ditemukan.');
}

$stmt = $pdo->prepare("
    SELECT * FROM product_variants
    WHERE product_id = ? AND is_active = 1
    ORDER BY harga_jual ASC
");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll();

if (empty($variants)) {
    die('Produk ini belum punya varian aktif.');
}

// Opsi menu & ketersediaan per outlet
$option_groups = menu_option_groups($pdo, [$product_id])[$product_id] ?? [];
$soldout       = $has_table ? menu_soldout_variants($pdo, $outlet_id, array_column($variants, 'id')) : [];
$first_avail   = null;
foreach ($variants as $i => $v) { if (empty($soldout[(int)$v['id']])) { $first_avail = $i; break; } }
$all_soldout   = $first_avail === null;
$sel           = $variants[$first_avail ?? 0];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title><?= htmlspecialchars($product['nama']) ?> · Tokopi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --espresso: #412D15;
            --sand: #E1DCC9;
            --sand-light: #F3F0E6;
            --sand-50: #FAF6EC;
            --bg: #EDEAE3;
            --text-muted: #8a8478;
            --red: #A83232;
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding-bottom: 100px;
            /* Ruang untuk CTA bawah */
        }

        /* HERO ICON MONOKROM */
        .hero {
            height: 240px;
            background: linear-gradient(160deg, var(--sand) 0%, #6b5a45 60%, var(--espresso) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border-radius: 0 0 24px 24px;
            color: #fff;
        }

        .hero { overflow: hidden; background: var(--sand-light); }
        .mod-group[hidden] { display: none !important; }
        .chip.is-out { opacity: .5; text-decoration: line-through; cursor: not-allowed; }
        .chip-out { text-decoration: none; display: inline-block; margin-left: 6px; font-size: .72rem; font-weight: 800; background: var(--red); color: #fff; padding: 1px 6px; border-radius: 6px; }
        .chip-price { opacity: .75; font-weight: 600; margin-left: 4px; font-size: .9em; }
        .mod-hint { float: right; font-weight: 600; text-transform: none; letter-spacing: 0; font-size: .72rem; color: var(--text-muted); }
        .mod-hint.req { color: var(--espresso); }
        .mod-group.has-error .opt-chips { outline: 2px solid var(--red); outline-offset: 4px; border-radius: 12px; }
        .hero .hero-img { position: absolute; inset: 0; }
        .hero .pimg-ph span { font-size: 5rem; }

        .btn-back-float {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, .95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--espresso);
            font-size: 1.1rem;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
            z-index: 5;
        }

        /* BODY CARD */
        .detail-body {
            padding: 24px 22px;
            margin-top: -24px;
            background: var(--bg);
            /* Ikut bg utama atau putih? Kita pakai bg utama biar nyatu */
            border-radius: 24px 24px 0 0;
            position: relative;
            z-index: 3;
        }

        .detail-kategori {
            display: inline-block;
            background: var(--espresso);
            color: var(--sand);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }

        .detail-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            color: var(--espresso);
            font-weight: 700;
            margin: 0 0 6px;
            line-height: 1.15;
        }

        .detail-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--espresso);
            margin-bottom: 14px;
        }

        .detail-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 24px;
        }

        /* CHIPS VARIAN */
        .opt-section {
            margin-bottom: 22px;
        }

        .opt-label {
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--espresso);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }

        .opt-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .chip {
            padding: 10px 16px;
            border-radius: 12px;
            background: #fff;
            border: 2px solid #f0ede5;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--espresso);
            min-width: 52px;
            text-align: center;
            transition: 0.15s;
            box-shadow: 0 2px 6px rgba(65, 45, 21, .04);
            cursor: pointer;
            font-family: inherit;
        }

        .chip.active {
            background: var(--espresso);
            color: #fff;
            border-color: var(--espresso);
        }

        /* QTY CONTROL */
        .qty-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 16px 20px;
            border-radius: 16px;
            border: 1px solid #f0ede5;
            margin-top: 20px;
        }

        .qty-label {
            font-weight: 800;
            color: var(--espresso);
            font-size: 0.9rem;
        }

        .qty-ctrl {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .qty-btn {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: var(--sand-50);
            border: none;
            color: var(--espresso);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
        }

        .qty-btn:active {
            background: var(--sand);
        }

        .qty-btn:disabled {
            opacity: 0.3;
        }

        .qty-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.15rem;
            font-weight: 700;
            min-width: 26px;
            text-align: center;
        }

        /* CATATAN */
        .note-box {
            margin-top: 22px;
            background: #fff;
            border: 1px solid #f0ede5;
            padding: 16px;
            border-radius: 16px;
            font-size: 0.85rem;
            color: var(--espresso);
            width: 100%;
            font-family: inherit;
            resize: none;
            outline: none;
        }

        .note-box::placeholder {
            color: #bbb;
        }

        .note-box:focus {
            border-color: var(--espresso);
        }

        /* STICKY CTA */
        .sticky-cta {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 14px 22px 24px;
            background: #fff;
            border-top: 1px solid #f5f2ea;
            box-shadow: 0 -4px 20px rgba(65, 45, 21, .06);
            z-index: 100;
        }

        .btn-primary {
            width: 100%;
            padding: 17px 22px;
            background: var(--espresso);
            color: #fff;
            border-radius: 16px;
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: inherit;
            border: none;
            box-shadow: 0 6px 20px rgba(65, 45, 21, .3);
            cursor: pointer;
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .btn-primary .total {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
        }

        /* TOAST */
        .toast-success {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #2E7D5B;
            color: #fff;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 4px 20px rgba(46, 125, 91, .4);
            z-index: 9999;
            display: none;
            text-align: center;
        }
    </style>
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>

<body>

    <!-- HERO -->
    <div class="hero">
        <a href="/tokopi/customer/menu.php" class="btn-back-float" aria-label="Kembali ke menu">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
        <?= product_img($product['image_path'], $product['nama'], 'hero-img') ?>
    </div>

    <!-- BODY -->
    <div class="detail-body">
        <div class="detail-kategori"><?= htmlspecialchars($product['kategori_nama']) ?></div>
        <h1 class="detail-title"><?= htmlspecialchars($product['nama']) ?></h1>
        <div class="detail-price" id="display-price">
            Rp <?= number_format((float)$sel['harga_jual'], 0, ',', '.') ?>
        </div>
        <div class="detail-desc">
            <?= htmlspecialchars($product['description'] ?: 'Menu pilihan kami, dibuat dengan bahan-bahan berkualitas.') ?>
        </div>

        <!-- Varian -->
        <div class="opt-section">
            <div class="opt-label">Pilih Varian</div>
            <div class="opt-chips" id="variant-chips">
                <?php
                $harga_termurah = min(array_map(function ($v) {
                    return (float)$v['harga_jual'];
                }, $variants));
                ?>
                <?php foreach ($variants as $i => $v):
                    $harga_v = (float)$v['harga_jual'];
                    $delta = $harga_v - $harga_termurah;
                ?>
                    <?php $is_out = !empty($soldout[(int)$v['id']]); ?>
                    <button type="button"
                        class="chip <?= $i === $first_avail ? 'active' : '' ?> <?= $is_out ? 'is-out' : '' ?>"
                        data-variant-id="<?= (int)$v['id'] ?>"
                        data-price="<?= $harga_v ?>"
                        data-nama="<?= htmlspecialchars($v['nama']) ?>"
                        data-temp="<?= htmlspecialchars((string)$v['temperature']) ?>"
                        <?= $is_out ? 'disabled aria-disabled="true"' : '' ?>
                        aria-pressed="<?= $i === $first_avail ? 'true' : 'false' ?>"
                        onclick="pilihVarian(this)">
                        <?= htmlspecialchars($v['nama']) ?>
                        <?php if ($is_out): ?>
                            <span class="chip-out">Habis</span>
                        <?php elseif ($delta > 0): ?>
                            <span style="opacity:0.75; font-weight:600; margin-left:4px; font-size:0.9em;">
                                +<?= number_format($delta, 0, ',', '.') ?>
                            </span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Opsi menu -->
        <?php foreach ($option_groups as $g):
            $single = $g['max'] === 1;
            $hint = $g['required'] ? ($single ? 'Wajib · pilih 1' : 'Wajib · pilih 1–' . $g['max']) : ($single ? 'Opsional' : 'Opsional · maks ' . $g['max']);
        ?>
            <?php $hidden = ($g['applies_to'] !== 'ALL' && $g['applies_to'] !== strtoupper((string)$sel['temperature'])); ?>
            <div class="opt-section mod-group" <?= $hidden ? 'hidden' : '' ?> data-applies="<?= htmlspecialchars($g['applies_to']) ?>" data-group="<?= (int)$g['id'] ?>" data-min="<?= (int)$g['min'] ?>" data-max="<?= (int)$g['max'] ?>" data-name="<?= htmlspecialchars($g['nama']) ?>"
                 role="<?= $single ? 'radiogroup' : 'group' ?>" aria-labelledby="mg-<?= (int)$g['id'] ?>">
                <div class="opt-label" id="mg-<?= (int)$g['id'] ?>">
                    <?= htmlspecialchars($g['nama']) ?>
                    <span class="mod-hint <?= $g['required'] ? 'req' : '' ?>"><?= $hint ?></span>
                </div>
                <div class="opt-chips">
                    <?php foreach ($g['options'] as $o): $on = $o['is_default'] && $g['required']; ?>
                        <button type="button" class="chip mod-chip <?= $on ? 'active' : '' ?>"
                            role="<?= $single ? 'radio' : 'checkbox' ?>" aria-checked="<?= $on ? 'true' : 'false' ?>"
                            data-option-id="<?= (int)$o['id'] ?>" data-price="<?= $o['price'] ?>" data-nama="<?= htmlspecialchars($o['nama']) ?>">
                            <?= htmlspecialchars($o['nama']) ?>
                            <?php if ($o['price'] > 0): ?><span class="chip-price">+<?= number_format($o['price'] / 1000, 0, ',', '.') ?>rb</span><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="field-error mod-error" hidden><i class="bi bi-exclamation-circle" aria-hidden="true"></i> <span></span></div>
            </div>
        <?php endforeach; ?>

        <!-- Qty -->
        <div class="qty-row">
            <div class="qty-label">Jumlah</div>
            <div class="qty-ctrl">
                <button class="qty-btn" onclick="ubahQty(-1)" id="qty-minus" aria-label="Kurangi jumlah" disabled>−</button>
                <span class="qty-val" id="qty-display" aria-live="polite">1</span>
                <button class="qty-btn" onclick="ubahQty(1)" id="qty-plus" aria-label="Tambah jumlah">+</button>
            </div>
        </div>

        <!-- Catatan -->
        <label for="note-input" class="opt-label" style="display:block;margin-top:18px;">Catatan <span style="font-weight:500;text-transform:none;letter-spacing:0;">(opsional)</span></label>
        <textarea id="note-input" class="note-box" rows="2" maxlength="120" placeholder="Contoh: es dipisah, dibungkus"></textarea>
    </div>

    <!-- STICKY CTA -->
    <div class="sticky-cta">
        <?php if ($has_table && $all_soldout): ?>
        <button class="btn-primary" disabled style="justify-content:center;opacity:.6;">Maaf, menu ini sedang habis</button>
        <?php elseif (!$has_table): ?>
        <a href="/tokopi/customer/t.php" class="btn-primary" style="text-decoration:none;justify-content:center;gap:8px;">
            <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Scan QR meja untuk memesan
        </a>
        <?php else: ?>
        <button onclick="tambahKeCart()" class="btn-primary" id="btn-add">
            <span><i class="bi bi-bag-plus me-1"></i> Tambah ke Keranjang</span>
            <span class="total" id="total-display">
                Rp <?= number_format((float)$sel['harga_jual'], 0, ',', '.') ?>
            </span>
        </button>
        <?php endif; ?>
    </div>

    <div class="toast-success" id="toast" role="status" aria-live="polite"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Ditambahkan ke keranjang</div>

    <script>
        const PRODUCT_ID = <?= (int)$product['id'] ?>;
        const PRODUCT_NAME = <?= json_encode($product['nama']) ?>;
        let selectedVariant = {
            id: <?= (int)$sel['id'] ?>,
            nama: <?= json_encode($sel['nama']) ?>,
            price: <?= (float)$sel['harga_jual'] ?>
        };
        let qty = 1;

        function rupiah(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        }

        function pilihVarian(el) {
            if (el.disabled) return;
            document.querySelectorAll('#variant-chips .chip').forEach(c => { c.classList.remove('active'); c.setAttribute('aria-pressed', 'false'); });
            el.classList.add('active');
            el.setAttribute('aria-pressed', 'true');

            selectedVariant = {
                id: parseInt(el.dataset.variantId),
                nama: el.dataset.nama,
                price: parseFloat(el.dataset.price)
            };
            syncGroupsToVariant();
            updateTotal();
        }

        function ubahQty(delta) {
            qty = Math.max(1, Math.min(99, qty + delta));
            document.getElementById('qty-display').textContent = qty;
            document.getElementById('qty-minus').disabled = (qty <= 1);
            document.getElementById('qty-plus').disabled = (qty >= 99);
            updateTotal();
        }

        // ----- Opsi menu -----
        // Grup yang berlaku untuk varian terpilih (mis. "Tingkat es" hanya untuk Iced)
        function activeGroups() { return Array.from(document.querySelectorAll('.mod-group')).filter(g => !g.hidden); }
        function syncGroupsToVariant() {
            const temp = (document.querySelector('#variant-chips .chip.active')?.dataset.temp || '').toUpperCase();
            document.querySelectorAll('.mod-group').forEach(g => {
                const a = g.dataset.applies || 'ALL';
                const show = a === 'ALL' || a === temp;
                g.hidden = !show;
                if (!show) groupError(g, '');
            });
        }
        function selectedOptions() {
            return activeGroups().flatMap(g => Array.from(g.querySelectorAll('.mod-chip.active'))).map(c => ({
                id: +c.dataset.optionId, nama: c.dataset.nama, price: +c.dataset.price,
                group: c.closest('.mod-group').dataset.name
            }));
        }
        function optionsTotal() { return selectedOptions().reduce((s, o) => s + o.price, 0); }
        function groupError(g, msg) {
            const box = g.querySelector('.mod-error');
            box.hidden = !msg;
            box.querySelector('span').textContent = msg || '';
            g.classList.toggle('has-error', !!msg);
        }
        document.querySelectorAll('.mod-group').forEach(g => {
            const max = +g.dataset.max;
            g.querySelectorAll('.mod-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    const on = chip.classList.contains('active');
                    if (max === 1) {
                        // Radio: grup wajib tidak bisa dikosongkan, opsional bisa dibatalkan
                        if (on && +g.dataset.min > 0) return;
                        g.querySelectorAll('.mod-chip').forEach(c => { c.classList.remove('active'); c.setAttribute('aria-checked', 'false'); });
                        if (!on) { chip.classList.add('active'); chip.setAttribute('aria-checked', 'true'); }
                    } else {
                        const count = g.querySelectorAll('.mod-chip.active').length;
                        if (!on && count >= max) { groupError(g, 'Maksimal ' + max + ' pilihan.'); return; }
                        chip.classList.toggle('active', !on);
                        chip.setAttribute('aria-checked', !on ? 'true' : 'false');
                    }
                    groupError(g, '');
                    updateTotal();
                });
            });
        });

        function updateTotal() {
            const unit = selectedVariant.price + optionsTotal();
            document.getElementById('display-price').textContent = rupiah(unit);
            const t = document.getElementById('total-display');
            if (t) t.textContent = rupiah(unit * qty);
        }

        function tambahKeCart() {
            const note = document.getElementById('note-input').value.trim();

            // Validasi grup wajib
            let firstBad = null;
            activeGroups().forEach(g => {
                const n = g.querySelectorAll('.mod-chip.active').length;
                if (n < +g.dataset.min) { groupError(g, 'Pilih ' + g.dataset.name.toLowerCase() + ' dulu.'); firstBad = firstBad || g; }
            });
            if (firstBad) { firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' }); firstBad.querySelector('.mod-chip').focus({ preventScroll: true }); return; }
            const opts = selectedOptions();
            const optIds = opts.map(o => o.id).sort((a, b) => a - b);

            let cart = {};
            try {
                cart = JSON.parse(localStorage.getItem('tokopi_cart') || '{}');
            } catch (e) {
                cart = {};
            }

            // Hash sederhana agar catatan berisi emoji/huruf non-Latin tidak membuat btoa() error
            let h = 0;
            for (let i = 0; i < note.length; i++) h = (h * 31 + note.charCodeAt(i)) | 0;
            const key = 'v' + selectedVariant.id + (optIds.length ? '_m' + optIds.join('.') : '') + (note ? '_n' + (h >>> 0).toString(36) : '');

            if (cart[key]) {
                cart[key].qty += qty;
            } else {
                cart[key] = {
                    variant_id: selectedVariant.id,
                    product_id: PRODUCT_ID,
                    product_nama: PRODUCT_NAME,
                    variant_nama: selectedVariant.nama,
                    base_harga: selectedVariant.price,
                    modifiers: opts,
                    harga: selectedVariant.price + optionsTotal(),
                    qty: qty,
                    note: note
                };
            }

            localStorage.setItem('tokopi_cart', JSON.stringify(cart));

            const toast = document.getElementById('toast');
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
                window.location.href = '/tokopi/customer/menu.php';
            }, 800);
        }

        // Init
        updateTotal();
    </script>

</body>

</html>