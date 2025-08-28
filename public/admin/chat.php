<?php
session_start();
require_once __DIR__ . '/../../src/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get list of users with unread messages first, then users with read messages
$stmt = $mysqli->prepare("
    SELECT DISTINCT 
        u.id,
        u.name,
        u.email,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = FALSE) as unread_count,
        (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) 
         OR (sender_id = ? AND receiver_id = u.id) 
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) 
         OR (sender_id = ? AND receiver_id = u.id) 
         ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM users u
    JOIN messages m ON u.id = m.sender_id OR u.id = m.receiver_id
    WHERE u.role = 'user' AND (m.sender_id = ? OR m.receiver_id = ?)
    GROUP BY u.id
    ORDER BY unread_count DESC, last_message_time DESC
");
$stmt->bind_param("iiiiiii", $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id, $admin_id);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get selected user's messages if any
$selected_user_id = $_GET['user_id'] ?? ($users[0]['id'] ?? null);
if ($selected_user_id) {
    $stmt = $mysqli->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
        OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiii", $selected_user_id, $admin_id, $admin_id, $selected_user_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Mark messages as read
    $mysqli->query("UPDATE messages SET is_read = TRUE WHERE receiver_id = $admin_id AND sender_id = $selected_user_id");
}

$page_title = "Chat dengan Pelanggan";
include '../../templates/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-4 col-lg-3">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Daftar Percakapan</h5>
                </div>
                <div class="list-group list-group-flush" style="height: 500px; overflow-y: auto;">
                    <?php foreach ($users as $user): ?>
                        <a href="?user_id=<?= $user['id'] ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start 
                                  <?= $selected_user_id == $user['id'] ? 'active' : '' ?>">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars(substr($user['last_message'], 0, 30)) ?>...</small>
                            </div>
                            <?php if ($user['unread_count'] > 0): ?>
                                <span class="badge bg-primary rounded-pill"><?= $user['unread_count'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-8 col-lg-9">
            <?php if ($selected_user_id): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Chat dengan <?= htmlspecialchars($users[array_search($selected_user_id, array_column($users, 'id'))]['name']) ?>
                        </h5>
                    </div>
                    <div class="card-body chat-body" style="height: 400px; overflow-y: auto;" id="chatContainer">
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?= $message['sender_id'] == $admin_id ? 'admin-message text-end' : 'user-message' ?> mb-3">
                                <div class="message-content d-inline-block p-2 px-3 rounded-3 
                                    <?= $message['sender_id'] == $admin_id ? 'bg-primary text-white' : 'bg-light' ?>">
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
                            <input type="hidden" id="receiverId" value="<?= $selected_user_id ?>">
                            <input type="text" class="form-control" id="messageInput" placeholder="Ketik pesan..." required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Pilih pengguna untuk memulai percakapan
                </div>
            <?php endif; ?>
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
.admin-message .message-content {
    border-radius: 15px 15px 0 15px !important;
}
.user-message .message-content {
    border-radius: 15px 15px 15px 0 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.getElementById('chatContainer');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const receiverId = document.getElementById('receiverId');

    if (chatContainer) {
        // Scroll to bottom of chat
        chatContainer.scrollTop = chatContainer.scrollHeight;

        // Handle message submission
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // Send message to server
            fetch('../process_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    receiver_id: receiverId.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add message to chat
                    const messageHtml = `
                        <div class="message admin-message text-end mb-3">
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
            const userId = receiverId.value;
            fetch(`get_new_messages_admin.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(message => {
                            const messageHtml = `
                                <div class="message user-message mb-3">
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
    }
});
</script>

<?php include '../../templates/admin_footer.php'; ?>
