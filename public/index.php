<?php
session_start();
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/auth.php';

// Proses login pelanggan dari form header
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = $_POST['username'];  // Using 'username' name for backward compatibility, but it's actually the username
    $password = $_POST['password'];
    $result = loginUser($username, $password);
    if ($result['success']) {
        $_SESSION['login_success'] = 'Login berhasil!';
        header('Location: index.php');
        exit();
    } else {
        $_SESSION['login_error'] = 'id atau password salah';
        header('Location: index.php');
        exit();
    }
}

// Check for success message from registration
$message = '';
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Dapatkan id court default per jenis agar tombol Booking langsung mengarah ke court terpilih
$futsalId = null; $badmintonId = null;
if ($stmt = $mysqli->prepare("SELECT id FROM courts WHERE type = 'futsal' ORDER BY id LIMIT 1")) {
  $stmt->execute();
  $stmt->bind_result($fid);
  if ($stmt->fetch()) { $futsalId = (int)$fid; }
  $stmt->close();
}
if ($stmt = $mysqli->prepare("SELECT id FROM courts WHERE type = 'badminton' ORDER BY id LIMIT 1")) {
  $stmt->execute();
  $stmt->bind_result($bid);
  if ($stmt->fetch()) { $badmintonId = (int)$bid; }
  $stmt->close();
}

include __DIR__ . '/../templates/header.php';
?>

<!-- Popup notification -->

<?php if (!empty($message)): ?>
  <div class="custom-notif <?php echo ($message === 'Registrasi berhasil!') ? 'success' : 'error'; ?>">
    <span><?php echo htmlspecialchars($message); ?></span>
    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none';">&times;</button>
  </div>
<?php endif; ?>

