<?php
// includes/menu_options.php
// Opsi menu (modifier) & ketersediaan menu ("Habis") — dipakai customer, checkout, dan admin.

/**
 * Grup opsi aktif untuk sekumpulan produk.
 * @return array [product_id => [ [id, nama, min, max, required, options => [[id, nama, price, is_default]]] ]]
 */
function menu_option_groups(PDO $pdo, array $product_ids) {
    $product_ids = array_values(array_unique(array_map('intval', $product_ids)));
    if (!$product_ids) return [];
    $in = implode(',', array_fill(0, count($product_ids), '?'));
    $st = $pdo->prepare("
        SELECT pmg.product_id, g.id AS group_id, g.nama AS group_nama, g.min_select, g.max_select, g.is_required, g.applies_to,
               o.id AS option_id, o.nama AS option_nama, o.price_delta, o.is_default
        FROM product_modifier_groups pmg
        JOIN modifier_groups g  ON g.id = pmg.group_id AND g.is_active = 1
        JOIN modifier_options o ON o.group_id = g.id AND o.is_active = 1
        WHERE pmg.product_id IN ($in)
        ORDER BY pmg.product_id, pmg.sort_order, g.sort_order, g.id, o.sort_order, o.id
    ");
    $st->execute($product_ids);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $pid = (int)$r['product_id']; $gid = (int)$r['group_id'];
        if (!isset($out[$pid][$gid])) {
            $req = (int)$r['is_required'] === 1;
            $min = max($req ? 1 : 0, (int)$r['min_select']);
            $max = max($min, (int)$r['max_select'], 1);
            $out[$pid][$gid] = ['id' => $gid, 'nama' => $r['group_nama'], 'min' => $min, 'max' => $max, 'required' => $min > 0, 'applies_to' => $r['applies_to'] ?: 'ALL', 'options' => []];
        }
        $out[$pid][$gid]['options'][] = [
            'id' => (int)$r['option_id'], 'nama' => $r['option_nama'],
            'price' => (float)$r['price_delta'], 'is_default' => (int)$r['is_default'] === 1,
        ];
    }
    foreach ($out as $pid => $groups) $out[$pid] = array_values($groups);
    return $out;
}

/**
 * Validasi opsi yang dikirim pelanggan untuk satu produk.
 * Harga SELALU dihitung ulang di server dari database.
 * @return array ['snapshot' => [...], 'total' => float]
 * @throws Exception dengan pesan yang bisa ditampilkan ke pelanggan
 */
function menu_validate_options(array $groups, array $option_ids, $product_nama, $temperature = null) {
    // Hanya grup yang berlaku untuk suhu varian ini (mis. "Tingkat es" hanya untuk ICED)
    $groups = menu_groups_for_temperature($groups, $temperature);
    $option_ids = array_values(array_unique(array_map('intval', $option_ids)));
    $by_option = [];
    foreach ($groups as $g) foreach ($g['options'] as $o) $by_option[$o['id']] = [$g, $o];

    $picked = [];
    foreach ($option_ids as $oid) {
        if (!isset($by_option[$oid])) throw new Exception("Pilihan untuk {$product_nama} sudah tidak tersedia. Hapus dari keranjang lalu pilih lagi.");
        $picked[$by_option[$oid][0]['id']][] = $by_option[$oid][1];
    }

    $snapshot = []; $total = 0.0;
    foreach ($groups as $g) {
        $n = count($picked[$g['id']] ?? []);
        if ($n < $g['min']) throw new Exception("Pilih {$g['nama']} untuk {$product_nama}.");
        if ($n > $g['max']) throw new Exception("{$g['nama']} untuk {$product_nama} maksimal {$g['max']} pilihan.");
        foreach ($picked[$g['id']] ?? [] as $o) {
            $snapshot[] = ['group' => $g['nama'], 'option_id' => $o['id'], 'nama' => $o['nama'], 'price' => $o['price']];
            $total += $o['price'];
        }
    }
    return ['snapshot' => $snapshot, 'total' => $total];
}

/** Saring grup sesuai suhu varian: ALL selalu berlaku; HOT/ICED hanya untuk varian dengan suhu itu. */
function menu_groups_for_temperature(array $groups, $temperature) {
    $t = strtoupper((string)$temperature);
    return array_values(array_filter($groups, function ($g) use ($t) {
        $a = $g['applies_to'] ?? 'ALL';
        return $a === 'ALL' || $a === $t;
    }));
}

/** Ringkasan opsi untuk ditampilkan: "Less sugar, Oat milk (+8rb)". */
function menu_options_label($modifiers_json) {
    $mods = is_array($modifiers_json) ? $modifiers_json : json_decode((string)$modifiers_json, true);
    if (!$mods) return '';
    return implode(', ', array_map(function ($m) { return $m['nama']; }, $mods));
}

/**
 * Varian yang HABIS di outlet: ada bahan resep yang stoknya kurang dari kebutuhan 1 porsi.
 * Aturannya sama dengan pengecekan stok saat checkout. Varian tanpa resep dianggap selalu tersedia.
 * @return array<int,bool> [variant_id => true] untuk yang habis
 */
function menu_soldout_variants(PDO $pdo, $outlet_id, array $variant_ids) {
    $outlet_id = (int)$outlet_id;
    $variant_ids = array_values(array_unique(array_map('intval', $variant_ids)));
    if ($outlet_id <= 0 || !$variant_ids) return [];
    $in = implode(',', array_fill(0, count($variant_ids), '?'));
    $st = $pdo->prepare("
        SELECT r.variant_id
        FROM recipes r
        JOIN recipe_items ri ON ri.recipe_id = r.id
        LEFT JOIN stocks s ON s.ingredient_id = ri.ingredient_id AND s.outlet_id = ?
        WHERE r.variant_id IN ($in)
        GROUP BY r.variant_id
        HAVING SUM(COALESCE(s.qty, 0) < ri.qty / GREATEST(r.yield_qty, 1)) > 0
    ");
    $st->execute(array_merge([$outlet_id], $variant_ids));
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $vid) $out[(int)$vid] = true;
    return $out;
}

/** Produk yang SEMUA varian aktifnya habis di outlet. @return array<int,bool> */
function menu_soldout_products(PDO $pdo, $outlet_id, array $product_ids) {
    $product_ids = array_values(array_unique(array_map('intval', $product_ids)));
    if ((int)$outlet_id <= 0 || !$product_ids) return [];
    $in = implode(',', array_fill(0, count($product_ids), '?'));
    $st = $pdo->prepare("SELECT id, product_id FROM product_variants WHERE is_active = 1 AND product_id IN ($in)");
    $st->execute($product_ids);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out_v = menu_soldout_variants($pdo, $outlet_id, array_column($rows, 'id'));
    $total = []; $out = [];
    foreach ($rows as $r) {
        $pid = (int)$r['product_id'];
        $total[$pid] = ($total[$pid] ?? 0) + 1;
        if (!empty($out_v[(int)$r['id']])) $out[$pid] = ($out[$pid] ?? 0) + 1;
    }
    $res = [];
    foreach ($out as $pid => $n) if ($n >= $total[$pid]) $res[$pid] = true;
    return $res;
}
