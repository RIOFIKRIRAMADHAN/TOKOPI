<?php
// actions/contact_mark_read.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner', 'manager']);

$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0 || !in_array($action, ['read', 'delete'])) {
    redirect('/tokopi/views/contact_inbox.php');
}

try {
    if ($action === 'read') {
        $stmt = $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Pesan ditandai sebagai dibaca.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$id]);
        flash_set('success', 'Pesan berhasil dihapus.');
    }
} catch (Throwable $e) {
    error_log('[contact_mark_read] ' . $e->getMessage());
    flash_set('error', 'Terjadi kesalahan sistem.');
}

redirect('/tokopi/views/contact_inbox.php');