<?php
require_once __DIR__.'/../src/layout.php';
$u = require_role(['hostel_admin', 'system_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ticket_id = (int)$_POST['ticket_id'];
    $action = $_POST['action'] ?? '';
    
    if (!in_array($action, ['in_progress', 'resolved'])) {
        exit('Invalid action');
    }
    
    try {
        $pdo = db();
        // Check permission
        if ($u['role'] === 'hostel_admin') {
            $a = $pdo->prepare('SELECT h.id FROM maintenance_tickets t JOIN hostels h ON h.id = t.hostel_id WHERE t.id = ? AND h.admin_id = ?');
            $a->execute([$ticket_id, $u['id']]);
            if (!$a->fetch()) throw new RuntimeException('Forbidden');
        }
        
        $pdo->prepare('UPDATE maintenance_tickets SET status = ? WHERE id = ?')->execute([$action, $ticket_id]);
        flash('success', 'Ticket marked as ' . str_replace('_', ' ', $action) . '.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect('manage_maintenance.php');
}

$sql = 'SELECT t.*, u.name as student_name, r.room_number, h.name as hostel_name 
        FROM maintenance_tickets t 
        JOIN users u ON u.id = t.student_id 
        JOIN rooms r ON r.id = t.room_id 
        JOIN hostels h ON h.id = t.hostel_id';
$p = [];
if ($u['role'] === 'hostel_admin') {
    $sql .= ' WHERE h.admin_id = ?';
    $p[] = $u['id'];
}
$sql .= ' ORDER BY t.created_at DESC';

$s = db()->prepare($sql);
$s->execute($p);
$tickets = $s->fetchAll();

page_start('Manage Maintenance');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tools text-primary me-2"></i>Manage Maintenance</h2>
    <a href="<?=url('dashboard.php')?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white pb-0 border-bottom-0 pt-4">
        <h4 class="card-title mb-0">Maintenance Tickets</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Hostel & Room</th>
                        <th style="width: 35%;">Issue</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tickets as $t): ?>
                    <tr>
                        <td class="fw-bold"><?=e($t['student_name'])?></td>
                        <td><?=e($t['hostel_name'])?><br><span class="badge bg-secondary">Room <?=e($t['room_number'])?></span></td>
                        <td class="text-muted small"><?=nl2br(e($t['issue']))?></td>
                        <td class="text-muted small"><?=date('M d, Y', strtotime($t['created_at']))?></td>
                        <td>
                            <?php if($t['status'] === 'open'): ?>
                                <span class="badge bg-warning text-dark">Open</span>
                            <?php elseif($t['status'] === 'in_progress'): ?>
                                <span class="badge bg-info text-dark">In Progress</span>
                            <?php elseif($t['status'] === 'resolved'): ?>
                                <span class="badge bg-success">Resolved</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($t['status'] !== 'resolved'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf" value="<?=csrf()?>">
                                <input type="hidden" name="ticket_id" value="<?=$t['id']?>">
                                
                                <?php if($t['status'] === 'open'): ?>
                                <button class="btn btn-sm btn-info text-dark me-1" name="action" value="in_progress" title="Mark In Progress"><i class="fas fa-spinner"></i> Start</button>
                                <?php endif; ?>
                                
                                <button class="btn btn-sm btn-success" name="action" value="resolved" title="Mark Resolved"><i class="fas fa-check"></i> Resolve</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small"><i class="fas fa-check-circle me-1 text-success"></i>Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(!$tickets): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No maintenance tickets found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php page_end(); ?>
