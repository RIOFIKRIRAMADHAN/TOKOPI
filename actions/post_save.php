<?php
// actions/post_save.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/tokopi/views/posts.php');
}

verify_csrf();

$id       = (int)($_POST['id'] ?? 0);
$title    = trim($_POST['title'] ?? '');
$slug     = trim($_POST['slug'] ?? '');
$excerpt  = trim($_POST['excerpt'] ?? '');
$content  = $_POST['content'] ?? '';
$status   = $_POST['status'] ?? 'draft';
$pub_date = $_POST['published_at'] ?? null;
$author   = current_user_id();

if ($title === '' || $content === '') {
    flash_set('error', 'Judul dan isi artikel wajib diisi.');
    redirect('/tokopi/views/post_form.php' . ($id ? '?id='.$id : ''));
}

// Logika Published At
if ($status === 'published') {
    if (empty($pub_date)) {
        $pub_date = date('Y-m-d H:i:s');
    } else {
        $pub_date = date('Y-m-d H:i:s', strtotime($pub_date));
    }
} else {
    $pub_date = null; // Draft = NULL
}

// Auto-generate slug
if ($slug === '') {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
}

// Pastikan slug unik
$base_slug = $slug;
$counter = 1;
while (true) {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt->execute([$slug]);
    }
    if (!$stmt->fetch()) break;
    $slug = $base_slug . '-' . $counter++;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("
            UPDATE posts SET title=?, slug=?, excerpt=?, content=?, status=?, published_at=?, author_id=?
            WHERE id=?
        ");
        $stmt->execute([$title, $slug, $excerpt, $content, $status, $pub_date, $author, $id]);
        $msg = 'Artikel berhasil diperbarui.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO posts (title, slug, excerpt, content, status, published_at, author_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $slug, $excerpt, $content, $status, $pub_date, $author]);
        $msg = 'Artikel baru berhasil dibuat.';
    }

    redirect('/tokopi/views/posts.php?success=' . urlencode($msg));

} catch (Throwable $e) {
    error_log('[post_save] ' . $e->getMessage());
    flash_set('error', 'Terjadi kesalahan sistem saat menyimpan artikel.');
    redirect('/tokopi/views/post_form.php' . ($id ? '?id='.$id : ''));
}