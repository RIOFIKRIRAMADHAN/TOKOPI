# Review UI/UX Tokopi

> Cakupan: `customer/` (QR ordering, mobile-first), `views/` (admin dashboard), `company/` (company profile).
> Metode: review source code (server lokal tidak berjalan saat review, jadi belum ada screenshot). Pedoman: skill *ui-ux-pro-max* (aksesibilitas, touch target, responsive, typography/color, data-dense dashboard).
> Status: **semua 4 fase sudah diimplementasikan (21 Nov 2026), belum di-commit.** Lihat bagian 5 untuk ringkasan perubahan.

Label prioritas: **P0** = rusak atau menyesatkan, **P1** = dampak UX besar, **P2** = polish.

---

## 0. Masalah lintas folder (fondasi)

Masalah ini muncul di ketiga folder. Perbaikannya paling hemat kalau dikerjakan **sebelum** redesign per halaman.

| # | Prio | Temuan | Bukti | Usulan |
|---|------|--------|-------|--------|
| 0.1 | P0 | **Ada dua palet espresso yang berbeda.** `tokens.css` memakai `--color-espresso: #2D1B0A`, sedangkan 12 file customer dan `header_public.php` mendefinisikan ulang `--espresso: #412D15` di `:root` masing-masing. Akibatnya warna brand tidak konsisten antar halaman. | `assets/css/tokens.css:8`, `includes/header_public.php`, `customer/cart.php`, `checkout.php`, `track.php`, dll. | Satu sumber token. Semua halaman memuat `app.css` dan tidak boleh lagi mendefinisikan `:root` sendiri. |
| 0.2 | P0 | **Admin memakai variabel yang tidak ada.** `includes/header.php` memakai `var(--espresso)` dan `var(--sand-light)`, padahal token admin bernama `--color-*`. Akibatnya badge outlet dan judul topbar jatuh ke warna default. | `includes/header.php` (`.outlet-badge`, `.topbar .page-title`, `.user-avatar`) | Ganti ke `--color-espresso` / `--color-sand-light`, atau tambahkan alias di `tokens.css`. |
| 0.3 | P1 | **Styling tersebar.** Setiap halaman punya blok `<style>` sendiri, ratusan `style="..."` inline, dan `!important` di mana-mana (contoh: `company/index.php` meng-override hampir semua warna dengan `!important`). Komponen yang sama (`.btn-primary`, `.bottom-nav`, `.page-hero`) didefinisikan ulang di banyak file. | `customer/orders.php` mendefinisikan ulang `.bottom-nav`; `.page-hero` di-copy-paste ke 5 halaman company | Pindahkan ke `components.css`/`layout.css`. Satu komponen, satu definisi. |
| 0.4 | P1 | **Emoji dipakai sebagai ikon/UI** (🎁 ☕ 🔥 ✅ 👍), padahal Bootstrap Icons sudah dimuat. Tampilannya beda di tiap OS dan terasa tidak profesional. | `customer/home.php`, `rewards.php`, `product.php` (toast), `layout.css` (`.promo-hero::after`), `views/dashboard.php` | Ganti dengan ikon SVG / Bootstrap Icons. |
| 0.5 | P1 | **Fokus keyboard dihilangkan** (`box-shadow: none` saat `:focus`) dan tombol ikon tidak punya `aria-label` (tombol back, `+`/`−` qty, hapus item, toggler navbar). | `company/faq.php` (`.accordion-button:focus`), `customer/product.php` (`.btn-back-float`), `customer/cart.php` | Pakai `:focus-visible` dengan ring yang jelas dan tambahkan `aria-label`. |
| 0.6 | P2 | Path ditulis hardcode `/tokopi/...` di semua link dan asset. | semua file | Gunakan helper `url()` / `$APP_BASE` (sudah ada pola di `header_public.php`). |
| 0.7 | P2 | `alert()` / `confirm()` bawaan browser dipakai untuk error dan konfirmasi (12× di `pos.php`, 9× di `qr_orders.php`, 8× di `checkout.php`). | | Toast + modal konfirmasi yang konsisten (`ui.js` sudah ada, bisa diperluas). |

---

## 1. `customer/`: QR Ordering (mobile-first)

### Yang sudah baik
- Alur jelas: scan meja → menu → detail → cart → checkout → QRIS/cash → track.
- Bottom nav 4 item, cart float, sticky CTA "Tambah ke Keranjang" yang langsung menampilkan total. Pola ini sudah tepat.
- Halaman tracking punya step status yang jelas.

