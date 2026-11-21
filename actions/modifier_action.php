<?php
// actions/modifier_action.php — kelola grup opsi menu & pemasangannya ke produk
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager', 'gudang']);

$tenant_id = current_tenant_id();
$action    = $_GET['action'] ?? '';
$back      = '/tokopi/views/modifiers.php';

function mod_group_owned(PDO $pdo, $id, $tenant_id) {
    $st = $pdo->prepare("SELECT id FROM modifier_groups WHERE id = ? AND tenant_id = ?");
    $st->execute([(int)$id, (int)$tenant_id]);
    return (bool)$st->fetchColumn();
}

/** Simpan daftar produk untuk satu grup (hanya produk milik tenant). */
function mod_sync_products(PDO $pdo, $group_id, array $product_ids, $tenant_id) {
    $product_ids = array_values(array_unique(array_filter(array_map('intval', $product_ids))));
    $pdo->prepare("DELETE pmg FROM product_modifier_groups pmg JOIN products p ON p.id = pmg.product_id WHERE pmg.group_id = ? AND p.tenant_id = ?")
        ->execute([$group_id, $tenant_id]);
    if (!$product_ids) return;
    $in = implode(',', array_fill(0, count($product_ids), '?'));
    $st = $pdo->prepare("SELECT id FROM products WHERE tenant_id = ? AND id IN ($in)");
    $st->execute(array_merge([$tenant_id], $product_ids));
    $ins = $pdo->prepare("INSERT IGNORE INTO product_modifier_groups (product_id, group_id, sort_order) SELECT ?, id, sort_order FROM modifier_groups WHERE id = ?");
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $pid) $ins->execute([$pid, $group_id]);
}

