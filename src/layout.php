<?php require_once __DIR__.'/bootstrap.php'; ?>
<?php function page_start(string $title): void { $u=current_user(); ?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=e($title)?> | HostelCloud</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?=url('assets/app.css')?>">
</head>
<body>
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4">
      <a class="navbar-brand" href="<?=url()?>"><b>HostelCloud</b></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="<?=url('hostels.php')?>">Hostels</a></li>
          <?php if($u && $u['role'] === 'student'): ?>
            <li class="nav-item"><a class="nav-link text-danger" href="<?=url('wishlist.php')?>"><i class="fas fa-heart"></i> Wishlist</a></li>
          <?php endif; ?>
        </ul>
        <div class="d-flex align-items-center gap-3">
          <button id="darkModeToggle" class="btn btn-sm btn-outline-secondary">🌗 Theme</button>
          <?php if($u): ?>
            <a href="<?=url('dashboard.php')?>" class="nav-link">Dashboard</a>
            <a href="<?=url('profile.php')?>" class="nav-link">Profile</a>
            <a href="<?=url('logout.php')?>" class="btn btn-sm btn-danger">Logout</a>
          <?php else: ?>
            <a href="<?=url('login.php')?>" class="nav-link">Login</a>
            <a href="<?=url('register.php')?>" class="btn btn-sm btn-primary">Register</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
  
  <main class="container my-4">
    <?php if($m=flash('success')): ?><div class="alert alert-success"><?=e($m)?></div><?php endif;?>
    <?php if($m=flash('error')): ?><div class="alert alert-danger"><?=e($m)?></div><?php endif;?>
<?php } function page_end(): void { ?>
  </main>
  
  <footer class="text-center py-4 text-muted border-top mt-auto">
    <div class="mb-2">CSBC 252 Cloud-Based Hostel Management System</div>
    <a href="<?=url('architecture.php')?>" class="text-decoration-none text-secondary small hover-text-primary"><i class="fab fa-aws"></i> View Cloud Architecture</a>
  </footer>
  
  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?=url('assets/app.js')?>"></script>
</body>
</html>
<?php } ?>