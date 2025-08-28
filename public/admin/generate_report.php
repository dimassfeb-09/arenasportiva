<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/db_connect.php';

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];

    // Fetch data for the report
    $start_date = $mysqli->real_escape_string($start_date);
    $end_date = $mysqli->real_escape_string($end_date);
    
    $sql = "
        SELECT b.*, u.name as user_name, c.name as court_name,
               p.amount as payment_amount, p.status as payment_status,
               c.price_per_hour * b.duration_hours as subtotal,
               CASE 
                   WHEN b.duration_hours >= 6 THEN ROUND((c.price_per_hour * b.duration_hours) * 0.10)
                   WHEN b.duration_hours >= 4 THEN ROUND((c.price_per_hour * b.duration_hours) * 0.05)
                   ELSE 0
               END as discount_amount,
               CASE 
                   WHEN b.duration_hours >= 6 THEN 
                       (c.price_per_hour * b.duration_hours) - 
                       ROUND((c.price_per_hour * b.duration_hours) * 0.10)
                   WHEN b.duration_hours >= 4 THEN 
                       (c.price_per_hour * b.duration_hours) - 
                       ROUND((c.price_per_hour * b.duration_hours) * 0.05)
                   ELSE 
                       c.price_per_hour * b.duration_hours
               END as total_price
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN courts c ON b.court_id = c.id
        JOIN payments p ON b.id = p.booking_id
        WHERE b.status = 'confirmed' AND p.status = 'success'
        AND DATE(b.start_datetime) BETWEEN '$start_date' AND '$end_date'
        ORDER BY b.start_datetime ASC
    ";
    
    $result = $mysqli->query($sql);
    
    $bookings = [];
    $total_revenue = 0;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
            $total_revenue += $row['total_price'];
        }
    }

    // --- PDF Generation ---
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Arena Sportiva Admin');
    $pdf->SetTitle('Laporan Pemasukan');
    $pdf->SetSubject('Laporan Pemasukan dari ' . $start_date . ' sampai ' . $end_date);

    // Header
    $pdf->SetHeaderData('', 0, 'Laporan Pemasukan Arena Sportiva', "Periode: " . date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date)));
    $pdf->setHeaderFont(['helvetica', 'B', 12]);
    $pdf->setFooterFont(['helvetica', '', 8]);

    // Margins
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(15);
    $pdf->SetFooterMargin(15);

    $pdf->SetAutoPageBreak(TRUE, 25);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Laporan Pemasukan', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 15, 'Periode: ' . date('d F Y', strtotime($start_date)) . ' - ' . date('d F Y', strtotime($end_date)), 0, 1, 'C');

    // Calculate totals
    $total_subtotal = 0;
    $total_discount = 0;
    $total_revenue = 0;
    foreach ($bookings as $booking) {
        $total_subtotal += $booking['subtotal'];
        $total_discount += $booking['discount_amount'];
        $total_revenue += $booking['total_price'];
    }

    // Summary
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 10, 'Ringkasan', 0, 1, 'L');
    $html = '<table border="1" cellpadding="5">
                <tr>
                    <td width="200">Total Booking Sukses</td>
                    <td width="200">' . count($bookings) . ' booking</td>
                </tr>
                <tr>
                    <td>Total Subtotal</td>
                    <td>Rp ' . number_format($total_subtotal, 0, ',', '.') . '</td>
                </tr>
                <tr>
                    <td>Total Diskon</td>
                    <td>Rp ' . number_format($total_discount, 0, ',', '.') . '</td>
                </tr>
                <tr style="background-color: #f8f9fa;">
                    <td><strong>Total Pendapatan Bersih</strong></td>
                    <td><strong>Rp ' . number_format($total_revenue, 0, ',', '.') . '</strong></td>
                </tr>
            </table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(10);

    // Table Header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(10, 7, 'ID', 1, 0, 'C', 1);
    $pdf->Cell(35, 7, 'Pengguna', 1, 0, 'C', 1);
    $pdf->Cell(35, 7, 'Lapangan', 1, 0, 'C', 1);
    $pdf->Cell(35, 7, 'Waktu Booking', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Subtotal', 1, 0, 'C', 1);
    $pdf->Cell(25, 7, 'Diskon', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Total', 1, 1, 'C', 1);

    // Table Body
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(255, 255, 255);
    $fill = 0;
    foreach ($bookings as $booking) {
        $pdf->Cell(10, 6, '#' . $booking['id'], 'LR', 0, 'L', $fill);
        $pdf->Cell(35, 6, htmlspecialchars($booking['user_name']), 'LR', 0, 'L', $fill);
        $pdf->Cell(35, 6, htmlspecialchars($booking['court_name']), 'LR', 0, 'L', $fill);
        $pdf->Cell(35, 6, date('d/m/y H:i', strtotime($booking['start_datetime'])), 'LR', 0, 'L', $fill);
        $pdf->Cell(30, 6, 'Rp ' . number_format($booking['subtotal'], 0, ',', '.'), 'LR', 0, 'R', $fill);
        $pdf->Cell(25, 6, 'Rp ' . number_format($booking['discount_amount'], 0, ',', '.'), 'LR', 0, 'R', $fill);
        $pdf->Cell(30, 6, 'Rp ' . number_format($booking['total_price'], 0, ',', '.'), 'LR', 1, 'R', $fill);
        $fill = !$fill;
    }
    $pdf->Cell(200, 0, '', 'T');

    // --- Output --- 
    $pdf->Output('laporan_pemasukan_' . $start_date . '_' . $end_date . '.pdf', 'I');

} else {
    // Redirect back if dates are not set
    header('Location: report.php');
    exit();
}
