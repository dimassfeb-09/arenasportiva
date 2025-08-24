<?php
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit;
}

require_once __DIR__ . '/../src/db_connect.php';

// Ambil riwayat booking & pembayaran user
$stmt = $mysqli->prepare(
	"SELECT b.id, b.booking_code, b.start_datetime, b.duration_hours, b.status AS booking_status,
	        c.name AS court_name, c.type AS court_type, c.price_per_hour,
	        p.status AS payment_status, p.amount, p.method, p.proof_url, p.created_at AS paid_at
	 FROM bookings b
	 JOIN courts c ON c.id = b.court_id
	 LEFT JOIN payments p ON p.booking_id = b.id
	 WHERE b.user_id = ?
	 ORDER BY COALESCE(p.created_at, b.start_datetime) DESC"
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include __DIR__ . '/../templates/header.php';
?>

<div class="container py-4" style="margin-top: 70px; max-width: 1100px;">
	<div class="card table-card">
		<div class="card-body">
			            <h2 class="mb-4">Riwayat Transaksi</h2>
            <div class="alert alert-info mb-3">
                <small><strong>Info:</strong> Jika booking anda ditolak (rejected), Segera hubungi admin via WhatsApp Untuk penarikan dana.</small>
            </div>
			<!-- bar hijau dihapus sesuai permintaan -->

	<?php if (empty($rows)): ?>
		<p>Belum ada transaksi.</p>
	<?php else: ?>
		<table class="table table-hover align-middle">
			<thead>
				<tr>
					<th>Kode</th>
					<th>Lapangan</th>
					<th>Jadwal</th>
					<th>Durasi</th>
					<th>Total</th>
					<th>Status Booking</th>
					<th>Status Pembayaran</th>
					<th>Bukti</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($rows as $r):
				$total = (int)$r['price_per_hour'] * (int)$r['duration_hours'];
			?>
				<tr>
					<td><code><?= htmlspecialchars($r['booking_code']) ?></code></td>
					<td><?= htmlspecialchars($r['court_name']) ?> (<?= htmlspecialchars($r['court_type']) ?>)</td>
					<td><?= htmlspecialchars($r['start_datetime']) ?></td>
					<td><?= (int)$r['duration_hours'] ?> jam</td>
					<td><strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></td>
					                    <td>
                        <?php if ($r['booking_status'] === 'rejected'): ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php elseif ($r['booking_status'] === 'confirmed'): ?>
                            <span class="badge bg-success">Confirmed</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($r['booking_status']) ?></span>
                        <?php endif; ?>
                    </td>
					<td>
						<?php if ($r['payment_status']): ?>
							<span class="badge <?= $r['payment_status']==='success' ? 'bg-success' : ($r['payment_status']==='failed' ? 'bg-danger' : 'bg-warning text-dark') ?>">
								<?= htmlspecialchars($r['payment_status']) ?>
							</span>
						<?php else: ?>
							-
						<?php endif; ?>
					</td>
					<td>
						<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ticketModal" 
							data-booking='<?= json_encode([
								"kode" => $r["booking_code"],
								"lapangan" => $r["court_name"],
								"tipe" => $r["court_type"],
								"jadwal" => $r["start_datetime"],
								"durasi" => $r["duration_hours"],
								"harga" => $r["price_per_hour"],
								"total" => $r["amount"],
								"status" => $r["booking_status"],
								"pembayaran" => $r["payment_status"],
								"metode" => $r["method"],
								"bukti" => $r["proof_url"]
							]) ?>'>Lihat</button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
		</div>
	</div>
<!-- Modal Tiket Booking -->
<div class="modal fade" id="ticketModal" tabindex="-1">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h5 class="modal-title">Tiket Booking</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<div id="ticketContent" class="p-2">
					<!-- Isi tiket booking akan diisi via JS -->
				</div>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
	var ticketModal = document.getElementById('ticketModal');
	ticketModal.addEventListener('show.bs.modal', function(event){
		var button = event.relatedTarget;
		var data = JSON.parse(button.getAttribute('data-booking'));
				var html = `<table class='table table-bordered mb-0'>
					<tr><th>Kode Booking</th><td>${data.kode}</td></tr>
					<tr><th>Lapangan</th><td>${data.lapangan} (${data.tipe})</td></tr>
					<tr><th>Jadwal</th><td>${data.jadwal}</td></tr>
					<tr><th>Durasi</th><td>${data.durasi} jam</td></tr>
					<tr><th>Harga/jam</th><td>Rp ${parseInt(data.harga).toLocaleString('id-ID')}</td></tr>
					<tr><th>Total Bayar</th><td><strong>Rp ${parseInt(data.total).toLocaleString('id-ID')}</strong></td></tr>
					<tr><th>Status Booking</th><td><span class='badge bg-${data.status=='confirmed'?'success':(data.status=='pending'?'warning':'danger')}'>${data.status}</span></td></tr>
					<tr><th>Status Pembayaran</th><td><span class='badge bg-${data.pembayaran=='success'?'success':(data.pembayaran=='pending'?'warning':'danger')}'>${data.pembayaran??'-'}</span></td></tr>
					<tr><th>Metode Pembayaran</th><td>${data.metode??'-'}</td></tr>
				</table>`;
		document.getElementById('ticketContent').innerHTML = html;
	});
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>


