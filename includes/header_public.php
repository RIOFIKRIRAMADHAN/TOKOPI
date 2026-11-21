<?php
// includes/header_public.php — header situs publik (company profile)
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($COMPANY_BASE)) {
    $__script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $__app = preg_match('#^(.*?)/company/#', $__script, $__m) ? rtrim($__m[1], '/') : rtrim(dirname($__script), '/');
    $APP_BASE     = $__app;
    $COMPANY_BASE = $__app . '/company';
}

$page_title = $page_title ?? 'Tokopi';
$page_desc  = $page_desc ?? 'Tokopi — kedai kopi lokal di Jakarta. Biji pilihan, pesan langsung dari meja lewat QR, kumpulkan poin tiap belanja.';
$active_nav = $active_nav ?? '';
$__title    = trim(preg_replace('/\s*[—·|-]\s*Tokopi\s*$/u', '', $page_title));
$__title    = ($__title === '' || $__title === 'Tokopi') ? 'Tokopi — Kopi lokal, pesan dari meja' : $__title . ' · Tokopi';

// ---------- Data bersama situs publik ----------
// Jam buka (satu sumber untuk hero, footer, kontak)
$PUBLIC_HOURS = ['open' => '08:00', 'close' => '22:00', 'label' => 'Setiap hari, 08.00–22.00'];
$__now = date('H:i');
$IS_OPEN_NOW = ($__now >= $PUBLIC_HOURS['open'] && $__now < $PUBLIC_HOURS['close']);

// Tenant & cabang publik
if (!isset($PUBLIC_TENANT_ID)) {
    $PUBLIC_TENANT_ID = 0;
    $PUBLIC_OUTLETS = [];
    try {
        $PUBLIC_TENANT_ID = (int)$pdo->query("SELECT id FROM tenants ORDER BY id LIMIT 1")->fetchColumn();
        $__st = $pdo->prepare("SELECT id, code, nama, alamat, no_hp FROM outlets WHERE tenant_id = ? AND is_active = 1 ORDER BY id");
        $__st->execute([$PUBLIC_TENANT_ID]);
        $PUBLIC_OUTLETS = $__st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $__e) {
        error_log('[header_public] ' . $__e->getMessage());
    }
}
// Kata kunci Google Maps per cabang (kode outlet). Cabang yang tidak ada di sini
// memakai alamat dari tabel outlets.
$OUTLET_MAP_QUERY = [
    'TPI-01' => 'Lippo Mall Kemang, Jl. Pangeran Antasari No.36, Bangka, Mampang Prapatan, Jakarta Selatan', // Kemang
    'TPI-03' => 'Toko Kopian, Menteng, Jakarta Pusat', // Menteng
];
if (!function_exists('outlet_map_query')) {
    function outlet_map_query(array $o) {
        global $OUTLET_MAP_QUERY;
        if (!empty($OUTLET_MAP_QUERY[$o['code'] ?? ''])) return $OUTLET_MAP_QUERY[$o['code']];
        return 'Tokopi ' . ($o['alamat'] ?: $o['nama']);
    }
}
if (!function_exists('outlet_short')) {
    function outlet_short($nama) { return trim(preg_replace('/^.*?[-–]\s*/u', '', (string)$nama)) ?: (string)$nama; }
}

$__nav = [
    'home'    => ['Beranda', '/index.php'],
    'menu'    => ['Menu', '/menu.php'],
    'about'   => ['Cerita', '/about.php'],
    'blog'    => ['Jurnal', '/blog.php'],
    'contact' => ['Cabang & Kontak', '/contact.php'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($__title) ?></title>
    <meta name="description" content="<?= e($page_desc) ?>">
    <meta property="og:title" content="<?= e($__title) ?>">
    <meta property="og:description" content="<?= e($page_desc) ?>">
    <meta name="theme-color" content="#F6F1E7">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght,SOFT@0,9..144,400..700,30;1,9..144,400..700,30&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $APP_BASE ?>/assets/css/pimg.css?v=20261121b" rel="stylesheet">
    <link href="<?= $APP_BASE ?>/assets/css/public.css?v=20261121e" rel="stylesheet">
    <script>document.documentElement.classList.add('js');</script>
</head>

<body>
    <a class="skip-link" href="#main">Lewati ke konten</a>

    <header class="p-nav" id="pNav">
        <div class="wrap">
            <a class="p-logo" href="<?= $COMPANY_BASE ?>/index.php" aria-label="Tokopi, ke beranda">
                <span class="p-logo-mark" aria-hidden="true"><i class="bi bi-cup-hot-fill"></i></span>
                Tokopi
            </a>
            <button class="p-burger" id="pBurger" type="button" aria-expanded="false" aria-controls="pMenu" aria-label="Buka menu navigasi">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <nav class="p-menu" id="pMenu" aria-label="Navigasi utama">
                <ul class="p-links">
                    <?php foreach ($__nav as $__k => $__it): ?>
                        <li><a href="<?= $COMPANY_BASE . $__it[1] ?>" <?= $active_nav === $__k ? 'aria-current="page"' : '' ?>><?= e($__it[0]) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?= $APP_BASE ?>/customer/home.php" class="btn-p p-nav-cta">
                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Pesan dari meja
                </a>
            </nav>
        </div>
    </header>

    <main id="main">
