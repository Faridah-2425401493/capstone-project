<?php
require_once __DIR__.'/../src/bootstrap.php';
if (current_user()) redirect('dashboard.php');

$token = $_GET['token'] ?? '';
if (!$token) {
    flash('error', 'Invalid or missing password reset token.');
    redirect('login.php');
}

$pdo = db();
// Check if token exists and is valid (e.g. not older than 1 hour)
$s = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR');
$s->execute([$token]);
$reset = $s->fetch();

if (!$reset) {
    flash('error', 'Password reset token is invalid or has expired.');
    redirect('forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 8) {
        flash('error', 'Password must be at least 8 characters.');
    } else {
        $pdo->prepare('UPDATE users SET password = ? WHERE email = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $reset['email']]);
        
        // Invalidate token
        $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$reset['email']]);
        
        flash('success', 'Your password has been successfully reset. Please log in.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | HostelCloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .brand-color { background-color: rgba(41, 42, 102, 1); color: white; border: none; }
        .brand-color:hover { background-color: rgba(30, 31, 80, 1); color: white; }
    </style>
</head>
<body class="d-flex align-items-center py-4 bg-light" style="min-height: 100vh;">
    <main class="form-signin w-100 m-auto" style="max-width: 400px;">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center brand-color mx-auto" style="width: 70px; height: 70px; font-size: 1.5rem;">
                        <i class="fas fa-key"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-3">Reset Password</h3>
                <p class="text-muted mb-4 small">Enter your new password below.</p>
                
                <?php if($m=flash('error')): ?><div class="alert alert-danger"><?=e($m)?></div><?php endif;?>
                
                <form method="post">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    
                    <div class="form-floating mb-4 text-start">
                        <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required minlength="8">
                        <label for="password">New Password</label>
                    </div>
                    
                    <button class="w-100 btn btn-lg brand-color mb-3" type="submit">Update Password</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
