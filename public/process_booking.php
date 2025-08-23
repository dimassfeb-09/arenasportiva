<?php
session_start();

// 1) Proteksi: pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?auth=1');
    exit;
}

// 2) Hanya terima method POST dan data yang lengkap
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

// 3) Ambil data dari form
$user_id        = $_SESSION['user_id'];
$court_id       = (int) $_POST['court_id'];
$start          = $_POST['slot'];       // format "YYYY-MM-DD HH:00"
$dur            = (int) $_POST['duration'];
$customer_name  = trim($_POST['customer_name']);
$customer_phone = trim($_POST['customer_phone']);

// 4) Generate kode booking unik
$code = 'BK-'. date('Ymd') . '-' . strtoupper(substr(md5(uniqid()),0,6));

// 5) Simpan ke tabel bookings dengan status pending
$stmt = $mysqli->prepare("
    INSERT INTO bookings
      (user_id, court_id, start_datetime, duration_hours, status, booking_code)
    VALUES (?, ?, ?, ?, 'pending', ?)
");
$stmt->bind_param('iisis', $user_id, $court_id, $start, $dur, $code);

if (!$stmt->execute()) {
    die('Gagal menyimpan booking: ' . $stmt->error);
}

// 6) Simpan ID booking terakhir & info customer ke session untuk halaman selanjutnya
$_SESSION['last_booking_id'] = $stmt->insert_id;
$_SESSION['customer_name']   = $customer_name;
$_SESSION['customer_phone']  = $customer_phone;
$stmt->close();

// 7) Redirect ke halaman pembayaran
header('Location: payment.php');
exit;