### Temuan

| # | Prio | Temuan | Lokasi | Usulan |
|---|------|--------|--------|--------|
| C1 | P0 | **Di desktop layout melebar 100%.** Belum ada container `max-width` di home, menu, cart, atau checkout. Bottom nav, list menu, dan sticky CTA terbentang selebar layar. (`t.php`, `track.php`, dan `login` sudah membatasi 400–440px, sehingga perilakunya tidak konsisten.) | `layout.css` `.customer-layout`, `.bottom-nav`, `.cart-float`, `.sticky-cta` | Bungkus semua halaman customer dengan **app-shell max 480px di tengah**, dengan background luar berwarna sand dan elemen fixed (nav, CTA, cart) ikut dibatasi `max-width` + `left:50%; transform`. Ini cara standar QR-order/PWA di desktop, murah, dan tidak merusak mobile. Opsional di ≥1024px: grid menu 2 kolom dan cart sebagai side panel. |
| C2 | P1 | **Tidak ada foto produk.** Semua produk ditampilkan sebagai ikon cangkir di atas background warna yang dirotasi (`bg-dark` / `bg-sand` / `bg-cream`). Produk berbeda terlihat sama, jadi user susah memilih. (Koreksi: kolom `products.image_path` ternyata sudah ada di DB, hanya belum dipakai dan belum ada form upload.) | `home.php`, `menu.php`, `product.php` `.hero` | Tambah kolom `image_path` + upload di admin. Selama gambar belum ada, pakai placeholder yang **informatif** (inisial produk + warna per kategori), bukan ikon generik. |
| C3 | P1 | **Tombol "+" di list menu bukan tombol.** Itu hanya `<span>` di dalam link ke detail, ukurannya 32×32 (di bawah 44px), dan user mengira bisa langsung menambahkan ke keranjang. | `menu.php` `.menu-add` | Jadikan quick-add sungguhan (untuk produk 1 varian) atau ganti jadi chevron. Target sentuh minimal 44px. |
| C4 | P1 | **Error checkout lewat `alert()`**, termasuk pesan teknis "Error backend. Cek Console (F12)" yang terlihat oleh pelanggan. | `checkout.php:548-650` | Error inline di dekat field (nama wajib diisi) + toast untuk error jaringan. Pesan dalam bahasa manusia, tanpa menyebut F12. |
| C5 | P1 | **State "belum scan meja" tidak konsisten**: kadang badge merah, kadang banner merah, kadang link diam-diam diarahkan ke `t.php`. User bisa menjelajah menu lalu baru tahu tidak bisa pesan setelah mengetuk produk. | `home.php`, `menu.php` | Satu pola: banner info (bukan merah/danger) di atas yang berisi CTA "Scan QR Meja". Produk tetap bisa dilihat, tapi CTA di detail diganti "Scan meja untuk memesan". |
| C6 | P1 | Tidak ada langkah/progress di checkout (Keranjang → Data → Bayar), sehingga user tidak tahu posisinya. | `cart.php`, `checkout.php`, `qris.php` | Stepper 3 langkah yang ringkas di header. |
| C7 | P2 | Header home terlalu tinggi (brand + tagline + greeting + badge meja) sehingga konten utama terdorong ke bawah. Promo hero hanya berisi teks tanpa aksi. | `home.php` | Header ringkas (logo + meja dalam satu baris), promo jadi carousel yang bisa diklik ke produk. |
| C8 | P2 | Quick actions (Pesan/Order/Rewards/Profil) menduplikasi bottom nav. | `home.php .qa-grid` | Ganti dengan kartu Poin & Voucher (info baru), bukan navigasi duplikat. |
| C9 | P2 | Teks 11px (`--text-xs`) dipakai untuk label nav, harga, dan deskripsi. Terlalu kecil di HP. | `layout.css` | Minimum 12px untuk label dan 14–16px untuk body/harga. |
| C10 | P2 | Toast "✅ Ditambahkan" tidak diumumkan ke screen reader, dan badge cart tidak beranimasi. | `product.php` | `role="status"` + animasi kecil (bump) di badge cart. |
| C11 | P2 (bug) | `btoa(note)` akan error kalau catatan mengandung emoji/karakter non-Latin, sehingga tombol tambah ke keranjang gagal. | `product.php` `tambahKeCart()` | Encode dengan `encodeURIComponent` dulu, atau hash sederhana. |

