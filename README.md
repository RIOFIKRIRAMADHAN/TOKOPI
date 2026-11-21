# Tokopi

ERP, Point of Sale, dan QR ordering untuk coffee shop multi-cabang, lengkap dengan website company profile. Dibuat dengan PHP native + MariaDB, berjalan di XAMPP.

Aplikasi terdiri dari tiga bagian:

| Bagian | Folder | Untuk siapa | URL lokal |
|---|---|---|---|
| Website company | `company/` | Pengunjung umum | `http://localhost/tokopi/company/index.php` |
| QR ordering | `customer/` | Pelanggan (HP), scan QR di meja | `http://localhost/tokopi/customer/home.php` |
| Admin & POS | `views/` | Owner, manager, kasir, gudang, finance | `http://localhost/tokopi/login.php` |

---

## Fitur

### Website company (`company/`)
- Beranda dengan status buka/tutup, papan "Favorit bulan ini" dari data penjualan, menu terlaris, cara pesan via QR, daftar cabang, testimoni, dan program member
- Menu lengkap per kategori dengan harga per varian
- Halaman Cerita (About) dengan ilustrasi SVG beranimasi
- Cabang & Kontak: peta Google Maps per cabang + form kontak (masuk ke Inbox admin)
- Jurnal (blog), FAQ, Syarat & Ketentuan, Kebijakan Privasi

### QR ordering pelanggan (`customer/`)
- Scan QR meja → lihat menu → pesan → bayar QRIS (Midtrans) atau tunai di kasir → pantau status pesanan
- Foto produk; produk tanpa foto otomatis memakai placeholder inisial
- **Opsi menu**: tingkat gula, pilihan susu, tambahan (extra shot, dll.), tingkat es. Grup opsi bisa dibatasi untuk varian Hot atau Iced saja
- **Menu "Habis" otomatis** bila stok bahan resep di cabang itu tidak cukup
- Tambah cepat untuk menu 1 varian, bar keranjang, stepper checkout
- Akun member: poin (1 poin per Rp10.000), tier Bronze → Silver → Gold, tukar poin jadi voucher
- Mobile-first; di desktop tampil sebagai app-shell 480px di tengah layar

### Admin & POS (`views/`)
- **Dashboard**: KPI hari ini & bulan ini dengan perbandingan (vs kemarin / bulan lalu), grafik omzet 7 hari, jam sibuk, perbandingan cabang, menu terlaris, stok kritis
- **Operasional**: POS kasir, antrian pesanan QR, shift kasir, meja & cetak QR
- **Katalog**: produk (dengan upload foto), varian, kategori, **opsi menu**, resep (BOM)
- **Inventori**: bahan baku, stok per cabang, riwayat pergerakan stok
- **Keuangan**: laporan penjualan, laba rugi (grafik waterfall), pengeluaran; export CSV & print
- **Pelanggan**: data member, rewards, laporan loyalty
- **Website**: artikel/blog, testimoni, inbox pesan kontak
- **Pengaturan**: outlet, pengguna, pengaturan toko
- Tabel besar bisa dicari, diurutkan per kolom, dan dibagi per halaman

### Hak akses (role)
| Role | Akses utama |
|---|---|
| `owner` | Semua menu, termasuk outlet, pengguna, pengaturan, website |
| `manager` | Dashboard, operasional, katalog, inventori, keuangan, pelanggan, testimoni & inbox |
| `kasir` | POS, antrian QR, shift |
| `gudang` | Katalog, inventori |
| `finance` | Dashboard, keuangan, pelanggan, testimoni & inbox |

---

## Tech stack

