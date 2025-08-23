<?php
// Redirect ke halaman utama jika bukan admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/src/db_connect.php';

// Fungsi untuk mengecek status pembayaran
function checkPaymentStatus($mysqli, $payment_id) {
    $stmt = $mysqli->prepare("
        SELECT 
            p.id,
            p.booking_id,
            p.status as payment_status,
            b.status as booking_status
        FROM payments p
        JOIN bookings b ON b.id = p.booking_id
        WHERE p.id = ?
    ");
$result = $mysqli->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Payment ID: " . $row['id'] . "<br>";
        echo "Booking ID: " . $row['booking_id'] . "<br>";
        echo "Amount: " . $row['amount'] . "<br>";
        echo "Payment Status: " . $row['status'] . "<br>";
        echo "Booking Status: " . $row['booking_status'] . "<br>";
        echo "User Balance: " . $row['balance'] . "<br>";
        echo "<hr>";
    }
} else {
    echo "Error: " . $mysqli->error;
}

// Test rejection manually
if (isset($_GET['test_reject']) && isset($_GET['payment_id'])) {
    $payment_id = (int)$_GET['payment_id'];
    
    echo "<h2>Testing Rejection for Payment ID: $payment_id</h2>";
    
    // Get payment details
    $stmt = $mysqli->prepare("SELECT p.booking_id, p.amount, b.user_id FROM payments p JOIN bookings b ON b.id = p.booking_id WHERE p.id = ?");
    $stmt->bind_param('i', $payment_id);
    $stmt->execute();
    $pay = $stmt->get_result()->fetch_assoc();
    
    if ($pay) {
        echo "Found payment: Booking ID=" . $pay['booking_id'] . ", Amount=" . $pay['amount'] . ", User ID=" . $pay['user_id'] . "<br>";
        
        // Test rejection
        $mysqli->begin_transaction();
        try {
            // Update payment status
            $u1 = $mysqli->prepare("UPDATE payments SET status='failed' WHERE id = ?");
            $u1->bind_param('i', $payment_id);
            $result1 = $u1->execute();
            echo "Payment update: " . ($result1 ? 'SUCCESS' : 'FAILED') . "<br>";
            
            // Update booking status
            $u2 = $mysqli->prepare("UPDATE bookings SET status='rejected' WHERE id = ?");
            $u2->bind_param('i', $pay['booking_id']);
            $result2 = $u2->execute();
            echo "Booking update: " . ($result2 ? 'SUCCESS' : 'FAILED') . "<br>";
            
            // Balance dihapus
            
            $mysqli->commit();
            echo "Transaction committed successfully!<br>";
            
        } catch (Exception $e) {
            $mysqli->rollback();
            echo "Error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "Payment not found!<br>";
    }
}
?>
