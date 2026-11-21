<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$table_nama  = $_SESSION['customer_table_nama'];
$outlet_nama = $_SESSION['customer_outlet_nama'];
$table_id    = (int)$_SESSION['customer_table_id'];
$outlet_id   = (int)$_SESSION['customer_outlet_id'];

// Peta foto produk untuk ditampilkan di keranjang
$stmt = $pdo->prepare("SELECT p.id, p.nama, p.image_path FROM products p JOIN outlets o ON o.tenant_id = p.tenant_id WHERE o.id = ?");
$stmt->execute([$outlet_id]);
$product_imgs = [];
foreach ($stmt->fetchAll() as $r) {
    $product_imgs[(int)$r['id']] = product_img($r['image_path'], $r['nama'], 'cart-pimg');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Keranjang · Tokopi</title>
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
            --green: #2E7D5B;
            --red: #A83232;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body {
            margin: 0; padding: 0;
            background: var(--bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding-bottom: 100px;
        }

        .page-header {
            background: #fff;
            padding: 16px 22px 18px;
            border-bottom: 1px solid #f5f2ea;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .page-header .back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--sand-50);
            border-radius: 10px;
            color: var(--espresso);
            font-size: 1rem;
            text-decoration: none;
            margin-bottom: 10px;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: var(--espresso);
            margin: 0;
        }
        .page-header p {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin: 3px 0 0;
        }

        .cart-container {
            padding: 14px 20px;
        }

        .cart-item {
            display: flex;
            gap: 12px;
            padding: 14px;
            background: #fff;
            border-radius: 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 6px rgba(65,45,21,.04);
        }
        .cart-img .cart-pimg { border-radius: 12px; }
        .cart-mods { font-size: .75rem; color: var(--espresso); background: var(--sand-50); border-radius: 8px; padding: 4px 8px; margin: 4px 0 2px; display: inline-block; line-height: 1.4; }
        .cart-mods span { color: var(--text-muted); font-weight: 600; }
        .cart-img .pimg-ph span { font-size: 1.3rem; }
        .cart-img {
            overflow: hidden;
            width: 62px;
            height: 62px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--sand-light), var(--sand));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }
        .cart-info { flex: 1; min-width: 0; }
        .cart-name {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--espresso);
            margin-bottom: 2px;
            line-height: 1.25;
        }
        .cart-var {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        .cart-note {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .cart-foot {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-price {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--espresso);
        }
        .qty-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            background: var(--sand-50);
            border-radius: 10px;
            padding: 4px 10px;
        }
        .qty-btn-mini {
            width: 24px;
            height: 24px;
            background: transparent;
            border: none;
            color: var(--espresso);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-family: inherit;
        }
        .qty-btn-mini:disabled { opacity: 0.3; }
        .qty-val-mini {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            min-width: 18px;
            text-align: center;
        }
        .btn-remove {
            background: transparent;
            border: none;
            color: var(--red);
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0;
            margin-left: 6px;
            opacity: 0.6;
        }

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .empty-cart i {
            font-size: 4rem;
            color: var(--sand);
            display: block;
            margin-bottom: 16px;
        }
        .empty-cart h3 {
            font-family: 'Playfair Display', serif;
            color: var(--espresso);
            margin: 0 0 6px;
        }
        .empty-cart a {
            display: inline-block;
            margin-top: 16px;
            padding: 12px 24px;
            background: var(--espresso);
            color: #fff;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .summary-card {
            margin: 14px 20px;
            background: #fff;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 1px 6px rgba(65,45,21,.04);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .summary-row:last-child { margin-bottom: 0; }
        .summary-row.total {
            padding-top: 14px;
            margin-top: 12px;
            border-top: 1.5px dashed var(--sand);
            font-weight: 800;
            color: var(--espresso);
            font-size: 1.05rem;
        }
        .summary-row .val {
            font-family: 'JetBrains Mono', monospace;
        }

        .sticky-cta {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            padding: 14px 20px 20px;
            background: #fff;
            border-top: 1px solid #f5f2ea;
            box-shadow: 0 -4px 20px rgba(65,45,21,.06);
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
            box-shadow: 0 6px 20px rgba(65,45,21,.3);
            cursor: pointer;
        }
        .btn-primary:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            box-shadow: none;
        }
        .btn-primary .total {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
        }
    </style>
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>
<body>

<div class="page-header">
    <a href="/tokopi/customer/menu.php" class="back" aria-label="Kembali ke menu">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
    </a>
    <h1>Keranjang</h1>
    <p id="cart-count-header">Memuat…</p>
</div>

<ol class="cx-steps" aria-label="Langkah pemesanan">
    <li class="current" aria-current="step">Keranjang</li>
    <li>Data & bayar</li>
    <li>Pesanan diproses</li>
</ol>

<div class="cart-container" id="cart-container">
    <!-- Isi dari JS -->
</div>

<div id="summary-wrapper" style="display:none;">
    <div class="summary-card">
        <div class="summary-row">
            <span>Subtotal</span>
            <span class="val" id="sum-subtotal">Rp 0</span>
        </div>
        <div class="summary-row total">
            <span>Total</span>
            <span class="val" id="sum-total">Rp 0</span>
        </div>
    </div>
</div>

<div class="tk-toast" id="tkToast" role="status" aria-live="polite"></div>

<div class="sticky-cta" id="sticky-cta" style="display:none;">
    <button class="btn-primary" onclick="lanjutCheckout()">
        <span>Lanjut Checkout</span>
        <span class="total" id="cta-total">Rp 0</span>
    </button>
</div>

<script>
const PRODUCT_IMG = <?= json_encode($product_imgs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const TABLE_INFO = {
    nama: <?= json_encode($table_nama) ?>,
    outlet: <?= json_encode($outlet_nama) ?>
};

function rupiah(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
}

function ambilCart() {
    try {
        return JSON.parse(localStorage.getItem('tokopi_cart') || '{}');
    } catch (e) {
        return {};
    }
}

function simpanCart(cart) {
    localStorage.setItem('tokopi_cart', JSON.stringify(cart));
}

function render() {
    const cart = ambilCart();
    const items = Object.entries(cart);
    const container = document.getElementById('cart-container');
    const countHeader = document.getElementById('cart-count-header');
    const summaryWrapper = document.getElementById('summary-wrapper');
    const stickyCta = document.getElementById('sticky-cta');

    if (items.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <i class="bi bi-bag-x"></i>
                <h3>Keranjang kosong</h3>
                <p>Yuk pilih menu dulu</p>
                <a href="/tokopi/customer/menu.php">Lihat Menu</a>
            </div>
        `;
        countHeader.textContent = '0 item';
        summaryWrapper.style.display = 'none';
        stickyCta.style.display = 'none';
        updateFloatingBadge(0);
        return;
    }

    let html = '';
    let subtotal = 0;
    let totalQty = 0;

    items.forEach(([key, item]) => {
        const lineTotal = item.harga * item.qty;
        subtotal += lineTotal;
        totalQty += item.qty;

        html += `
            <div class="cart-item">
                <div class="cart-img">${PRODUCT_IMG[item.product_id] || '<i class="bi bi-cup-hot" aria-hidden="true"></i>'}</div>
                <div class="cart-info">
                    <div class="cart-name">${escapeHtml(item.product_nama)}</div>
                    <div class="cart-var">${escapeHtml(item.variant_nama)}</div>
                    ${item.modifiers && item.modifiers.length ? `<div class="cart-mods">${item.modifiers.map(m => escapeHtml(m.nama) + (m.price > 0 ? ' <span>+' + Math.round(m.price / 1000) + 'rb</span>' : '')).join(' · ')}</div>` : ''}
                    ${item.note ? `<span class="cart-note"><i class="bi bi-pencil"></i> ${escapeHtml(item.note)}</span>` : ''}
                    <div class="cart-foot">
                        <span class="cart-price">${rupiah(lineTotal)}</span>
                        <div class="qty-controls">
                            <button class="qty-btn-mini" onclick="ubahQty('${key}', -1)" ${item.qty <= 1 ? 'disabled' : ''} aria-label="Kurangi ${escapeHtml(item.product_nama)}">−</button>
                            <span class="qty-val-mini" aria-label="Jumlah">${item.qty}</span>
                            <button class="qty-btn-mini" onclick="ubahQty('${key}', 1)" aria-label="Tambah ${escapeHtml(item.product_nama)}">+</button>
                            <button class="btn-remove" onclick="hapusItem('${key}')" aria-label="Hapus ${escapeHtml(item.product_nama)}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    countHeader.textContent = totalQty + ' item · ' + TABLE_INFO.nama;
    document.getElementById('sum-subtotal').textContent = rupiah(subtotal);
    document.getElementById('sum-total').textContent = rupiah(subtotal);
    document.getElementById('cta-total').textContent = rupiah(subtotal);
    summaryWrapper.style.display = 'block';
    stickyCta.style.display = 'block';
    updateFloatingBadge(totalQty);
}

function ubahQty(key, delta) {
    const cart = ambilCart();
    if (!cart[key]) return;
    cart[key].qty = Math.max(1, cart[key].qty + delta);
    simpanCart(cart);
    render();
}

// Hapus langsung + tombol "Urungkan" (lebih cepat daripada dialog konfirmasi)
function hapusItem(key) {
    const cart = ambilCart();
    const removed = cart[key];
    if (!removed) return;
    delete cart[key];
    simpanCart(cart);
    render();
    const t = document.getElementById('tkToast');
    t.innerHTML = '<span style="flex:1">' + escapeHtml(removed.product_nama) + ' dihapus</span>' +
        '<button type="button" id="undoBtn" style="background:none;border:0;color:#E1DCC9;font-weight:800;min-height:36px;">Urungkan</button>';
    t.style.pointerEvents = 'auto';
    t.classList.add('show');
    clearTimeout(t._h);
    t._h = setTimeout(() => { t.classList.remove('show'); t.style.pointerEvents = 'none'; }, 4500);
    document.getElementById('undoBtn').onclick = () => {
        const c = ambilCart();
        c[key] = removed;
        simpanCart(c);
        render();
        t.classList.remove('show');
        t.style.pointerEvents = 'none';
    };
}

function lanjutCheckout() {
    const cart = ambilCart();
    if (Object.keys(cart).length === 0) {
        return;
    }
    window.location.href = '/tokopi/customer/checkout.php';
}

function updateFloatingBadge(count) {
    // update badge di halaman lain tidak perlu di sini
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
}

// Init
render();
</script>

</body>
</html>