| Layer | Teknologi |
|---|---|
| Backend | PHP 7.4 native (prosedural, PDO) |
| Database | MariaDB 10.4 (bawaan XAMPP) |
| Frontend admin & customer | Bootstrap 5.3 (CDN), Vanilla JS, Chart.js 4 |
| Frontend website company | CSS custom (`public.css`), tanpa Bootstrap |
| Pembayaran | Midtrans Core API (QRIS) + webhook |
| QR meja | `api.qrserver.com` (gambar QR dari URL meja) |
| Library PHP (Composer) | `midtrans/midtrans-php`, `dompdf/dompdf` |
| Ikon & font | Bootstrap Icons; Plus Jakarta Sans, Playfair Display, Fraunces, JetBrains Mono |

---

## Instalasi (XAMPP)

### Prasyarat
- XAMPP dengan PHP 7.4+ dan MariaDB 10.4+
- Composer

### Langkah

> Aplikasi memakai path `/tokopi/` di banyak link, jadi folder project **harus** bernama `tokopi` dan berada langsung di `htdocs`.

```bash
# 1. Clone ke htdocs dengan nama folder "tokopi"
cd /Applications/XAMPP/xamppfiles/htdocs      # Windows: C:\xampp\htdocs
git clone https://github.com/RIOFIKRIRAMADHAN/TOKOPI.git tokopi
cd tokopi

# 2. Install library PHP
composer install

# 3. Buat file environment
cp .env.example .env
#    lalu isi DB_* dan key Midtrans Sandbox

# 4. Buat database + tabel
mysql -u root -e "CREATE DATABASE tokoku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root tokoku < database/schema.sql

# 5. Isi data awal (tenant, 3 outlet, akun owner/manager/kasir)
php database/seed.php

# 6. (Opsional) contoh opsi menu untuk demo
mysql -u root tokoku < database/migrations/2026_11_21_02_example_modifiers.sql
mysql -u root tokoku < database/migrations/2026_11_21_04_example_ice_level.sql
```

Lalu jalankan Apache & MySQL di XAMPP dan buka `http://localhost/tokopi/login.php`.

Setelah login sebagai owner, tambahkan kategori, produk + varian, resep, dan stok bahan dari menu admin. Tanpa produk, menu customer dan website masih kosong.

> Di XAMPP, perintah `mysql` dan `php` ada di `xamppfiles/bin/` (macOS) atau `C:\xampp\mysql\bin` dan `C:\xampp\php` (Windows). Langkah 4 dan 6 juga bisa lewat phpMyAdmin → Import.

### Akun default (dari `database/seed.php`)
| Role | Login | Password / PIN |
|---|---|---|
| Owner | `owner@tokopi.test` | `password` |
| Manager | `manager@tokopi.test` | `password` |
| Kasir (per cabang) | `kasir1`, `kasir2`, `kasir3` | PIN `1234` |

Ganti semua password ini sebelum aplikasi diakses orang lain.

### Update database yang sudah ada
Kalau database kamu dibuat **sebelum** fitur opsi menu, jalankan migrasi berikut (aman diulang di MariaDB):

```bash
mysql -u root tokoku < database/migrations/2026_11_21_01_product_modifiers.sql
mysql -u root tokoku < database/migrations/2026_11_21_03_modifier_temperature.sql
```

`database/schema.sql` sudah berisi kedua migrasi ini, jadi instalasi baru tidak perlu menjalankannya. Detail tiap file ada di [`database/migrations/README.md`](database/migrations/README.md).

---

## Environment (`.env`)

| Variable | Keterangan | Contoh |
|---|---|---|
| `APP_URL` | Base URL aplikasi | `http://localhost/tokopi` |
| `APP_ENV` | `local` / `production` | `local` |
| `APP_DEBUG` | Tampilkan error PHP | `true` (lokal), `false` (online) |
| `APP_TIMEZONE` | Zona waktu | `Asia/Jakarta` |
| `DB_HOST`, `DB_PORT` | Server database | `127.0.0.1`, `3306` |
| `DB_NAME` | Nama database | `tokoku` |
| `DB_USER`, `DB_PASS` | Kredensial database | `root`, *(kosong)* |
| `MIDTRANS_SERVER_KEY` | Server key Midtrans | `SB-Mid-server-…` |
| `MIDTRANS_CLIENT_KEY` | Client key Midtrans | `SB-Mid-client-…` |
| `MIDTRANS_IS_PRODUCTION` | `false` untuk Sandbox | `false` |
| `MIDTRANS_QRIS_ACQUIRER` | Acquirer QRIS | `gopay` |

