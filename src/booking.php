<?php
/**
 * Ambil semua lapangan.
 * @return array of ['id','name','type','price_per_hour']
 */
function getAllCourts($mysqli, $onlyAvailable = false) {
    if ($onlyAvailable) {
        $stmt = $mysqli->prepare("SELECT id, name, type, price_per_hour FROM courts WHERE status = 'available' ORDER BY id");
    } else {
        $stmt = $mysqli->prepare("SELECT id, name, type, price_per_hour FROM courts ORDER BY id");
    }
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ambil semua slot yang sudah dibooking (pending & confirmed) untuk suatu lapangan.
 * @return array of ['start_datetime','duration_hours']
 */
function getCourtBookings($mysqli, $court_id) {
    $stmt = $mysqli->prepare(
      "SELECT start_datetime, duration_hours
       FROM bookings
       WHERE court_id = ? AND status IN ('pending','confirmed')
       AND CONCAT(DATE(start_datetime), ' ', TIME(start_datetime)) >= NOW()");
    $stmt->bind_param('i', $court_id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}
