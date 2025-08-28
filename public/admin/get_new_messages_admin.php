<?php
session_start();
require_once __DIR__ . '/../../src/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit();
}

$stmt = $mysqli->prepare("
    SELECT m.*, u.name as sender_name 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE m.receiver_id = ? AND m.sender_id = ? AND m.is_read = FALSE
    ORDER BY m.created_at ASC
");
$stmt->bind_param("ii", $admin_id, $user_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark messages as read
if (!empty($messages)) {
    $mysqli->query("UPDATE messages SET is_read = TRUE WHERE receiver_id = $admin_id AND sender_id = $user_id");
}

echo json_encode(['success' => true, 'messages' => $messages]);
