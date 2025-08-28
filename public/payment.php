<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?auth=1');
    exit;
}

require_once __DIR__ . '/../src/db_connect.php';

// Get booking ID either from URL (history page) or session (new booking)
$booking_id = 0;
if (isset($_GET['code'])) {
    // Coming from history page - get booking by code
    $code = $_GET['code'];
    $stmt = $mysqli->prepare("SELECT id FROM bookings WHERE booking_code = ? AND user_id = ?");
    $stmt->bind_param('si', $code, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $booking_id = $row['id'];
    }
    $stmt->close();
} else if (isset($_SESSION['last_booking_id'])) {
    // Coming from new booking
    $booking_id = (int)$_SESSION['last_booking_id'];
}

if (!$booking_id) {
    header('Location: history.php?error=invalid_booking');
    exit;
}
$stmt = $mysqli->prepare(
  "SELECT b.booking_code, b.start_datetime, b.duration_hours,
       b.expired_at, b.status, b.discount_amount,
       c.id AS court_id, c.name, c.type, c.price_per_hour
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.id = ?"
);
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$stmt->bind_result($code, $start_dt, $duration, $expired_at, $status, $discount_amount, $court_id, $court_name, $court_type, $price);
$stmt->fetch();
$stmt->close();

$subtotal = $price * $duration;
$total = $subtotal - $discount_amount;

$user_id = $_SESSION['user_id'];

include __DIR__ . '/../templates/header.php';
?>

