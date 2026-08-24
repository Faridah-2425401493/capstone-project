<?php
require_once __DIR__.'/../src/layout.php';
$u = require_role('system_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount_amount = (float)($_POST['discount_amount'] ?? 0);
        
        if (empty($code) || $discount_amount <= 0) {
            flash('error', 'Invalid code or discount amount.');
        } else {
            try {
                $stmt = db()->prepare('INSERT INTO coupons (code, discount_amount, is_active) VALUES (?, ?, 1)');
                $stmt->execute([$code, $discount_amount]);
                flash('success', 'Coupon created successfully.');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    flash('error', 'Coupon code already exists.');
                } else {
                    flash('error', 'Database error.');
                }
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $is_active = (int)$_POST['is_active'];
        db()->prepare('UPDATE coupons SET is_active = ? WHERE id = ?')->execute([$is_active, $id]);
        flash('success', 'Coupon status updated.');
    }
    
    redirect('manage_coupons.php');
}

$s = db()->query('SELECT * FROM coupons ORDER BY created_at DESC');
$coupons = $s->fetchAll();

page_start('Manage Coupons');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tags text-primary me-2"></i>Manage Coupons</h2>
    <a href="<?=url('dashboard.php')?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white pt-4 pb-2 border-bottom-0">
                <h5 class="fw-bold mb-0">Create Coupon</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?=csrf()?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Coupon Code</label>
                        <input type="text" class="form-control text-uppercase" name="code" placeholder="e.g. WELCOME50" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Discount Amount (GHS)</label>
                        <input type="number" step="0.01" min="1" class="form-control" name="discount_amount" required>
                    </div>
                    
                    <button class="btn btn-primary w-100 fw-bold hover-lift">Create Coupon</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Code</th>
                                <th>Discount</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($coupons as $c): ?>
                            <tr>
                                <td class="ps-4 fw-bold font-monospace"><?=e($c['code'])?></td>
                                <td class="text-success fw-bold">- GHS <?=number_format((float)$c['discount_amount'], 2)?></td>
                                <td>
                                    <?php if($c['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small"><?=date('M d, Y', strtotime($c['created_at']))?></td>
                                <td class="text-end pe-4">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf" value="<?=csrf()?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?=$c['id']?>">
                                        <?php if($c['is_active']): ?>
                                            <input type="hidden" name="is_active" value="0">
                                            <button class="btn btn-sm btn-outline-danger" title="Deactivate">Deactivate</button>
                                        <?php else: ?>
                                            <input type="hidden" name="is_active" value="1">
                                            <button class="btn btn-sm btn-outline-success" title="Activate">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(!$coupons): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No coupons found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.btn:hover.hover-lift { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php page_end(); ?>
