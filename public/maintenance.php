<?php
require_once __DIR__.'/../src/layout.php';
$u = require_role('student');

$pdo = db();

// Find the student's currently checked-in room
$s = $pdo->prepare('
    SELECT b.room_id, r.hostel_id, r.room_number, h.name as hostel_name 
    FROM bookings b 
    JOIN rooms r ON r.id = b.room_id 
    JOIN hostels h ON h.id = r.hostel_id 
    WHERE b.student_id = ? AND b.status = "checked_in"
');
$s->execute([$u['id']]);
$current_booking = $s->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $issue = trim($_POST['issue'] ?? '');
    
    if (!$current_booking) {
        flash('error', 'You must be checked into a room to submit a maintenance request.');
    } elseif (strlen($issue) < 10) {
        flash('error', 'Please provide more details about the issue (at least 10 characters).');
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO maintenance_tickets (hostel_id, room_id, student_id, issue, status) VALUES (?, ?, ?, ?, "open")');
            $stmt->execute([$current_booking['hostel_id'], $current_booking['room_id'], $u['id'], $issue]);
            flash('success', 'Maintenance ticket submitted successfully. The admin has been notified.');
            redirect('maintenance.php');
        } catch (Throwable $e) {
            flash('error', 'Failed to submit ticket: ' . $e->getMessage());
        }
    }
}

// Fetch past tickets
$s = $pdo->prepare('
    SELECT t.*, r.room_number, h.name as hostel_name 
    FROM maintenance_tickets t 
    JOIN rooms r ON r.id = t.room_id 
    JOIN hostels h ON h.id = t.hostel_id 
    WHERE t.student_id = ? 
    ORDER BY t.created_at DESC
');
$s->execute([$u['id']]);
$tickets = $s->fetchAll();

page_start('Maintenance Requests');
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="mb-0"><i class="fas fa-tools text-primary me-2"></i>Maintenance</h2>
        <p class="text-muted mb-0">Report issues with your room.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white pt-4 pb-2 border-bottom-0">
                <h5 class="fw-bold mb-0">Report an Issue</h5>
            </div>
            <div class="card-body">
                <?php if($current_booking): ?>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?=csrf()?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Current Room</label>
                            <input type="text" class="form-control bg-light" value="<?=e($current_booking['hostel_name'])?> - Room <?=e($current_booking['room_number'])?>" readonly>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Describe the Issue</label>
                            <textarea class="form-control" name="issue" rows="5" placeholder="E.g., The AC is leaking water, or the fan is making a loud noise." required minlength="10"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Submit Ticket</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2 text-warning"></i>
                        <p class="mb-0">You must be checked into a room to report maintenance issues.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white pt-4 pb-2 border-bottom-0">
                <h5 class="fw-bold mb-0">My Tickets</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Room</th>
                                <th>Issue</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tickets as $t): ?>
                            <tr>
                                <td><span class="badge bg-secondary">Room <?=e($t['room_number'])?></span></td>
                                <td><?=nl2br(e($t['issue']))?></td>
                                <td>
                                    <?php if($t['status'] === 'open'): ?>
                                        <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Open</span>
                                    <?php elseif($t['status'] === 'in_progress'): ?>
                                        <span class="badge bg-info text-dark"><i class="fas fa-spinner fa-spin me-1"></i> In Progress</span>
                                    <?php elseif($t['status'] === 'resolved'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Resolved</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?=date('M d, Y h:i A', strtotime($t['created_at']))?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(!$tickets): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">You have not submitted any maintenance tickets.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php page_end(); ?>
