<?php
session_start();
// Proteksi halaman: harus login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/db_connect.php';

// Ambil nama user agar bisa ditampilkan
$stmt = $mysqli->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

// Sertakan header (yang sudah include Bootstrap CSS & navbar)
include __DIR__ . '/../templates/header.php';
?>

<div class="row">
  <div class="col-md-8 offset-md-2">
    <h2 class="mb-4">Dashboard</h2>
    <p class="lead">
      Selamat datang, <strong>
        <?= htmlspecialchars($user_name ?: 'User #' . $_SESSION['user_id']) ?>
      </strong>
    </p>

    <div class="mb-3">
      <a href="booking.php" class="btn btn-primary mr-2">Booking Lapangan</a>
      <a href="history.php" class="btn btn-info mr-2">Riwayat Transaksi</a>
      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="admin_panel.php" class="btn btn-secondary mr-2">Admin Panel</a>
      <?php endif; ?>
      <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
    <div class="alert alert-secondary mt-3" role="alert">
  <!-- Saldo dihapus -->
    </div>
  </div>
</div>

<?php
// Sertakan footer (penutup container + Bootstrap JS)
include __DIR__ . '/../templates/footer.php';