---

## 2. `views/`: Admin Dashboard

### Yang sudah baik
- Sidebar dikelompokkan per section, topbar sticky, ada outlet switcher.
- Laporan Sales/Laba Rugi sudah punya preset tanggal, print, dan export CSV.
- Hampir semua tabel sudah dibungkus `table-responsive`.

### Temuan

| # | Prio | Temuan | Lokasi | Usulan |
|---|------|--------|--------|--------|
| A1 | P0 | **Ikon POS kosong.** `bi-cash-register` tidak ada di Bootstrap Icons 1.11.3 (yang tersedia hanya `bi-cash`, `bi-cash-coin`, `bi-cash-stack`). Sudah dicek ke CSS CDN. | `includes/header.php:219` | Ganti ke `bi-cash-stack` / `bi-calculator`. |
| A2 | P1 | **Semua badge `bg-success` berdenyut terus** (`animation: softPulse infinite`), termasuk di setiap baris tabel. Ini noise visual di halaman yang padat data. | `includes/header.php` `.badge.bg-success` | Animasi hanya untuk indikator live (mis. "Antrian QR baru"), bukan untuk semua badge. |
| A3 | P1 | **Information architecture sidebar** kurang tepat: "Artikel & Blog" ada di *Pengaturan*, "Inbox Pesan" & "Testimoni" ada di *Loyalty*, "Pengeluaran" (input data) ada di *Laporan*. Ikon juga ganda (`journal-text` untuk Resep & Blog, `people` untuk Pengguna & Pelanggan). Total ±22 item dalam satu kolom panjang. | `includes/header.php:210-296` | Regroup: **Operasional** (POS, Antrian QR, Shift, Meja & QR) · **Katalog** (Produk, Kategori, Resep) · **Inventori** (Bahan, Stok, Riwayat) · **Keuangan** (Pengeluaran, Penjualan, Laba Rugi) · **Pelanggan** (Pelanggan, Rewards, Laporan Loyalty) · **Website** (Artikel, Testimoni, Inbox) · **Pengaturan** (Outlet, Pengguna, Toko). Section bisa di-collapse, ikon unik. |
| A4 | P1 | **Laporan tidak punya grafik.** Hanya `dashboard.php` yang memakai Chart.js. `reports/sales.php`, `profit_loss.php`, dan `loyalty.php` semuanya berupa tabel, padahal tujuannya analisis. | `views/reports/*` | Sales: line tren harian + bar per outlet + donut metode bayar. Laba Rugi: waterfall/stacked bar Omzet→HPP→Biaya→Laba. Loyalty: bar tier member + tren poin earned vs redeemed. Tabel tetap di bawah grafik sebagai detail. |
| A5 | P1 | **KPI card tanpa konteks.** Angka hari ini tidak dibandingkan dengan kemarin atau minggu lalu. HPP ditampilkan merah (warna "bahaya") padahal HPP normal. Font size dan warna di-inline per kartu. | `views/dashboard.php` KPI | Komponen `.kpi-card` standar: label, nilai, delta (▲/▼ % vs periode sebelumnya, hijau/merah sesuai makna), sparkline kecil. Merah hanya untuk kondisi buruk. |
| A6 | P1 | **Tabel besar tanpa pagination, search, atau sort** (customers, stock_history, expenses, products, users). Data 3 bulan seed akan berat dan susah dicari. | `views/*.php` | Pagination server-side + search + sort kolom. Header tabel sticky. Kolom angka rata kanan dengan `tabular-nums` (sebagian sudah). |
| A7 | P1 | **Filter tidak konsisten antar halaman**: dashboard hanya dropdown outlet, sales/PL date range + preset, loyalty date tanpa preset. | dashboard, reports | Satu komponen *filter bar*: periode (preset + custom) + outlet, dipakai di dashboard dan semua laporan, dan disimpan di query string. |
| A8 | P2 | Tabel dashboard tidak dibungkus `table-responsive`, sehingga overflow di tablet. | `views/dashboard.php` | Bungkus. |
| A9 | P2 | Tidak ada empty/loading state yang seragam ("Belum ada data." vs "Semua stok aman 👍"). | | Komponen empty state (ikon + kalimat + aksi). |
| A10 | P2 | Topbar: judul halaman + badge outlet + user. Tidak ada breadcrumb untuk halaman detail (`product_detail`, `customer_detail`, `shift_detail`). | | Breadcrumb + tombol kembali di halaman detail. |

