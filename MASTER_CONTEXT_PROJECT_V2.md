# MASTER CONTEXT PROJECT: TOKOPI (ERP & POS COFFEE SHOP)

> **Versi:** 2.0 — Update 2026-11-10

> **Status:** Aplikasi inti 95% selesai. Fase baru: Company Profile + Profile Settings + Blog CRUD + Contact Inbox.

> **Cara pakai:** Baca seluruh dokumen dulu. Jangan ulangi saran setup/CRUD dasar — semua sudah selesai. Fokus pada Next Step (§8).

---

## 1. INFORMASI UMUM & TECH STACK

- **Nama Aplikasi:** Tokopi

- **Deskripsi:** ERP + POS + Customer QR Ordering + Company Profile Publik untuk jaringan Coffee Shop Multi-Cabang (3 cabang).

- **Tech Stack:**

  - **Backend:** PHP Native (**PHP 7.4**, Procedural, PDO)

  - **Database:** MySQL / **MariaDB 10.4** (XAMPP), nama DB: **`tokoku`**

  - **Frontend Admin:** Bootstrap 5 CDN + Vanilla JS + Chart.js

  - **Frontend Customer:** Bootstrap 5 CDN + Vanilla JS (Mobile-first)

  - **Frontend Company Profile:** Bootstrap 5 CDN + Vanilla JS (Landing page style)

  - **Payment Gateway:** Midtrans (Sandbox, QRIS)

  - **PDF Generator:** DomPDF

  - **Editor Blog:** Summernote (CDN)

  - **Version Control:** Git (sudah ada)

- **Base URL (Lokal):** `http://localhost/tokopi/` atau `http://192.168.x.x/tokopi/`

- **Base URL Company Profile:** `http://localhost/tokopi/company/`

- **Arsitektur:** Multi-tenant & Multi-outlet. Semua query operasional **wajib** filter `tenant_id` **dan** `outlet_id`. **Pengecualian:** tabel publik company profile (single-tenant) — `contact_messages`, `testimonials`, `posts` tidak punya `tenant_id`.

---

## 2. STRUKTUR FOLDER (Terkini)

```text
tokopi/
├── actions/              # Handler POST (auth, kategori, produk, shift, profile, post, dll)
├── api/                  # Endpoint JSON (pos_checkout, qris, webhook, pos_qr_update_status, dll)
├── config/               # bootstrap.php, database.php (PDO + .env manual), midtrans.php
├── customer/             # App Pelanggan (UI Mobile)
│   └── api/              # Endpoint JSON khusus customer
├── company/              # [BARU] Company Profile Publik (single tenant)
│   ├── index.php         # Homepage (hero, about preview, menu, testimoni carousel, kontak)
│   ├── about.php         # Visi misi, sejarah
│   ├── menu.php          # Menu publik (read-only dari products)
│   ├── contact.php       # Form kontak + peta + info
│   ├── contact_submit.php # Handler POST contact form (honeypot)
│   ├── faq.php           # Hardcoded
│   ├── terms.php         # Hardcoded
│   ├── privacy.php       # Hardcoded
│   ├── blog.php          # List artikel (status='published')
│   ├── blog_detail.php   # Detail by ?slug=
│   ├── _partials/        # Hero, testimonial card, dll
│   └── assets/
│       ├── css/company.css
│       └── js/company.js
├── includes/             # header.php, footer.php, auth.php, helpers.php, loyalty.php
│   ├── header_public.php # [BARU] Navbar publik company profile
│   └── footer_public.php # [BARU]
├── uploads/              # [BARU]
│   ├── avatars/          # Avatar user
│   └── .htaccess         # Proteksi eksekusi PHP
├── views/                # Backoffice Admin
│   ├── reports/          # Penjualan, Laba Rugi
│   ├── profile.php       # [BARU] Profile Settings
│   ├── contact_inbox.php # [BARU] Inbox contact messages
│   ├── testimonials.php  # [BARU] CRUD testimoni
│   └── posts.php         # [BARU] CRUD Blog
├── index.php, login.php, register.php
```

## 3. DATABASE SCHEMA

### A. Tabel Inti (sudah ada)

