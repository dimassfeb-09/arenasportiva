<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_POST['booking_id'])
    || empty($_POST['method'])
    || empty($_FILES['proof'])
) {
    header('Location: payment.php');
    exit;
}

require_once __DIR__ . '/../src/db_connect.php';

// Ambil data
$booking_id = (int) $_POST['booking_id'];
$method     = $_POST['method'];           // 'qris' atau 'transfer'
$user_id    = $_SESSION['user_id'];


// ...hapus logika balance...
$discount = (int)($_POST['discount'] ?? 0);
$paid_amount = (int)($_POST['paid_amount'] ?? 0);

// Proses upload file
$uploadDir = __DIR__ . '/uploads/';
$origName  = $_FILES['proof']['name'];
$tmpName   = $_FILES['proof']['tmp_name'];
$error     = $_FILES['proof']['error'];

if ($error !== UPLOAD_ERR_OK) {
    die('Gagal upload file. Error code: ' . $error);
}
$ext = pathinfo($origName, PATHINFO_EXTENSION);
$newName = 'proof_' . $booking_id . '_' . time() . '.' . $ext;
$dest = $uploadDir . $newName;

if (!move_uploaded_file($tmpName, $dest)) {
    die('Gagal memindahkan file ke folder uploads.');
}

// Dapatkan total amount dari booking
$stmt = $mysqli->prepare(
    "SELECT b.duration_hours * c.price_per_hour AS amount
     FROM bookings b
     JOIN courts c ON b.court_id = c.id
     WHERE b.id = ?"
);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$stmt->bind_result($amount);
$stmt->fetch();
$stmt->close();

// Diskon hanya dari durasi

// Simpan ke tabel payments
$stmt = $mysqli->prepare("
    INSERT INTO payments
      (booking_id, amount, paid_amount, discount, method, proof_image, proof_url, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
");
$proofUrl = 'uploads/' . $newName;  // path relatif untuk browser
$stmt->bind_param('idddsss', $booking_id, $amount, $paid_amount, $discount, $method, $newName, $proofUrl);
if (!$stmt->execute()) {
    die('Gagal menyimpan pembayaran: ' . $stmt->error);
}
$stmt->close();




// Bersihkan session booking_id
unset($_SESSION['last_booking_id']);

// Redirect ke halaman konfirmasi
header('Location: payment_success.php');
exit;
