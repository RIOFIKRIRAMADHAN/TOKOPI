<?php
require_once __DIR__ . '/../config/bootstrap.php';

// Hapus session pelanggan
unset($_SESSION['customer_outlet_id']);
unset($_SESSION['customer_outlet_nama']);
unset($_SESSION['customer_table_id']);
unset($_SESSION['customer_table_code']);
unset($_SESSION['customer_table_nama']);
unset($_SESSION['customer_token']);
unset($_SESSION['customer_session_start']);
unset($_SESSION['customer_session_id']);

redirect('/tokopi/customer/menu.php');
