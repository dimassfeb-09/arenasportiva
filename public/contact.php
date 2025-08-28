<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hanya proses jika user sudah login
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Anda harus login terlebih dahulu untuk mengirim pesan.";
        header('Location: contact.php');
        exit;
    }

    $message_text = htmlspecialchars($_POST['message']);
    $stmt = $mysqli->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, 4, ?)");
    $stmt->bind_param("is", $_SESSION['user_id'], $message_text);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Pesan Anda telah dikirim ke admin. Kami akan segera meresponnya.";
    } else {
        $_SESSION['error_message'] = "Gagal mengirim pesan. Silakan coba lagi.";
    }

    header('Location: contact.php');
    exit;
}

include __DIR__ . '/../templates/header.php';
?>

<div class="contact-form-section">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Hubungi Kami</h1>
            <p class="lead text-secondary">Punya pertanyaan atau butuh bantuan? Jangan ragu untuk menghubungi kami.</p>
        </div>

        <div class="row g-5">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <style>
    .chat-container {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1rem;
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
    .chat-form {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1rem;
    }
    </style>
    <div class="card-body p-4">
                        <h4 class="card-title mb-4">Hubungi Admin</h4>
                        
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Silakan <a href="#" data-bs-toggle="offcanvas" data-bs-target="#authOffcanvas" class="alert-link">login</a> terlebih dahulu untuk mengirim pesan ke admin.
                            </div>
                        <?php else: ?>
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success">
                                    <?= $_SESSION['success_message'] ?>
                                    <?php unset($_SESSION['success_message']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['error_message'])): ?>
                                <div class="alert alert-danger">
                                    <?= $_SESSION['error_message'] ?>
                                    <?php unset($_SESSION['error_message']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Chat container -->
                            <div class="chat-container mb-3" style="height: 300px; overflow-y: auto;" id="chatContainer">
                                <?php
                                // Ambil riwayat chat
                                $stmt = $mysqli->prepare("
                                    SELECT m.*, u.name as sender_name 
                                    FROM messages m 
                                    JOIN users u ON m.sender_id = u.id 
                                    WHERE (m.sender_id = ? AND m.receiver_id = 4) 
                                    OR (m.sender_id = 4 AND m.receiver_id = ?)
                                    ORDER BY m.created_at ASC
                                ");
                                $stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
                                $stmt->execute();
                                $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                
                                foreach ($messages as $msg):
                                ?>
                                    <div class="message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'user-message text-end' : 'admin-message' ?> mb-3">
                                        <div class="message-content d-inline-block p-2 px-3 rounded-3 
                                            <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'bg-primary text-white' : 'bg-light' ?>">
                                            <?= htmlspecialchars($msg['message']) ?>
                                            <small class="d-block <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'text-light' : 'text-muted' ?>" style="font-size: 0.7rem;">
                                                <?= date('H:i', strtotime($msg['created_at'])) ?>
                                                <?= $msg['sender_id'] == 1 ? '- Admin' : '' ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <form action="contact.php" method="POST" class="chat-form">
                                <div class="mb-3">
                                    <label for="message" class="form-label">Pesan</label>
                                    <textarea class="form-control" id="message" name="message" rows="3" required 
                                             placeholder="Ketik pesan Anda di sini..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Pesan
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="contact-info">
                    <h4>Informasi Kontak</h4>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Alamat</strong>
                            <p>Jl. Arena Sportiva, Kota Sport, Indonesia</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Telepon</strong>
                            <p>(021) 123-4567</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong>
                            <p>arenasportiva@gmail.com</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fab fa-whatsapp"></i>
                        <div>
                            <strong>WhatsApp</strong>
                            <p><a href="https://wa.me/6285894781559" target="_blank">0858-9478-1559</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>