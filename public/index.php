<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/auth.php';

// Proses login pelanggan dari form header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);  
    $password = $_POST['password'];

    $result = loginUser($username, $password);

    if ($result['success']) {
        $_SESSION['success_message'] = 'Login berhasil!';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error_message'] = $result['message'];
        header('Location: index.php?auth=1'); // Redirect back to show login offcanvas
        exit();
    }
}

include __DIR__ . '/../templates/header.php';
?>

<!-- Hero Carousel -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <div class="carousel-item active">
            <img src="assets/img/slide_futsal.jpg" class="d-block w-100" alt="Futsal Slide">
            <div class="carousel-caption d-none d-md-block text-start">
                <h1 class="display-3 fw-bold">Arena Sportiva</h1>
                <p class="lead">Booking Lapangan Futsal & Badminton Terbaik di Kota Anda.</p>
                <a href="#courts" class="btn btn-primary btn-lg">Booking Sekarang</a>
            </div>
        </div>
        <div class="carousel-item">
            <img src="assets/img/slide_badminton.jpg" class="d-block w-100" alt="Badminton Slide">
            <div class="carousel-caption d-none d-md-block text-start">
                <h1 class="display-3 fw-bold">Kualitas & Kenyamanan</h1>
                <p class="lead">Fasilitas lengkap dengan standar internasional.</p>
                <a href="#courts" class="btn btn-primary btn-lg">Lihat Lapangan</a>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<!-- About Section -->
<section id="about" class="py-5 section-bg-light">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 text-center">
                <img src="assets/img/arena.jpg" class="img-fluid about-image" alt="Tentang Arena Sportiva">
            </div>
            <div class="col-lg-6">
                <h2 class="fw-bold">Selamat Datang di Arena Sportiva</h2>
                <p class="lead mb-4">Kami adalah portal online terdepan untuk pemesanan lapangan olahraga yang praktis dan efisien. Kami menyediakan lapangan <strong>futsal</strong> dan <strong>badminton</strong> berkualitas tinggi dengan jadwal fleksibel dan konfirmasi instan.</p>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-check-circle text-primary fa-2x me-3"></i>
                    <p class="mb-0">Sistem booking real-time untuk cek ketersediaan.</p>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-credit-card text-primary fa-2x me-3"></i>
                    <p class="mb-0">Pembayaran mudah dan aman dengan verifikasi cepat.</p>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-trophy text-primary fa-2x me-3"></i>
                    <p class="mb-0">Lapangan berstandar internasional untuk pengalaman terbaik.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Courts Gallery Section -->
<section id="courts" class="py-5 section-bg-dark">
    <div class="container text-center">
        <h2 class="section-title">Galeri Lapangan Kami</h2>
        <p class="section-subtitle">Pilih lapangan favorit Anda. Mau futsal atau badminton? Semua ada di sini!</p>
        <div class="row g-4 justify-content-center">
            <!-- Futsal Card -->
            <div class="col-lg-5 col-md-6">
                <div class="card gallery-card text-white">
                    <img src="assets/img/futsal.jpg" class="card-img-top gallery-card-img" alt="Futsal">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Futsal</h5>
                        <p class="card-text">Lapangan indoor dengan kualitas vinyl standar internasional. Luas, nyaman, dan ideal untuk permainan tim.</p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="booking.php?jenis=futsal" class="btn btn-primary">Booking Futsal</a>
                        <?php else: ?>
                            <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#authOffcanvas">Booking Futsal</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Badminton Card -->
            <div class="col-lg-5 col-md-6">
                <div class="card gallery-card text-white">
                    <img src="assets/img/badminton.jpg" class="card-img-top gallery-card-img" alt="Badminton">
                    <div class="card-body">
                        <h5 class="card-title fw-bold">Badminton</h5>
                        <p class="card-text">Lapangan indoor dengan alas karpet standar kejuaraan. Pencahayaan optimal dan anti-selip.</p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="booking.php?jenis=badminton" class="btn btn-primary">Booking Badminton</a>
                        <?php else: ?>
                            <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#authOffcanvas">Booking Badminton</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<?php include __DIR__ . '/../templates/footer.php'; ?>