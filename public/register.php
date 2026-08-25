<?php require_once __DIR__.'/../src/bootstrap.php'; if(current_user()) redirect('dashboard.php');
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$phone=trim($_POST['phone']??'');$student_id_number=trim($_POST['student_id_number']??'');$program_offering=trim($_POST['program_offering']??'');$password=$_POST['password']??'';
if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<8){flash('error','Enter valid details; password must be at least 8 characters.');}
else{try{$s=db()->prepare('INSERT INTO users(name,email,phone,student_id_number,program_offering,password) VALUES(?,?,?,?,?,?)');$s->execute([$name,$email,$phone,$student_id_number,$program_offering,password_hash($password,PASSWORD_DEFAULT)]);
send_email($email, 'Welcome to Hostel Cloud', "<h2>Welcome {$name}!</h2><p>Thank you for creating an account. You can now browse hostels and book rooms.</p>");
flash('success','Registration successful. Please log in.');redirect('login.php');}catch(PDOException $e){flash('error','Email is already registered.');}}}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | HostelCloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .form-width { width: 80%; }
        @media (max-width: 576px) { .form-width { width: 100% !important; } }
        .bg-side {
            background-image: url('<?=url("assets/images/register-bg.jpg")?>');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .bg-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .brand-color { background-color: rgba(41, 42, 102, 1); color: white; border: none; }
        .brand-color:hover { background-color: rgba(30, 31, 80, 1); color: white; }
        .text-brand { color: rgba(41, 42, 102, 1); }
    </style>
</head>
<body>
    <section style="width: 100vw; height: 100vh; position: relative;">
        <!-- Close Button -->
        <div class="p-3" style="position: absolute; top: 0; right: 0; z-index: 10;">
            <a class="fw-lighter fs-4 text-dark" href="<?=url()?>"><i class="fas fa-times"></i></a>
        </div>
        
        <div class="row m-0" style="width: 100%; height: 100%;">
            <!-- Left Side Form -->
            <div class="col p-0 d-flex flex-column align-items-center justify-content-between" style="height: 100vh; overflow-y: auto;">
                <div class="d-flex flex-column align-items-center justify-content-center w-100 py-5">
                    
                    <div class="text-center mb-4 mt-md-4">
                        <div class="mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center brand-color mx-auto" style="width: 80px; height: 80px; font-size: 2rem;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                        <h4 class="fw-light" style="letter-spacing: 5px; color: black;">HOSTELCLOUD</h4>
                        <h6 class="fw-light text-dark mt-2" style="letter-spacing: 1px;">Create your new account.</h6>
                    </div>
                    
                    <div class="px-3 px-md-5 mt-2 form-width">
                        <?php if($m=flash('success')): ?><div class="alert alert-success"><?=e($m)?></div><?php endif;?>
                        <?php if($m=flash('error')): ?><div class="alert alert-danger"><?=e($m)?></div><?php endif;?>
                        
                        <form method="post" style="font-size: 15px;">
                            <input type="hidden" name="csrf" value="<?=csrf()?>">
                            
                            <div class="mb-3">
                                <label class="form-label text-dark" for="name">Full Name</label>
                                <input class="form-control py-2" id="name" type="text" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-dark" for="email">Email Address</label>
                                <input class="form-control py-2" id="email" type="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-dark" for="student_id_number">Student ID Number (Optional)</label>
                                <input class="form-control py-2" id="student_id_number" type="text" name="student_id_number">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-dark" for="program_offering">Program Offering (Optional)</label>
                                <input class="form-control py-2" id="program_offering" type="text" name="program_offering">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-dark" for="phone">Phone Number (Optional)</label>
                                <input class="form-control py-2" id="phone" type="text" name="phone">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-dark" for="password">Password (Minimum 8 characters)</label>
                                <div class="input-group">
                                    <input class="form-control py-2" id="password" type="password" name="password" required>
                                    <span class="input-group-text bg-white" onclick="togglePassword()" style="cursor: pointer;">
                                        <i id="eye" class="far fa-eye-slash"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-4 mb-3">
                                <button type="submit" class="btn brand-color px-4 py-2">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="d-flex flex-column align-items-center justify-content-center w-100 pb-4 mt-auto">
                    <h6 class="fw-light text-dark mb-3">Already have an account? <a href="<?=url('login.php')?>" class="fw-bold text-brand text-decoration-none">Log in here.</a></h6>
                    <small class="text-dark text-center px-4" style="font-size: 0.75rem; letter-spacing: 1px;">
                        By creating an account, you agree to HostelCloud's <a href="#" class="text-dark">Terms of Service</a> and <a href="#" class="text-dark">Privacy Policy</a>.
                    </small>
                </div>
                
            </div>
            
            <!-- Right Side Image (Flipped layout compared to login to add variety, or kept consistent) -->
            <div class="col-5 d-none d-md-block p-0 bg-side">
                <div class="bg-overlay"></div>
            </div>
        </div>
    </section>
    
    <script>
        function togglePassword(){
            const field = document.getElementById('password');
            const eye = document.getElementById('eye');
            if (field.type === 'password') {
                field.type = 'text';
                eye.className = 'far fa-eye';
            } else {
                field.type = 'password';
                eye.className = 'far fa-eye-slash';
            }
        }
    </script>
</body>
</html>