**Arah visual admin:** gaya *data-dense dashboard*: background netral terang, kartu padding 12–16px, grid 12 kolom, body 13–14px, warna data terbatas (1 brand + hijau/merah semantik + 3–4 warna kategori untuk chart). Sidebar hitam bisa dipertahankan, tapi aksen aktifnya pakai sand agar kontras (saat ini espresso di atas hitam kontrasnya rendah).

---

## 3. `company/`: Company Profile

### Diagnosis: kenapa terasa "AI slop"

Rumusnya generik dan berulang di semua halaman:
1. Hero gradient espresso gelap + radial glow + eyebrow kuning uppercase + judul Playfair terpusat. **Sama persis di 6 halaman.**
2. Copy klise tanpa fakta: "Setiap Cangkir, Sebuah Cerita", "Lebih dari sekadar kedai kopi", "Tiga prinsip yang tidak kami tawar", "diseduh dengan sepenuh hati".
3. Kartu putih rounded + hover translateY di semua tempat (menu, testimoni, visi-misi, tim, blog).
4. Stock photo Unsplash yang **dipetakan berdasarkan urutan index, bukan produk**. Kalau produk ke-1 bukan Espresso, fotonya tetap foto espresso.
5. **Testimoni asli dipasangi foto wajah acak dari randomuser.me.** Ini menyesatkan (wajah orang yang tidak memberi testimoni) dan merusak kepercayaan. **P0, harus dihapus.**
6. Tim berupa avatar huruf (R, S, ...) tanpa foto. Visi & misi berupa kartu berikon teropong.
7. Tidak ada hal yang spesifik Tokopi: tidak ada daftar cabang, jam per cabang, asal biji, harga di beranda, atau ajakan memakai QR order yang menjadi diferensiasi produk.

### Temuan

| # | Prio | Temuan | Lokasi | Usulan |
|---|------|--------|--------|--------|
| P1 | P0 | Avatar randomuser.me di testimoni asli. | `company/index.php` testimoni | Hapus. Pakai inisial atau tanpa avatar, tampilkan nama + cabang + tanggal. |
| P2 | P0 | Gambar menu & blog dipetakan per index sehingga bisa tidak cocok dengan isinya. | `index.php $menu_images`, `blog.php $blog_images` | Ambil dari kolom gambar produk/post (lihat C2). Kalau kosong, placeholder tipografis. |
| P3 | P1 | **Konten saling bertentangan**: Misi menyebut "pengalaman pesan-antar", sedangkan FAQ bilang tidak melayani delivery. Footer hanya mencantumkan satu alamat, padahal disebut ada "3 cabang". | `about.php`, `faq.php`, `footer_public.php` | Selaraskan copy. Buat section **Cabang** (nama, alamat, jam, link Maps) dari tabel `outlets`. |
| P4 | P1 | Tombol "Login" di navbar publik mengarah ke login **customer**, tanpa penjelasan untuk apa (pengunjung company bukan pelanggan QR). | `header_public.php` | Ganti dengan CTA "Pesan dari Meja" (penjelasan QR) / "Member Area", tambahkan teks yang jelas. |
| P5 | P1 | `fade-in-up` di hampir setiap blok (konten tidak terlihat sebelum discroll, sehingga terlihat "kosong" di screenshot/SEO preview). | semua halaman | Animasi hanya di 1–2 momen kunci. Konten default terlihat. |
| P6 | P2 | Halaman legal/FAQ/kontak memakai hero besar yang sama, sehingga kontennya terdorong ke bawah. | `faq.php`, `terms.php`, `privacy.php`, `contact.php` | Header halaman ringkas (judul + 1 kalimat), tanpa gradient. |
| P7 | P2 | Fokus accordion FAQ dihilangkan; Google Maps iframe tanpa `title`. | `faq.php`, `contact.php` | Lihat 0.5. |

### Usulan arah visual baru (company)

> Catatan: rekomendasi otomatis skill untuk kata kunci "specialty coffee" memberi pola *Funnel* + gaya *Vibrant block* dan **Playfair sebagai body font**. Itu tidak cocok untuk brand ini, jadi tidak saya pakai mentah-mentah. Yang saya ambil hanya poin anti-pattern-nya: *hindari foto makanan jelek* dan *jangan sembunyikan jam buka*.

