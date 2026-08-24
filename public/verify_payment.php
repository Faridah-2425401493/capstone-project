<?php
require_once __DIR__.'/../src/layout.php';
$u = require_login();

$reference = $_GET['reference'] ?? '';
$booking_id = (int)($_GET['booking_id'] ?? 0);

if (!$reference || !$booking_id) {
    flash('error', 'Invalid payment reference.');
    redirect('dashboard.php');
}

$pdo = db();
$s = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND student_id = ? AND status = "approved"');
$s->execute([$booking_id, $u['id']]);
$booking = $s->fetch();

if (!$booking) {
    flash('error', 'Booking not found or not in an approved state.');
    redirect('dashboard.php');
}

// Check if payment already exists
$s = $pdo->prepare('SELECT * FROM payments WHERE reference = ?');
$s->execute([$reference]);
if ($s->fetch()) {
    flash('error', 'This payment reference has already been processed.');
    redirect('dashboard.php');
}

$secret_key = env('PAYSTACK_SECRET_KEY', '');
if (!$secret_key) {
    // For local development without Paystack keys, simulate success
    $status = 'success';
    $amount = 0; // We don't have the exact verified amount from API
} else {
    // Verify with Paystack API
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "authorization: Bearer " . $secret_key,
            "cache-control: no-cache"
        ],
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        flash('error', 'cURL Error: ' . $err);
        redirect('dashboard.php');
    }
    
    $tranx = json_decode($response, true);
    if (!$tranx || !$tranx['status']) {
        flash('error', 'Paystack verification failed: ' . ($tranx['message'] ?? 'Unknown error'));
        redirect('dashboard.php');
    }
    
    if ('success' === $tranx['data']['status']) {
        $status = 'success';
        $amount = $tranx['data']['amount'] / 100; // convert back from pesewas
    } else {
        $status = 'failed';
    }
}

try {
    $pdo->beginTransaction();
    
    // Fallback amount if simulation
    if (!isset($amount) || $amount == 0) {
        $s = $pdo->prepare('SELECT price FROM rooms WHERE id = ?');
        $s->execute([$booking['room_id']]);
        $room_price = $s->fetchColumn();
        
        $discount = 0;
        if (isset($_SESSION['applied_coupon_'.$booking_id])) {
            $discount = $_SESSION['applied_coupon_'.$booking_id]['discount'];
        }
        $amount = max(0, $room_price - $discount);
    }
    
    $pdo->prepare('INSERT INTO payments (booking_id, amount, reference, status) VALUES (?, ?, ?, ?)')
        ->execute([$booking_id, $amount, $reference, $status]);
        
    if ($status === 'success') {
        $pdo->prepare('UPDATE bookings SET status = "paid" WHERE id = ?')
            ->execute([$booking_id]);
            
        $pdo->prepare('UPDATE rooms SET occupied = occupied + 1 WHERE id = ?')
            ->execute([$booking['room_id']]);
        
        $pdo->commit();
        
        // Trigger email notification for successful payment
        $body = "<h2>Payment Successful</h2><p>Your payment of GHS {$amount} for booking #{$booking_id} was successful.</p>";
        send_email($u['email'], 'Payment Confirmation - Hostel Cloud', $body);
        
        flash('success', 'Payment successful! Your booking is now confirmed.');
    } else {
        $pdo->commit();
        flash('error', 'Payment verification returned as failed.');
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', 'System error while saving payment: ' . $e->getMessage());
}

redirect('dashboard.php');