1. **`tenants`** — Data toko (owner)

2. **`outlets`** — Cabang (tenant_id, code, nama)

3. **`users`** — Karyawan (tenant_id, outlet_id, role ENUM('owner','manager','kasir','barista','gudang','finance'), username, pin)

4. **`categories`** — Kategori produk

5. **`products`** — Produk (nama, image)

6. **`product_variants`** — Varian SKU (temperature, size, harga_jual, hpp_manual)

7. **`ingredients`** — Bahan baku (base_unit G/ML/PCS, avg_cost)

8. **`recipes`** & **`recipe_items`** — Resep per varian (gramasi) → hitung HPP

9. **`stocks`** — Saldo stok per outlet

10. **`stock_movements`** — Ledger pergerakan stok (audit trail)

11. **`shifts`** — Shift kasir (opening_cash, expected_cash, closing_cash, difference)

12. **`tables`** — Meja QR (outlet_id, code, qr_token unik)

13. **`orders`** — Header transaksi (order_no, outlet_id, shift_id, customer_session_id, customer_account_id, source ENUM('POS','QR'), status, discount_total, cost_total, completed_at)

14. **`order_items`** — Detail (qty, harga, unit_cost_snapshot)

15. **`payments`** — Pembayaran (method CASH/QRIS, status)

16. **`expenses`** — Pengeluaran operasional

17. **`customers`** — Akun loyalty (points, total_spent, tier ENUM('BRONZE','SILVER','GOLD'))

18. **`customer_points`** — Ledger poin (idempotent via order_id)

19. **`rewards`** — Katalog hadiah (points_cost, type)

20. **`customer_redemptions`** — Voucher (status UNUSED, RESERVED, USED)

### B. Tabel Baru (Migration SQL — MariaDB 10.4 compatible)

