<?php require_once __DIR__.'/../src/layout.php';$q=trim($_GET['q']??'');$gender=$_GET['gender']??'';
$min_price=$_GET['min_price']??'';$max_price=$_GET['max_price']??'';$amenities=trim($_GET['amenities']??'');
$sql='SELECT DISTINCT h.* FROM hostels h LEFT JOIN rooms r ON r.hostel_id=h.id WHERE 1';$p=[];
if($q!==''){$sql.=' AND (h.name LIKE ? OR h.location LIKE ?)';$p=["%$q%","%$q%"];}
if(in_array($gender,['male','female'],true)){$sql.=' AND (r.gender=? OR r.gender="any")';$p[]=$gender;}
if($min_price!==''){$sql.=' AND r.price >= ?';$p[]=(float)$min_price;}
if($max_price!==''){$sql.=' AND r.price <= ?';$p[]=(float)$max_price;}
if($amenities!==''){$sql.=' AND r.equipment LIKE ?';$p[]="%$amenities%";}
$sql.=' ORDER BY h.created_at DESC';$s=db()->prepare($sql);$s->execute($p);$hostels=$s->fetchAll();page_start('Hostels'); ?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="mb-0">Explore Hostels</h2>
        <p class="text-muted mb-0">Find the perfect accommodation for you.</p>
    </div>
</div>

<div class="card shadow-sm mb-5 border-0 bg-light">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-12">
                <label class="form-label text-muted small fw-bold mb-1">Search Hostel or Location</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                    <input class="form-control border-start-0 ps-0" name="q" value="<?=e($q)?>" placeholder="Search by hostel name or location...">
                </div>
            </div>
            
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold mb-1">Min Price (GHS)</label>
                <input type="number" step="0.01" class="form-control" name="min_price" value="<?=e($min_price)?>" placeholder="e.g. 500">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold mb-1">Max Price (GHS)</label>
                <input type="number" step="0.01" class="form-control" name="max_price" value="<?=e($max_price)?>" placeholder="e.g. 2000">
            </div>
            
            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold mb-1">Amenities</label>
                <input type="text" class="form-control" name="amenities" value="<?=e($amenities)?>" placeholder="e.g. WiFi, AC, Fridge">
            </div>

            <div class="col-md-3">
                <label class="form-label text-muted small fw-bold mb-1">Filter by Gender</label>
                <select class="form-select" name="gender">
                    <option value="">Any gender</option>
                    <option value="male" <?=$gender==='male'?'selected':''?>>Male Only</option>
                    <option value="female" <?=$gender==='female'?'selected':''?>>Female Only</option>
                </select>
            </div>
            
            <div class="col-12 mt-4 text-end">
                <a href="hostels.php" class="btn btn-outline-secondary me-2">Clear Filters</a>
                <button class="btn btn-primary px-4" type="submit">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4 mb-5">
    <?php foreach($hostels as $h):?>
    <div class="col-md-6 col-lg-4">
        <article class="card h-100 shadow-sm hover-lift border-0">
            <?php if($h['image_path']):?>
                <img class="card-img-top" src="<?=url($h['image_path'])?>" alt="<?=e($h['name'])?>" style="height: 220px; object-fit: cover;">
            <?php else: ?>
                <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 220px;">
                    <i class="fas fa-building fa-3x opacity-50"></i>
                </div>
            <?php endif;?>
            <div class="card-body d-flex flex-column">
                <h4 class="card-title fw-bold mb-2"><?=e($h['name'])?></h4>
                <p class="card-text text-muted mb-3"><i class="fas fa-map-marker-alt me-2 text-danger"></i><?=e($h['location'])?></p>
                <div class="mt-auto">
                    <a class="btn btn-outline-primary w-100" href="<?=url('hostel.php?id='.$h['id'])?>">View Hostel Details</a>
                </div>
            </div>
        </article>
    </div>
    <?php endforeach;?>
    
    <?php if(!$hostels):?>
    <div class="col-12">
        <div class="alert alert-info text-center py-5 rounded-4 border-0 shadow-sm bg-white text-dark">
            <i class="fas fa-search fa-3x mb-3 text-muted opacity-50"></i>
            <h4>No hostels found.</h4>
            <p class="text-muted mb-0">Try adjusting your search criteria.</p>
        </div>
    </div>
    <?php endif;?>
</div>

<style>
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.card:hover.hover-lift { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php page_end();?>