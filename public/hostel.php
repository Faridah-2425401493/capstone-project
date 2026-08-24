<?php require_once __DIR__.'/../src/layout.php';$id=(int)($_GET['id']??0);$s=db()->prepare('SELECT * FROM hostels WHERE id=?');$s->execute([$id]);$h=$s->fetch();if(!$h){http_response_code(404);exit('Hostel not found');}
$gender=$_GET['gender']??'';$sql='SELECT * FROM rooms WHERE hostel_id=? AND status="available" AND occupied<capacity';$p=[$id];if(in_array($gender,['male','female'],true)){$sql.=' AND (gender=? OR gender="any")';$p[]=$gender;}$s=db()->prepare($sql);$s->execute($p);$rooms=$s->fetchAll();
$is_wishlisted=false;if(current_user()&&current_user()['role']==='student'){$ws=db()->prepare('SELECT 1 FROM wishlists WHERE student_id=? AND hostel_id=?');$ws->execute([current_user()['id'],$id]);$is_wishlisted=(bool)$ws->fetch();}

$rev_stmt=db()->prepare('SELECT r.*, u.name as student_name FROM reviews r JOIN users u ON u.id=r.student_id WHERE r.hostel_id=? ORDER BY r.created_at DESC');
$rev_stmt->execute([$id]);$reviews=$rev_stmt->fetchAll();
$avg_rating=0;if(count($reviews)>0){$sum=array_reduce($reviews,fn($c,$i)=>$c+$i['rating'],0);$avg_rating=round($sum/count($reviews),1);}
$can_review=false;if(current_user()&&current_user()['role']==='student'){
    $chk=db()->prepare('SELECT 1 FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.student_id=? AND r.hostel_id=? AND b.status="checked_out" AND NOT EXISTS(SELECT 1 FROM reviews rw WHERE rw.student_id=b.student_id AND rw.hostel_id=r.hostel_id)');
    $chk->execute([current_user()['id'],$id]);$can_review=(bool)$chk->fetch();
}

page_start($h['name']);?>

<div class="row mb-5">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow border-0 overflow-hidden rounded-4">
            <?php if($h['image_path']):?>
                <img class="card-img-top" src="<?=url($h['image_path'])?>" alt="<?=e($h['name'])?>" style="max-height: 400px; object-fit: cover;">
            <?php else: ?>
                <div class="card-img-top bg-secondary text-white d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
                    <i class="fas fa-building fa-4x mb-3 opacity-50"></i>
                    <h3 class="opacity-50">No Image Available</h3>
                </div>
            <?php endif;?>
            <div class="card-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="card-title fw-bold mb-0"><?=e($h['name'])?></h1>
                    <div class="d-flex gap-2">
                        <?php if(current_user()&&current_user()['role']==='student'):?>
                        <form method="post" action="wishlist.php">
                            <input type="hidden" name="csrf" value="<?=csrf()?>">
                            <input type="hidden" name="hostel_id" value="<?=$id?>">
                            <?php if($is_wishlisted): ?>
                                <input type="hidden" name="action" value="remove">
                                <button class="btn btn-outline-danger"><i class="fas fa-heart"></i> Saved</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="add">
                                <button class="btn btn-outline-secondary"><i class="far fa-heart"></i> Save</button>
                            <?php endif; ?>
                        </form>
                        <?php endif;?>
                        <span class="badge bg-primary fs-6 p-2 d-flex align-items-center"><i class="fas fa-layer-group me-1"></i> <?=e($h['total_floors']??'1')?> Floors</span>
                    </div>
                </div>
                <h5 class="text-muted mb-4">
                    <i class="fas fa-map-marker-alt text-danger me-2"></i><?=e($h['location'])?>
                    <?php if(count($reviews)>0): ?>
                    <span class="ms-3 text-warning"><i class="fas fa-star"></i> <?=number_format((float)$avg_rating, 1)?> (<?=count($reviews)?> reviews)</span>
                    <?php endif; ?>
                </h5>
                
                <hr class="my-4">
                
                <h5 class="fw-bold">Description</h5>
                <p class="card-text text-secondary lh-lg"><?=nl2br(e($h['description']))?></p>
            </div>
        </div>
    </div>
</div>

