<?php
require_once __DIR__ . '/../config/bootstrap.php';

echo "<pre>";
echo "SEBELUM UNSET:\n";
echo "customer_table_id: " . ($_SESSION['customer_table_id'] ?? 'KOSONG') . "\n";
echo "customer_session_id: " . ($_SESSION['customer_session_id'] ?? 'KOSONG') . "\n";
echo "customer_account_id: " . ($_SESSION['customer_account_id'] ?? 'KOSONG') . "\n";

unset($_SESSION['customer_account_id']);
unset($_SESSION['customer_account_name']);
unset($_SESSION['customer_account_email']);

echo "\nSESUDAH UNSET:\n";
echo "customer_table_id: " . ($_SESSION['customer_table_id'] ?? 'KOSONG') . "\n";
echo "customer_session_id: " . ($_SESSION['customer_session_id'] ?? 'KOSONG') . "\n";
echo "customer_account_id: " . ($_SESSION['customer_account_id'] ?? 'KOSONG') . "\n";
echo "</pre>";