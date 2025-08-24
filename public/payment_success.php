<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
include __DIR__ . '/../templates/header.php';
?>

<div class="container py-5" style="max-width: 720px;">
  <div class="card success-card text-center p-4">
    <div class="card-body">
      <svg class="success-icon" viewBox="0 0 24 24" fill="#28a745" xmlns="http://www.w3.org/2000/svg"><path d="M12 0a12 12 0 1012 12A12.013 12.013 0 0012 0zm5.707 8.293l-6.364 6.364a1 1 0 01-1.414 0L6.293 11.02a1 1 0 011.414-1.414l2.293 2.293 5.657-5.657a1 1 0 011.414 1.414z"/></svg>
      <h3 class="mb-2">Terima kasih!</h3>
      <p class="mb-3">Bukti pembayaran berhasil diunggah. Status Anda <strong>pending</strong> — tunggu verifikasi admin.</p>
      <a href="history.php" class="btn btn-primary">Lihat Riwayat Transaksi</a>
      <div class="text-muted small mt-3">Anda dapat memantau status di halaman Riwayat.</div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
