<?php
require_once __DIR__.'/../src/bootstrap.php';
$u = require_role('student');
$id = (int)($_GET['booking_id'] ?? 0);

$s = db()->prepare('
    SELECT b.*, p.amount, p.reference, p.created_at as paid_at,
           r.room_number, r.price, r.floor_number, h.name as hostel_name, h.location, h.contact_phone
    FROM bookings b
    JOIN payments p ON p.booking_id = b.id
    JOIN rooms r ON r.id = b.room_id
    JOIN hostels h ON h.id = r.hostel_id
    WHERE b.id = ? AND b.student_id = ? AND p.status = "success"
');
$s->execute([$id, $u['id']]);
$inv = $s->fetch();

if (!$inv) {
    flash('error', 'Invoice not found or payment not completed.');
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?=e($inv['reference'])?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .invoice-box {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        @media print {
            body { background: white; }
            .invoice-box { box-shadow: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="mb-4 mt-4 text-end no-print">
            <a href="dashboard.php" class="btn btn-outline-secondary me-2">Back to Dashboard</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print / Save PDF</button>
        </div>
        
        <div class="invoice-box rounded border">
            <div class="row border-bottom pb-4 mb-4">
                <div class="col-md-6">
                    <h2 class="fw-bold text-primary mb-1">HostelCloud</h2>
                    <p class="text-muted mb-0">Accommodation Receipt</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h4 class="fw-bold text-dark mb-1">INVOICE</h4>
                    <p class="text-muted mb-0">Ref: <strong><?=e($inv['reference'])?></strong><br>Date: <?=date('M d, Y', strtotime($inv['paid_at']))?></p>
                </div>
            </div>
            
            <div class="row mb-5">
                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase fw-bold mb-3">Billed To:</h6>
                    <h5 class="fw-bold mb-1"><?=e($u['name'])?></h5>
                    <p class="mb-0 text-muted"><?=e($u['email'])?><br><?=e($u['phone'])?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h6 class="text-muted text-uppercase fw-bold mb-3">Hostel Details:</h6>
                    <h5 class="fw-bold mb-1"><?=e($inv['hostel_name'])?></h5>
                    <p class="mb-0 text-muted"><?=e($inv['location'])?><br><?=e($inv['contact_phone'])?></p>
                </div>
            </div>
            
            <table class="table table-bordered mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-center">Floor</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Room <?=e($inv['room_number'])?></strong>
                            <br><small class="text-muted">Hostel accommodation fee</small>
                        </td>
                        <td class="text-center align-middle"><?=e($inv['floor_number'] ?? '1')?></td>
                        <td class="text-end align-middle">GHS <?=number_format((float)$inv['price'], 2)?></td>
                    </tr>
                </tbody>
            </table>
            
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span>GHS <?=number_format((float)$inv['price'], 2)?></span>
                    </div>
                    <?php if($inv['price'] > $inv['amount']): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Discount Applied:</span>
                        <span>- GHS <?=number_format((float)($inv['price'] - $inv['amount']), 2)?></span>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <h5 class="fw-bold mb-0">Total Paid:</h5>
                        <h5 class="fw-bold text-success mb-0">GHS <?=number_format((float)$inv['amount'], 2)?></h5>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5 pt-4 border-top text-muted small">
                <p class="mb-0">Thank you for booking with HostelCloud.</p>
                <p>This is a computer generated receipt and does not require a physical signature.</p>
            </div>
        </div>
    </div>
</body>
</html>
