-- =====================================================================
-- Tokopi · CONTOH grup "Tingkat es" (OPSIONAL, untuk demo).
-- Wajib, hanya untuk varian ICED, dipasang ke produk yang punya varian ICED.
-- Tidak dibuat ulang bila grup bernama "Tingkat es" sudah ada.
-- =====================================================================
SET @t := (SELECT id FROM tenants ORDER BY id LIMIT 1);
SET @has := (SELECT COUNT(*) FROM modifier_groups WHERE tenant_id = @t AND LOWER(nama) IN ('tingkat es', 'tingkat ice', 'ice level'));
SET @sort := (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM modifier_groups WHERE tenant_id = @t);

INSERT INTO modifier_groups (tenant_id, nama, min_select, max_select, applies_to, is_required, sort_order)
SELECT @t, 'Tingkat es', 1, 1, 'ICED', 1, 0 FROM DUAL WHERE @has = 0;
SET @g := IF(@has = 0, LAST_INSERT_ID(), NULL);

INSERT INTO modifier_options (group_id, nama, price_delta, sort_order, is_default)
SELECT * FROM (
    SELECT @g AS g, 'Normal ice' AS n, 0 AS p, 1 AS s, 1 AS d UNION ALL
    SELECT @g, 'Less ice', 0, 2, 0 UNION ALL
    SELECT @g, 'Extra ice', 0, 3, 0 UNION ALL
    SELECT @g, 'Es dipisah', 0, 4, 0
) x WHERE x.g IS NOT NULL;

INSERT IGNORE INTO product_modifier_groups (product_id, group_id, sort_order)
SELECT DISTINCT p.id, @g, 0
FROM products p
JOIN product_variants v ON v.product_id = p.id AND v.temperature = 'ICED' AND v.is_active = 1
WHERE p.tenant_id = @t AND @g IS NOT NULL;