```sql
USE tokoku;

-- 1. Contact messages
CREATE TABLE IF NOT EXISTS contact_messages (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama          VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL,
    phone         VARCHAR(30)   NULL,
    subject       VARCHAR(200)  NOT NULL,
    message       TEXT          NOT NULL,
    ip_address    VARCHAR(45)   NULL,
    is_read       TINYINT(1)    NOT NULL DEFAULT 0,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Testimonials
CREATE TABLE IF NOT EXISTS testimonials (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id       BIGINT UNSIGNED NULL,
    nama              VARCHAR(100)  NOT NULL,
    teks              TEXT          NOT NULL,
    rating            TINYINT       NOT NULL DEFAULT 5,
    is_visible        TINYINT(1)    NOT NULL DEFAULT 1,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_visible (is_visible),
    CONSTRAINT fk_testi_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. users — kolom baru untuk profile settings
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email         VARCHAR(150) NULL AFTER username,
    ADD COLUMN IF NOT EXISTS phone         VARCHAR(30)  NULL AFTER email,
    ADD COLUMN IF NOT EXISTS avatar_path   VARCHAR(255) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS last_login_at DATETIME     NULL;

-- 4. posts — Blog CRUD tanpa gambar
CREATE TABLE IF NOT EXISTS posts (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(200) NOT NULL,
    slug           VARCHAR(200) NOT NULL UNIQUE,
    excerpt        VARCHAR(300) NULL,
    content        LONGTEXT     NOT NULL,
    author_id      BIGINT UNSIGNED NULL,
    status         ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at   DATETIME     NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_pub (status, published_at),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. FITUR UTAMA & LOGIKA BISNIS

### A. Backoffice (Admin) — SUDAH SELESAI

- **Multi-Tenant & Multi-Outlet:** Filter `tenant_id` + `outlet_id` di semua query operasional. Owner bisa switch outlet. **Manager terkunci di 1 outlet.**

- **Auth:** Owner login **email**, Kasir login **username + PIN**. Ada register tenant baru.

- **POS:** Cash & QRIS (Midtrans). Cek stok real-time. Auto-deduct via `stock_movements`. Wajib buka shift (`shift_id` masuk ke `orders`). Cetak Struk 80mm + Tiket Dapur (DomPDF).

- **Shift Kasir:** `opening_cash` → `expected_cash` vs `closing_cash` → `difference`.

- **Resep & HPP:** HPP = Σ(gramasi × avg_cost). `unit_cost_snapshot` di `order_items` wajib diisi saat transaksi (laporan historis tidak berubah saat harga bahan naik).

- **Antrian QR di POS:** Kasir konfirmasi order Cash dari customer (`pos_qr_confirm_cash`), lalu proses.

- **Laporan:** Dashboard KPI (Omzet, HPP, Profit), Chart 7 hari, Penjualan, Laba Rugi (Omzet − HPP − Expenses), Pengeluaran.

- **Status Order QR:** `PAID → PREPARING → READY → COMPLETED` via `api/pos_qr_update_status.php`.

### B. Customer QR Ordering (Mobile) — SUDAH SELESAI

- **Scan QR Meja:** Deteksi meja & outlet. Guest pakai `customer_session_id` unik.

- **Loyalty:** Rp 10.000 = 1 Poin. Naik tier (BRONZE → SILVER → GOLD). Tukar reward.

- **Checkout:** QRIS (generate QR) atau Cash (masuk antrean kasir).

- **Live Tracking:** Diterima → Dibuat → Siap → Selesai via AJAX polling.

- **Voucher:** `RESERVED` saat checkout → `USED` setelah lunas.

### C. Company Profile (BARU — Public Site) — SEDANG DIKERJAKAN

- **Single tenant** (Tokopi pusat). Tabel publik **tanpa** `tenant_id`.

- **URL:** `/tokopi/company/`

- **Layout:** `header_public.php` + `footer_public.php` (file baru, **tidak** menyentuh `header.php`/`footer.php` existing).

- **Design:** Hybrid landing page — palette Espresso/Sand konsisten, hero besar, card, animasi ringan (fade-in on scroll).

- **Halaman:**

  - `index.php` — Hero, about preview, featured menu, testimoni carousel (5), kontak singkat

  - `about.php` — Visi misi, sejarah (hardcoded)

  - `menu.php` — Read-only dari `products` (aktif)

  - `contact.php` — Form + peta iframe Kemang + info alamat/telepon/email

  - `faq.php`, `terms.php`, `privacy.php` — Hardcoded

  - `blog.php`, `blog_detail.php` — dari tabel `posts` (published)

- **Contact Form:** Simpan ke `contact_messages`, **honeypot field** (tanpa captcha).

- **Testimoni:** CRUD admin, carousel di homepage. Field: nama, teks, rating 1–5, is_visible. **Tanpa foto** (pakai inisial avatar).

- **Peta:** 1 iframe Google Maps embed, titik Kemang.

- **SEO:** SKIP (hanya `<title>` standar per halaman).

- **Auth link:** Navbar publik → tombol Login ke `login.php`.

### D. Profile Settings (BARU) — SEDANG DIKERJAKAN

- **Siapa:** Semua role (owner, manager, kasir, barista, gudang, finance).

- **Bisa diubah:** nama, email, phone, avatar (upload ke `/uploads/avatars/`), ganti PIN (wajib verifikasi PIN lama).

- **Tidak bisa diubah:** username, role, tenant, outlet.

- **File:** `views/profile.php` + `actions/profile_update.php` + `actions/profile_pin_change.php`.

### E. Blog & News (BARU — CRUD Tanpa Gambar) — SEDANG DIKERJAKAN

- **Tabel:** `posts`.

- **Admin CRUD:** `views/posts.php` + `actions/post_save.php`.

- **Field:** title, slug (auto dari title, editable, cek unik), excerpt, content (Summernote), status (draft/published), published_at.

- **Public:** `company/blog.php` (list published) + `company/blog_detail.php?slug=`.

- **Sanitasi HTML server-side** sebelum simpan (allowlist tag: p, br, strong, em, u, ul, ol, li, h2, h3, h4, a, blockquote).

### F. Contact Inbox (BARU) — SEDANG DIKERJAKAN

- Owner lihat pesan masuk dari contact form. Halaman `views/contact_inbox.php`.

- Tandai read/unread, hapus.

---

## 5. UI/UX & DESIGN SYSTEM

- **Palette:** Espresso `#412D15`, Sand `#E1DCC9`, Background `#EDEAE3` / `#FAF9F5`.

