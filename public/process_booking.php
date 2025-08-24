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

$code       = 'BK-'. date('Ymd') . '-' . strtoupper(substr(md5(uniqid()),0,6));
$expired_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$stmt = $mysqli->prepare("
    INSERT INTO bookings 
      (user_id, court_id, start_datetime, duration_hours, status, booking_code, expired_at)
    VALUES (?, ?, ?, ?, 'pending', ?, ?)
");
$stmt->bind_param('iisiss', $user_id, $court_id, $start, $dur, $code, $expired_at);

if (!$stmt->execute()) {
    die('Gagal menyimpan booking: ' . $stmt->error);
}

$_SESSION['last_booking_id'] = $stmt->insert_id;
$_SESSION['customer_name']   = $customer_name;
$_SESSION['customer_phone']  = $customer_phone;
$stmt->close();

header('Location: payment.php');
exit;
