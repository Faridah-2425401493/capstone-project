<?php
require_once __DIR__.'/../src/layout.php';
$u = require_role('system_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'] ?? '';
    
    // Cannot suspend yourself
    if ($user_id === $u['id']) {
        flash('error', 'You cannot suspend your own account.');
    } else {
        try {
            $is_suspended = ($action === 'suspend') ? 1 : 0;
            $stmt = db()->prepare('UPDATE users SET is_suspended = ? WHERE id = ?');
            $stmt->execute([$is_suspended, $user_id]);
            flash('success', 'User account ' . ($is_suspended ? 'suspended' : 'activated') . ' successfully.');
        } catch (Throwable $e) {
            flash('error', 'Failed to update user.');
        }
    }
    
    redirect('manage_users.php');
}

$search = trim($_GET['search'] ?? '');
$sql = 'SELECT id, name, email, phone, role, created_at, is_suspended, student_id_number, program_offering FROM users';
$params = [];

if ($search) {
    $sql .= ' WHERE name LIKE ? OR email LIKE ?';
    $params = ["%$search%", "%$search%"];
}

$sql .= ' ORDER BY created_at DESC';

$s = db()->prepare($sql);
$s->execute($params);
$users = $s->fetchAll();

page_start('Manage Users');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users-cog text-primary me-2"></i>Manage Users</h2>
    <a href="<?=url('dashboard.php')?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-white pb-0 border-bottom-0 pt-4">
        <form method="get" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search users by name or email..." value="<?=e($search)?>">
            <button class="btn btn-primary px-4"><i class="fas fa-search"></i></button>
            <?php if($search): ?>
                <a href="manage_users.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="card-body mt-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Name & Program</th>
                        <th>Email & Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr class="<?= $user['is_suspended'] ? 'table-secondary opacity-75' : '' ?>">
                        <td class="ps-4 fw-bold">
                            <?php if($user['id'] === $u['id']): ?>
                                <span class="text-primary"><?=e($user['name'])?> (You)</span>
                            <?php else: ?>
                                <?=e($user['name'])?>
                            <?php endif; ?>
                            <?php if(!empty($user['student_id_number'])): ?>
                            <div class="small fw-normal text-muted mt-1"><i class="fas fa-id-card me-1"></i> <?=e($user['student_id_number'])?></div>
                            <?php endif; ?>
                            <?php if(!empty($user['program_offering'])): ?>
                            <div class="small fw-normal text-muted"><i class="fas fa-graduation-cap me-1"></i> <?=e($user['program_offering'])?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><i class="fas fa-envelope text-muted me-1"></i> <?=e($user['email'])?></div>
                            <div class="small text-muted"><i class="fas fa-phone me-1"></i> <?=e($user['phone'])?></div>
                        </td>
                        <td><span class="badge bg-info text-dark"><?=ucwords(str_replace('_', ' ', e($user['role'])))?></span></td>
                        <td>
                            <?php if($user['is_suspended']): ?>
                                <span class="badge bg-danger">Suspended</span>
                            <?php else: ?>
                                <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if($user['id'] !== $u['id']): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="csrf" value="<?=csrf()?>">
                                <input type="hidden" name="user_id" value="<?=$user['id']?>">
                                <?php if($user['is_suspended']): ?>
                                    <button class="btn btn-sm btn-outline-success hover-lift" name="action" value="activate"><i class="fas fa-check-circle me-1"></i> Activate</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-danger hover-lift" name="action" value="suspend" onclick="return confirm('Are you sure you want to suspend this user? They will not be able to log in.');"><i class="fas fa-ban me-1"></i> Suspend</button>
                                <?php endif; ?>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(!$users): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No users found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.btn:hover.hover-lift { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php page_end(); ?>
