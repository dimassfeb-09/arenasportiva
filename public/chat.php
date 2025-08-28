<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get admin ID (assuming admin has ID 1)
$admin_id = 1;

// Get chat history
$stmt = $mysqli->prepare("
    SELECT m.*, u.name as sender_name, u.role as sender_role 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
    OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->bind_param("iiii", $user_id, $admin_id, $admin_id, $user_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Mark messages as read
$mysqli->query("UPDATE messages SET is_read = TRUE WHERE receiver_id = $user_id AND sender_id = $admin_id");

include '../templates/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Chat dengan Admin</h5>
                </div>
                <div class="card-body chat-body" style="height: 400px; overflow-y: auto;" id="chatContainer">
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?= $message['sender_id'] == $user_id ? 'user-message text-end' : 'admin-message' ?> mb-3">
                            <div class="message-content d-inline-block p-2 px-3 rounded-3 
                                <?= $message['sender_id'] == $user_id ? 'bg-primary text-white' : 'bg-light' ?>">
                                <?= htmlspecialchars($message['message']) ?>
                                <small class="d-block text-muted" style="font-size: 0.7rem;">
                                    <?= date('H:i', strtotime($message['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer">
                    <form id="messageForm" class="d-flex gap-2">
                        <input type="text" class="form-control" id="messageInput" placeholder="Ketik pesan..." required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-body {
    background: #f8f9fa;
}
.message-content {
    max-width: 70%;
    word-wrap: break-word;
}
.user-message .message-content {
    border-radius: 15px 15px 0 15px !important;
}
.admin-message .message-content {
    border-radius: 15px 15px 15px 0 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.getElementById('chatContainer');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');

    // Scroll to bottom of chat
    chatContainer.scrollTop = chatContainer.scrollHeight;

    // Handle message submission
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const message = messageInput.value.trim();
        if (!message) return;

        // Send message to server
        fetch('process_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                receiver_id: 1 // Admin ID
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add message to chat
                const messageHtml = `
                    <div class="message user-message text-end mb-3">
                        <div class="message-content d-inline-block p-2 px-3 rounded-3 bg-primary text-white">
                            ${message}
                            <small class="d-block text-muted" style="font-size: 0.7rem;">
                                ${new Date().toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}
                            </small>
                        </div>
                    </div>
                `;
                chatContainer.insertAdjacentHTML('beforeend', messageHtml);
                chatContainer.scrollTop = chatContainer.scrollHeight;
                messageInput.value = '';
            }
        });
    });

    // Check for new messages every 5 seconds
    setInterval(function() {
        fetch('get_new_messages.php')
            .then(response => response.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        const messageHtml = `
                            <div class="message admin-message mb-3">
                                <div class="message-content d-inline-block p-2 px-3 rounded-3 bg-light">
                                    ${message.message}
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">
                                        ${new Date(message.created_at).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'})}
                                    </small>
                                </div>
                            </div>
                        `;
                        chatContainer.insertAdjacentHTML('beforeend', messageHtml);
                    });
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                }
            });
    }, 5000);
});
</script>

<?php include '../templates/footer.php'; ?>
