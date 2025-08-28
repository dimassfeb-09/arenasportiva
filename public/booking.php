<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?auth=1");
    exit;
}

require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/booking.php';

// Get user data for form pre-fill
$user_stmt = $mysqli->prepare("SELECT name, phone FROM users WHERE id = ?");
$user_stmt->bind_param('i', $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

$jenis = $_GET['jenis'] ?? 'futsal';
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get all courts for the selected type
$courts_stmt = $mysqli->prepare("SELECT id, name, type, price_per_hour FROM courts WHERE status = 'available' AND type = ? ORDER BY id");
$courts_stmt->bind_param('s', $jenis);
$courts_stmt->execute();
$courts_result = $courts_stmt->get_result();
$all_courts = $courts_result->fetch_all(MYSQLI_ASSOC);
$courts_stmt->close();

// Get all bookings for the selected date to disable slots
$bookings_stmt = $mysqli->prepare("SELECT court_id, start_datetime, duration_hours FROM bookings WHERE DATE(start_datetime) = ? AND status IN ('pending', 'confirmed')");
$bookings_stmt->bind_param('s', $selected_date);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$day_bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
$bookings_stmt->close();

$blocked_slots = [];
foreach ($day_bookings as $b) {
    $dt = new DateTime($b['start_datetime']);
    for ($i = 0; $i < $b['duration_hours']; $i++) {
        $blocked_slots[$b['court_id']][] = $dt->format('H:00');
        $dt->modify('+1 hour');
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="profile-page py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold text-dark">Booking Lapangan</h1>
            <p class="lead text-secondary">Pilih jadwal dan lapangan yang Anda inginkan.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Discount Alert -->
                <div class="alert alert-info mb-4">
                    <h5><i class="fas fa-tag me-2"></i>Promo Spesial!</h5>
                    <ul class="mb-0">
                        <li>Diskon 5% untuk booking 4-5 jam</li>
                        <li>Diskon 10% untuk booking 6 jam atau lebih</li>
                    </ul>
                </div>
                <form method="post" action="process_booking.php" id="bookingForm">
                    <div class="booking-page-card">
                        <!-- Step 1: Date Selection -->
                        <h4 class="mb-3">1. Pilih Tanggal</h4>
                        <div class="mb-4">
                            <input type="date" id="datePicker" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" min="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Step 2: Court and Time Selection -->
                        <h4 class="mb-3">2. Pilih Lapangan & Waktu</h4>
                        <ul class="nav nav-tabs nav-fill mb-3" id="courtTabs" role="tablist">
                            <?php foreach ($all_courts as $index => $c): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" id="court-tab-<?= $c['id'] ?>" data-bs-toggle="tab" data-bs-target="#court-pane-<?= $c['id'] ?>" type="button" role="tab"><?= htmlspecialchars($c['name']) ?></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="tab-content">
                            <?php foreach ($all_courts as $index => $c): ?>
                                <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" id="court-pane-<?= $c['id'] ?>" role="tabpanel">
                                    <div class="d-flex flex-wrap">
                                        <?php 
                                        // 10:00 to 23:00
                                        for ($h = 9; $h <= 23; $h++) { 
                                            $slot_time = sprintf('%02d:00', $h);
                                            $slot_datetime = $selected_date . ' ' . $slot_time;
                                            $is_blocked = isset($blocked_slots[$c['id']]) && in_array($slot_time, $blocked_slots[$c['id']]);
                                            $input_id = 'slot_' . $c['id'] . '_' . $h;
                                        ?>
                                            <div class="form-check form-check-inline m-1">
                                                <input id="<?= $input_id ?>" class="slot-input" type="radio" name="slot" value="<?= $slot_datetime ?>" data-court-id="<?= $c['id'] ?>" data-court-name="<?= htmlspecialchars($c['name']) ?>" data-price="<?= $c['price_per_hour'] ?>" <?= $is_blocked ? 'disabled' : '' ?>>
                                                <label for="<?= $input_id ?>" class="slot-badge"><?= $slot_time ?></label>
                                            </div>
                                        <?php }
                                        // 00:00 to 01:00 (next day, but still considered as late night)
                                        for ($h = 0; $h <= 1; $h++) {
                                            $slot_time = sprintf('%02d:00', $h);
                                            $slot_datetime = $selected_date . ' ' . $slot_time;
                                            $is_blocked = isset($blocked_slots[$c['id']]) && in_array($slot_time, $blocked_slots[$c['id']]);
                                            $input_id = 'slot_' . $c['id'] . '_' . $h;
                                        ?>
                                            <div class="form-check form-check-inline m-1">
                                                <input id="<?= $input_id ?>" class="slot-input" type="radio" name="slot" value="<?= $slot_datetime ?>" data-court-id="<?= $c['id'] ?>" data-court-name="<?= htmlspecialchars($c['name']) ?>" data-price="<?= $c['price_per_hour'] ?>" <?= $is_blocked ? 'disabled' : '' ?>>
                                                <label for="<?= $input_id ?>" class="slot-badge"><?= $slot_time ?></label>
                                            </div>
                                        <?php }
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Step 3: Booking Details -->
                        <h4 class="mb-3 mt-4">3. Lengkapi Data</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="duration" class="form-label">Durasi (Jam)</label>
                                <select name="duration" id="duration" class="form-select" required>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> jam</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="customer_name" class="form-label">Nama Pemesan</label>
                                <input type="text" name="customer_name" class="form-control" value="<?= htmlspecialchars($user_data['name']) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="customer_phone" class="form-label">Nomor Telepon</label>
                                <input type="text" name="customer_phone" class="form-control" value="<?= htmlspecialchars($user_data['phone']) ?>" required>
                            </div>
                        </div>
                        <input type="hidden" name="court_id" id="court_id_hidden">
                        <input type="hidden" name="discount_amount" id="discount_amount_hidden">
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="booking-summary">
                    <h4 class="mb-3">Ringkasan Booking</h4>
                    <div id="summaryContent">
                        <p class="text-muted">Silakan pilih tanggal, lapangan, dan waktu untuk melihat ringkasan.</p>
                    </div>
                    <button type="submit" form="bookingForm" class="btn btn-primary w-100 mt-3 d-none" id="btnProceedPayment">Lanjut ke Pembayaran</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const datePicker = document.getElementById('datePicker');
    const bookingForm = document.getElementById('bookingForm');
    const summaryContent = document.getElementById('summaryContent');
    const btnProceedPayment = document.getElementById('btnProceedPayment');
    const courtIdHidden = document.getElementById('court_id_hidden');

    datePicker.addEventListener('change', function() {
        const selectedDate = this.value;
        const url = new URL(window.location);
        url.searchParams.set('date', selectedDate);
        window.location.href = url.toString();
    });

    bookingForm.addEventListener('change', updateSummary);

    function updateSummary() {
        const selectedSlot = document.querySelector('input[name="slot"]:checked');
        const duration = parseInt(document.getElementById('duration').value);

        if (!selectedSlot) {
            summaryContent.innerHTML = '<p class="text-muted">Silakan pilih waktu.</p>';
            btnProceedPayment.classList.add('d-none');
            return;
        }

        const courtName = selectedSlot.dataset.courtName;
        const price = parseInt(selectedSlot.dataset.price);
        const courtId = selectedSlot.dataset.courtId;
        const time = selectedSlot.value.split(' ')[1];
        const subtotal = price * duration;
        let discount = 0;
        let total = subtotal;
        let discountText = '';
        
        // Calculate discounts based on duration
        if (duration >= 6) {
            discount = Math.floor(subtotal * 0.1); // 10% discount
            discountText = '(Diskon 10%)';
        } else if (duration >= 4) {
            discount = Math.floor(subtotal * 0.05); // 5% discount
            discountText = '(Diskon 5%)';
        }

        courtIdHidden.value = courtId;

        summaryContent.innerHTML = `
            <table class="table table-sm table-borderless">
                <tr>
                    <td class="text-muted">Tanggal</td>
                    <td class="text-end fw-bold">${new Date(datePicker.value).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</td>
                </tr>
                <tr>
                    <td class="text-muted">Lapangan</td>
                    <td class="text-end fw-bold">${courtName}</td>
                </tr>
                <tr>
                    <td class="text-muted">Waktu</td>
                    <td class="text-end fw-bold">${time}</td>
                </tr>
                <tr>
                    <td class="text-muted">Durasi</td>
                    <td class="text-end fw-bold">${duration} jam</td>
                </tr>
                <tr>
                    <td class="text-muted">Harga per Jam</td>
                    <td class="text-end fw-bold">Rp ${price.toLocaleString('id-ID')}</td>
                </tr>
                <tr>
                    <td class="text-muted">Subtotal</td>
                    <td class="text-end fw-bold">Rp ${subtotal.toLocaleString('id-ID')}</td>
                </tr>
                ${discount > 0 ? `
                <tr>
                    <td class="text-muted d-flex align-items-center">
                        <span class="badge bg-success me-2">
                            <i class="fas fa-tag"></i>
                        </span>
                        <div>
                            ${duration >= 6 ? 'Diskon 10%' : 'Diskon 5%'}
                            <small class="d-block text-muted">
                                ${duration >= 6 ? '(Booking ≥ 6 jam)' : '(Booking 4-5 jam)'}
                            </small>
                        </div>
                    </td>
                    <td class="text-end fw-bold text-success">- Rp ${discount.toLocaleString('id-ID')}</td>
                </tr>
                ` : ''}
                <tr class="border-top">
                    <td class="pt-3"><strong>Total Pembayaran</strong></td>
                    <td class="text-end pt-3"><strong class="h5">Rp ${total.toLocaleString('id-ID')}</strong></td>
                </tr>
            </table>
        `;
        btnProceedPayment.classList.remove('d-none');
    }

    // Initial summary update on page load
    updateSummary();

    // Update discount amount before form submission
    bookingForm.addEventListener('submit', function() {
        const duration = parseInt(document.getElementById('duration').value);
        const selectedSlot = document.querySelector('input[name="slot"]:checked');
        if (selectedSlot) {
            const price = parseInt(selectedSlot.dataset.price);
            const subtotal = price * duration;
            let discount = 0;
            
            if (duration >= 6) {
                discount = Math.floor(subtotal * 0.1); // 10% discount
            } else if (duration >= 4) {
                discount = Math.floor(subtotal * 0.05); // 5% discount
            }
            
            document.getElementById('discount_amount_hidden').value = discount;
        }
    });
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>