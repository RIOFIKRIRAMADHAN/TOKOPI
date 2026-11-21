<?php
// includes/helpers.php

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function flash_set($key, $message) {
    $_SESSION['flash'][$key] = $message;
}

function flash_get($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

function verify_csrf() {
    $token = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('CSRF token tidak valid. Refresh halaman.');
    }
}

function old($key, $default = '') {
    return $_SESSION['old'][$key] ?? $default;
}

function old_clear() {
    unset($_SESSION['old']);
}

// =========================================
// FOTO PRODUK (+ fallback bila belum diupload)
// =========================================

/** URL publik foto produk, atau null bila belum ada / file hilang. */
function product_image_url($path) {
    $path = trim((string)$path);
    if ($path === '') return null;
    $abs = __DIR__ . '/../' . ltrim($path, '/');
    if (!is_file($abs)) return null;
    return '/tokopi/' . ltrim($path, '/');
}

/** Inisial 1–2 huruf dari nama produk ("Es Kopi Susu" -> "EK"). */
function product_initials($nama) {
    $words = preg_split('/\s+/', trim((string)$nama)) ?: [];
    $out = '';
    foreach ($words as $w) {
        if ($w === '') continue;
        $out .= function_exists('mb_substr') ? mb_substr($w, 0, 1) : substr($w, 0, 1);
        if (strlen($out) >= 2) break;
    }
    return strtoupper($out ?: '?');
}

/**
 * Render foto produk. Jika belum ada foto, tampilkan placeholder
 * tipografis (inisial + warna hangat yang konsisten per produk).
 * $class ditambahkan ke elemen luar.
 */
function product_img($path, $nama, $class = '') {
    $url = product_image_url($path);
    $cls = trim('pimg ' . $class);
    if ($url) {
        return '<div class="' . e($cls) . '"><img src="' . e($url) . '" alt="' . e($nama) . '" loading="lazy" decoding="async"></div>';
    }
    // 6 nada hangat yang konsisten untuk nama yang sama
    $tones = ['t1', 't2', 't3', 't4', 't5', 't6'];
    $tone  = $tones[abs(crc32((string)$nama)) % count($tones)];
    return '<div class="' . e($cls) . ' pimg-ph pimg-' . $tone . '" role="img" aria-label="' . e($nama) . ' (belum ada foto)">'
         . '<span aria-hidden="true">' . e(product_initials($nama)) . '</span></div>';
}

/**
 * Simpan upload foto produk. Mengembalikan path relatif (uploads/products/...)
 * atau melempar RuntimeException dengan pesan yang bisa ditampilkan ke admin.
 */
function product_image_store(array $file, $product_id) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload foto gagal (kode ' . (int)$file['error'] . ').');
    if ($file['size'] > 3 * 1024 * 1024) throw new RuntimeException('Ukuran foto maksimal 3 MB.');

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Format foto harus JPG, PNG, atau WEBP.');

    $dir = __DIR__ . '/../uploads/products/';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) throw new RuntimeException('Folder upload tidak bisa dibuat.');

    $name = 'product_' . (int)$product_id . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) throw new RuntimeException('Gagal menyimpan foto.');
    return 'uploads/products/' . $name;
}

/** Hapus file foto lama (abaikan bila tidak ada). */
function product_image_delete($path) {
    $path = trim((string)$path);
    if ($path === '' || strpos($path, 'uploads/products/') !== 0) return;
    $abs = __DIR__ . '/../' . $path;
    if (is_file($abs)) @unlink($abs);
}

/**
 * Badge perubahan (%) vs periode sebelumnya.
 * $higher_is_good=false untuk metrik biaya (HPP, pengeluaran).
 */
function delta_badge($current, $previous, $higher_is_good = true, $suffix = '') {
    $current = (float)$current; $previous = (float)$previous;
    if ($previous <= 0) {
        if ($current <= 0) return '<span class="delta flat">—</span>';
        return '<span class="delta flat" title="Tidak ada data pembanding">baru</span>';
    }
    $pct = ($current - $previous) / $previous * 100;
    if (abs($pct) < 0.5) return '<span class="delta flat">0%</span>';
    $up   = $pct > 0;
    $good = $up === (bool)$higher_is_good;
    $cls  = $good ? 'up' : 'down';
    $icon = $up ? 'bi-arrow-up-right' : 'bi-arrow-down-right';
    $txt  = number_format(abs($pct), abs($pct) < 10 ? 1 : 0, ',', '.') . '%';
    return '<span class="delta ' . $cls . '"><i class="bi ' . $icon . '" aria-hidden="true"></i>'
         . '<span class="visually-hidden">' . ($up ? 'naik' : 'turun') . ' </span>' . $txt . '</span>'
         . ($suffix !== '' ? ' <span>' . e($suffix) . '</span>' : '');
}

/** Kelas warna cover tipografis untuk artikel (konsisten per judul). */
function post_cover_tone($title) {
    $tones = ['pimg-t1', 'pimg-t3', 'pimg-t4', 'pimg-t5', 'pimg-t2', 'pimg-t6'];
    return $tones[abs(crc32((string)$title)) % count($tones)];
}

/** Estimasi waktu baca (menit) dari konten HTML. */
function read_minutes($html) {
    $words = str_word_count(strip_tags((string)$html));
    return max(1, (int)round($words / 200));
}
