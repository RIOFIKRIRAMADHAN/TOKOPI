<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$outlet_id   = (int)$_SESSION['customer_outlet_id'];
$table_nama  = $_SESSION['customer_table_nama'];
$outlet_nama = $_SESSION['customer_outlet_nama'];

$stmt = $pdo->prepare("SELECT tenant_id FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$tenant_id = (int)$stmt->fetchColumn();

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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#412D15">
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

        .hero > i {
            font-size: 7rem;
            opacity: 0.9;
        }

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
</head>

<body>

    <!-- HERO -->
    <div class="hero">
        <a href="/tokopi/customer/menu.php" class="btn-back-float">
            <i class="bi bi-arrow-left"></i>
        </a>
        <i class="bi bi-cup-hot"></i>
    </div>

    <!-- BODY -->
    <div class="detail-body">
        <div class="detail-kategori"><?= htmlspecialchars($product['kategori_nama']) ?></div>
        <h1 class="detail-title"><?= htmlspecialchars($product['nama']) ?></h1>
        <div class="detail-price" id="display-price">
            Rp <?= number_format((float)$variants[0]['harga_jual'], 0, ',', '.') ?>
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
                    <button type="button"
                        class="chip <?= $i === 0 ? 'active' : '' ?>"
                        data-variant-id="<?= (int)$v['id'] ?>"
                        data-price="<?= $harga_v ?>"
                        data-nama="<?= htmlspecialchars($v['nama']) ?>"
                        onclick="pilihVarian(this)">
                        <?= htmlspecialchars($v['nama']) ?>
                        <?php if ($delta > 0): ?>
                            <span style="opacity:0.75; font-weight:600; margin-left:4px; font-size:0.9em;">
                                +<?= number_format($delta, 0, ',', '.') ?>
                            </span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Qty -->
        <div class="qty-row">
            <div class="qty-label">Jumlah</div>
            <div class="qty-ctrl">
                <button class="qty-btn" onclick="ubahQty(-1)" id="qty-minus">−</button>
                <span class="qty-val" id="qty-display">1</span>
                <button class="qty-btn" onclick="ubahQty(1)" id="qty-plus">+</button>
            </div>
        </div>

        <!-- Catatan -->
        <textarea id="note-input" class="note-box" rows="2" placeholder="Catatan tambahan (contoh: less ice, tanpa gula...)"></textarea>
    </div>

    <!-- STICKY CTA -->
    <div class="sticky-cta">
        <button onclick="tambahKeCart()" class="btn-primary" id="btn-add">
            <span><i class="bi bi-bag-plus me-1"></i> Tambah ke Keranjang</span>
            <span class="total" id="total-display">
                Rp <?= number_format((float)$variants[0]['harga_jual'], 0, ',', '.') ?>
            </span>
        </button>
    </div>

    <div class="toast-success" id="toast">✅ Ditambahkan ke keranjang</div>

    <script>
        const PRODUCT_ID = <?= (int)$product['id'] ?>;
        const PRODUCT_NAME = <?= json_encode($product['nama']) ?>;
        let selectedVariant = {
            id: <?= (int)$variants[0]['id'] ?>,
            nama: <?= json_encode($variants[0]['nama']) ?>,
            price: <?= (float)$variants[0]['harga_jual'] ?>
        };
        let qty = 1;

        function rupiah(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        }

        function pilihVarian(el) {
            document.querySelectorAll('#variant-chips .chip').forEach(c => c.classList.remove('active'));
            el.classList.add('active');

            selectedVariant = {
                id: parseInt(el.dataset.variantId),
                nama: el.dataset.nama,
                price: parseFloat(el.dataset.price)
            };
            updateTotal();
        }

        function ubahQty(delta) {
            qty = Math.max(1, Math.min(99, qty + delta));
            document.getElementById('qty-display').textContent = qty;
            document.getElementById('qty-minus').disabled = (qty <= 1);
            document.getElementById('qty-plus').disabled = (qty >= 99);
            updateTotal();
        }

        function updateTotal() {
            const total = selectedVariant.price * qty;
            document.getElementById('display-price').textContent = rupiah(selectedVariant.price);
            document.getElementById('total-display').textContent = rupiah(total);
        }

        function tambahKeCart() {
            const note = document.getElementById('note-input').value.trim();

            let cart = {};
            try {
                cart = JSON.parse(localStorage.getItem('tokopi_cart') || '{}');
            } catch (e) {
                cart = {};
            }

            const key = 'v' + selectedVariant.id + (note ? '_n' + btoa(note).substring(0, 10) : '');

            if (cart[key]) {
                cart[key].qty += qty;
            } else {
                cart[key] = {
                    variant_id: selectedVariant.id,
                    product_id: PRODUCT_ID,
                    product_nama: PRODUCT_NAME,
                    variant_nama: selectedVariant.nama,
                    harga: selectedVariant.price,
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