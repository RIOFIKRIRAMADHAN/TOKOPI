<?php
// actions/post_delete.php — Delete Blog Post
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager']);

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flash_set('error', 'ID artikel tidak valid.');
    redirect('/tokopi/views/posts.php');
}

// Cek keberadaan
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    flash_set('error', 'Artikel tidak ditemukan.');
    redirect('/tokopi/views/posts.php');
}

// Hapus
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$id]);

flash_set('success', 'Artikel berhasil dihapus.');
redirect('/tokopi/views/posts.php');