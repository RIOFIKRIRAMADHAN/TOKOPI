# Migrasi database

Jalankan berurutan sesuai nama file, misalnya lewat phpMyAdmin (tab **Import**) atau CLI:

```bash
mysql -u root tokoku < database/migrations/2026_11_21_01_product_modifiers.sql
```

| File | Wajib? | Isi |
|------|--------|-----|
| `2026_11_21_01_product_modifiers.sql` | **Wajib** | Tabel `product_modifier_groups`, kolom `is_active`/`sort_order`/`is_default` di grup & opsi, kolom `modifiers` + `modifier_total` di `order_items`. |
| `2026_11_21_02_example_modifiers.sql` | Opsional (demo) | Contoh grup: Tingkat gula, Pilihan susu, Tambahan — dipasang ke produk kategori `coffee`. Hanya dibuat bila tenant belum punya grup opsi. |

Catatan:
- File 01 aman dijalankan berulang di **MariaDB**. Di **MySQL**, hapus `IF NOT EXISTS` pada baris `ADD COLUMN` dan jalankan sekali.
- Pesanan lama tidak terpengaruh: kolom baru bernilai `NULL` / `0`.