- **Font:** Playfair Display (Heading), Plus Jakarta Sans (Body), JetBrains Mono (Angka/Uang).

- **Admin:** Split-screen login, sidebar hitam elegan, micro-interaction hover.

- **Customer:** Elegan, mobile-first, card layout, icon monokrom (Bootstrap Icons).

- **Company Profile:** Landing page hybrid — palette brand konsisten, hero besar, testimoni carousel, animasi fade-in on scroll.

- **Responsif:** Breakpoint Bootstrap (`sm 576`, `md 768`, `lg 992`, `xl 1200`). Prioritas terkecil **360px** (Android standar). **Dosen minta semua halaman responsif.**

---

## 6. BUG FIX & KEAMANAN (WAJIB DIPERTAHANKAN)

1. **Multi-tenant Data Leak:** Semua query operasional filter `tenant_id` + `outlet_id`.

2. **Guest Privacy:** Riwayat guest filter `customer_session_id`; login filter `customer_account_id`.

3. **Race Condition Stok:** `SELECT ... FOR UPDATE` dalam `beginTransaction()`.

4. **Loyalty Idempotent:** Cek `order_id` di `customer_points` di 3 titik: `api_check_status`, `midtrans_webhook`, `pos_qr_confirm_cash`.

5. **Voucher Double-Use:** Guard status `RESERVED`.

6. **Shift Kosong:** `pos_checkout.php` memasukkan `shift_id` ke `orders`.

7. **Modal Backdrop Bug:**

   - `show.bs.modal` di `footer.php` hapus `.modal-backdrop` + `.modal-open`.

   - Matikan `transform: translateY` pada `.btn:hover` di dalam `.modal`.

8. **Session Fixation:** `session_regenerate_id(true)` setelah login.

9. **Brute Force PIN:** Limit 5x gagal login.

10. **File Debug:** `_backfill.php`, `test_env.php`, dll sudah dihapus/diblokir `.htaccess`.

11. **Error Disclosure:** **JANGAN** `echo $e->getMessage()` di catch endpoint API. Pakai `error_log()` + pesan generik.

12. **Race di Update Status Order:** Tambah `AND status = ?` (status lama) di WHERE UPDATE + cek `rowCount()`. Kalau 0 → 409 "status sudah berubah".

---

## 7. ATURAN UNTUK AI DI ROOM BARU

1. **Anggap sudah baca seluruh konteks ini.**

2. **Jangan** ulangi saran setup, CRUD dasar, struktur folder, atau fitur yang selesai.

3. **Fokus Next Step** (§8).

4. **Kode = PHP 7.4 Native + PDO**, tanpa framework.

5. **SQL kompatibel MariaDB 10.4 / XAMPP.**

6. **Jangan truncate/delete data asli.** Seeder idempotent.

7. **User suka kode lengkap siap paste per file**, dengan **checklist test per modul**.

8. **Format laporan bug dari user:**

   ```text
   Modul: [...]
   File: [...]
   Aksi: [...]
   Yang diharapkan: [...]
   Yang terjadi: [...]
   Error message / log: [...]
   Browser: [...]
   ```

9. **Commit git setelah modul lulus test**, bukan setengah jalan.

10. **Kalau ada keputusan arsitektur baru, update dokumen ini.**

---

## 8. STATUS & NEXT STEP

### Status

- **Inti (POS, Customer QR, Loyalty, Laporan):** 95% selesai.

- **Database:** Bersih, siap seeder idempotent.

- **Company Profile + Profile Settings + Blog CRUD:** baru mulai, migration SQL siap.

- **Laporan TA:** belum dibuat.

- **PPT HTML:** `presentasi-tokopi.html` sudah jadi — perlu review & sinkronisasi angka dengan DB terbaru.

- **Hosting:** Ngrok (demo) atau shared hosting. **Hindari InfinityFree** (webhook Midtrans diblokir).

### Next Step — Urutan Eksekusi

