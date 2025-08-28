<?php
require_once __DIR__ . '/../../src/db_connect.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Cek dan batalkan semua booking yang sudah expired
$stmt = $mysqli->prepare("
    UPDATE bookings 
    SET 
        status = 'cancelled',
        cancelled_at = NOW(),
        cancel_reason = 'expired'
    WHERE 
        status = 'pending' 
        AND expired_at < NOW()
");
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

echo json_encode([
    'success' => true,
    'cancelled_bookings' => $affected
]);
