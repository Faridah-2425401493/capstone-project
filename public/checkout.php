<?php 
require_once __DIR__.'/../src/layout.php';
$u = require_login();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('dashboard.php');

$sql = 'SELECT b.*, r.price, r.room_number, h.name hostel_name 
        FROM bookings b 
        JOIN rooms r ON r.id = b.room_id 
        JOIN hostels h ON h.id = r.hostel_id 
        WHERE b.id = ? AND b.student_id = ?';
$s = db()->prepare($sql);
$s->execute([$id, $u['id']]);
$booking = $s->fetch();

if (!$booking) {
    flash('error', 'Booking not found.');
    redirect('dashboard.php');
}
if ($booking['status'] === 'paid') {
    flash('success', 'This booking is already paid.');
    redirect('dashboard.php');
}
if ($booking['status'] !== 'approved') {
    flash('error', 'Booking is not approved for payment yet.');
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    verify_csrf();
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $stmt = db()->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1');
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    
    if ($coupon) {
        $_SESSION['applied_coupon_'.$id] = [
            'id' => $coupon['id'],
            'code' => $coupon['code'],
            'discount' => (float)$coupon['discount_amount']
        ];
        flash('success', 'Coupon applied successfully!');
    } else {
        unset($_SESSION['applied_coupon_'.$id]);
        flash('error', 'Invalid or inactive coupon code.');
    }
    redirect("checkout.php?id=$id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon'])) {
    verify_csrf();
    unset($_SESSION['applied_coupon_'.$id]);
    flash('success', 'Coupon removed.');
    redirect("checkout.php?id=$id");
}

$discount = 0;
$coupon_code = null;
if (isset($_SESSION['applied_coupon_'.$id])) {
    $discount = $_SESSION['applied_coupon_'.$id]['discount'];
    $coupon_code = $_SESSION['applied_coupon_'.$id]['code'];
}

$amount = max(0, $booking['price'] - $discount);
$amount_in_pesewas = $amount * 100; // Paystack works in the smallest currency unit
$email = $u['email'];
$reference = 'BK_' . $booking['id'] . '_' . time();

page_start('Checkout'); 
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 text-center">
                <i class="fas fa-lock text-success mb-2" style="font-size: 2rem;"></i>
                <h3 class="card-title fw-bold">Secure Checkout</h3>
            </div>
            <div class="card-body p-4">
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Hostel</span>
                        <span class="fw-bold"><?=e($booking['hostel_name'])?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Room Number</span>
                        <span class="fw-bold">#<?=e($booking['room_number'])?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Student Name</span>
                        <span class="fw-bold"><?=e($u['name'])?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Room Price</span>
                        <span class="fw-bold">GHS <?=number_format((float)$booking['price'], 2)?></span>
                    </li>
                    <?php if($discount > 0): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 text-success">
                        <span><i class="fas fa-tag me-1"></i> Coupon (<?=e($coupon_code)?>)</span>
                        <span class="fw-bold">- GHS <?=number_format($discount, 2)?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom-0 pb-0">
                        <span class="text-muted">Total Amount</span>
                        <h4 class="fw-bold text-success mb-0">GHS <?=number_format($amount, 2)?></h4>
                    </li>
                </ul>
                
                <?php if($discount == 0): ?>
                <form method="post" class="mb-4 d-flex">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    <input type="text" name="coupon_code" class="form-control me-2" placeholder="Coupon Code">
                    <button type="submit" name="apply_coupon" class="btn btn-outline-secondary text-nowrap">Apply</button>
                </form>
                <?php else: ?>
                <form method="post" class="mb-4">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    <button type="submit" name="remove_coupon" class="btn btn-link text-danger p-0 text-decoration-none"><i class="fas fa-times me-1"></i>Remove Coupon</button>
                </form>
                <?php endif; ?>

                <button type="button" class="btn btn-primary w-100 fw-bold py-3 hover-lift" onclick="payWithPaystack()">
                    <i class="fas fa-credit-card me-2"></i> Pay GHS <?=number_format($amount, 2)?>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
function payWithPaystack() {
    let handler = PaystackPop.setup({
        key: '<?=env('PAYSTACK_PUBLIC_KEY', 'pk_test_demo')?>', // Replace with your public key
        email: '<?=e($email)?>',
        amount: <?=$amount_in_pesewas?>,
        currency: 'GHS',
        ref: '<?=$reference?>',
        onClose: function(){
            alert('Window closed.');
        },
        callback: function(response){
            let ref = response.reference;
            window.location.href = 'verify_payment.php?reference=' + ref + '&booking_id=<?=$booking['id']?>';
        }
    });
    handler.openIframe();
}
</script>

<?php page_end(); ?>
