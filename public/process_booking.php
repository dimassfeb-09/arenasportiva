<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?auth=1');
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_POST['court_id'])
    || empty($_POST['slot'])
    || empty($_POST['duration'])
    || empty($_POST['customer_name'])
    || empty($_POST['customer_phone'])
) {
    header('Location: booking.php');
    exit;
}

require_once __DIR__ . '/../src/db_connect.php';

$user_id        = $_SESSION['user_id'];
$court_id       = (int) $_POST['court_id'];
$start          = $_POST['slot'];       // format "YYYY-MM-DD HH:00"
$dur            = (int) $_POST['duration'];
$customer_name  = trim($_POST['customer_name']);
$customer_phone = trim($_POST['customer_phone']);

// Get court price first
$price_stmt = $mysqli->prepare("SELECT price_per_hour FROM courts WHERE id = ?");
$price_stmt->bind_param('i', $court_id);
$price_stmt->execute();
$price_result = $price_stmt->get_result();
$court_data = $price_result->fetch_assoc();
$price_stmt->close();

$subtotal = $court_data['price_per_hour'] * $dur;
$discount_amount = 0;

// Calculate discount based on duration
if ($dur >= 6) {
    // 10% discount for 6 hours or more
    $discount_amount = floor($subtotal * 0.1);
} elseif ($dur >= 4) {
    // 5% discount for 4-5 hours
    $discount_amount = floor($subtotal * 0.05);
}

$code       = 'BK-'. date('Ymd') . '-' . strtoupper(substr(md5(uniqid()),0,6));
// Set waktu expired 2 jam dari sekarang dengan timezone Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');
$expired_at = date('Y-m-d H:i:s', strtotime('+2 hours'));

$stmt = $mysqli->prepare("
    INSERT INTO bookings 
      (user_id, court_id, start_datetime, duration_hours, status, booking_code, expired_at, discount_amount)
    VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)
");
$stmt->bind_param('iisissi', $user_id, $court_id, $start, $dur, $code, $expired_at, $discount_amount);

if (!$stmt->execute()) {
    die('Gagal menyimpan booking: ' . $stmt->error);
}

$_SESSION['last_booking_id'] = $stmt->insert_id;
$_SESSION['customer_name']   = $customer_name;
$_SESSION['customer_phone']  = $customer_phone;
$stmt->close();

header('Location: payment.php');
exit;
