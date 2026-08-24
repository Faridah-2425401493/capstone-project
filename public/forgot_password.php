<?php
require_once __DIR__.'/../src/bootstrap.php';
if (current_user()) redirect('dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
    } else {
        $pdo = db();
        $s = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
        $s->execute([$email]);
        $user = $s->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_resets (email, token) VALUES (?, ?)')
                ->execute([$email, $token]);
                
            $reset_link = url("reset_password.php?token=$token");
            $body = "<p>Hi {$user['name']},</p><p>You requested a password reset. Click the link below to reset your password:</p><p><a href=\"$reset_link\">$reset_link</a></p><p>If you did not request this, please ignore this email.</p>";
            
            send_email($email, 'Password Reset Request', $body);
        }
        
        // Always show success to prevent email enumeration
        flash('success', 'If an account exists with that email, a password reset link has been sent.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | HostelCloud</title>
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
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-3">Forgot Password</h3>
                <p class="text-muted mb-4 small">Enter your email address and we'll send you a link to reset your password.</p>
                
                <?php if($m=flash('success')): ?><div class="alert alert-success"><?=e($m)?></div><?php endif;?>
                <?php if($m=flash('error')): ?><div class="alert alert-danger"><?=e($m)?></div><?php endif;?>
                
                <form method="post">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    
                    <div class="form-floating mb-4 text-start">
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                        <label for="email">Email address</label>
                    </div>
                    
                    <button class="w-100 btn btn-lg brand-color mb-3" type="submit">Send Reset Link</button>
                    <a href="<?=url('login.php')?>" class="text-decoration-none text-muted small"><i class="fas fa-arrow-left me-1"></i> Back to login</a>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
