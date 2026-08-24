<?php require_once __DIR__.'/../src/layout.php'; page_start('Find Student Accommodation'); 
$slides = db()->query('SELECT * FROM hostels WHERE image_path IS NOT NULL ORDER BY created_at DESC LIMIT 5')->fetchAll();
$hostels = db()->query('SELECT h.*, COUNT(r.id) rooms FROM hostels h LEFT JOIN rooms r ON r.hostel_id=h.id GROUP BY h.id ORDER BY h.created_at DESC LIMIT 6')->fetchAll(); 
?>

<!-- Carousel Section -->
<?php if($slides): ?>
<div id="roomCarousel" class="carousel slide mb-5 shadow-sm rounded-4 overflow-hidden" data-bs-ride="carousel">
  <div class="carousel-inner">
    <?php foreach($slides as $i => $slide): ?>
    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
      <img src="<?= $slide['image_path'] ? url($slide['image_path']) : 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&q=80&w=1200&h=500' ?>" class="d-block w-100" alt="Room Image">
      <div class="carousel-caption d-none d-md-block">
        <h2><?= e($slide['name']) ?></h2>
        <p class="mb-1 text-light"><strong>Location:</strong> <?= e($slide['location']) ?> | <strong>Floors in Hostel:</strong> <?= e($slide['total_floors']) ?></p>
        <p class="mb-2 text-light text-truncate" style="max-width: 600px; margin: 0 auto;"><?= e($slide['description']) ?></p>
        <div class="mt-3">
          <a href="<?=url('hostel.php?id='.$slide['id'])?>" class="btn btn-primary px-4">View Hostel Details</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div>
<?php else: ?>
<div class="p-5 mb-4 bg-body-tertiary rounded-4 text-center">
  <div class="container-fluid py-5">
    <h1 class="display-5 fw-bold">Find Your Student Accommodation</h1>
    <p class="col-md-8 mx-auto fs-4">Search available hostels and request accommodation online securely and quickly.</p>
    <a class="btn btn-primary btn-lg" href="<?=url('hostels.php')?>">Browse Hostels</a>
  </div>
</div>
<?php endif; ?>

<!-- Hostels Grid -->
<h3 class="mb-4">Available Hostels</h3>
<div class="row g-4">
  <?php foreach($hostels as $h): ?>
  <div class="col-md-6 col-lg-4">
    <article class="card h-100">
      <?php if($h['image_path']):?>
        <img class="card-img-top" src="<?=url($h['image_path'])?>" alt="Hostel" style="height: 200px; object-fit: cover;">
      <?php else: ?>
        <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 200px;">
          <span>No Image</span>
        </div>
      <?php endif;?>
      <div class="card-body d-flex flex-column">
        <h4 class="card-title"><?=e($h['name'])?></h4>
        <p class="card-text text-muted mb-1"><small>📍 <?=e($h['location'])?></small></p>
        <p class="card-text text-muted mb-3"><small>🏢 <?=e($h['total_floors'])?> Floors | 🛏️ <?=e($h['rooms'])?> rooms listed</small></p>
        <div class="mt-auto">
          <a class="btn btn-outline-primary w-100" href="<?=url('hostel.php?id='.$h['id'])?>">View Hostel</a>
        </div>
      </div>
    </article>
  </div>
  <?php endforeach;?>
</div>

<?php page_end(); ?>