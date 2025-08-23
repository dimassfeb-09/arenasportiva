<footer class="text-center py-3 bg-dark text-white">
  &copy; <?= date('Y') ?> Booking Lapangan
</footer>
<script 
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" 
  integrity="sha384-..." crossorigin="anonymous">
</script>
<script>
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
