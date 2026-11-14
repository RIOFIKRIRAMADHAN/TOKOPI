<?php
// customer/reset_table.php
require_once __DIR__ . '/../config/bootstrap.php';

// Hapus semua session meja
unset($_SESSION['customer_table_id']);
unset($_SESSION['customer_table_nama']);
unset($_SESSION['customer_table_code']);
unset($_SESSION['customer_outlet_id']);
unset($_SESSION['customer_outlet_nama']);
unset($_SESSION['customer_token']);
unset($_SESSION['customer_session_start']);

flash_set('success', 'Sesi meja berhasil dihapus. Silakan scan QR ulang untuk memesan.');

// Kalau dia guest, tendang ke scan. Kalau login, balik ke profil.
if (empty($_SESSION['customer_account_id'])) {
    redirect('/tokopi/customer/t.php');
} else {
    redirect('/tokopi/customer/profile.php');
}