<div class="row align-items-end mb-4">
    <div class="col-md-6">
        <h3 class="fw-bold mb-0">Available Rooms</h3>
        <p class="text-muted mb-0">Rooms with available spaces.</p>
    </div>
    <div class="col-md-6 mt-3 mt-md-0">
        <div class="card shadow-sm border-0 bg-light">
            <div class="card-body p-3">
                <form class="row g-2 align-items-center">
                    <input type="hidden" name="id" value="<?=$id?>">
                    <div class="col-auto flex-grow-1">
                        <select class="form-select" name="gender">
                            <option value="">All rooms</option>
                            <option value="male" <?=$gender==='male'?'selected':''?>>Male Allowed</option>
                            <option value="female" <?=$gender==='female'?'selected':''?>>Female Allowed</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <?php foreach($rooms as $r):?>
    <div class="col-md-6 col-lg-4">
        <article class="card h-100 shadow-sm border-0 hover-lift">
            <?php if($r['image_path']):?>
                <img class="card-img-top" src="<?=url($r['image_path'])?>" style="height: 200px; object-fit: cover;">
            <?php else: ?>
                <div class="card-img-top bg-light text-secondary d-flex justify-content-center align-items-center" style="height: 200px;">
                    <i class="fas fa-bed fa-3x opacity-25"></i>
                </div>
            <?php endif;?>
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h3 class="card-title fw-bold mb-0">Room <?=e($r['room_number'])?></h3>
                    <span class="badge bg-success fs-6">GHS <?=e($r['price'])?></span>
                </div>
                
                <ul class="list-unstyled text-muted small mb-4 mt-3">
                    <li class="mb-2"><i class="fas fa-venus-mars me-2"></i> Gender: <span class="fw-bold text-dark text-capitalize"><?=e($r['gender'])?></span></li>
                    <li class="mb-2"><i class="fas fa-users me-2"></i> Availability: <span class="fw-bold text-dark"><?=$r['capacity']-$r['occupied']?> space(s)</span></li>
                    <li class="mb-2"><i class="fas fa-layer-group me-2"></i> Floor: <span class="fw-bold text-dark"><?=e($r['floor_number']??'1')?></span></li>
                    <?php if(!empty($r['equipment'])): ?>
                    <li class="mb-0"><i class="fas fa-tv me-2"></i> Equipment: <span class="text-dark"><?=e($r['equipment'])?></span></li>
                    <?php endif; ?>
                </ul>
                
                <div class="mt-auto">
                    <?php if(current_user()&&current_user()['role']==='student'):?>
                    <form method="post" action="<?=url('book.php')?>">
                        <input type="hidden" name="csrf" value="<?=csrf()?>">
                        <input type="hidden" name="room_id" value="<?=$r['id']?>">
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold">Roommate Preference (Optional)</label>
                            <input type="text" class="form-control form-control-sm" name="roommate_request" placeholder="Name or Email of friend">
                        </div>
                        <button class="btn btn-primary w-100 fw-bold">Request Booking</button>
                    </form>
                    <?php elseif(!current_user()):?>
                    <a class="btn btn-outline-primary w-100 fw-bold" href="<?=url('login.php')?>">Login to Book</a>
                    <?php else: ?>
                    <button class="btn btn-secondary w-100" disabled>Students Only</button>
                    <?php endif;?>
                </div>
            </div>
        </article>
    </div>
    <?php endforeach;?>
    
    <?php if(!$rooms):?>
    <div class="col-12">
        <div class="alert alert-warning text-center py-4 border-0 shadow-sm">
            <i class="fas fa-info-circle fa-2x mb-2 text-warning"></i>
            <h5 class="mb-0">No rooms available matching your criteria.</h5>
        </div>
    </div>
    <?php endif;?>
</div>

<div class="row mb-5">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold mb-0">Reviews (<?=count($reviews)?>)</h3>
            <?php if($can_review): ?>
            <a href="<?=url('review.php?hostel_id='.$id)?>" class="btn btn-outline-primary"><i class="fas fa-star me-1"></i> Leave a Review</a>
            <?php endif; ?>
        </div>
        
        <?php if($reviews): ?>
            <div class="row g-4">
                <?php foreach($reviews as $rev): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="fw-bold mb-0"><?=e($rev['student_name'])?></h6>
                                <span class="text-warning small">
                                    <?php for($i=1;$i<=5;$i++): ?>
                                        <i class="<?= $i <= $rev['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </span>
                            </div>
                            <p class="text-muted small mb-2"><?=date('M d, Y', strtotime($rev['created_at']))?></p>
                            <?php if($rev['comment']): ?>
                                <p class="mb-0 text-secondary"><?=nl2br(e($rev['comment']))?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body text-center py-5">
                    <i class="far fa-comments fa-3x text-muted opacity-50 mb-3"></i>
                    <h5 class="text-muted">No reviews yet.</h5>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.card:hover.hover-lift { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php page_end();?>