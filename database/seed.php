<?php
// database/seed.php
// Jalankan sekali: php database/seed.php
// Membuat: 1 tenant, 3 outlet, 1 owner, 1 manager, 3 kasir

require_once __DIR__ . '/../config/database.php';

echo "=== SEEDER TOKOPI ===\n\n";

try {
    $pdo->beginTransaction();

    // Bersihkan dulu (kalau re-run)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE order_items");
    $pdo->exec("TRUNCATE TABLE payments");
    $pdo->exec("TRUNCATE TABLE orders");
    $pdo->exec("TRUNCATE TABLE order_sequences");
    $pdo->exec("TRUNCATE TABLE shifts");
    $pdo->exec("TRUNCATE TABLE stock_movements");
    $pdo->exec("TRUNCATE TABLE stocks");
    $pdo->exec("TRUNCATE TABLE recipe_items");
    $pdo->exec("TRUNCATE TABLE recipes");
    $pdo->exec("TRUNCATE TABLE modifier_options");
    $pdo->exec("TRUNCATE TABLE modifier_groups");
    $pdo->exec("TRUNCATE TABLE product_variants");
    $pdo->exec("TRUNCATE TABLE products");
    $pdo->exec("TRUNCATE TABLE categories");
    $pdo->exec("TRUNCATE TABLE ingredients");
    $pdo->exec("TRUNCATE TABLE users");
    $pdo->exec("TRUNCATE TABLE outlets");
    $pdo->exec("TRUNCATE TABLE tenants");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 1. BUAT TENANT
    $stmt = $pdo->prepare("INSERT INTO tenants (nama_toko, email, password, alamat, no_hp, status_langganan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Tokopi Coffee',
        'owner@tokopi.test',
        password_hash('password', PASSWORD_BCRYPT),
        'Jl. Contoh No. 1, Jakarta',
        '08123456789',
        1 // premium
    ]);
    $tenant_id = $pdo->lastInsertId();
    echo "✓ Tenant dibuat: Tokopi Coffee (id=$tenant_id)\n";

    // 2. BUAT 3 OUTLET
    $outlets = [
        ['TPI-01', 'Tokopi - Kemang',    'Jl. Kemang Raya No. 10, Jakarta Selatan', '021-1111111'],
        ['TPI-02', 'Tokopi - Senopati',  'Jl. Senopati No. 25, Jakarta Selatan',    '021-2222222'],
        ['TPI-03', 'Tokopi - Menteng',   'Jl. Cikini Raya No. 5, Jakarta Pusat',    '021-3333333'],
    ];
    $stmt = $pdo->prepare("INSERT INTO outlets (tenant_id, code, nama, alamat, no_hp) VALUES (?, ?, ?, ?, ?)");
    $outlet_ids = [];
    foreach ($outlets as $o) {
        $stmt->execute([$tenant_id, $o[0], $o[1], $o[2], $o[3]]);
        $outlet_ids[] = $pdo->lastInsertId();
    }
    echo "✓ 3 Outlet dibuat: Kemang, Senopati, Menteng\n";

    // 3. BUAT USER OWNER
    $stmt = $pdo->prepare("INSERT INTO users (tenant_id, outlet_id, nama, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $tenant_id,
        null, // owner akses semua
        'Rayhan Owner',
        'owner@tokopi.test',
        password_hash('password', PASSWORD_BCRYPT),
        'owner'
    ]);
    echo "✓ Owner dibuat: owner@tokopi.test / password\n";

    // 4. BUAT MANAGER (khusus outlet Kemang)
    $stmt->execute([
        $tenant_id,
        $outlet_ids[0],
        'Manager Kemang',
        'manager@tokopi.test',
        password_hash('password', PASSWORD_BCRYPT),
        'manager'
    ]);
    echo "✓ Manager dibuat: manager@tokopi.test / password (outlet: Kemang)\n";

    // 5. BUAT 3 KASIR (1 per outlet, login pakai username + pin)
    $stmt = $pdo->prepare("INSERT INTO users (tenant_id, outlet_id, nama, username, pin, role) VALUES (?, ?, ?, ?, ?, ?)");
    $kasirs = [
        ['Kasir Kemang',   'kasir1', '1234'],
        ['Kasir Senopati', 'kasir2', '1234'],
        ['Kasir Menteng',  'kasir3', '1234'],
    ];
    foreach ($kasirs as $i => $k) {
        $stmt->execute([
            $tenant_id,
            $outlet_ids[$i],
            $k[0],
            $k[1],
            password_hash($k[2], PASSWORD_BCRYPT),
            'kasir'
        ]);
        echo "✓ Kasir dibuat: {$k[1]} / PIN {$k[2]} (outlet: {$outlets[$i][1]})\n";
    }

    // 6. BUAT KATEGORI DEFAULT
    $stmt = $pdo->prepare("INSERT INTO categories (tenant_id, nama, slug, sort_order) VALUES (?, ?, ?, ?)");
    $cats = [
        ['Promo & Combo', 'promo-combo', 1],
        ['Baru',          'baru',        2],
        ['Makanan',       'makanan',     3],
        ['Coffee',        'coffee',      4],
        ['Non Coffee',    'non-coffee',  5],
    ];
    foreach ($cats as $c) {
        $stmt->execute([$tenant_id, $c[0], $c[1], $c[2]]);
    }
    echo "✓ 5 Kategori dibuat\n";

    $pdo->commit();

    echo "\n=== SEEDER SELESAI ===\n";
    echo "\nAkun login:\n";
    echo "  Owner   : owner@tokopi.test / password\n";
    echo "  Manager : manager@tokopi.test / password\n";
    echo "  Kasir 1 : kasir1 / PIN 1234\n";
    echo "  Kasir 2 : kasir2 / PIN 1234\n";
    echo "  Kasir 3 : kasir3 / PIN 1234\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
    exit(1);
}
