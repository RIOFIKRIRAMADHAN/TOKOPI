-- =====================================================================
-- Tokopi · Migrasi: Opsi menu (modifier) per produk
-- Aman dijalankan berulang di MariaDB (XAMPP memakai MariaDB).
-- Untuk MySQL murni: hapus kata "IF NOT EXISTS" pada ADD COLUMN lalu jalankan SEKALI saja.
--
-- Isi:
--   1. modifier_groups  + sort_order, is_active
--   2. modifier_options + is_active, is_default
--   3. product_modifier_groups (produk <-> grup opsi)
--   4. order_items      + modifiers (snapshot JSON), modifier_total
-- =====================================================================

ALTER TABLE modifier_groups
    ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 0 AFTER is_required,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order;

ALTER TABLE modifier_options
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER sort_order,
    ADD COLUMN IF NOT EXISTS is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

CREATE TABLE IF NOT EXISTS product_modifier_groups (
    product_id INT(10) UNSIGNED NOT NULL,
    group_id   INT(10) UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (product_id, group_id),
    KEY idx_pmg_group (group_id),
    CONSTRAINT fk_pmg_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_pmg_group   FOREIGN KEY (group_id)   REFERENCES modifier_groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Snapshot opsi yang dipilih per item, contoh:
-- [{"group":"Tingkat gula","option_id":3,"nama":"Less sugar","price":0}]
-- modifier_total = total tambahan harga PER UNIT (sudah termasuk di harga_satuan).
ALTER TABLE order_items
    ADD COLUMN IF NOT EXISTS modifiers LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL AFTER cost_breakdown,
    ADD COLUMN IF NOT EXISTS modifier_total DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER modifiers;
