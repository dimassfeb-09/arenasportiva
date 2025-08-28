</div> <!-- End Main Content Wrapper from header.php -->

<footer class="site-footer section-bg-dark">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="text-white">Arena Sportiva</h5>
                <p class="text-secondary">Portal online terdepan untuk pemesanan lapangan olahraga yang praktis dan efisien. Kami menyediakan lapangan futsal dan badminton berkualitas tinggi.</p>
                <div class="d-flex social-links">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                <h5 class="text-white">Tautan</h5>
                <ul class="list-unstyled footer-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="index.php#about">Tentang Kami</a></li>
                    <li><a href="index.php#courts">Lapangan</a></li>
                    <li><a href="contact.php">Kontak</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                <h5 class="text-white">Jam Buka</h5>
                <p class="text-secondary mb-1">Senin - Minggu</p>
                <p class="text-secondary fw-bold">09:00 - 02:00 WIB</p>
            </div>
            <div class="col-lg-3 col-md-6">
                <h5 class="text-white">Kontak</h5>
                <ul class="list-unstyled text-secondary">
                    <li class="d-flex mb-2">
                        <i class="fas fa-map-marker-alt mt-1 me-2"></i>
                        <span>Jl. Arena Sportiva, Kota Sport, Indonesia</span>
                    </li>
                    <li class="d-flex mb-2">
                        <i class="fas fa-phone mt-1 me-2"></i>
                        <span>(021) 123-4567</span>
                    </li>
                    <li class="d-flex">
                        <i class="fas fa-envelope mt-1 me-2"></i>
                        <span>info@arenasportiva.com</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="text-center text-secondary pt-4 mt-4 border-top border-secondary border-opacity-25">
            &copy; <?= date('Y') ?> Arena Sportiva. All Rights Reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Script to automatically show the login/register offcanvas if the `auth=1` URL parameter is present.
  (function(){
    const params = new URLSearchParams(window.location.search);
    if (params.get('auth') === '1') {
      const offcanvasEl = document.getElementById('authOffcanvas');
      if (offcanvasEl && window.bootstrap) {
        const off = new bootstrap.Offcanvas(offcanvasEl);
        off.show();
      }
    }
  })();
</script>
</body>
</html>