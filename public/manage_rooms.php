<?php require_once __DIR__.'/../src/layout.php';$u=require_role(['hostel_admin','system_admin']);$hid=(int)($_GET['hostel_id']??$_POST['hostel_id']??0);
function allowed_hostel(int $id,array $u): bool {$s=$u['role']==='system_admin'?db()->prepare('SELECT id FROM hostels WHERE id=?'):db()->prepare('SELECT id FROM hostels WHERE id=? AND admin_id=?');$s->execute($u['role']==='system_admin'?[$id]:[$id,$u['id']]);return (bool)$s->fetch();}
if(!$hid||!allowed_hostel($hid,$u)){http_response_code(403);exit('Select a hostel you manage.');}
if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();try{$img=!empty($_FILES['image']['name'])?upload_file($_FILES['image'],'rooms'):null;db()->prepare('INSERT INTO rooms(hostel_id,room_number,floor_number,equipment,gender,capacity,price,description,image_path) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$hid,trim($_POST['room_number']),(int)($_POST['floor_number']??1),trim($_POST['equipment']??''),$_POST['gender'],max(1,(int)$_POST['capacity']),(float)$_POST['price'],trim($_POST['description']),$img]);flash('success','Room added.');}catch(Throwable $e){flash('error',$e->getMessage());}redirect('manage_rooms.php?hostel_id='.$hid);}
$s=db()->prepare('SELECT * FROM rooms WHERE hostel_id=? ORDER BY room_number');$s->execute([$hid]);$rows=$s->fetchAll();page_start('Manage Rooms');?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-bed text-success me-2"></i>Manage Rooms</h2>
    <a href="<?=url('manage_hostels.php')?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back to Hostels</a>
</div>

<div class="row">
    <div class="col-lg-4 mb-4 mb-lg-0">
        <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
            <div class="card-header bg-white pb-0 border-bottom-0 pt-4">
                <h4 class="card-title mb-0">Add New Room</h4>
            </div>
            <div class="card-body pt-3">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    <input type="hidden" name="hostel_id" value="<?=$hid?>">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Room Number</label>
                            <input class="form-control" name="room_number" placeholder="E.g., 101" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Floor Number</label>
                            <input class="form-control" type="number" min="1" name="floor_number" value="1" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Capacity</label>
                            <input class="form-control" type="number" name="capacity" min="1" value="1" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-muted">Gender Allowed</label>
                            <select class="form-select" name="gender">
                                <option value="any">Any gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Price (GHS)</label>
                        <input class="form-control" type="number" step="0.01" name="price" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Equipment List</label>
                        <input class="form-control" name="equipment" placeholder="E.g., AC, TV, Fridge">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Optional details"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Room Image</label>
                        <input class="form-control" type="file" name="image" accept="image/*">
                    </div>
                    <button class="btn btn-success w-100 fw-bold" type="submit"><i class="fas fa-plus me-2"></i>Add Room</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white pb-0 border-bottom-0 pt-4">
                <h4 class="card-title mb-0">Existing Rooms</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Room</th>
                                <th>Floor</th>
                                <th>Gender</th>
                                <th>Capacity</th>
                                <th>Occupied</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $r):?>
                            <tr>
                                <td class="fw-bold">
                                    <?php if($r['image_path']): ?>
                                    <img src="<?=url($r['image_path'])?>" alt="" class="rounded me-2 object-fit-cover" width="40" height="40">
                                    <?php else: ?>
                                    <div class="d-inline-block rounded bg-success bg-opacity-25 text-success text-center me-2" style="width: 40px; height: 40px; line-height: 40px;">
                                        <i class="fas fa-bed"></i>
                                    </div>
                                    <?php endif; ?>
                                    #<?=e($r['room_number'])?>
                                </td>
                                <td><?=e($r['floor_number']??'1')?></td>
                                <td class="text-capitalize"><?=e($r['gender'])?></td>
                                <td><?=$r['capacity']?></td>
                                <td>
                                    <?php if($r['occupied'] >= $r['capacity']): ?>
                                        <span class="badge bg-danger">Full (<?=$r['occupied']?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark"><?=$r['occupied']?> / <?=$r['capacity']?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-success">GHS <?=e($r['price'])?></td>
                            </tr>
                            <?php endforeach;?>
                            <?php if(!$rows): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No rooms added to this hostel yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php page_end();?>