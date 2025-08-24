<?php
session_start();
if (!isset($_SESSION['user_id'], $_SESSION['last_booking_id'])) {
    header('Location: index.php?auth=1');
    exit;
}

require_once __DIR__ . '/../src/db_connect.php';

$booking_id = (int)$_SESSION['last_booking_id'];
$stmt = $mysqli->prepare(
  "SELECT b.booking_code, b.start_datetime, b.duration_hours,
       b.expired_at, b.status,
       c.id AS court_id, c.name, c.type, c.price_per_hour
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.id = ?"
);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$stmt->bind_result($code, $start_dt, $duration, $expired_at, $status, $court_id, $court_name, $court_type, $price);
$stmt->fetch();
$stmt->close();

$total = $price * $duration;

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
      <form method="post" action="process_payment.php" enctype="multipart/form-data" class="mt-4" id="paymentForm">
        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
        <input type="hidden" name="amount" value="<?= (int)$total ?>">

        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Metode Pembayaran</label>
            <select id="payMethod" name="method" class="form-select" required>
              <option value="qris">QRIS</option>
              <option value="transfer">Transfer Bank</option>
            </select>

            <div id="methodInfo" class="mt-3">
              <div id="info-qris">
                <p class="mb-2">Silakan scan QR berikut menggunakan aplikasi pembayaran Anda:</p>
                <img src="assets/img/qris.png" alt="QRIS" style="max-width:280px;height:auto;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.25)">
              </div>
              <div id="info-transfer" style="display:none">
                <p class="mb-1"><strong>BANK BCA</strong></p>
                <p class="mb-1">PT ARENA SPORTIVA</p>
                <p class="mb-1">NO REK <strong>2309853810</strong></p>
                <small class="text-muted">Mohon unggah bukti transfer setelah melakukan pembayaran.</small>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <?php
            $user_id = $_SESSION['user_id'];
            $stmt = $mysqli->prepare("SELECT coupon_discount FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->bind_result($coupon_discount);
            $stmt->fetch();
            $stmt->close();
            ?>
            <div class="mb-3">
              <label class="form-label">Coupon/Diskon Anda</label>
              <div class="d-flex align-items-center gap-2">
                <span id="couponInfo" class="form-control-plaintext text-success fw-bold" style="font-size:1.2em;">
                  <?= ($coupon_discount > 0) ? 'Rp ' . number_format($coupon_discount, 0, ',', '.') : 'Tidak ada coupon/diskon' ?>
                </span>
                <?php if ($coupon_discount > 0): ?>
                  <button type="button" class="btn btn-outline-primary btn-sm" id="applyCouponBtn">Pakai Coupon</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelCouponBtn" style="display:none;">Batalkan Coupon</button>
                <?php endif; ?>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Discount</label>
              <input type="number" id="discount" name="discount" class="form-control" value="0" min="0" max="<?= (int)$total ?>" step="1000" readonly>
              <small class="text-muted">Discount dari coupon Anda jika dipakai.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Jumlah yang harus ditransfer</label>
              <input type="number" id="paidAmount" name="paid_amount" class="form-control fw-bold" value="<?= (int)$total ?>" min="0" step="1000" required style="font-size:1.2em;">
              <small class="text-muted">Jumlah ini akan dikurangi dengan discount coupon jika dipakai.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Upload Bukti Transfer/QR</label>
              <input type="file" name="proof" class="form-control" accept="image/*" required>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-success flex-fill">Kirim Bukti & Selesaikan</button>
              <button type="button" class="btn btn-danger flex-fill" id="cancelBookingBtn">Batalkan Booking</button>
            </div>
          </div>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  <?php if ($status === 'pending'): ?>
(function(){
  // pakai kolom expired_at dari database
  var expireAt = <?= strtotime($expired_at) ?> * 1000;
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

  (function(){
    var sel = document.getElementById('payMethod');
    var qris = document.getElementById('info-qris');
    var transfer = document.getElementById('info-transfer');
    function update(){
      if (!sel) return;
      var v = sel.value;
      if (v === 'qris') { qris.style.display = ''; transfer.style.display = 'none'; }
      else { qris.style.display = 'none'; transfer.style.display = ''; }
    }
    if (sel) { sel.addEventListener('change', update); update(); }

    var discount = document.getElementById('discount');
    var paidAmount = document.getElementById('paidAmount');
    var applyCouponBtn = document.getElementById('applyCouponBtn');
    var cancelCouponBtn = document.getElementById('cancelCouponBtn');
    var couponDiscount = <?= (int)$coupon_discount ?>;
    var totalAmount = <?= (int)$total ?>;

    function calculateAmount() {
      var discountAmount = parseInt(discount.value) || 0;
      var remaining = Math.max(0, totalAmount - discountAmount);
      paidAmount.value = remaining;
    }

    if (discount && paidAmount) {
      discount.addEventListener('input', calculateAmount);
    }

    if (applyCouponBtn) {
      applyCouponBtn.addEventListener('click', function(){
        discount.value = couponDiscount;
        calculateAmount();
        applyCouponBtn.style.display = 'none';
        if (cancelCouponBtn) cancelCouponBtn.style.display = '';
      });
    }
    if (cancelCouponBtn) {
      cancelCouponBtn.addEventListener('click', function(){
        discount.value = 0;
        calculateAmount();
        cancelCouponBtn.style.display = 'none';
        if (applyCouponBtn) applyCouponBtn.style.display = '';
      });
    }

    var cancelBookingBtn = document.getElementById('cancelBookingBtn');
    if (cancelBookingBtn) {
      cancelBookingBtn.addEventListener('click', function(){
        if (confirm('Yakin ingin membatalkan booking ini?')) {
          window.location.href = 'cancel_booking.php?booking_id=<?= $booking_id ?>';
        }
      });
    }

    calculateAmount();
  })();
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
