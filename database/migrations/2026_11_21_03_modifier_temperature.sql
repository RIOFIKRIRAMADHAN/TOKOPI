-- =====================================================================
-- Tokopi · Migrasi: grup opsi bisa dibatasi untuk varian Hot / Iced
-- Contoh: "Tingkat es" hanya muncul untuk varian ICED.
--   ALL  = semua varian (termasuk varian tanpa suhu, mis. makanan)
--   HOT  = hanya varian temperature = 'HOT'
--   ICED = hanya varian temperature = 'ICED'
-- MariaDB: aman dijalankan berulang. MySQL: hapus "IF NOT EXISTS", jalankan sekali.
-- =====================================================================
ALTER TABLE modifier_groups
    ADD COLUMN IF NOT EXISTS applies_to ENUM('ALL','HOT','ICED') NOT NULL DEFAULT 'ALL' AFTER max_select;