**Arah: "Editorial kedai kopi lokal", hangat, terang, tipografis, dan berbasis fakta.**

- **Warna:** background kertas terang `#F6F1E7`, teks espresso `#2D1B0A` (satu nilai, dari `tokens.css`), satu aksen terracotta/roast `#B5532A` untuk CTA dan highlight. Section gelap espresso hanya **sekali** (misalnya blok QR order), bukan di setiap hero.
- **Tipografi:** headline serif berkarakter dengan ukuran besar dan rata kiri (Fraunces atau tetap Playfair, tapi dipakai lebih berani: 64–96px di desktop), body Plus Jakarta Sans 16–17px, angka/harga JetBrains Mono. Eyebrow kuning uppercase dihapus.
- **Layout:** grid asimetris, tidak semuanya rata tengah. Elemen sederhana yang terasa buatan tangan, seperti garis tipis pemisah dan nomor section, menggantikan kartu rounded + shadow di mana-mana.
- **Beranda baru (urutan):**
  1. Hero split: headline spesifik ("Kopi lokal, pesan dari meja.") + jam buka hari ini + 2 CTA (Lihat Menu · Cara Pesan via QR) + satu foto nyata.
  2. Strip fakta: jumlah cabang · asal biji · jam buka · poin per Rp10rb.
  3. Menu unggulan dalam format **daftar menu ala papan kafe** (nama ····· harga) dengan data asli dari DB. Lebih jujur daripada kartu stok foto.
  4. **Cara pesan via QR** dalam 3 langkah (ini fitur pembeda Tokopi, sekarang hanya disebut sekilas di CTA).
  5. Cabang (kartu alamat + jam + Maps) dari tabel `outlets`.
  6. Testimoni berupa kutipan teks tanpa wajah palsu.
  7. Program loyalty (Bronze → Silver → Gold) + CTA daftar member.
- **About:** timeline singkat (2024 → cabang 1 → 2 → 3), foto tim asli atau dihilangkan, nilai-nilai ditulis sebagai kalimat konkret, bukan kartu ikon.
- **Menu publik:** layout daftar menu bergaya papan dengan sticky kategori di samping (desktop) atau atas (mobile).

---

## 4. Rencana redesign (setelah disetujui)

| Fase | Isi | File utama | Estimasi dampak |
|------|-----|------------|-----------------|
| **1. Fondasi** | 0.1–0.5: satukan token, alias variabel admin, pindahkan komponen berulang ke `components.css`, ganti emoji, fokus & aria. Perbaiki A1, P1 (hapus wajah palsu), C11. | `assets/css/*`, `includes/*` | Semua halaman konsisten, bug P0 hilang |
| **2. Customer** | App-shell max 480px untuk desktop (C1), placeholder produk informatif (C2), quick-add (C3), error inline + stepper checkout (C4, C6), state belum-scan (C5), polish home (C7–C10) | `customer/*.php`, `layout.css` | Bisa dipakai nyaman di desktop & HP |
| **3. Company** | Redesign arah editorial: header/footer publik, beranda, about, menu, kontak/cabang, header ringkas untuk FAQ/legal | `company/*.php`, `includes/header_public.php`, `footer_public.php` | Hilang kesan template/AI |
| **4. Admin** | Sidebar regroup (A3), KPI card + delta (A5), filter bar seragam (A7), grafik di laporan (A4), pagination/search tabel (A6), A2, A8–A10 | `includes/header.php`, `views/dashboard.php`, `views/reports/*`, tabel-tabel besar | Dashboard benar-benar untuk analisis |

Tidak ada perubahan yang akan di-push ke git.

### Keputusan yang perlu dari kamu
1. **Fase mana yang disetujui, dan urutannya?** (saran: 1 → 2 → 3 → 4)
2. **Foto produk (C2/P2):** boleh menambah kolom `image_path` di tabel `products` + upload di admin? Atau sementara placeholder tipografis saja?
3. **Arah visual company:** setuju dengan "editorial kedai kopi lokal" (terang, tipografis, aksen terracotta)? Atau mau tetap dominan gelap espresso?
4. **Admin:** boleh regroup sidebar (A3) dan menambah grafik di laporan (A4)? Keduanya mengubah navigasi yang sudah dikenal user internal.

---

## 5. Yang sudah dikerjakan

