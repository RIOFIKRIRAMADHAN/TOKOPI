<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'kasir']);

$page_title = 'Antrian Pesanan QR';
$active_menu = 'qr_orders';
$tenant_id = current_tenant_id();
$outlet_id = current_outlet_id();

if (!$outlet_id) {
    die('Anda belum di-assign ke outlet.');
}

$stmt = $pdo->prepare("SELECT nama FROM outlets WHERE id = ?");
$stmt->execute([$outlet_id]);
$outlet = $stmt->fetch();

$success = flash_get('success');
$error   = flash_get('error');

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .qr-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--sand);
    }

    .qr-tab {
        padding: 10px 18px;
        font-size: 0.85rem;
        font-weight: 700;
        color: #999;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        user-select: none;
        transition: 0.15s;
    }

    .qr-tab.active {
        color: var(--espresso);
        border-bottom-color: var(--espresso);
    }

    .qr-tab .badge-count {
        display: inline-block;
        background: #A83232;
        color: #fff;
        font-size: 0.65rem;
        padding: 2px 7px;
        border-radius: 999px;
        margin-left: 6px;
        min-width: 20px;
        text-align: center;
        font-family: 'JetBrains Mono', monospace;
    }

    .qr-tab.active .badge-count {
        background: var(--espresso);
    }

    .order-qr-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 16px;
    }

    .order-qr-card {
        background: #fff;
        border-radius: 16px;
        padding: 18px;
        border: 2px solid #f0f0f0;
        display: flex;
        flex-direction: column;
    }

    .order-qr-card.status-PAID {
        border-color: #4ade80;
    }

    .order-qr-card.status-PREPARING {
        border-color: #facc15;
    }

    .order-qr-card.status-READY {
        border-color: #60a5fa;
    }

    .order-qr-card.status-PENDING_PAYMENT {
        border-color: #cbd5e1;
    }

    .order-qr-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 12px;
        border-bottom: 1px dashed #e5e5e5;
        margin-bottom: 12px;
    }

    .order-qr-no {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 800;
        font-size: 0.85rem;
        color: var(--espresso);
        margin-bottom: 2px;
    }

    .order-qr-time {
        font-size: 0.7rem;
        color: #999;
    }

    .order-qr-status {
        font-size: 0.65rem;
        font-weight: 800;
        padding: 4px 10px;
        border-radius: 6px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    .status-badge-PAID {
        background: #dcfce7;
        color: #166534;
    }

    .status-badge-PREPARING {
        background: #fef3c7;
        color: #92400e;
    }

    .status-badge-READY {
        background: #dbeafe;
        color: #1e40af;
    }

    .status-badge-PENDING_PAYMENT {
        background: #f1f5f9;
        color: #475569;
    }

    .customer-info {
        background: var(--sand-light);
        padding: 10px 12px;
        border-radius: 10px;
        margin-bottom: 12px;
    }

    .customer-label {
        font-size: 0.65rem;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 2px;
    }

    .customer-name {
        font-family: 'Playfair Display', serif;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--espresso);
        line-height: 1.1;
    }

    .table-info {
        font-size: 0.75rem;
        color: var(--espresso);
        margin-top: 4px;
        font-weight: 600;
    }

    .order-items-list {
        flex: 1;
        margin-bottom: 12px;
    }

    .order-item-row {
        display: flex;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f5f5f5;
        font-size: 0.85rem;
    }

    .order-item-row:last-child {
        border-bottom: none;
    }

    .order-item-qty {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 800;
        color: var(--espresso);
        min-width: 28px;
        font-size: 0.9rem;
    }

    .order-item-detail {
        flex: 1;
        min-width: 0;
    }

    .order-item-name {
        font-weight: 700;
        color: var(--espresso);
        line-height: 1.2;
    }

    .order-item-variant {
        font-size: 0.75rem;
        color: #999;
        margin-top: 2px;
    }

    .order-item-note {
        font-size: 0.7rem;
        color: #c2410c;
        background: #fef3c7;
        padding: 3px 8px;
        border-radius: 6px;
        margin-top: 4px;
        display: inline-block;
        font-weight: 600;
    }

    .order-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
    }

    .order-actions button {
        flex: 1;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        transition: 0.15s;
        font-family: inherit;
    }

    .btn-proses {
        background: #facc15;
        color: #713f12;
    }

    .btn-proses:hover {
        background: #eab308;
    }

    .btn-selesai {
        background: #4ade80;
        color: #064e3b;
    }

    .btn-selesai:hover {
        background: #22c55e;
        color: #fff;
    }

    .btn-detail {
        background: #e5e7eb;
        color: #374151;
    }

    .btn-detail:hover {
        background: #d1d5db;
    }

    .btn-confirm-cash {
        background: #4ade80;
        color: #064e3b;
    }

    .btn-confirm-cash:hover {
        background: #22c55e;
        color: #fff;
    }

    .btn-cancel-cash {
        background: #fca5a5;
        color: #7f1d1d;
    }

    .btn-cancel-cash:hover {
        background: #f87171;
        color: #fff;
    }

    .empty-qr {
        text-align: center;
        padding: 60px 20px;
        color: #bbb;
    }

    .empty-qr i {
        font-size: 3rem;
        display: block;
        margin-bottom: 12px;
        color: var(--sand);
    }

    .empty-qr h4 {
        font-family: 'Playfair Display', serif;
        color: var(--espresso);
        margin: 0 0 6px;
    }
