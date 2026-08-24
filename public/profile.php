<?php 
require_once __DIR__.'/../src/layout.php';
$u = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $student_id_number = trim($_POST['student_id_number'] ?? '');
    $program_offering = trim($_POST['program_offering'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!$name) {
        flash('error', 'Name is required.');
    } else {
        try {
            $pdo = db();
            if ($password) {
                if (strlen($password) < 8) {
                    throw new RuntimeException('Password must be at least 8 characters.');
                }
                $s = $pdo->prepare('UPDATE users SET name = ?, phone = ?, student_id_number = ?, program_offering = ?, password = ? WHERE id = ?');
                $s->execute([$name, $phone, $student_id_number, $program_offering, password_hash($password, PASSWORD_DEFAULT), $u['id']]);
            } else {
                $s = $pdo->prepare('UPDATE users SET name = ?, phone = ?, student_id_number = ?, program_offering = ? WHERE id = ?');
                $s->execute([$name, $phone, $student_id_number, $program_offering, $u['id']]);
            }
            // Update session data
            $_SESSION['user_id'] = $u['id']; 
            flash('success', 'Profile updated successfully.');
            redirect('profile.php');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
    }
}

page_start('My Profile'); 
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white pt-4 pb-0 border-bottom-0 text-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3 class="card-title fw-bold">My Profile</h3>
                <p class="text-muted"><?=e($u['email'])?> &middot; <span class="badge bg-secondary"><?=e(ucwords(str_replace('_', ' ', $u['role'])))?></span></p>
            </div>
            <div class="card-body p-4">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?=e($u['name'])?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" class="form-control" name="phone" value="<?=e($u['phone'] ?? '')?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Student ID Number</label>
                        <input type="text" class="form-control" name="student_id_number" value="<?=e($u['student_id_number'] ?? '')?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Program Offering</label>
                        <input type="text" class="form-control" name="program_offering" value="<?=e($u['program_offering'] ?? '')?>">
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5 class="fw-bold mb-3">Change Password</h5>
                    <p class="text-muted small">Leave blank if you do not want to change your password.</p>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">New Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Minimum 8 characters">
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <a href="dashboard.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 hover-lift">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.btn:hover.hover-lift { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php page_end(); ?>
