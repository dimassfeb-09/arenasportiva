<?php
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Booking ID is required']);
    exit;
}

$booking_id = $_GET['id'];

// Prepare and execute query
$stmt = $conn->prepare("SELECT status FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['status' => $row['status']]);
} else {
    echo json_encode(['error' => 'Booking not found']);
}

$stmt->close();
$conn->close();
?>
