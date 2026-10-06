<?php
// config/database.php
// Compatible PHP 7.4

function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $val = trim($parts[1], " \t\n\r\0\x0B\"'");
        $_ENV[$key] = $val;
        putenv("$key=$val");
    }
}

loadEnv(__DIR__ . '/../.env');

function env(string $key, $default = null) {
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
}

$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$dbname = env('DB_NAME', 'tokoku');
$username = env('DB_USER', 'root');
$password = env('DB_PASS', '');

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
