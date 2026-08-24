<?php
require_once __DIR__.'/../src/layout.php';
$u = require_role('student');
$hostel_id = (int)($_GET['hostel_id'] ?? 0);

$pdo = db();
$s = $pdo->prepare('SELECT name FROM hostels WHERE id = ?');
$s->execute([$hostel_id]);
$hostel = $s->fetch();

if (!$hostel) {
    flash('error', 'Hostel not found.');
    redirect('dashboard.php');
}

// Ensure student has a checked_out booking for this hostel
$s = $pdo->prepare('
    SELECT b.id 
    FROM bookings b 
    JOIN rooms r ON r.id = b.room_id 
    WHERE b.student_id = ? AND r.hostel_id = ? AND b.status = "checked_out"
    LIMIT 1
');
$s->execute([$u['id'], $hostel_id]);
if (!$s->fetch()) {
    flash('error', 'You can only review hostels you have previously checked out of.');
    redirect('hostel.php?id='.$hostel_id);
}

// Ensure student hasn't already reviewed this hostel
$s = $pdo->prepare('SELECT id FROM reviews WHERE student_id = ? AND hostel_id = ?');
$s->execute([$u['id'], $hostel_id]);
if ($s->fetch()) {
    flash('error', 'You have already reviewed this hostel.');
    redirect('hostel.php?id='.$hostel_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        flash('error', 'Please select a valid rating between 1 and 5 stars.');
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO reviews (hostel_id, student_id, rating, comment) VALUES (?, ?, ?, ?)');
            $stmt->execute([$hostel_id, $u['id'], $rating, $comment]);
            flash('success', 'Thank you! Your review has been submitted.');
            redirect('hostel.php?id='.$hostel_id);
        } catch (Throwable $e) {
            flash('error', 'Failed to submit review.');
        }
    }
}

page_start('Review ' . $hostel['name']);
?>

<div class="row justify-content-center mb-5">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 mt-4 rounded-4">
            <div class="card-header bg-white pt-4 pb-0 border-bottom-0 text-center">
                <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                    <i class="fas fa-star"></i>
                </div>
                <h3 class="card-title fw-bold">Rate Your Stay</h3>
                <p class="text-muted">How was your experience at <strong><?=e($hostel['name'])?></strong>?</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    
                    <div class="mb-4 text-center">
                        <label class="form-label fw-bold d-block mb-3">Rating</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Review Comment (Optional)</label>
                        <textarea class="form-control bg-light" name="comment" rows="4" placeholder="Share details of your own experience at this hostel..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold hover-lift">Submit Review</button>
                    <a href="hostel.php?id=<?=$hostel_id?>" class="btn btn-link w-100 mt-2 text-muted text-decoration-none">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Star Rating CSS using flex-direction row-reverse trick */
.star-rating {
  display: flex;
  flex-direction: row-reverse;
  justify-content: center;
}
.star-rating input {
  display: none;
}
.star-rating label {
  color: #ddd;
  font-size: 2.5rem;
  padding: 0 5px;
  cursor: pointer;
  transition: color 0.2s;
}
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
  color: #ffc107;
}
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.btn:hover.hover-lift { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php page_end(); ?>