`.env` tidak ikut di-commit. Jangan pernah commit server key Midtrans.

---

## Pembayaran QRIS & webhook

- Pesanan QR dengan metode QRIS dibuat lewat Midtrans Core API (`customer/api/checkout.php`).
- Status lunas dikirim Midtrans ke **webhook** `api/midtrans_webhook.php`. Daftarkan URL publiknya di dashboard Midtrans: *Settings → Configuration → Payment Notification URL*, misalnya `https://<domain-kamu>/tokopi/api/midtrans_webhook.php`.
- `localhost` tidak bisa dihubungi Midtrans. Untuk demo, buka XAMPP ke internet dengan tunnel (mis. ngrok dengan domain statis) lalu pakai URL tunnel itu sebagai Notification URL.
- Tanpa webhook, pesanan tunai tetap bisa dikonfirmasi kasir dari halaman Antrian QR.

---

## Struktur project

```
tokopi/
├── actions/            # Handler form POST (produk, opsi menu, stok, user, dll.)
├── api/                # Endpoint JSON: POS, antrian QR, QRIS, webhook Midtrans
├── assets/
│   ├── css/
│   │   ├── tokens.css      # Design token (warna, spacing, tipografi)
│   │   ├── components.css  # Komponen dasar
│   │   ├── layout.css      # Layout admin, POS, customer
│   │   ├── pimg.css        # Foto produk + placeholder
│   │   ├── admin.css       # Gaya admin (sidebar, KPI, chart, tabel)
│   │   ├── customer.css    # Gaya QR ordering (app-shell, bar keranjang)
│   │   ├── public.css      # Gaya website company
│   │   └── app.css         # Bundle: tokens + pimg + components + layout
│   └── js/
│       ├── cart.js          # Keranjang (localStorage) + bar keranjang
│       ├── ui.js            # Utilitas UI customer
│       ├── admin-charts.js  # Default Chart.js + format Rupiah
│       └── admin-table.js   # Cari / urut / halaman untuk tabel admin
├── company/            # Website company profile
├── config/             # bootstrap.php, database.php (.env loader), midtrans.php
├── customer/           # QR ordering pelanggan (+ customer/api/)
├── database/
│   ├── schema.sql      # Struktur semua tabel (tanpa data)
│   ├── seed.php        # Data awal: tenant, outlet, akun
│   └── migrations/     # Perubahan skema bertahap
├── includes/           # Header/footer, helpers, menu_options.php, ilustrasi SVG
├── uploads/            # Foto produk & avatar (tidak di-commit, kecuali .htaccess)
├── views/              # Halaman admin & POS (+ views/reports/)
├── .env.example
└── composer.json
```

---

## Catatan pengembangan

- **Tanpa build step.** Edit CSS/JS langsung. Saat mengubah file CSS/JS, naikkan angka `?v=` di tag `<link>`/`<script>` supaya browser tidak memakai cache lama.
- **Warna** diambil dari `assets/css/tokens.css`. Jangan mendefinisikan ulang `:root` di halaman.
- **Foto produk** disimpan di `uploads/products/` (JPG/PNG/WEBP, maks 3 MB). Helper `product_img()` di `includes/helpers.php` menampilkan foto atau placeholder.
- **Opsi menu & stok habis** ada di `includes/menu_options.php`. Harga opsi selalu dihitung ulang di server saat checkout.
- File `_backfill*.php`, `_debug_*.php`, `_test_*.php`, `test*.php`, dan `seed_*.php` hanya untuk lokal dan di-ignore git. Jangan diunggah ke server.

## Lisensi

Project akademik untuk keperluan demo. © 2026 Tim Tokopi.