</style>

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Antrian Pesanan QR</h5>
        <small class="text-muted">Outlet: <strong><?= e($outlet['nama']) ?></strong> · Auto-refresh tiap 5 detik</small>
    </div>
    <button class="btn btn-outline-dark btn-sm" onclick="loadOrders()">
        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
    </button>
</div>

<div class="qr-tabs">
    <div class="qr-tab" data-status="CASH_PENDING">
        💰 Menunggu Bayar <span class="badge-count" id="count-CASH_PENDING">0</span>
    </div>
    <div class="qr-tab active" data-status="PAID">
        🔔 Baru <span class="badge-count" id="count-PAID">0</span>
    </div>
    <div class="qr-tab" data-status="PREPARING">
        ⏳ Diproses <span class="badge-count" id="count-PREPARING">0</span>
    </div>
    <div class="qr-tab" data-status="READY">
        ✅ Siap <span class="badge-count" id="count-READY">0</span>
    </div>
    <div class="qr-tab" data-status="all">
        Semua
    </div>
</div>

<div id="orders-container">
    <div class="empty-qr">
        <i class="bi bi-hourglass"></i>
        <h4>Memuat antrian...</h4>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-detail-body">
                Loading...
            </div>
        </div>
    </div>
</div>

<script>
    function rupiah(n) {
        return 'Rp ' + Math.round(n).toLocaleString('id-ID');
    }

    function getElapsed(timeStr) {
        if (!timeStr) return '-';
        const now = new Date();
        const then = new Date(timeStr.replace(' ', 'T'));
        const diff = Math.floor((now - then) / 1000);
        if (diff < 60) return diff + ' detik lalu';
        if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
        if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
        return Math.floor(diff / 86400) + ' hari lalu';
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : s;
        return d.innerHTML;
    }

    let activeStatus = 'PAID';
    let allOrders = [];
    let cashPendingOrders = [];

    async function loadOrders() {
        try {
            // Load order aktif (PAID, PREPARING, READY)
            const res = await fetch('/tokopi/api/pos_qr_orders.php');
            const data = await res.json();
            if (data.success) {
                allOrders = data.orders || [];
                document.getElementById('count-PAID').textContent = data.stats.PAID || 0;
                document.getElementById('count-PREPARING').textContent = data.stats.PREPARING || 0;
                document.getElementById('count-READY').textContent = data.stats.READY || 0;
            }

            // Load order cash menunggu bayar
            const res2 = await fetch('/tokopi/api/pos_qr_pending_cash.php');
            const data2 = await res2.json();
            if (data2.success) {
                cashPendingOrders = data2.orders || [];
                document.getElementById('count-CASH_PENDING').textContent = data2.total || 0;
            }

            // Update sidebar badge
            const sidebarCount = document.getElementById('sidebar-qr-count');
            if (sidebarCount) {
                const total = (data.stats?.PAID || 0) + (data2.total || 0);
                if (total > 0) {
                    sidebarCount.textContent = total;
                    sidebarCount.style.display = 'inline-block';
                } else {
                    sidebarCount.style.display = 'none';
                }
            }

            renderOrders();
        } catch (e) {
            console.error('Load error:', e);
        }
    }

    function renderOrders() {
        const container = document.getElementById('orders-container');

        let filtered = [];
        if (activeStatus === 'CASH_PENDING') {
            filtered = cashPendingOrders;

        } else if (activeStatus === 'all') {
            const combined = [...cashPendingOrders, ...allOrders];
            const uniqueMap = new Map();
            combined.forEach(o => uniqueMap.set(o.id, o));
            filtered = Array.from(uniqueMap.values());

            } else {
            filtered = allOrders.filter(o => o.status === activeStatus);
        }

        if (filtered.length === 0) {
            const emptyMsg = activeStatus === 'CASH_PENDING' ?
                'Tidak ada pesanan yang menunggu bayar cash.' :
                'Pesanan QR baru akan muncul di sini.';
            container.innerHTML = `
            <div class="empty-qr">
                <i class="bi bi-cup-hot"></i>
                <h4>Tidak ada pesanan</h4>
                <p>${emptyMsg}</p>
            </div>
        `;
            return;
        }

        let html = '<div class="order-qr-grid">';
        filtered.forEach(o => {
            const isCashPending = activeStatus === 'CASH_PENDING' ||
                (activeStatus === 'all' && o.status === 'PENDING_PAYMENT');

            html += `
            <div class="order-qr-card status-${isCashPending ? 'PENDING_PAYMENT' : o.status}">
                <div class="order-qr-header">
                    <div>
                        <div class="order-qr-no">${escapeHtml(o.order_no)}</div>
                        <div class="order-qr-time">
                            <i class="bi bi-clock me-1"></i>${getElapsed(o.created_at)}
                        </div>
                    </div>
                    <span class="order-qr-status status-badge-${isCashPending ? 'PENDING_PAYMENT' : o.status}">
                        ${isCashPending ? '💰 Menunggu Bayar' : o.status.replace('_', ' ')}
                    </span>
                </div>

                <div class="customer-info">
                    <div class="customer-label">Pesanan atas nama</div>
                    <div class="customer-name">${escapeHtml(o.customer_name || '-')}</div>
                    <div class="table-info">
                        <i class="bi bi-geo-alt-fill me-1"></i>
                        ${escapeHtml(o.table_nama || 'Meja')} · ${o.items.length} item
                    </div>
                </div>

                <div class="order-items-list">
                    ${o.items.map(it => `
                        <div class="order-item-row">
                            <div class="order-item-qty">${it.qty}x</div>
                            <div class="order-item-detail">
                                <div class="order-item-name">${escapeHtml(it.product_nama)}</div>
                                <div class="order-item-variant">${escapeHtml(it.variant_nama)}</div>
                                ${it.note ? `<div class="order-item-note"><i class="bi bi-pencil"></i> ${escapeHtml(it.note)}</div>` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>

                <div class="order-actions">
                    <button class="btn-detail" onclick="showDetail(${o.id}, ${isCashPending})">
                        <i class="bi bi-eye"></i> Detail
                    </button>
                    ${isCashPending ? `
                        <button class="btn-cancel-cash" onclick="cancelCash(${o.id})">
                            <i class="bi bi-x-lg"></i> Batal
                        </button>
                        <button class="btn-confirm-cash" onclick="confirmCash(${o.id})">
                            <i class="bi bi-check-lg"></i> Terima Bayar
                        </button>
                    ` : ''}
                    ${!isCashPending && o.status === 'PAID' ? `
                        <button class="btn-proses" onclick="updateStatus(${o.id}, 'PREPARING')">
                            <i class="bi bi-play-fill"></i> Proses
                        </button>
                    ` : ''}
                    ${!isCashPending && o.status === 'PREPARING' ? `
                        <button class="btn-selesai" onclick="updateStatus(${o.id}, 'READY')">
                            <i class="bi bi-check-lg"></i> Siap
                        </button>
                    ` : ''}
                    ${!isCashPending && o.status === 'READY' ? `
                        <button class="btn-selesai" onclick="updateStatus(${o.id}, 'COMPLETED')">
                            <i class="bi bi-bag-check-fill"></i> Selesai
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    async function confirmCash(orderId) {
        if (!confirm('Konfirmasi terima pembayaran cash?')) return;

        try {
            const res = await fetch('/tokopi/api/pos_qr_confirm_cash.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            });
            const data = await res.json();

            if (!data.success) {
                alert('Gagal: ' + (data.message || 'Error'));
                return;
            }

            alert('✅ Pembayaran dikonfirmasi. Order masuk ke antrian dapur.');
            loadOrders();
        } catch (e) {
            alert('Error: ' + e.message);
        }
    }

    async function cancelCash(orderId) {
        const reason = prompt('Alasan batalkan order? (contoh: pelanggan tidak datang)');
        if (!reason) return;

        try {
            const res = await fetch('/tokopi/api/pos_qr_cancel_cash.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    reason: reason
                })
            });
            const data = await res.json();

            if (!data.success) {
                alert('Gagal: ' + (data.message || 'Error'));
                return;
            }

            loadOrders();
        } catch (e) {
            alert('Error: ' + e.message);
        }
    }

    async function updateStatus(orderId, newStatus) {
        if (!confirm(`Ubah status jadi ${newStatus}?`)) return;

        try {
            const res = await fetch('/tokopi/api/pos_qr_update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: newStatus
                })
            });
            const data = await res.json();

            if (!data.success) {
                alert('Gagal: ' + (data.message || 'Error'));
                return;
            }

            loadOrders();
        } catch (e) {
            alert('Error: ' + e.message);
        }
    }

    function showDetail(orderId, isCashPending) {
        let order;
        if (isCashPending) {
            order = cashPendingOrders.find(o => o.id === orderId);
        } else {
            order = allOrders.find(o => o.id === orderId);
        }
        if (!order) return;

        let html = `
        <div class="mb-3">
            <div class="text-muted small text-uppercase">Order No</div>
            <div class="fw-bold" style="font-family: 'JetBrains Mono', monospace;">${escapeHtml(order.order_no)}</div>
        </div>
        <div class="mb-3">
            <div class="text-muted small text-uppercase">Nama Pelanggan</div>
            <div style="font-size:1.3rem; font-weight:800; color:var(--espresso);">${escapeHtml(order.customer_name || '-')}</div>
            ${order.customer_phone ? `<div class="small text-muted"><i class="bi bi-telephone me-1"></i>${escapeHtml(order.customer_phone)}</div>` : ''}
        </div>
        <div class="mb-3">
            <div class="text-muted small text-uppercase">Meja</div>
            <div class="fw-bold">${escapeHtml(order.table_nama || '-')}</div>
        </div>
        <div class="mb-3">
            <div class="text-muted small text-uppercase mb-2">Item Pesanan</div>
            ${order.items.map(it => `
                <div class="d-flex gap-3 py-2 border-bottom">
                    <div class="fw-bold" style="font-family:'JetBrains Mono',monospace; color:var(--espresso); min-width:36px;">${it.qty}x</div>
                    <div style="flex:1;">
                        <div class="fw-bold">${escapeHtml(it.product_nama)}</div>
                        <div class="small text-muted">${escapeHtml(it.variant_nama)}</div>
                        ${it.note ? `<div class="small mt-1" style="color:#c2410c; background:#fef3c7; padding:3px 8px; border-radius:6px; display:inline-block;"><i class="bi bi-pencil"></i> ${escapeHtml(it.note)}</div>` : ''}
                    </div>
                    <div class="text-end small" style="font-family:'JetBrains Mono',monospace;">${rupiah(it.line_total)}</div>
                </div>
            `).join('')}
        </div>
        <div class="d-flex justify-content-between pt-3 border-top">
            <div class="fw-bold">TOTAL</div>
            <div class="fw-bold" style="font-family:'JetBrains Mono',monospace; font-size:1.1rem;">${rupiah(order.grand_total)}</div>
        </div>
        ${order.payment_method ? `
            <div class="d-flex justify-content-between mt-2 small text-muted">
                <div>Metode Bayar</div>
                <div>${escapeHtml(order.payment_method)} (${escapeHtml(order.payment_status)})</div>
            </div>
        ` : ''}
        ${order.voucher ? `
            <div class="d-flex justify-content-between mt-2 small text-muted">
                <div>Voucher</div>
                <div>${escapeHtml(order.voucher.nama)} (${escapeHtml(order.voucher.type)})</div>
            </div>
            <div class="d-flex justify-content-between mt-1 small text-muted">
                <div>Poin Digunakan</div>
                <div>${order.voucher.points_used}</div>
            </div>
            <div class="d-flex justify-content-between mt-1 small text-muted">
                <div>Diskon</div>
                <div>${rupiah(order.voucher.discount)}</div>
            </div>
        ` : ''}
    `;


        // Tambahkan tombol cetak di bawah detail
        html += `
        <div class="d-flex gap-2 mt-3 pt-3 border-top">
            <button class="btn btn-sm btn-outline-dark flex-fill" onclick="printStruk(${order.id})">
                <i class="bi bi-printer me-1"></i> Cetak Struk
            </button>
            <button class="btn btn-sm btn-outline-dark flex-fill" onclick="printKitchen(${order.id})">
                <i class="bi bi-journal-text me-1"></i> Tiket Dapur
            </button>
        </div>
    `;

        document.getElementById('modal-detail-body').innerHTML = html;
        new bootstrap.Modal('#modalDetail').show();
    }

    function printStruk(orderId) {
        window.open('/tokopi/views/receipt.php?order_id=' + orderId, '_blank');
    }

    function printKitchen(orderId) {
        window.open('/tokopi/views/receipt.php?order_id=' + orderId + '&mode=kitchen', '_blank');
    }

    document.querySelectorAll('.qr-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.qr-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeStatus = tab.dataset.status;
            renderOrders();
        });
    });

    loadOrders();
    setInterval(loadOrders, 5000);
    window.addEventListener('focus', loadOrders);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>