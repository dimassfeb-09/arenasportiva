<?php
session_start();
// proteksi: hanya admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// load koneksi mysqli
require __DIR__ . '/../src/db_connect.php';

// 1) Ambil daftar pembayaran pending
$sql = "
  SELECT p.id AS payment_id,
         b.booking_code,
         u.name    AS customer,
         c.name    AS court,
         p.method,
         p.amount AS paid_amount,
         p.proof_url,
         p.created_at
  FROM payments p
  JOIN bookings b ON b.id = p.booking_id
  JOIN users    u ON u.id = b.user_id
  JOIN courts   c ON c.id = b.court_id
  WHERE p.status = 'pending'
  ORDER BY p.created_at ASC
";

$pendings = [];
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $pendings[] = $row;
    }
    $res->free();
}

// 2) Hitung omzet harian 30 hari terakhir
$sql2 = "
  SELECT DATE(p.created_at) AS tgl,
         COUNT(*)          AS trx,
         SUM(p.amount)     AS omzet
  FROM payments p
  WHERE p.status = 'success'
    AND p.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  GROUP BY DATE(p.created_at)
  ORDER BY DATE(p.created_at) DESC
";

$revenues = [];
if ($res2 = $mysqli->query($sql2)) {
    while ($row = $res2->fetch_assoc()) {
        $revenues[] = $row;
    }
    $res2->free();
}
?>
<?php include __DIR__.'/../templates/header.php'; ?>

  <div class="container mt-4">
    <h1>Admin Panel</h1>

    <h3>Pembayaran Pending</h3>
    <?php if (empty($pendings)): ?>
      <p>Tidak ada pembayaran pending.</p>
    <?php else: ?>
             <form action="admin_process.php" method="post" id="adminForm">
       <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th>#</th><th>Kode</th><th>Customer</th><th>Lapangan</th>
            <th>Metode</th><th>Dibayar</th><th>Diskon (Rp)</th><th>Bukti</th><th>Waktu</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($pendings as $i => $p): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($p['booking_code']) ?></td>
            <td><?= htmlspecialchars($p['customer']) ?></td>
            <td><?= htmlspecialchars($p['court']) ?></td>
            <td><?= strtoupper($p['method']) ?></td>
            <td>Rp <?= number_format($p['paid_amount'],0,',','.') ?></td>
            <td style="width:140px;">
              <input type="number" class="form-control form-control-sm" name="discount[<?= $p['payment_id'] ?>]" min="0" step="1000" placeholder="0">
            </td>
            <td><a href="<?= htmlspecialchars($p['proof_url']) ?>" target="_blank">Lihat</a></td>
            <td><?= $p['created_at'] ?></td>
                         <td>
                 <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">
                 <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                 <button type="submit" name="action" value="reject"  class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menolak pembayaran ini? Dana akan dikembalikan ke saldo user.')">Reject</button>
             </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </form>
    <?php endif; ?>

    <h3 class="mt-5">Omzet 30 Hari Terakhir</h3>
    <table class="table table-striped">
      <thead><tr><th>Tanggal</th><th>Transaksi</th><th>Omzet (Rp)</th></tr></thead>
      <tbody>
      <?php foreach ($revenues as $r): ?>
        <tr>
          <td><?= $r['tgl'] ?></td>
          <td><?= $r['trx'] ?></td>
          <td><?= number_format($r['omzet'],0,',','.') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php include __DIR__.'/../templates/footer.php'; ?>
