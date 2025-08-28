<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
include __DIR__ . '/../templates/header.php';
?>

<div class="profile-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg border-0 rounded-3 p-4 p-md-5 text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success fa-5x"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Pembayaran Berhasil Diterima!</h3>
                    <p class="lead text-muted mb-4">
                        Bukti pembayaran Anda telah berhasil diunggah. Status booking Anda sekarang <strong>pending</strong>.
                        Mohon tunggu verifikasi dari admin kami.
                    </p>
                    <a href="history.php" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="fas fa-history me-2"></i>Lihat Riwayat Transaksi
                    </a>
                    <p class="text-muted small mb-0">
                        Anda akan menerima notifikasi setelah booking Anda dikonfirmasi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>