<?php if (!empty($_SESSION['login_success'])): ?>
  <div class="custom-notif success">
    <span><?php echo htmlspecialchars($_SESSION['login_success']); ?></span>
    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none';">&times;</button>
  </div>
  <?php unset($_SESSION['login_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['login_error'])): ?>
  <div class="custom-notif error">
    <span><?php echo htmlspecialchars($_SESSION['login_error']); ?></span>
    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none';">&times;</button>
  </div>
  <?php unset($_SESSION['login_error']); ?>
<?php endif; ?>

<style>
.custom-notif {
  position: fixed;
  top: 30px;
  left: 50%;
  transform: translateX(-50%);
  min-width: 320px;
  max-width: 90vw;
  padding: 16px 32px 16px 24px;
  border-radius: 16px;
  box-shadow: 0 6px 32px rgba(0,0,0,0.18);
  font-size: 1.1rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  animation: fadeInNotif 0.7s;
}
.custom-notif.success {
  background: #e6f9ed;
  color: #218838;
  border: 1px solid #b2e2c7;
}
.custom-notif.error {
  background: #ffe6e6;
  color: #c82333;
  border: 1px solid #f5bcbc;
}
.custom-notif .close-btn {
  background: none;
  border: none;
  font-size: 1.3rem;
  color: inherit;
  margin-left: 18px;
  cursor: pointer;
  opacity: 0.7;
  transition: opacity 0.2s;
}
.custom-notif .close-btn:hover {
  opacity: 1;
}
@keyframes fadeInNotif {
  from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
  to   { opacity: 1; transform: translateX(-50%) translateY(0); }
}
</style>

<!-- Hero Carousel -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img src="assets/img/slide_futsal.jpg" class="d-block w-100" alt="Futsal Slide" style="filter: brightness(0.85);">
      <div class="hero-caption">
        <div>
          <p>Selamat Datang di Website</p>
          <h1>Arena Sportiva</h1>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <img src="assets/img/slide_badminton.jpg" class="d-block w-100" alt="Badminton Slide" style="filter: brightness(0.85);">
      <div class="hero-caption">
        <div>
          <p>Selamat Datang di website</p>
          <h1>Arena Sportiva</h1>
        </div>
      </div>
    </div>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div>

<!-- About Section -->
<section id="about" class="about-section py-5">
  <div class="container">
    <div class="row align-items-center g-5">
      <!-- Left Column - Large Image -->
      <div class="col-lg-6">
        <div class="about-image-wrap">
          <img src="assets/img/futsal.jpg" class="img-fluid" alt="Arena Sportiva – Futsal">
        </div>
      </div>
      
      <!-- Right Column - Content and Small Image -->
      <div class="col-lg-6">
        <div class="ps-lg-4">
          <span class="about-badge">Tentang</span>
          <h2 class="about-title">Arena Sportiva</h2>
          <p class="mb-3">
            Selamat datang di Arena Sportiva, portal online untuk memesan lapangan olahraga secara praktis.
            Kami menyediakan lapangan <strong>futsal</strong> dan <strong>badminton</strong> yang nyaman
            dengan pilihan jadwal fleksibel dan konfirmasi cepat.
          </p>
          <p class="mb-4">
            Dengan sistem booking real‑time, Anda dapat memastikan ketersediaan lapangan favorit,
            serta mengunggah bukti pembayaran dan menunggu verifikasi admin dengan mudah.
          </p>
          <a href="#courts" class="btn btn-primary">Selengkapnya</a>
        </div>
        
        <!-- Small Image Below Content -->
        <div class="mt-4">
          <img src="assets/img/badminton.jpg" class="about-thumb" alt="Arena Sportiva – Badminton">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Booking Section -->
<section id="courts" class="booking-section">
  <div class="overlay"></div>
  <div class="container text-center py-5">
    <h2 class="mb-5">Mau futsal atau badminton? Semua ada di sini!</h2>
    <div class="row justify-content-center">
      <!-- Futsal -->
      <div class="col-md-5 mb-4">
        <div class="card court-card">
          <img src="assets/img/futsal.jpg" class="card-img-top" alt="Futsal">
          <div class="card-body">
            <h5 class="card-title text-dark">Futsal</h5>
            <p class="card-text text-secondary">Indoor berstandar internasional. Nyaman & luas!</p>
            <?php if(isset($_SESSION['user_id'])): ?>
              <a href="booking.php?jenis=futsal" class="btn btn-primary">Booking</a>
            <?php else: ?>
              <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#authOffcanvas">Booking</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <!-- Badminton -->
      <div class="col-md-5 mb-4">
        <div class="card court-card">
          <img src="assets/img/badminton.jpg" class="card-img-top" alt="Badminton">
          <div class="card-body">
            <h5 class="card-title text-dark">Badminton</h5>
            <p class="card-text text-secondary">Indoor full karpet standar kejuaraan!</p>
            <?php if(isset($_SESSION['user_id'])): ?>
              <a href="booking.php?jenis=badminton" class="btn btn-primary">Booking</a>
            <?php else: ?>
              <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#authOffcanvas">Booking</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- About Arena Sportiva Section -->
<section class="py-5 about-arena-section">
  <div class="container">
    <div class="row g-4">
      <!-- About Section -->
      <div class="col-md-4">
        <h4 class="mb-4">Arena Sportiva</h4>
        <p class="mb-3">
          Selamat Datang di Arena Sportiva, portal online yang memudahkan Anda untuk merencanakan dan memastikan lapangan olahraga favorit selalu tersedia tepat waktu. Kami adalah mitra setia bagi para pencinta olahraga yang mencari cara praktis dan efisien untuk memesan lapangan futsal dan badminton.
        </p>
        <p class="mb-0">
          Dengan sistem booking real-time dan pembayaran yang aman, kami memastikan pengalaman booking yang nyaman dan terpercaya untuk semua pelanggan kami.
        </p>
      </div>
      
      <!-- Links Section -->
      <div class="col-md-4">
        <h4 class="mb-4">Tautan</h4>
        <ul class="list-unstyled">
          <li class="mb-3">
            <a href="index.php" class="text-decoration-none d-flex align-items-center nav-link" style="color: #cfd3d6;">
              <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" class="me-3">
                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
              </svg>
              <span style="font-size: 15px;">Home</span>
            </a>
          </li>
          <li class="mb-3">
            <a href="index.php#courts" class="text-decoration-none d-flex align-items-center nav-link" style="color: #cfd3d6;">
              <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" class="me-3">
                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
              </svg>
              <span style="font-size: 15px;">Data Lapangan</span>
            </a>
          </li>
          <li class="mb-3">
            <a href="contact.php" class="text-decoration-none d-flex align-items-center nav-link" style="color: #cfd3d6;">
              <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" class="me-3">
                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
              </svg>
              <span style="font-size: 15px;">Kontak</span>
            </a>
          </li>
          <li class="mb-3">
            <a href="#about" class="text-decoration-none d-flex align-items-center nav-link" style="color: #cfd3d6;">
              <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" class="me-3">
                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
              </svg>
              <span style="font-size: 15px;">Tentang</span>
            </a>
          </li>
        </ul>
        
        <h5 class="mb-3 mt-4">Sosial Media</h5>
        <div class="d-flex gap-4">
          <a href="https://www.instagram.com/" target="_blank" class="text-decoration-none social-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
            </svg>
          </a>
          <a href="https://wa.me/6285894781559" target="_blank" class="text-decoration-none social-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
            </svg>
          </a>
        </div>
      </div>
      
      <!-- Address Section -->
      <div class="col-md-4">
        <h4 class="mb-4">Alamat</h4>
        <div class="address-info">
          <div class="d-flex align-items-start">
            <svg width="22" height="22" viewBox="0 0 16 16" fill="currentColor" class="me-3 mt-1" style="color: #cfd3d6;">
              <path fill-rule="evenodd" d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
            </svg>
            <div>
              <p class="address-title">Jl. Arena Sportiva</p>
              <p>Kota Sport, Indonesia</p>
              <p class="mb-0">Buka: 08:00 - 24:00 WIB</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../templates/footer.php'; ?>
