<?php
// config/midtrans.php
// Inisialisasi Midtrans SDK

require_once __DIR__ . '/../vendor/autoload.php';

function midtrans_init(): void
{
    static $done = false;
    if ($done) return;

    \Midtrans\Config::$serverKey    = env('MIDTRANS_SERVER_KEY');
    \Midtrans\Config::$clientKey    = env('MIDTRANS_CLIENT_KEY');
    \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', 'false') === 'true';
    \Midtrans\Config::$isSanitized  = true;
    \Midtrans\Config::$is3ds        = true;

    $done = true;
}

function midtrans_server_key(): string
{
    return (string) env('MIDTRANS_SERVER_KEY', '');
}

function midtrans_is_production(): bool
{
    return env('MIDTRANS_IS_PRODUCTION', 'false') === 'true';
}

function midtrans_qris_acquirer(): string
{
    return (string) env('MIDTRANS_QRIS_ACQUIRER', 'gopay');
}
