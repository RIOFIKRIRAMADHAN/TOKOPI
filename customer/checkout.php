<?php
require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['customer_table_id'])) {
    redirect('/tokopi/customer/t.php');
}

$table_id    = (int)$_SESSION['customer_table_id'];
$outlet_id   = (int)$_SESSION['customer_outlet_id'];
$table_nama  = $_SESSION['customer_table_nama'];
$outlet_nama = $_SESSION['customer_outlet_nama'];
$is_logged_in  = !empty($_SESSION['customer_account_id']);
$customer_name = $_SESSION['customer_account_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2D1B0A">
    <title>Checkout · Tokopi</title>
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
        }

        .page-header {
            background: #fff;
            padding: 16px 22px 18px;
            border-bottom: 1px solid #f5f2ea;
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

        .co-form {
            padding: 20px;
        }

        .co-form label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--espresso);
            margin-bottom: 8px;
            margin-top: 18px;
        }

        .co-form label:first-child {
            margin-top: 0;
        }

        .co-form label .opt {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.72rem;
        }

        .co-input {
            width: 100%;
            background: #fff;
            border: 1.5px solid #f0ede5;
            border-radius: 14px;
            padding: 14px 16px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1a1a1a;
            outline: none;
            transition: border-color 0.15s;
        }

        .co-input::placeholder {
            color: #ccc;
        }

        .co-input:focus {
            border-color: var(--espresso);
        }

        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 4px;
        }

        .payment-opt {
            display: flex;
            gap: 12px;
            align-items: center;
            padding: 16px;
            background: #fff;
            border: 2px solid #f0ede5;
            border-radius: 14px;
            cursor: pointer;
            transition: 0.15s;
        }

        .payment-opt.active {
            border-color: var(--espresso);
            background: var(--sand-50);
        }

        .payment-opt input {
            display: none;
        }

        .payment-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--sand-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .payment-info {
            flex: 1;
        }

        .payment-name {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--espresso);
        }

        .payment-desc {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .payment-check {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid #ddd;
            flex-shrink: 0;
            position: relative;
        }

        .payment-opt.active .payment-check {
            border-color: var(--espresso);
            background: var(--espresso);
        }

        .payment-opt.active .payment-check::after {
            content: '✓';
            color: #fff;
            font-size: 0.75rem;
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .summary-card {
            margin: 4px 20px 14px;
            background: #fff;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 1px 6px rgba(65, 45, 21, .04);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

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
            bottom: 0;
            left: 0;
            right: 0;
            padding: 14px 20px 20px;
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

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-primary .total {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1rem;
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #fff;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-overlay .spinner-border {
            width: 3rem;
            height: 3rem;
            margin-bottom: 16px;
        }
    </style>
    <link href="/tokopi/assets/css/customer.css?v=20261121d" rel="stylesheet">
</head>

<body>

    <div class="page-header">
        <a href="/tokopi/customer/cart.php" class="back" aria-label="Kembali ke keranjang">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
        <h1>Checkout</h1>
        <p>Isi data untuk pesanan kamu · <?= htmlspecialchars($table_nama) ?></p>
    </div>

    <ol class="cx-steps" aria-label="Langkah pemesanan">
        <li class="done">Keranjang</li>
        <li class="current" aria-current="step">Data & bayar</li>
        <li>Pesanan diproses</li>
    </ol>

    <div class="co-form">
        <label for="customer-name">Nama untuk dipanggil <span style="color:var(--red)" aria-hidden="true">*</span></label>
        <input type="text" id="customer-name" class="co-input" placeholder="Contoh: Rayhan" maxlength="40" required autocomplete="given-name" aria-describedby="name-error" value="<?= e($customer_name) ?>">
        <div class="field-error" id="name-error" hidden><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Isi nama supaya barista bisa memanggilmu.</div>

        <label for="customer-phone">Nomor HP <span class="opt">(opsional)</span></label>
        <input type="tel" id="customer-phone" class="co-input" placeholder="08xxxxxxxxxx" maxlength="15" inputmode="tel" autocomplete="tel">

        <label id="pay-label" style="margin-top:26px;">Metode Pembayaran</label>
        <div class="payment-options" id="payment-options" role="radiogroup" aria-labelledby="pay-label">
            <div class="payment-opt active" data-method="QRIS" role="radio" aria-checked="true" tabindex="0">
                <div class="payment-icon"><i class="bi bi-qr-code" aria-hidden="true"></i></div>
                <div class="payment-info">
                    <div class="payment-name">QRIS</div>
                    <div class="payment-desc">Scan & bayar langsung dari HP</div>
                </div>
                <div class="payment-check"></div>
            </div>
            <div class="payment-opt" data-method="CASH" role="radio" aria-checked="false" tabindex="-1">
                <div class="payment-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></div>
                <div class="payment-info">
                    <div class="payment-name">Bayar di Kasir</div>
                    <div class="payment-desc">Pesan dulu, bayar tunai ke kasir</div>
                </div>
                <div class="payment-check"></div>
            </div>
        </div>
    </div>

    <?php if ($is_logged_in): ?>
        <div class="co-form" style="padding-top:0;">
            <label style="margin-top:0;">Voucher Saya <span class="opt">(opsional)</span></label>
            <div id="voucher-wrapper">
                <div class="voucher-placeholder" id="voucher-placeholder" style="padding:14px; background:#FAF6EC; border-radius:12px; font-size:0.82rem; color:#8a8478; text-align:center;">
                    Memuat voucher...
                </div>
                <select id="voucher-select" class="co-input" style="display:none; font-weight:600;" onchange="onVoucherChange()">
                    <option value="">— Tidak pakai voucher —</option>
                </select>
                <div id="voucher-info" style="display:none; margin-top:8px; padding:10px 14px; background:#dcfce7; border-radius:10px; font-size:0.75rem; color:#166534; line-height:1.45;"></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="summary-card">
        <div class="summary-row">
            <span>Subtotal</span>
            <span class="val" id="sum-subtotal">Rp 0</span>
        </div>
        <div class="summary-row" id="discount-row" style="display:none; color:#2E7D5B;">
            <span>Diskon Voucher</span>
            <span class="val" id="sum-discount">− Rp 0</span>
        </div>
        <div class="summary-row total">
            <span>Total</span>
            <span class="val" id="sum-total">Rp 0</span>
        </div>
    </div>

    <div class="sticky-cta">
        <button class="btn-primary" onclick="submitOrder()" id="btn-submit">
            <span id="btn-label">Bayar Sekarang</span>
            <span class="total" id="cta-total">Rp 0</span>
        </button>
    </div>

    <div class="tk-toast error" id="tkToast" role="alert"></div>

    <div class="loading-overlay" id="loading">
        <div class="spinner-border" role="status"></div>
        <div>Memproses pesanan...</div>
    </div>

    <script>
        const TABLE_ID = <?= (int)$table_id ?>;
        const OUTLET_ID = <?= (int)$outlet_id ?>;

        let paymentMethod = 'QRIS';
        let cart = {};

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

        function renderSummary() {
            const items = Object.values(cart);
            let subtotal = 0;
            items.forEach(it => subtotal += it.harga * it.qty);

            document.getElementById('sum-subtotal').textContent = rupiah(subtotal);
            document.getElementById('sum-total').textContent = rupiah(subtotal);
            document.getElementById('cta-total').textContent = rupiah(subtotal);

            return subtotal;
        }

        // Payment method switcher
        document.querySelectorAll('.payment-opt').forEach(opt => {
            opt.addEventListener('click', () => {
                document.querySelectorAll('.payment-opt').forEach(o => o.classList.remove('active'));
                opt.classList.add('active');
                paymentMethod = opt.dataset.method;
                document.getElementById('btn-label').textContent =
                    paymentMethod === 'QRIS' ? 'Bayar Sekarang' : 'Pesan & Bayar di Kasir';
            });
        });

        function updateButtonLabel() {
            document.getElementById('btn-label').textContent =
                paymentMethod === 'QRIS' ? 'Bayar Sekarang' : 'Pesan & Bayar di Kasir';
        }

        // ============================================
        // VOUCHER
        // ============================================
        let vouchers = [];
        let selectedVoucher = null;
        let subtotal = 0;

        async function loadVouchers() {
            if (!<?= $is_logged_in ? 'true' : 'false' ?>) return;

            try {
                const res = await fetch('/tokopi/customer/api/my_vouchers.php');
                const data = await res.json();

                const placeholder = document.getElementById('voucher-placeholder');
                const select = document.getElementById('voucher-select');

                if (!data.vouchers || data.vouchers.length === 0) {
                    placeholder.innerHTML = '<i class="bi bi-ticket-perforated"></i> Tidak ada voucher aktif';
                    return;
                }

                vouchers = data.vouchers;
                placeholder.style.display = 'none';
                select.style.display = 'block';

                data.vouchers.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v.id;
                    opt.textContent = `${v.reward_nama} — s/d ${v.expires_at_fmt}`;
                    opt.dataset.code = v.voucher_code;
                    opt.dataset.type = v.reward_type;
                    opt.dataset.value = v.reward_value;
                    opt.dataset.name = v.reward_nama;
                    select.appendChild(opt);
                });
            } catch (e) {
                console.error(e);
            }
        }

        function onVoucherChange() {
            const select = document.getElementById('voucher-select');
            const voucherId = select.value;

            if (!voucherId) {
                selectedVoucher = null;
                document.getElementById('voucher-info').style.display = 'none';
                renderSummary();
                return;
            }

            const opt = select.options[select.selectedIndex];
            selectedVoucher = {
                id: parseInt(voucherId),
                code: opt.dataset.code,
                type: opt.dataset.type,
                value: parseFloat(opt.dataset.value),
                nama: opt.dataset.name,
            };

            const info = document.getElementById('voucher-info');
            let infoText = '';
            if (selectedVoucher.type === 'DISCOUNT_NOMINAL') {
                infoText = `<strong>✓ ${selectedVoucher.nama}</strong> — potongan Rp ${Math.round(selectedVoucher.value).toLocaleString('id-ID')}`;
            } else if (selectedVoucher.type === 'DISCOUNT_PERCENT') {
                infoText = `<strong>✓ ${selectedVoucher.nama}</strong> — potongan ${selectedVoucher.value}%`;
            }
            info.innerHTML = infoText;
            info.style.display = 'block';

            renderSummary();
        }

        // Update renderSummary untuk hitung diskon
        const originalRenderSummary = renderSummary;
        renderSummary = function() {
            const items = Object.values(cart);
            subtotal = 0;
            items.forEach(it => subtotal += it.harga * it.qty);

            let discount = 0;
            if (selectedVoucher) {
                if (selectedVoucher.type === 'DISCOUNT_NOMINAL') {
                    discount = Math.min(selectedVoucher.value, subtotal);
                } else if (selectedVoucher.type === 'DISCOUNT_PERCENT') {
                    discount = Math.floor(subtotal * selectedVoucher.value / 100);
                }
            }

            const total = subtotal - discount;

            document.getElementById('sum-subtotal').textContent = rupiah(subtotal);
            document.getElementById('sum-total').textContent = rupiah(total);
            document.getElementById('cta-total').textContent = rupiah(total);

            const discRow = document.getElementById('discount-row');
            if (discount > 0) {
                document.getElementById('sum-discount').textContent = '− ' + rupiah(discount);
                discRow.style.display = 'flex';
            } else {
                discRow.style.display = 'none';
            }

            return total;
        };

        function showError(msg) {
            const t = document.getElementById('tkToast');
            t.innerHTML = '<i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> ' + msg;
            t.classList.add('show');
            clearTimeout(t._h);
            t._h = setTimeout(() => t.classList.remove('show'), 5000);
        }

        // Validasi nama inline
        const nameInput = document.getElementById('customer-name');
        nameInput.addEventListener('input', () => {
            if (nameInput.value.trim()) {
                nameInput.classList.remove('is-invalid');
                nameInput.removeAttribute('aria-invalid');
                document.getElementById('name-error').hidden = true;
            }
        });

        // Metode bayar: bisa dipilih dengan keyboard (panah / spasi / enter)
        (function () {
            const opts = Array.from(document.querySelectorAll('.payment-opt'));
            const sync = () => opts.forEach(o => {
                const on = o.classList.contains('active');
                o.setAttribute('aria-checked', on ? 'true' : 'false');
                o.tabIndex = on ? 0 : -1;
            });
            opts.forEach((o, i) => {
                o.addEventListener('click', () => setTimeout(sync, 0));
                o.addEventListener('keydown', (e) => {
                    if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); o.click(); }
                    if (e.key === 'ArrowDown' || e.key === 'ArrowRight' || e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                        e.preventDefault();
                        const n = opts[(i + (e.key === 'ArrowDown' || e.key === 'ArrowRight' ? 1 : opts.length - 1)) % opts.length];
                        n.click(); n.focus();
                    }
                });
            });
        })();

        async function submitOrder() {
            const nama = document.getElementById('customer-name').value.trim();
            const phone = document.getElementById('customer-phone').value.trim();

            if (nama === '') {
                nameInput.classList.add('is-invalid');
                nameInput.setAttribute('aria-invalid', 'true');
                document.getElementById('name-error').hidden = false;
                nameInput.focus();
                return;
            }

            const items = Object.values(cart).map(it => ({
                variant_id: it.variant_id,
                qty: it.qty,
                note: it.note || '',
                modifiers: (it.modifiers || []).map(m => m.id)
            }));

            if (items.length === 0) {
                showError('Keranjang kosong. Tambahkan menu dulu, ya.');
                return;
            }

            // Tampilkan loading
            document.getElementById('loading').classList.add('show');
            document.getElementById('btn-submit').disabled = true;

            const payload = {
                table_id: TABLE_ID,
                outlet_id: OUTLET_ID,
                customer_name: nama,
                customer_phone: phone,
                payment_method: paymentMethod,
                items: items,
                client_request_id: crypto.randomUUID(),
                voucher_code: selectedVoucher ? selectedVoucher.code : null,
            };

            function resetLoading() {
                document.getElementById('loading').classList.remove('show');
                document.getElementById('btn-submit').disabled = false;
            }

            try {
                const res = await fetch('/tokopi/customer/api/checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                // Cek response status dulu
                const rawText = await res.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (jsonErr) {
                    resetLoading();
                    console.error('Backend response bukan JSON:', rawText);
                    showError('Pesanan belum terkirim karena ada gangguan di server. Coba lagi sebentar lagi, atau pesan lewat kasir.');
                    return;
                }

                if (!res.ok || !data.success) {
                    resetLoading();
                    showError(data.message || 'Pesanan belum berhasil dibuat. Coba lagi.');
                    return;
                }



                // Redirect berdasarkan metode
                if (paymentMethod === 'QRIS') {
                    if (!data.qr_url) {
                        resetLoading();
                        showError('QRIS sedang tidak tersedia. Pilih "Bayar di Kasir" atau coba lagi.');
                        return;
                    }

                    localStorage.removeItem('tokopi_cart');
                    sessionStorage.setItem('tokopi_pending_order', JSON.stringify({
                        order_id: data.order_id,
                        order_no: data.order_no,
                        qr_url: data.qr_url,
                        qr_string: data.qr_string,
                        grand_total: data.grand_total
                    }));
                    window.location.href = '/tokopi/customer/qris.php';
                } else {
                    // Cash → redirect ke halaman "tunggu bayar di kasir"
                    if (!data.order_id) {
                        resetLoading();
                        showError('Pesanan belum tercatat. Coba lagi atau hubungi kasir.');
                        return;
                    }
                    localStorage.removeItem('tokopi_cart');
                    window.location.href = '/tokopi/customer/waiting_cash.php?order_id=' + data.order_id;
                }
            } catch (e) {
                resetLoading();
                console.error(e);
                showError('Koneksi terputus. Periksa internet kamu lalu coba lagi.');
            }
        }

        // Init
        cart = ambilCart();
        if (Object.keys(cart).length === 0) {
            window.location.replace('/tokopi/customer/menu.php');
        }
        renderSummary();
        updateButtonLabel();
        loadVouchers();
    </script>

</body>

</html>