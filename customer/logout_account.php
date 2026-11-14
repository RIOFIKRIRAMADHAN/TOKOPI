<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Hapus hanya session akun, session meja tetap
unset($_SESSION['customer_account_id']);
unset($_SESSION['customer_account_name']);
unset($_SESSION['customer_account_email']);
// Hapus table session juga saat logout
unset($_SESSION['customer_table_id']);
unset($_SESSION['customer_table_nama']);
unset($_SESSION['customer_table_code']);
unset($_SESSION['customer_outlet_id']);
unset($_SESSION['customer_outlet_nama']);
unset($_SESSION['customer_token']);
unset($_SESSION['customer_session_start']);

flash_set('success', 'Berhasil logout dari akun.');
redirect('/tokopi/customer/home.php');