| Fase | Modul | Estimasi | Risiko |
|---|---|---|---|
| **1** | Public layout (`header_public.php` + `footer_public.php` + `company.css`) | 1–2 jam | 🟢 Aman |
| **2** | Homepage + About + FAQ + T&C + Privacy | 2–3 jam | 🟢 Aman |
| **3** | Contact form + inbox admin + honeypot | 2 jam | 🟡 Sedang |
| **4** | Testimoni — CRUD + carousel + dummy | 2 jam | 🟢 Aman |
| **5** | Profile Settings (semua user) | 2–3 jam | 🟡 Sedang (sentuh `users`) |
| **6** | Blog CRUD (tanpa gambar) | 3–4 jam | 🟡 Sedang |
| **7** | Responsive audit halaman publik | 1–2 jam | 🟢 Aman |
| **8** | Test end-to-end + fix bug | 2–4 jam | — |

**Total: 15–22 jam kerja tersebar.** Prinsip: publik dulu → sentuh `users` belakangan → blog paling akhir (paling gampang dipotong kalau mepet).

### Setelah Selesai

1. Finalisasi laporan TA Bab 1–5.

2. Review & finalisasi PPT.

3. Deploy demo (Ngrok / shared hosting).

---

## 9. KEPUTUSAN DESAIN FINAL (Jangan Diubah Tanpa Konfirmasi)

| # | Topik | Keputusan |
|---|---|---|
| 1 | Company Profile tenant | Single tenant (Tokopi pusat) |
| 2 | URL Company Profile | Subfolder `/tokopi/company/` |
| 3 | Layout Company Profile | File baru (`header_public.php`, `footer_public.php`) |
| 4 | Design Company Profile | Hybrid landing page |
| 5 | Contact Form submit | Simpan DB `contact_messages` + inbox admin |
| 6 | Anti-spam | Honeypot (tanpa captcha) |
| 7 | Peta | 1 iframe Google Maps, titik Kemang |
| 8 | Testimoni | Tabel `testimonials`, CRUD admin, carousel 5, tanpa foto, rating 1–5, dummy disediakan |
| 9 | About/FAQ/T&C/Privacy | Hardcoded |
| 10 | Blog & News | CRUD tanpa gambar, Summernote, admin panel |
| 11 | Profile Settings | Edit: nama, email, phone, avatar, ganti PIN. Tidak bisa: username, role |
| 12 | User Management (owner→karyawan) | SKIP — future work Bab 5 |
| 13 | SEO / Meta | SKIP |
| 14 | Responsif | Bootstrap breakpoint, prioritas 360px |
| 15 | Upload folder | `/uploads/avatars/` + `.htaccess` proteksi |

---

## 10. CATATAN TEKNIS — PERLU DIWASPADAI

- **Tabel `tables`** — selalu backtick (`` `tables` ``).

- **Tenant filter manual** — setiap query baru wajib `WHERE tenant_id = ?`. Sebaiknya ada helper `tenant_where()`.

- **`expected_cash`** valid hanya jika semua CASH tercatat di `payments` status lunas. Pastikan QRIS pending tidak ikut terhitung.

- **`avg_cost`** — pastikan metodologi (moving average?) konsisten.

- **Idempotency poin** — per `order_id` + jenis ledger (earn vs redeem), jangan saling memblokir.

- **`current_outlet_id()`** — pastikan mengembalikan outlet aktif session (owner switch), bukan outlet default.

- **Seeder** — kalau seed order historis, `unit_cost_snapshot` pakai `avg_cost` **pada tanggal order**, bukan harga sekarang.

- **Blog content** — tanpa gambar, pakai Summernote. Sanitasi HTML server-side (allowlist tag) sebelum simpan.

- **Slug blog** — auto dari title (lowercase, strip non-alphanumeric, dash). Cek unik; kalau sudah ada tambah `-2`, `-3`, dst.

- **Honeypot** — field hidden via CSS (`display:none`), nama field menipu (misal `website_url`). Kalau terisi → reject silently (return 200 OK tapi tidak insert).

- **Avatar upload** — validasi MIME (`image/jpeg`, `image/png`, `image/webp`), max 2MB, rename ke `user_{id}_{timestamp}.{ext}`, simpan di `/uploads/avatars/`.