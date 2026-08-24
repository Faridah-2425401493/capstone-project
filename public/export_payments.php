<?php
require_once __DIR__.'/../src/bootstrap.php';
$u = require_role(['hostel_admin', 'system_admin']);

$pdo = db();

$sql = '
    SELECT p.reference, p.amount, p.status, p.created_at as payment_date, 
           u.name as student_name, u.email as student_email, 
           h.name as hostel_name, r.room_number 
    FROM payments p 
    JOIN bookings b ON b.id = p.booking_id 
    JOIN rooms r ON r.id = b.room_id 
    JOIN hostels h ON h.id = r.hostel_id 
    JOIN users u ON u.id = b.student_id 
    WHERE p.status = "success"
';

$params = [];
if ($u['role'] === 'hostel_admin') {
    $sql .= ' AND h.admin_id = ?';
    $params[] = $u['id'];
}

$sql .= ' ORDER BY p.created_at DESC';

$s = $pdo->prepare($sql);
$s->execute($params);
$payments = $s->fetchAll(PDO::FETCH_ASSOC);

$filename = "payments_export_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Reference', 'Student Name', 'Student Email', 'Hostel', 'Room Number', 'Amount Paid (GHS)', 'Status', 'Payment Date']);

foreach ($payments as $p) {
    fputcsv($output, [
        $p['reference'],
        $p['student_name'],
        $p['student_email'],
        $p['hostel_name'],
        $p['room_number'],
        number_format((float)$p['amount'], 2),
        $p['status'],
        $p['payment_date']
    ]);
}

fclose($output);
exit;
