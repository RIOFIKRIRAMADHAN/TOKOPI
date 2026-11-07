<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Hapus hanya session akun, session meja tetap
unset($_SESSION['customer_account_id']);
unset($_SESSION['customer_account_name']);
unset($_SESSION['customer_account_email']);

flash_set('success', 'Berhasil logout dari akun.');
redirect('/tokopi/customer/home.php');
