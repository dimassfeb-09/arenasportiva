<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';

if (!isset($_SESSION['user_id'], $_GET['booking_id'])) {
    header('Location: index.php');
    exit;
}

$booking_id = (int)$_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Pastikan booking milik user dan belum dibayar
$stmt = $mysqli->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking || $booking['status'] !== 'pending') {
    header('Location: history.php?cancel=fail');
    exit;
}

// Hapus booking
$stmt = $mysqli->prepare("DELETE FROM bookings WHERE id = ?");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$stmt->close();

// Bersihkan session booking
unset($_SESSION['last_booking_id']);

header('Location: history.php?cancel=success');
exit;