**Fondasi**
- `assets/css/tokens.css`: alias `--espresso`, `--sand`, `--text-muted`, dll. sekarang menunjuk ke satu sumber token. Ditambah aksen `--color-accent: #A9471F`.
- `assets/css/pimg.css` + helper `product_img()`: menampilkan foto produk bila ada. Bila belum ada, tampil placeholder inisial dengan 6 nada warna hangat yang konsisten per produk.
- Bug lama diperbaiki: sisa teks `cat > … << 'EOF'` / `EOF` yang ikut tercetak di `actions/product_action.php`, `views/products.php`, dan `views/categories.php`.
- Emoji yang dipakai sebagai ikon diganti dengan Bootstrap Icons di customer & admin.

**Foto produk (admin)**
- `views/products.php`: kolom foto, upload dengan preview, opsi "hapus foto", info "N belum ada foto", dan pencarian.
- `actions/product_action.php`: menyimpan ke `uploads/products/` (JPG/PNG/WEBP, maks 3 MB, cek MIME). File lama dihapus saat diganti atau produknya dihapus.

**Customer**
- `assets/css/customer.css` dimuat di semua halaman: app-shell 480px di tengah untuk tablet/desktop, fokus keyboard, target sentuh ≥44px, teks minimum 12px, zoom tidak lagi dikunci.
- Home: header ringkas, banner info "belum scan meja", kartu poin member (menggantikan quick action yang duplikat), dan foto produk.
- Menu: foto, tombol **tambah cepat** sungguhan untuk produk 1 varian, chevron untuk produk multi-varian, dan seluruh kartu bisa diketuk.
- Detail produk: foto hero, bisa dilihat tanpa scan meja (CTA berubah jadi "Scan QR meja"), dan bug `btoa()` pada catatan berisi emoji sudah diperbaiki.
- Keranjang & checkout: stepper 3 langkah, foto item, hapus dengan "Urungkan", error nama inline, dan toast berbahasa manusia (tidak ada lagi "Cek Console F12"). Keranjang sekarang **baru dikosongkan setelah pesanan berhasil**; sebelumnya ikut terhapus walau QRIS gagal.

**Admin**
- `assets/css/admin.css` + `includes/header.php`: sidebar dikelompokkan ulang (Ringkasan, Operasional, Katalog, Inventori, Keuangan, Pelanggan, Website, Pengaturan), bisa dilipat, ikon unik, item aktif kontras tinggi, menu user dropdown, dan badge tidak lagi berdenyut (kecuali antrian QR).
- Dashboard: KPI dengan delta (vs kemarin s/d jam sama, vs bulan lalu periode sama), grafik 7 hari vs 7 hari sebelumnya, jam sibuk, cabang, dan stok kritis dengan progress bar.
- Laporan Penjualan / Laba Rugi / Loyalty: filter bar seragam (`includes/report_filter.php`), KPI + delta, grafik tren, donut metode bayar, waterfall laba rugi, dan sebaran tier.
- Tabel besar (`assets/js/admin-table.js`): cari, urutkan per kolom, dan 25 baris per halaman.

**Company**
- `assets/css/public.css`, header & footer baru: font Fraunces + Plus Jakarta Sans, latar kertas terang, satu aksen terracotta, satu section gelap per halaman. Bootstrap tidak lagi dipakai di situs publik.
- Beranda: hero dengan status buka/tutup + papan "Favorit bulan ini" dari data penjualan, strip fakta, menu terlaris, cara pesan via QR, cabang dari tabel `outlets`, testimoni **tanpa foto wajah**, dan tier member.
- Menu: papan menu per kategori dengan harga per varian dan nav kategori sticky. About: cerita yang konsisten (klaim "pesan-antar" dihapus). Kontak: semua cabang + link Maps + validasi inline. Jurnal: cover tipografis (bukan foto stok yang salah pasang) + estimasi waktu baca. FAQ memakai `<details>` native. Legal memakai tanggal update dari file, bukan tanggal hari ini.

**Belum / di luar cakupan**
- POS (`views/pos.php`) dan Antrian QR belum di-redesign; keduanya hanya ikut tema baru.
- Halaman customer sekunder (rewards, orders, profile, track, qris, waiting_cash) hanya mendapat shell, token, dan perbaikan ikon, belum ditata ulang.
- Belum ada foto produk asli. Placeholder akan otomatis tergantikan begitu admin mengupload foto.
- Pagination masih di sisi browser. Untuk data yang sangat besar (misalnya riwayat stok > ribuan baris), sebaiknya dipindah ke server.
