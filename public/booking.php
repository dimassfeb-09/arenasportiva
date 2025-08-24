<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?auth=1');
    exit;
}

require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/booking.php';

$jenis = $_GET['jenis'] ?? '';
if (!isset($_GET['court_id'])) {
  if ($jenis) {
    $stmt = $mysqli->prepare("SELECT id FROM courts WHERE type = ? AND status = 'available' ORDER BY id LIMIT 1");
    $stmt->bind_param('s', $jenis);
    $stmt->execute();
    $stmt->bind_result($firstId);
    if ($stmt->fetch()) {
      header('Location: booking.php?jenis=' . $jenis . '&court_id=' . $firstId);
      exit;
    }
    $stmt->close();
  }
}

$court_id = isset($_GET['court_id']) ? (int)$_GET['court_id'] : 0;
$bookings = $court_id ? getCourtBookings($mysqli, $court_id) : [];

// daftar lapangan
if ($jenis) {
  $stmt = $mysqli->prepare("SELECT id, name, type, price_per_hour FROM courts WHERE status = 'available' AND type = ? ORDER BY id");
  $stmt->bind_param('s', $jenis);
} else {
  $stmt = $mysqli->prepare("SELECT id, name, type, price_per_hour FROM courts WHERE status = 'available' ORDER BY type, id");
}
$stmt->execute();
$res = $stmt->get_result();
$allCourts = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// hitung blocked slots sekali saja
$blocked = [];
foreach ($bookings as $b) {
  $dt = new DateTime($b['start_datetime']);
  for ($i = 0; $i < $b['duration_hours']; $i++) {
    $blocked[] = $dt->format('Y-m-d H:00');
    $dt->modify('+1 hour');
  }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="container py-4">
  <div class="card shadow-sm">
    <div class="card-body">
      <h2 class="mb-4">Booking Lapangan</h2>

      <div class="mb-3">
        <label class="form-label">Pilih Lapangan</label>
        <select id="courtSelect" name="court_id" class="form-select w-auto d-inline-block">
          <?php foreach ($allCourts as $c): ?>
            <option value="<?= $c['id'] ?>" data-jenis="<?= htmlspecialchars($c['type']) ?>" <?= $c['id'] == $court_id ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?> (<?= ucfirst($c['type']) ?>, Rp <?= number_format($c['price_per_hour'], 0, ',', '.') ?>/jam)</option>
          <?php endforeach; ?>
        </select>
        <script>
          document.getElementById('courtSelect').addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            var jenis = selected.getAttribute('data-jenis');
            var courtId = this.value;
            window.location.href = "booking.php?jenis=" + jenis + "&court_id=" + courtId;
          });
        </script>
      </div>

      <form method="post" action="process_booking.php" class="mt-3 booking-card">
        <input type="hidden" name="court_id" value="<?= $court_id ?>">

  <?php
  $today = new DateTime();
  for ($d = 0; $d < 7; $d++):
    $day = (clone $today)->modify("+{$d} days");
    $label = $day->format('l, d-m-Y');
  ?>
    <h5 class="mt-3"><?= $label ?></h5>
    <div class="form-group">
      <?php
      // jam 10–23
      for ($h = 10; $h <= 23; $h++):
        $slot = $day->format('Y-m-d') . ' ' . sprintf('%02d:00', $h);
        $disabled = in_array($slot, $blocked) ? 'disabled' : '';
        $inputId = 'slot_' . $day->format('Ymd') . '_' . sprintf('%02d',$h);
      ?>
        <div class="form-check form-check-inline">
          <input id="<?= $inputId ?>" class="slot-input" type="radio" name="slot" value="<?= $slot ?>" <?= $disabled ?>>
          <label for="<?= $inputId ?>" class="slot-badge"><?= sprintf('%02d:00',$h) ?></label>
        </div>
      <?php endfor; ?>

      <?php
      // jam 00–02 hari berikutnya
      $next = (clone $day)->modify('+1 day');
      for ($h = 0; $h <= 2; $h++):
        $slot = $next->format('Y-m-d') . ' ' . sprintf('%02d:00', $h);
        $disabled = in_array($slot, $blocked) ? 'disabled' : '';
        $inputId = 'slot_' . $next->format('Ymd') . '_' . sprintf('%02d',$h);
      ?>
        <div class="form-check form-check-inline">
          <input id="<?= $inputId ?>" class="slot-input" type="radio" name="slot" value="<?= $slot ?>" <?= $disabled ?>>
          <label for="<?= $inputId ?>" class="slot-badge"><?= sprintf('%02d:00',$h) ?></label>
        </div>
      <?php endfor; ?>
    </div>
    <hr>
  <?php endfor; ?>

      <div class="row g-3 mt-2">
        <div class="col-md-4">
          <label class="form-label">Durasi (jam)</label>
          <select name="duration" class="form-select" required>
            <?php for ($i = 1; $i <= 4; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?> jam</option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nama</label>
          <input type="text" name="customer_name" class="form-control" placeholder="Nama lengkap" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Nomor Telepon</label>
          <input type="text" name="customer_phone" class="form-control" placeholder="08xxxxxxxxxx" required>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Lanjut ke Pembayaran</button>
      </div>
      </form>
    </div>
  </div>
</div>



<?php include __DIR__ . '/../templates/footer.php'; ?>