<div class="profile-page py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold text-black">Pembayaran Booking</h1>
            <p class="lead text-secondary">Selesaikan pembayaran Anda untuk mengkonfirmasi booking.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="payment-info-card">
                    <h4 class="mb-3">Detail Booking</h4>
                    <table class="table table-bordered mb-4">
                        <tr><th>Kode Booking</th><td><?= htmlspecialchars($code) ?></td></tr>
                        <tr><th>Lapangan</th><td><?= htmlspecialchars($court_name) ?> (<?= ucfirst($court_type) ?>)</td></tr>
                        <tr><th>Tanggal & Jam</th><td><?= date('d M Y, H:i', strtotime($start_dt)) ?></td></tr>
                        <tr><th>Durasi</th><td><?= $duration ?> jam</td></tr>
                        <tr><th>Harga/jam</th><td>Rp <?= number_format($price,0,',','.') ?></td></tr>
                        <tr><th>Subtotal</th><td>Rp <?= number_format($subtotal,0,',','.') ?></td></tr>
                        <?php if ($discount_amount > 0): ?>
                        <tr>
                            <th>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-success me-2">
                                        <i class="fas fa-tag"></i>
                                    </span>
                                    Diskon <?= ($duration >= 6) ? '10%' : '5%' ?>
                                    <small class="text-muted ms-2">
                                        <?php if ($duration >= 6): ?>
                                            (Booking ≥ 6 jam)
                                        <?php else: ?>
                                            (Booking 4-5 jam)
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </th>
                            <td class="text-success">- Rp <?= number_format($discount_amount,0,',','.') ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr><th>Total Bayar</th><td><strong>Rp <?= number_format($total,0,',','.') ?></strong></td></tr>
                        <?php if (!empty($_SESSION['customer_name']) || !empty($_SESSION['customer_phone'])): ?>
                        <tr><th>Nama Pemesan</th><td><?= htmlspecialchars($_SESSION['customer_name'] ?? '') ?></td></tr>
                        <tr><th>No. Telepon Pemesan</th><td><?= htmlspecialchars($_SESSION['customer_phone'] ?? '') ?></td></tr>
                        <?php endif; ?>
                    </table>

                    <?php if ($status === 'pending'): ?>
                    <h4 class="mb-3">Metode Pembayaran</h4>
                    <form method="post" action="process_payment.php" enctype="multipart/form-data" id="paymentForm">
                        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
                        <input type="hidden" name="amount" value="<?= (int)$total ?>">
                        <input type="hidden" name="discount" id="discount_hidden" value="0">
                        <input type="hidden" name="paid_amount" id="paid_amount_hidden" value="<?= (int)$total ?>">

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-check payment-method-option p-3 text-center">
                                    <input class="form-check-input" type="radio" name="method" id="methodQris" value="qris" required>
                                    <label class="form-check-label w-100" for="methodQris">
                                        <img src="assets/img/logoqris.png" alt="QRIS" class="img-fluid mb-2">
                                        <p class="fw-bold mb-0">QRIS</p>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check payment-method-option p-3 text-center">
                                    <input class="form-check-input" type="radio" name="method" id="methodTransfer" value="transfer" required>
                                    <label class="form-check-label w-100" for="methodTransfer">
                                        <i class="fas fa-university fa-3x mb-2 text-primary"></i>
                                        <p class="fw-bold mb-0">Transfer Bank</p>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="qrisInfo" class="mb-4 payment-info d-none">
                            <div class="alert alert-info">
                                <h5 class="alert-heading">Pembayaran QRIS</h5>
                                <p class="mb-0">Scan QR Code berikut untuk melakukan pembayaran:</p>
                                <img src="assets/img/qris.png" alt="QR Code" class="img-fluid mt-3" style="max-width: 200px;">
                            </div>
                        </div>

                        <div id="transferInfo" class="mb-4 payment-info d-none">
                            <div class="alert alert-info">
                                <h5 class="alert-heading">Transfer Bank</h5>
                                <p class="mb-0">Silakan transfer ke rekening berikut:</p>
                                <ul class="list-unstyled mt-2 mb-0">
                                    <li><strong>Bank BCA</strong></li>
                                    <li>No. Rek: 1234567890</li>
                                    <li>a.n. Arena Sportiva</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="proof" class="form-label">Upload Bukti Transfer/QR</label>
                            <input type="file" name="proof" id="proof" class="form-control" accept="image/*" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">Kirim Bukti & Selesaikan</button>
                            <button type="button" class="btn btn-danger btn-lg" id="cancelBookingBtn">Batalkan Booking</button>
                        </div>

                        <script>
                        // Handle payment method selection
                        document.querySelectorAll('input[name="method"]').forEach(radio => {
                            radio.addEventListener('change', function() {
                                // Hide all payment info sections
                                document.querySelectorAll('.payment-info').forEach(info => {
                                    info.classList.add('d-none');
                                });
                                
                                // Show selected method info
                                if (this.value === 'qris') {
                                    document.getElementById('qrisInfo').classList.remove('d-none');
                                } else if (this.value === 'transfer') {
                                    document.getElementById('transferInfo').classList.remove('d-none');
                                }
                            });
                        });

                        // Handle form submission
                        document.getElementById('paymentForm').addEventListener('submit', function(e) {
                            const method = document.querySelector('input[name="method"]:checked');
                            const proof = document.getElementById('proof');
                            
                            if (!method) {
                                e.preventDefault();
                                alert('Silakan pilih metode pembayaran');
                                return;
                            }
                            
                            if (!proof.files.length) {
                                e.preventDefault();
                                alert('Silakan upload bukti pembayaran');
                                return;
                            }
                        });
                        </script>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            Booking ini sudah tidak dalam status pending. Status: <strong><?= htmlspecialchars($status) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="payment-info-card">
                    <h4 class="mb-3">Ringkasan Pembayaran</h4>
                    <?php if ($status === 'pending'): ?>
                    <p class="text-muted mb-2">Sisa waktu pembayaran:</p>
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-clock me-2"></i>
                            <div>
                                <small class="d-block">Selesaikan pembayaran dalam:</small>
                                <div id="countdown" class="countdown-timer h4 mb-0 text-center">
                                    <span id="hours">00</span>:<span id="minutes">00</span>:<span id="seconds">00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    // Set the expiration time with timezone offset
                    const expiredAt = new Date("<?= $expired_at ?>").getTime();
                    let isExpired = false;
                    
                    // Function to check if booking is expired from server
                    async function checkBookingStatus() {
                        try {
                            const response = await fetch('api/check_booking_status.php?id=<?= $booking_id ?>');
                            const data = await response.json();
                            if (data.status === 'cancelled') {
                                clearInterval(countdownTimer);
                                clearInterval(statusChecker);
                                document.getElementById("countdown").innerHTML = "EXPIRED";
                                
                                const warningDiv = document.createElement('div');
                                warningDiv.className = 'alert alert-warning mt-3';
                                warningDiv.innerHTML = 'Booking telah dibatalkan karena melewati batas waktu pembayaran. Halaman akan dialihkan dalam 5 detik...';
                                document.getElementById("countdown").parentNode.appendChild(warningDiv);
                                
                                setTimeout(() => {
                                    window.location.href = 'history.php?expired=1';
                                }, 5000);
                            }
                        } catch (error) {
                            console.error('Error checking booking status:', error);
                        }
                    }

                    // Check booking status every 30 seconds
                    const statusChecker = setInterval(checkBookingStatus, 30000);
                    
                    // Update the countdown every second
                    const countdownTimer = setInterval(function() {
                        const now = new Date().getTime();
                        const distance = expiredAt - now;
                        
                        // Calculate hours, minutes, and seconds
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                        
                        // Display the countdown
                        document.getElementById("hours").innerHTML = hours.toString().padStart(2, '0');
                        document.getElementById("minutes").innerHTML = minutes.toString().padStart(2, '0');
                        document.getElementById("seconds").innerHTML = seconds.toString().padStart(2, '0');
                        
                        // If countdown is finished and not already expired
                        if (distance < 0 && !isExpired) {
                            isExpired = true;
                            clearInterval(countdownTimer);
                            clearInterval(statusChecker);
                            checkBookingStatus(); // Check status immediately
                        }
                    }, 1000);
                    </script>
                    <?php endif; ?>

                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Booking
                            <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center fw-bold">
                            Jumlah Harus Dibayar
                            <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookingId = <?= $booking_id ?>;
    const totalAmount = <?= (int)$total ?>;
    let currentDiscount = 0;

    const paymentMethodOptions = document.querySelectorAll('.payment-method-option');
    const selectedMethodInput = document.getElementById('selected_method');
    const methodInfoDiv = document.getElementById('methodInfo');

    const couponDisplay = document.getElementById('couponDisplay');
    const finalAmountDisplay = document.getElementById('finalAmountDisplay');
    const discountHidden = document.getElementById('discount_hidden');
    const paidAmountHidden = document.getElementById('paid_amount_hidden');

    const applyCouponBtn = document.getElementById('applyCouponBtn');
    const cancelCouponBtn = document.getElementById('cancelCouponBtn');
    const cancelBookingBtn = document.getElementById('cancelBookingBtn');

    // Payment Method Selection
    paymentMethodOptions.forEach(option => {
        option.addEventListener('click', function() {
            paymentMethodOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            const method = this.dataset.method;
            selectedMethodInput.value = method;
            updateMethodInfo(method);
        });
    });

    function updateMethodInfo(method) {
        let infoHtml = '';
        if (method === 'qris') {
            infoHtml = `
                <div class="text-center">
                    <p class="mb-2">Silakan scan QR berikut menggunakan aplikasi pembayaran Anda:</p>
                    <img src="assets/img/qris.png" alt="QRIS" class="img-fluid" style="max-width:250px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.15);">
                </div>
            `;
        } else if (method === 'transfer') {
            infoHtml = `
                <div>
                    <p class="mb-1"><strong>BANK BCA</strong></p>
                    <p class="mb-1">PT ARENA SPORTIVA</p>
                    <p class="mb-1">NO REK <strong>2309853810</strong></p>
                    <small class="text-muted">Mohon unggah bukti transfer setelah melakukan pembayaran.</small>
                </div>
            `;
        }
        methodInfoDiv.innerHTML = infoHtml;
    }

    // Coupon Logic
    function updateAmounts() {
        const finalAmount = totalAmount - currentDiscount;
        couponDisplay.textContent = `Rp ${currentDiscount.toLocaleString('id-ID')}`;
        finalAmountDisplay.textContent = `Rp ${finalAmount.toLocaleString('id-ID')}`;
        discountHidden.value = currentDiscount;
        paidAmountHidden.value = finalAmount;
    }

    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', function() {
            currentDiscount = couponDiscount;
            updateAmounts();
            applyCouponBtn.classList.add('d-none');
            cancelCouponBtn.classList.remove('d-none');
        });
    }

    if (cancelCouponBtn) {
        cancelCouponBtn.addEventListener('click', function() {
            currentDiscount = 0;
            updateAmounts();
            cancelCouponBtn.classList.add('d-none');
            applyCouponBtn.classList.remove('d-none');
        });
    }

    // Cancel Booking Button
    if (cancelBookingBtn) {
        cancelBookingBtn.addEventListener('click', function() {
            if (confirm('Yakin ingin membatalkan booking ini?')) {
                window.location.href = `cancel_booking.php?booking_id=${bookingId}`;
            }
        });
    }

    // Countdown Timer
    <?php if ($status === 'pending'): ?>
    const expireAt = <?= strtotime($expired_at) ?> * 1000;
    const countdownEl = document.getElementById('countdown');

    function updateCountdown() {
        const now = Date.now();
        const distance = expireAt - now;

        if (distance <= 0) {
            countdownEl.innerHTML = '<span class="text-danger">00:00</span>';
            // Disable form elements
            const form = document.getElementById('paymentForm');
            if (form) {
                const elements = form.elements;
                for (let i = 0; i < elements.length; i++) {
                    elements[i].disabled = true;
                }
            }
            // Show expired message and redirect
            Swal.fire({
                title: 'Waktu Pembayaran Habis!',
                text: 'Booking Anda akan dibatalkan secara otomatis.',
                icon: 'warning',
                allowOutsideClick: false,
                showCancelButton: false,
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = `cancel_booking.php?booking_id=${bookingId}&reason=expired`;
            });
            return;
        }

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        if (distance <= 300000) { // 5 minutes or less
            countdownEl.innerHTML = `<span class="text-danger">${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}</span>`;
        } else {
            countdownEl.innerHTML = `<span>${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}</span>`;
        }
    }

    // Check server-side expiry status every 30 seconds
    function checkBookingStatus() {
        fetch('api/check_expired_bookings.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.cancelled_bookings > 0) {
                    location.reload(); // Reload if any bookings were cancelled
                }
            });
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
    setInterval(checkBookingStatus, 30000); // Check every 30 seconds
    <?php endif; ?>

    // Initial setup
    updateAmounts();
    // Select QRIS by default
    document.querySelector('.payment-method-option[data-method="qris"]').click();
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>