// ============================================================
// SIMPAN GRUP (tambah / edit) + opsi + produk
// ============================================================
if ($action === 'save') {
    verify_csrf();
    $id          = (int)($_POST['id'] ?? 0);
    $nama        = trim($_POST['nama'] ?? '');
    $is_required = !empty($_POST['is_required']) ? 1 : 0;
    $max_select  = max(1, min(10, (int)($_POST['max_select'] ?? 1)));
    $is_active   = !empty($_POST['is_active']) ? 1 : 0;
    $applies_to  = in_array($_POST['applies_to'] ?? 'ALL', ['ALL', 'HOT', 'ICED'], true) ? $_POST['applies_to'] : 'ALL';
    $opts_in     = $_POST['options'] ?? [];
    $default_key = $_POST['default_option'] ?? '';

    if ($nama === '' || mb_strlen($nama) > 100) {
        flash_set('error', 'Nama grup wajib diisi (maks 100 karakter).');
        redirect($back);
    }

    // Bersihkan baris opsi
    $options = [];
    foreach ((array)$opts_in as $key => $o) {
        $on = trim($o['nama'] ?? '');
        if ($on === '') continue;
        $raw   = trim((string)($o['price'] ?? '0'));
        $price = is_numeric($raw) ? (float)$raw : (float)str_replace(['.', ','], ['', '.'], $raw);
        if ($price < 0 || $price > 1000000) { flash_set('error', "Harga opsi \"$on\" tidak valid."); redirect($back); }
        $options[] = [
            'id' => (int)($o['id'] ?? 0), 'nama' => mb_substr($on, 0, 100), 'price' => $price,
            'is_active' => !empty($o['is_active']) ? 1 : 0, 'is_default' => ((string)$key === (string)$default_key) ? 1 : 0,
        ];
    }
    if (!$options) { flash_set('error', 'Tambahkan minimal satu pilihan.'); redirect($back); }
    $active_count = count(array_filter($options, function ($o) { return $o['is_active']; }));
    if ($is_required && $active_count === 0) { flash_set('error', 'Grup wajib harus punya minimal satu pilihan aktif.'); redirect($back); }
    $max_select = min($max_select, max(1, $active_count));
    $min_select = $is_required ? 1 : 0;

    try {
        $pdo->beginTransaction();
        if ($id > 0) {
            if (!mod_group_owned($pdo, $id, $tenant_id)) throw new Exception('Grup tidak ditemukan.');
            $pdo->prepare("UPDATE modifier_groups SET nama = ?, min_select = ?, max_select = ?, applies_to = ?, is_required = ?, is_active = ? WHERE id = ? AND tenant_id = ?")
                ->execute([$nama, $min_select, $max_select, $applies_to, $is_required, $is_active, $id, $tenant_id]);
        } else {
            $sort = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM modifier_groups WHERE tenant_id = " . (int)$tenant_id)->fetchColumn();
            $pdo->prepare("INSERT INTO modifier_groups (tenant_id, nama, min_select, max_select, applies_to, is_required, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$tenant_id, $nama, $min_select, $max_select, $applies_to, $is_required, $sort, $is_active]);
            $id = (int)$pdo->lastInsertId();
        }

        // Opsi: update yang ada, tambah yang baru, hapus yang dibuang dari form
        $keep = [];
        $upd = $pdo->prepare("UPDATE modifier_options SET nama = ?, price_delta = ?, sort_order = ?, is_active = ?, is_default = ? WHERE id = ? AND group_id = ?");
        $ins = $pdo->prepare("INSERT INTO modifier_options (group_id, nama, price_delta, sort_order, is_active, is_default) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($options as $i => $o) {
            if ($o['id'] > 0) {
                $upd->execute([$o['nama'], $o['price'], $i + 1, $o['is_active'], $o['is_default'], $o['id'], $id]);
                $keep[] = $o['id'];
            } else {
                $ins->execute([$id, $o['nama'], $o['price'], $i + 1, $o['is_active'], $o['is_default']]);
                $keep[] = (int)$pdo->lastInsertId();
            }
        }
        $in = implode(',', array_fill(0, count($keep), '?'));
        $pdo->prepare("DELETE FROM modifier_options WHERE group_id = ? AND id NOT IN ($in)")->execute(array_merge([$id], $keep));

        mod_sync_products($pdo, $id, (array)($_POST['product_ids'] ?? []), $tenant_id);
        $pdo->commit();
        flash_set('success', 'Grup opsi "' . $nama . '" tersimpan.');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('error', 'Gagal menyimpan: ' . $e->getMessage());
    }
    redirect($back);
}

// ============================================================
// HAPUS GRUP (riwayat pesanan tetap aman: opsi tersimpan sebagai snapshot)
// ============================================================
elseif ($action === 'delete') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if (mod_group_owned($pdo, $id, $tenant_id)) {
        $pdo->prepare("DELETE FROM modifier_groups WHERE id = ? AND tenant_id = ?")->execute([$id, $tenant_id]);
        flash_set('success', 'Grup opsi dihapus.');
    } else {
        flash_set('error', 'Grup tidak ditemukan.');
    }
    redirect($back);
}

// ============================================================
// SIMPAN GRUP UNTUK SATU PRODUK (dari halaman detail produk)
// ============================================================
elseif ($action === 'product_groups') {
    verify_csrf();
    $product_id = (int)($_POST['product_id'] ?? 0);
    $st = $pdo->prepare("SELECT id FROM products WHERE id = ? AND tenant_id = ?");
    $st->execute([$product_id, $tenant_id]);
    if (!$st->fetchColumn()) { flash_set('error', 'Produk tidak ditemukan.'); redirect('/tokopi/views/products.php'); }

    $group_ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['group_ids'] ?? [])))));
    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM product_modifier_groups WHERE product_id = ?")->execute([$product_id]);
    if ($group_ids) {
        $in = implode(',', array_fill(0, count($group_ids), '?'));
        $st = $pdo->prepare("SELECT id, sort_order FROM modifier_groups WHERE tenant_id = ? AND id IN ($in)");
        $st->execute(array_merge([$tenant_id], $group_ids));
        $ins = $pdo->prepare("INSERT INTO product_modifier_groups (product_id, group_id, sort_order) VALUES (?, ?, ?)");
        foreach ($st->fetchAll() as $g) $ins->execute([$product_id, $g['id'], $g['sort_order']]);
    }
    $pdo->commit();
    flash_set('success', 'Opsi menu untuk produk ini tersimpan.');
    redirect('/tokopi/views/product_detail.php?id=' . $product_id);
}

redirect($back);
