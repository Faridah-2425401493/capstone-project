<?php require_once __DIR__.'/../src/layout.php';$u=require_role(['hostel_admin','system_admin']);if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$id=(int)($_POST['id']??0);$name=trim($_POST['name']);$location=trim($_POST['location']);$desc=trim($_POST['description']);$phone=trim($_POST['contact_phone']);$total_floors=(int)($_POST['total_floors']??1);try{$img=!empty($_FILES['image']['name'])?upload_file($_FILES['image'],'hostels'):null;if($id){$sql='UPDATE hostels SET name=?,location=?,description=?,total_floors=?,contact_phone=?'.($img?',image_path=?':'').' WHERE id=?'.($u['role']==='hostel_admin'?' AND admin_id='.$u['id']:'');$args=[$name,$location,$desc,$total_floors,$phone];if($img)$args[]=$img;$args[]=$id;db()->prepare($sql)->execute($args);}else{db()->prepare('INSERT INTO hostels(admin_id,name,location,description,total_floors,contact_phone,image_path) VALUES(?,?,?,?,?,?,?)')->execute([$u['id'],$name,$location,$desc,$total_floors,$phone,$img]);}flash('success','Hostel saved.');}catch(Throwable $e){flash('error',$e->getMessage());}redirect('manage_hostels.php');}
$s=$u['role']==='system_admin'?db()->query('SELECT * FROM hostels ORDER BY id DESC'):(function()use($u){$x=db()->prepare('SELECT * FROM hostels WHERE admin_id=? ORDER BY id DESC');$x->execute([$u['id']]);return $x;})();$rows=$s->fetchAll();page_start('Manage Hostels');?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-building text-primary me-2"></i>Manage Hostels</h2>
</div>

<div class="row">
    <div class="col-lg-4 mb-4 mb-lg-0">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-white pb-0 border-bottom-0 pt-4">
                <h4 class="card-title mb-0">Add New Hostel</h4>
            </div>
            <div class="card-body pt-3">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Hostel Name</label>
                        <input class="form-control" name="name" placeholder="E.g., Sunrise Elite" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Location</label>
                        <input class="form-control" name="location" placeholder="E.g., North Campus" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Total Floors</label>
                            <input class="form-control" type="number" min="1" name="total_floors" value="1" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Contact Phone</label>
                            <input class="form-control" name="contact_phone" placeholder="Optional">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Brief details about the hostel"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Cover Image</label>
                        <input class="form-control" type="file" name="image" accept="image/*">
                    </div>
                    <button class="btn btn-primary w-100 fw-bold" type="submit"><i class="fas fa-plus me-2"></i>Save Hostel</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pb-0 border-bottom-0 pt-4">
                <h4 class="card-title mb-0">Existing Hostels</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Location</th>
                                <th>Floors</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $h):?>
                            <tr>
                                <td class="fw-bold text-dark">
                                    <?php if($h['image_path']): ?>
                                    <img src="<?=url($h['image_path'])?>" alt="" class="rounded me-2 object-fit-cover" width="40" height="40">
                                    <?php else: ?>
                                    <div class="d-inline-block rounded bg-secondary bg-opacity-25 text-secondary text-center me-2" style="width: 40px; height: 40px; line-height: 40px;">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <?php endif; ?>
                                    <?=e($h['name'])?>
                                </td>
                                <td><?=e($h['location'])?></td>
                                <td><?=e($h['total_floors'])?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?=url('manage_rooms.php?hostel_id='.$h['id'])?>"><i class="fas fa-door-open me-1"></i> Rooms</a>
                                </td>
                            </tr>
                            <?php endforeach;?>
                            <?php if(!$rows): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No hostels registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php page_end();?>