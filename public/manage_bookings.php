<?php require_once __DIR__.'/../src/layout.php';$u=require_role(['hostel_admin','system_admin']);if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$id=(int)$_POST['booking_id'];$action=$_POST['action'];if(!in_array($action,['approved','rejected','checked_in','checked_out'],true))exit('Invalid action');
try{$pdo=db();$pdo->beginTransaction();$s=$pdo->prepare('SELECT b.*,r.id room_id,r.capacity,r.occupied,u.email student_email,u.name student_name,r.room_number,h.name hostel_name FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN users u ON u.id=b.student_id JOIN hostels h ON h.id=r.hostel_id WHERE b.id=? FOR UPDATE');$s->execute([$id]);$b=$s->fetch();if(!$b)throw new RuntimeException('Booking not found.');
if($u['role']==='hostel_admin'){$a=$pdo->prepare('SELECT h.id FROM hostels h JOIN rooms r ON r.hostel_id=h.id WHERE r.id=? AND h.admin_id=?');$a->execute([$b['room_id'],$u['id']]);if(!$a->fetch())throw new RuntimeException('Forbidden');}

if($action==='approved'&&$b['status']!=='pending')throw new RuntimeException('Can only approve pending bookings.');
if($action==='rejected'&&!in_array($b['status'], ['pending','approved']))throw new RuntimeException('Can only reject pending or unpaid approved bookings.');
if($action==='checked_in'&&$b['status']!=='paid')throw new RuntimeException('Student must pay before checking in.');
if($action==='checked_out'&&$b['status']!=='checked_in')throw new RuntimeException('Can only checkout checked-in students.');

if($action==='approved'){
    // Assignment happens after payment
} elseif($action==='checked_out'){
    $pdo->prepare('UPDATE rooms SET occupied=occupied-1 WHERE id=?')->execute([$b['room_id']]);
}

$pdo->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$action,$id]);$pdo->commit();
if($action==='approved') {
    send_email($b['student_email'], 'Booking Approved!', "<p>Hi {$b['student_name']},</p><p>Your booking for Room {$b['room_number']} at {$b['hostel_name']} has been approved. Please log in to complete your payment.</p>");
} elseif($action==='rejected') {
    send_email($b['student_email'], 'Booking Rejected', "<p>Hi {$b['student_name']},</p><p>We are sorry, but your booking request for Room {$b['room_number']} at {$b['hostel_name']} has been rejected.</p>");
}
flash('success','Booking marked as '.str_replace('_',' ',$action).'.');}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();flash('error',$e->getMessage());}redirect('manage_bookings.php');}
$sql='SELECT b.*,u.name student,h.name hostel,r.room_number FROM bookings b JOIN users u ON u.id=b.student_id JOIN rooms r ON r.id=b.room_id JOIN hostels h ON h.id=r.hostel_id'.($u['role']==='hostel_admin'?' WHERE h.admin_id=?':'').' ORDER BY b.created_at DESC';$s=db()->prepare($sql);$s->execute($u['role']==='hostel_admin'?[$u['id']]:[]);$rows=$s->fetchAll();page_start('Manage Bookings');?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-clipboard-list text-warning me-2"></i>Manage Bookings</h2>
    <a href="<?=url('dashboard.php')?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white pb-0 border-bottom-0 pt-4">
        <h4 class="card-title mb-0">Booking Requests</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Hostel</th>
                        <th>Room</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $b):?>
                    <tr>
                        <td class="fw-bold"><i class="fas fa-user-graduate me-2 text-muted"></i><?=e($b['student'])?></td>
                        <td><?=e($b['hostel'])?></td>
                        <td class="fw-bold text-dark">#<?=e($b['room_number'])?></td>
                        <td class="text-muted small"><?=e(date('M d, Y', strtotime($b['created_at'])))?></td>
                        <td>
                            <?php if($b['status'] === 'approved'): ?>
                                <span class="badge bg-success">Approved (Unpaid)</span>
                            <?php elseif($b['status'] === 'paid'): ?>
                                <span class="badge bg-primary">Paid (Ready for Check-in)</span>
                            <?php elseif($b['status'] === 'checked_in'): ?>
                                <span class="badge bg-info text-dark">Checked In</span>
                            <?php elseif($b['status'] === 'checked_out'): ?>
                                <span class="badge bg-secondary">Checked Out</span>
                            <?php elseif($b['status'] === 'rejected'): ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                            <?php if($b['roommate_request']): ?>
                                <div class="small text-muted mt-1" title="Roommate Preference">
                                    <i class="fas fa-user-friends"></i> <?=e($b['roommate_request'])?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf" value="<?=csrf()?>">
                                <input type="hidden" name="booking_id" value="<?=$b['id']?>">
                                <?php if($b['status']==='pending'):?>
                                <button class="btn btn-sm btn-success me-1" name="action" value="approved" title="Approve"><i class="fas fa-check"></i> Approve</button>
                                <button class="btn btn-sm btn-danger" name="action" value="rejected" title="Reject"><i class="fas fa-times"></i></button>
                                <?php elseif($b['status']==='paid'):?>
                                <button class="btn btn-sm btn-primary" name="action" value="checked_in"><i class="fas fa-sign-in-alt me-1"></i> Check In</button>
                                <?php elseif($b['status']==='checked_in'):?>
                                <button class="btn btn-sm btn-secondary" name="action" value="checked_out"><i class="fas fa-sign-out-alt me-1"></i> Check Out</button>
                                <?php else: ?>
                                <span class="text-muted small"><i class="fas fa-lock me-1"></i>Closed</span>
                                <?php endif;?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach;?>
                    <?php if(!$rows): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No booking requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php page_end();?>