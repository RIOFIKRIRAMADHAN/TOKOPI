# Tokopi

Sistem ERP & Point of Sale untuk Coffee Shop Multi-Cabang. Kelola cabang, stok, pelanggan, loyalty, dan laporan dalam satu platform.

## Fitur Utama

### 🏢 Multi-Tenant & Multi-Outlet
- Manajemen tenant (brand/grup usaha)
- Outlet/cabang dengan konfigurasi sendiri
- Role-based access: Owner, Manager, Kasir

### 🛒 Point of Sale (POS)
- Interface kasir responsif (desktop & mobile)
- Cart floating, keranjang side panel
- Pembayaran: Tunai, QRIS, E-Wallet
- Cetak struk thermal 80mm

### 📦 Manajemen Produk & Stok
- Produk dengan varian (ukuran, topping, dll)
- Kategori & tag produk
- Resep (BOM) untuk auto-deduksi stok bahan
- Transfer antar outlet
- Stock take & history

### 👥 Customer & Loyalty
- Customer portal (PWA-style): scan QR meja → order → bayar
- Loyalty points: 1 poin / Rp 10.000
- Tier: Bronze → Silver → Gold
- Redeem voucher kopi gratis & diskon
- Riwayat order & profil pelanggan

### 📊 Laporan & Analitik
- Penjualan harian/bulanan per outlet
- Profit & Loss (COGS vs Revenue)
- Produk terlaris & margin
- Laporan pengeluaran per outlet
- Loyalty & retention metrics

### 🌐 Company Profile (Public)
- Home, About, Menu, Blog, Contact
- Legal: Privacy Policy, Terms, FAQ
- Blog CRUD (admin) + public view
- SEO-friendly, responsive

### 🎨 Design System
- Tokens: warna, spacing, typography, shadow, radius
- Components: button, card, form, table, badge, modal, dll
- Layouts: Admin, POS, Customer (PWA), Public
- Light theme only (no dark mode)

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | PHP 8.1+ (native, no framework) |
| Database | MySQL / MariaDB |
| Frontend | Vanilla JS + CSS Custom Properties |
| CSS | Design tokens + components + layouts (no framework) |
| Icons | Bootstrap Icons |
| Fonts | Plus Jakarta Sans, Playfair Display, JetBrains Mono |
| QR | Endroid QR Code |

## Struktur Project

```
tokopi/
├── actions/           # Endpoint POST (auth, orders, customers, dll)
├── api/               # API endpoints (QRIS, webhooks)
├── assets/
│   ├── css/
│   │   ├── tokens.css      # Design tokens (warna, spacing, dll)
│   │   ├── components.css  # UI components
│   │   ├── layout.css      # Page layouts
│   │   └── app.css         # Bundle (import all)
│   └── js/
│       ├── ui.js           # UI utilities
│       └── cart.js         # Cart logic
├── company/           # Public pages (Home, Blog, Menu, About, Contact, Legal)
├── config/            # Bootstrap, DB, constants
├── customer/          # Customer portal (PWA-style)
├── includes/          # Shared partials (header, footer)
├── uploads/           # File uploads (gitignored)
├── views/             # Admin pages (dashboard, POS, products, reports, dll)
├── .gitignore
├── composer.json      # PHP dependencies (Endroid QR Code)
└── README.md
```

## Instalasi

### Prasyarat
- PHP 8.1+
- MySQL/MariaDB 10.5+
- Composer
- Web server (Apache/Nginx) dengan mod_rewrite

### Langkah

```bash
# 1. Clone repo
git clone https://github.com/RIOFIKRIRAMADHAN/TOKOPI.git
cd TOKOPI

# 2. Install PHP dependencies
composer install

# 3. Buat database
mysql -u root -p < database/schema.sql  # (jika ada file schema)
# Atau buat manual via phpMyAdmin

# 4. Konfigurasi environment
cp .env.example .env
# Edit .env: DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_URL, dll

# 5. Setup virtual host / document root ke folder project
# Contoh Apache:
# DocumentRoot /path/to/tokopi
# <Directory /path/to/tokopi>
#     AllowOverride All
#     Require all granted
# </Directory>

# 6. Akses di browser
# http://localhost/tokopi/login.php
```

### Default Login (setelah seed)
- **Owner**: `owner@tokopi.test` / `password`
- **Kasir**: `kasir1` / PIN `123456`

## Environment Variables

| Variable | Deskripsi | Contoh |
|----------|-----------|--------|
| `APP_URL` | Base URL aplikasi | `http://localhost/tokopi` |
| `DB_HOST` | Host database | `localhost` |
| `DB_NAME` | Nama database | `tokopi` |
| `DB_USER` | User database | `root` |
| `DB_PASS` | Password database | `secret` |
| `MIDTRANS_SERVER_KEY` | Midtrans server key (production) | `Mid-server-xxx` |
| `MIDTRANS_CLIENT_KEY` | Midtrans client key | `Mid-client-xxx` |
| `MIDTRANS_IS_PRODUCTION` | `true` atau `false` | `false` |

## Development

### Menjalankan di Local (XAMPP)
```bash
# Letakkan folder di htdocs
# Start Apache & MySQL di XAMPP
# Akses: http://localhost/tokopi/login.php
```

### CSS Development
```bash
# Tokopi pakai CSS native (no build step)
# Edit: assets/css/tokens.css, components.css, layout.css
# Bundle: assets/css/app.css (import third-party + tokens + components + layout)
```

### Database Seeding
```bash
# Jalankan seeder manual via browser atau CLI
php seed_ultimate.php
```

## Deployment (Production)

1. **Server**: Ubuntu 22.04 + Nginx + PHP-FPM 8.2 + MariaDB
2. **SSL**: Let's Encrypt (Certbot)
3. **Env**: Set `APP_ENV=production`, `MIDTRANS_IS_PRODUCTION=true`
4. **Uploads**: Symlink `uploads/` ke shared storage
5. **Queue/Worker**: (jika pakai) Supervisor untuk background jobs

## Lisensi

Proprietary — Hak cipta © 2024 Tokopi. Untuk internal use only.

---

**Dibuat dengan ☕ oleh Tim Tokopi**