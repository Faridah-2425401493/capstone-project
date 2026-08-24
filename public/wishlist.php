<?php
require_once __DIR__.'/../src/layout.php';
$u = require_role('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $hostel_id = (int)$_POST['hostel_id'];
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $s = db()->prepare('INSERT IGNORE INTO wishlists (student_id, hostel_id) VALUES (?, ?)');
            $s->execute([$u['id'], $hostel_id]);
            flash('success', 'Hostel added to your wishlist!');
        } elseif ($action === 'remove') {
            $s = db()->prepare('DELETE FROM wishlists WHERE student_id=? AND hostel_id=?');
            $s->execute([$u['id'], $hostel_id]);
            flash('success', 'Hostel removed from your wishlist!');
        }
    } catch (Throwable $e) {
        flash('error', 'Something went wrong.');
    }
    
    // Check where they came from
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'hostel.php') !== false) {
        redirect('hostel.php?id=' . $hostel_id);
    }
    redirect('wishlist.php');
}

// GET REQUEST - Show the wishlist
$sql = 'SELECT h.* FROM hostels h JOIN wishlists w ON w.hostel_id = h.id WHERE w.student_id = ? ORDER BY w.created_at DESC';
$s = db()->prepare($sql);
$s->execute([$u['id']]);
$hostels = $s->fetchAll();

page_start('My Wishlist');
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="mb-0"><i class="fas fa-heart text-danger me-2"></i>My Wishlist</h2>
        <p class="text-muted mb-0">Hostels you have saved for later.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <?php foreach($hostels as $h):?>
    <div class="col-md-6 col-lg-4">
        <article class="card h-100 shadow-sm hover-lift border-0">
            <?php if($h['image_path']):?>
                <img class="card-img-top" src="<?=url($h['image_path'])?>" alt="<?=e($h['name'])?>" style="height: 220px; object-fit: cover;">
            <?php else: ?>
                <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 220px;">
                    <i class="fas fa-building fa-3x opacity-50"></i>
                </div>
            <?php endif;?>
            <div class="card-body d-flex flex-column">
                <h4 class="card-title fw-bold mb-2"><?=e($h['name'])?></h4>
                <p class="card-text text-muted mb-3"><i class="fas fa-map-marker-alt me-2 text-danger"></i><?=e($h['location'])?></p>
                <div class="mt-auto d-flex gap-2">
                    <a class="btn btn-outline-primary flex-grow-1" href="<?=url('hostel.php?id='.$h['id'])?>">View Hostel</a>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf" value="<?=csrf()?>">
                        <input type="hidden" name="hostel_id" value="<?=$h['id']?>">
                        <input type="hidden" name="action" value="remove">
                        <button class="btn btn-danger" type="submit" title="Remove"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </article>
    </div>
    <?php endforeach;?>
    
    <?php if(!$hostels):?>
    <div class="col-12">
        <div class="alert alert-info text-center py-5 rounded-4 border-0 shadow-sm bg-white text-dark">
            <i class="far fa-heart fa-3x mb-3 text-muted opacity-50"></i>
            <h4>Your wishlist is empty.</h4>
            <p class="text-muted mb-0">Explore hostels and save them here for later.</p>
            <a href="hostels.php" class="btn btn-primary mt-3">Explore Hostels</a>
        </div>
    </div>
    <?php endif;?>
</div>

<style>
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.card:hover.hover-lift { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php page_end();?>
