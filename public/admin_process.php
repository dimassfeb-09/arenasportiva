<?php
session_start();
// Proteksi: hanya admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../src/db_connect.php';

$payment_id = (int)($_POST['payment_id'] ?? 0);
$action     = $_POST['action']     ?? '';

if (!$payment_id || !in_array($action, ['approve','reject'])) {
    die("Request tidak valid");
}

// Ambil detail pembayaran & booking untuk user_id
$stmt = $mysqli->prepare("
    SELECT p.booking_id, p.amount, b.user_id, p.status as payment_status, b.status as booking_status
    FROM payments p
    JOIN bookings b ON b.id = p.booking_id
    WHERE p.id = ?
");
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$res = $stmt->get_result();
$pay = $res->fetch_assoc();
if (!$pay) {
    die("Pembayaran tidak ditemukan");
}

// Debug: log payment details
error_log("Payment details - ID: $payment_id, Booking ID: " . $pay['booking_id'] . ", Amount: " . $pay['amount'] . ", User ID: " . $pay['user_id']);
error_log("Current status - Payment: " . $pay['payment_status'] . ", Booking: " . $pay['booking_status']);

// Jika Reject: set status ke 'failed' dan tambah balance user
if ($action === 'reject') {
    $mysqli->begin_transaction();
    try {
        // Update status payment & booking saja
        $u1 = $mysqli->prepare("UPDATE payments SET status='failed' WHERE id = ?");
        $u1->bind_param('i', $payment_id);
        $result1 = $u1->execute();
        $u2 = $mysqli->prepare("UPDATE bookings SET status='rejected' WHERE id = ?");
        $u2->bind_param('i', $pay['booking_id']);
        $result2 = $u2->execute();
        $mysqli->commit();
        // Set pesan untuk user
        $_SESSION['notif'] = 'Transaksi ditolak. Silakan segera hubungi admin untuk pengembalian dana.';
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Error in reject process: " . $e->getMessage());
        die("Gagal memproses reject: " . $e->getMessage());
    }
    header('Location: admin_panel.php');
    exit;
}

// --- Approve flow ---
$mysqli->begin_transaction();
try {
    // 1) Update status payment & booking
    $u1 = $mysqli->prepare("UPDATE payments SET status='success' WHERE id = ?");
    $u1->bind_param('i', $payment_id);
    $u1->execute();

    $u2 = $mysqli->prepare("UPDATE bookings SET status='confirmed' WHERE id = ?");
    $u2->bind_param('i', $pay['booking_id']);
    $u2->execute();

    // 2) Hitung jumlah pembayaran sukses user 7 hari terakhir
    $stmt2 = $mysqli->prepare("
        SELECT COUNT(*) AS cnt
        FROM payments p
        JOIN bookings b ON b.id = p.booking_id
        WHERE b.user_id = ?
          AND p.status  = 'success'
          AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt2->bind_param('i', $pay['user_id']);
    $stmt2->execute();
    $cnt = $stmt2->get_result()->fetch_assoc()['cnt'];

    // 3) Jika sudah ≥2 booking sukses, berikan diskon 10% dari amount
    if ($cnt >= 2) {
        $discount = $pay['amount'] * 0.10;
        // Tambah ke balance user
        $u3 = $mysqli->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $u3->bind_param('di', $discount, $pay['user_id']);
        $u3->execute();
    }

    $mysqli->commit();
} catch (Exception $e) {
    $mysqli->rollback();
    die("Gagal memproses: " . $e->getMessage());
}

// Setelah selesai, redirect kembali
header('Location: admin_panel.php');
exit;
