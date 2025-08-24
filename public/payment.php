<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['last_booking_id'])) {
    header('Location: index.php?auth=1');
    exit;
}

require_once __DIR__ . '/../src/db_connect.php';

$booking_id = (int)$_SESSION['last_booking_id'];
$stmt = $mysqli->prepare("
  SELECT b.booking_code, b.start_datetime, b.duration_hours,
         b.expired_at, b.status,
         c.id AS court_id, c.name, c.type, c.price_per_hour
  FROM bookings b
  JOIN courts c ON b.court_id = c.id
  WHERE b.id = ?
");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$stmt->bind_result($code, $start_dt, $duration, $expired_at, $status, $court_id, $court_name, $court_type, $price);
$stmt->fetch();
$stmt->close();

$total = $price * $duration;

// hitung lapangan ke berapa
$lapanganNumber = null;
if ($res = $mysqli->query("SELECT id FROM courts ORDER BY id LIMIT 3")) {
  $ids = [];
  while ($row = $res->fetch_assoc()) { $ids[] = (int)$row['id']; }
  $pos = array_search((int)$court_id, $ids, true);
  if ($pos !== false) { $lapanganNumber = $pos + 1; }
}

include __DIR__ . '/../templates/header.php';
?>
<div class="container py-4">
  <div class="card shadow-sm payment-card">
    <div class="card-body">
      <h2 class="mb-4">Pembayaran Booking</h2>

      <?php if ($status === 'pending'): ?>
        <div class="alert alert-warning">
          Selesaikan pembayaran dalam waktu <strong><span id="countdown"></span></strong>.  
          Jika melewati batas waktu, booking akan otomatis dibatalkan.
        </div>
      <?php elseif ($status === 'cancelled'): ?>
        <div class="alert alert-danger">
          Booking ini sudah <strong>dibatalkan</strong>. Silakan buat booking baru.
        </div>
      <?php endif; ?>

      <table class="table table-bordered">
        <tr><th>Kode Booking</th><td><?= htmlspecialchars($code) ?></td></tr>
        <tr><th>Lapangan</th>
          <td>
            <?= htmlspecialchars($court_name) ?> (<?= $court_type ?>)
            <?php if ($lapanganNumber !== null): ?>
              — Lapangan <?= $lapanganNumber ?>
            <?php endif; ?>
          </td>
        </tr>
        <tr><th>Tanggal & Jam</th><td><?= $start_dt ?></td></tr>
        <tr><th>Durasi</th><td><?= $duration ?> jam</td></tr>
        <tr><th>Harga/jam</th><td>Rp <?= number_format($price,0,',','.') ?></td></tr>
        <tr><th>Total Bayar</th><td><strong>Rp <?= number_format($total,0,',','.') ?></strong></td></tr>
        <?php if (!empty($_SESSION['customer_name']) || !empty($_SESSION['customer_phone'])): ?>
        <tr><th>Nama</th><td><?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?></td></tr>
        <tr><th>No. Telepon</th><td><?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?></td></tr>
        <?php endif; ?>
      </table>

      <?php if ($status === 'pending'): ?>
      <!-- form pembayaran -->
      ... (form kamu tetap, tidak berubah) ...
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($status === 'pending' && $expired_at): ?>
(function(){
  var expireAt = <?= strtotime($expired_at) ?> * 1000;
  console.log("Expire At:", expireAt, "Expired At (php): <?= $expired_at ?>");
  var countdownEl = document.getElementById("countdown");

  function updateCountdown(){
    var now = Date.now();
    var distance = expireAt - now;

    if (distance <= 0) {
      countdownEl.textContent = "00:00";
      alert("Waktu pembayaran habis! Booking dibatalkan.");
      window.location.href = 'cancel_booking.php?booking_id=<?= $booking_id ?>';
      return;
    }

    var minutes = Math.floor((distance % (1000*60*60)) / (1000*60));
    var seconds = Math.floor((distance % (1000*60)) / 1000);

    countdownEl.textContent =
      String(minutes).padStart(2, '0') + ":" +
      String(seconds).padStart(2, '0');
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);
})();
<?php endif; ?>

</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
