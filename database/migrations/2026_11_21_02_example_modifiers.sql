-- =====================================================================
-- Tokopi · CONTOH data opsi menu untuk tenant pertama (OPSIONAL).
-- Jalankan hanya di lokal/demo. Hapus lewat Admin > Katalog > Opsi Menu.
-- Hanya dibuat bila tenant ini belum punya grup opsi sama sekali.
-- =====================================================================
SET @t := (SELECT id FROM tenants ORDER BY id LIMIT 1);
SET @has := (SELECT COUNT(*) FROM modifier_groups WHERE tenant_id = @t);

INSERT INTO modifier_groups (tenant_id, nama, min_select, max_select, is_required, sort_order)
SELECT @t, 'Tingkat gula', 1, 1, 1, 1 FROM DUAL WHERE @has = 0;
SET @g_gula := IF(@has = 0, LAST_INSERT_ID(), NULL);

INSERT INTO modifier_groups (tenant_id, nama, min_select, max_select, is_required, sort_order)
SELECT @t, 'Pilihan susu', 0, 1, 0, 2 FROM DUAL WHERE @has = 0;
SET @g_susu := IF(@has = 0, LAST_INSERT_ID(), NULL);

INSERT INTO modifier_groups (tenant_id, nama, min_select, max_select, is_required, sort_order)
SELECT @t, 'Tambahan', 0, 3, 0, 3 FROM DUAL WHERE @has = 0;
SET @g_add := IF(@has = 0, LAST_INSERT_ID(), NULL);

INSERT INTO modifier_options (group_id, nama, price_delta, sort_order, is_default)
SELECT * FROM (
    SELECT @g_gula AS g, 'Normal' AS n, 0 AS p, 1 AS s, 1 AS d UNION ALL
    SELECT @g_gula, 'Less sugar', 0, 2, 0 UNION ALL
    SELECT @g_gula, 'Tanpa gula', 0, 3, 0 UNION ALL
    SELECT @g_susu, 'Oat milk', 8000, 1, 0 UNION ALL
    SELECT @g_susu, 'Almond milk', 8000, 2, 0 UNION ALL
    SELECT @g_add, 'Extra shot espresso', 6000, 1, 0 UNION ALL
    SELECT @g_add, 'Gula aren', 4000, 2, 0 UNION ALL
    SELECT @g_add, 'Whipped cream', 5000, 3, 0
) x WHERE x.g IS NOT NULL;

-- Pasang ke semua produk kategori kopi (slug 'coffee')
INSERT IGNORE INTO product_modifier_groups (product_id, group_id, sort_order)
SELECT p.id, g.id, g.sort_order
FROM products p
JOIN categories c ON c.id = p.category_id AND c.slug = 'coffee'
JOIN modifier_groups g ON g.id IN (@g_gula, @g_susu, @g_add)
WHERE p.tenant_id = @t;
