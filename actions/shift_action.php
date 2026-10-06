<?php
// actions/shift_action.php
require_once __DIR__ . '/../config/bootstrap.php';
require_login();
require_role(['owner','manager','kasir']);

$action = $_GET['action'] ?? '';
$outlet_id = current_outlet_id();
$user_id   = current_user_id();

if (!$outlet_id) {
    flash_set('error', 'Anda belum di-assign ke outlet.');
    redirect('/tokopi/views/shift.php');
}

// ============================================
// BUKA SHIFT
// ============================================
if ($action === 'open') {
    verify_csrf();

    $opening_cash = (float)($_POST['opening_cash'] ?? 0);
    $note         = trim($_POST['note'] ?? '');

    if ($opening_cash < 0) {
        flash_set('error', 'Saldo awal tidak boleh negatif.');
        redirect('/tokopi/views/shift.php');
    }
    if ($note === '') $note = null;

    $stmt = $pdo->prepare("SELECT id FROM shifts WHERE cashier_id = ? AND status = 'OPEN'");
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        flash_set('error', 'Anda sudah punya shift aktif. Tutup dulu.');
        redirect('/tokopi/views/shift.php');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO shifts (outlet_id, cashier_id, opening_cash, status, note)
            VALUES (?, ?, ?, 'OPEN', ?)
        ");
        $stmt->execute([$outlet_id, $user_id, $opening_cash, $note]);
        flash_set('success', 'Shift dibuka. Saldo awal ' . rupiah($opening_cash) . '.');
    } catch (PDOException $e) {
        flash_set('error', 'Gagal buka shift: ' . $e->getMessage());
    }

    redirect('/tokopi/views/shift.php');
}

// ============================================
// TUTUP SHIFT
// ============================================
elseif ($action === 'close') {
    verify_csrf();

    $shift_id     = (int)($_POST['shift_id'] ?? 0);
    $closing_cash = (float)($_POST['closing_cash'] ?? 0);
    $note         = trim($_POST['note'] ?? '');

    if ($shift_id <= 0 || $closing_cash < 0) {
        flash_set('error', 'Data tidak valid.');
        redirect('/tokopi/views/shift.php');
    }
    if ($note === '') $note = null;

    $stmt = $pdo->prepare("
        SELECT s.* FROM shifts s
        WHERE s.id = ? AND s.cashier_id = ? AND s.status = 'OPEN'
    ");
    $stmt->execute([$shift_id, $user_id]);
    $shift = $stmt->fetch();

    if (!$shift) {
        flash_set('error', 'Shift tidak ditemukan atau sudah ditutup.');
        redirect('/tokopi/views/shift.php');
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(p.amount), 0)
            FROM payments p
            JOIN orders o ON o.id = p.order_id
            WHERE o.shift_id = ? AND p.method = 'CASH' AND p.status = 'SETTLED'
        ");
        $stmt->execute([$shift_id]);
        $cash_in = (float)$stmt->fetchColumn();

        $expected = (float)$shift['opening_cash'] + $cash_in;
        $difference = $closing_cash - $expected;

        if (abs($difference) >= 1 && $note === null) {
            flash_set('error', 'Ada selisih ' . rupiah($difference) . '. Mohon isi catatan.');
            redirect('/tokopi/views/shift.php');
        }

        $stmt = $pdo->prepare("
            UPDATE shifts
            SET closed_at = NOW(),
                expected_cash = ?,
                closing_cash = ?,
                difference = ?,
                status = 'CLOSED',
                note = ?
            WHERE id = ?
        ");
        $stmt->execute([$expected, $closing_cash, $difference, $note, $shift_id]);

        flash_set('success', 'Shift ditutup. Selisih: ' . rupiah($difference));
    } catch (PDOException $e) {
        flash_set('error', 'Gagal tutup shift: ' . $e->getMessage());
    }

    redirect('/tokopi/views/shift.php');
}

else {
    redirect('/tokopi/views/shift.php');
}
