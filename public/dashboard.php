<?php require_once __DIR__.'/../src/layout.php';$u=require_login();page_start('Dashboard');?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="card-title mb-1">Welcome, <?=e($u['name'])?></h2>
                    <p class="card-text text-muted mb-0">Role: <span class="badge bg-secondary"><?=e(ucwords(str_replace('_', ' ', $u['role'])))?></span>
                    <?php if($u['role']==='student' && !empty($u['student_id_number'])): ?>
                    &middot; <i class="fas fa-id-card ms-1"></i> ID: <?=e($u['student_id_number'])?>
                    <?php endif; ?>
                    <?php if($u['role']==='student' && !empty($u['program_offering'])): ?>
                    &middot; <i class="fas fa-graduation-cap ms-1"></i> Program: <?=e($u['program_offering'])?>
                    <?php endif; ?>
                    </p>
                </div>
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($u['role']==='student'){$s=db()->prepare('SELECT b.*,h.name hostel,r.room_number FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN hostels h ON h.id=r.hostel_id WHERE b.student_id=? ORDER BY b.created_at DESC');$s->execute([$u['id']]);$rows=$s->fetchAll();?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white pb-0 border-bottom-0 mt-2">
        <h3 class="card-title">My Bookings</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Hostel</th><th>Room</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach($rows as $x):?>
                    <tr>
                        <td><?=e($x['hostel'])?></td>
                        <td><?=e($x['room_number'])?></td>
                        <td>
                            <?php if($x['status'] === 'approved'): ?>
                                <span class="badge bg-success">Approved (Awaiting Payment)</span>
                            <?php elseif($x['status'] === 'paid'): ?>
                                <span class="badge bg-primary">Paid & Confirmed</span>
                            <?php elseif($x['status'] === 'checked_in'): ?>
                                <span class="badge bg-info text-dark">Checked In</span>
                            <?php elseif($x['status'] === 'checked_out'): ?>
                                <span class="badge bg-secondary">Checked Out</span>
                            <?php elseif($x['status'] === 'rejected'): ?>
                                <span class="badge bg-danger">Rejected</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?=e(date('M d, Y', strtotime($x['created_at'])))?></td>
                        <td class="text-end">
                            <?php if($x['status'] === 'approved'): ?>
                            <a href="<?=url('checkout.php?id='.$x['id'])?>" class="btn btn-sm btn-primary fw-bold"><i class="fas fa-credit-card me-1"></i>Pay Now</a>
                            <?php elseif(in_array($x['status'], ['paid', 'checked_in', 'checked_out'])): ?>
                            <a href="<?=url('invoice.php?booking_id='.$x['id'])?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-invoice me-1"></i> Receipt</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach;?>
                    <?php if(!$rows): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted mb-3"><i class="fas fa-bed fa-3x opacity-50"></i></div>
                            <h5 class="text-dark">You haven't booked any rooms yet.</h5>
                            <p class="text-muted">Explore our available hostels and secure your accommodation.</p>
                            <a href="<?=url('hostels.php')?>" class="btn btn-primary mt-2 px-4 hover-lift">Browse Hostels</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <a class="card shadow-sm h-100 text-decoration-none" href="<?=url('maintenance.php')?>">
            <div class="card-body text-center d-flex flex-column justify-content-center py-4 hover-lift">
                <div class="mb-3 text-primary" style="font-size: 2rem;"><i class="fas fa-tools"></i></div>
                <h5 class="card-title text-dark">Maintenance Requests</h5>
                <p class="card-text text-muted mb-0 small">Report an issue with your room.</p>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a class="card shadow-sm h-100 text-decoration-none" href="<?=url('profile.php')?>">
            <div class="card-body text-center d-flex flex-column justify-content-center py-4 hover-lift">
                <div class="mb-3 text-secondary" style="font-size: 2rem;"><i class="fas fa-user-cog"></i></div>
                <h5 class="card-title text-dark">My Profile</h5>
                <p class="card-text text-muted mb-0 small">Update your details and password.</p>
            </div>
        </a>
    </div>
</div>
<?php } ?>

<?php if(in_array($u['role'],['hostel_admin','system_admin'],true)):
if ($u['role'] === 'system_admin') {
    $metrics = [
        'hostels' => db()->query('SELECT count(*) FROM hostels')->fetchColumn(),
        'rooms' => db()->query('SELECT count(*) FROM rooms')->fetchColumn(),
        'bookings' => db()->query('SELECT count(*) FROM bookings')->fetchColumn(),
        'revenue' => db()->query('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status="success"')->fetchColumn(),
        'occupancy' => db()->query('SELECT COALESCE(SUM(occupied)/NULLIF(SUM(capacity),0)*100, 0) FROM rooms')->fetchColumn()
    ];
    $chart_data = db()->query('SELECT DATE(created_at) as date, count(*) as count FROM bookings GROUP BY date ORDER BY date DESC LIMIT 30')->fetchAll();
} else {
    $admin_id = (int)$u['id'];
    $metrics = [
        'hostels' => db()->query("SELECT count(*) FROM hostels WHERE admin_id=$admin_id")->fetchColumn(),
        'rooms' => db()->query("SELECT count(r.id) FROM rooms r JOIN hostels h ON h.id=r.hostel_id WHERE h.admin_id=$admin_id")->fetchColumn(),
        'bookings' => db()->query("SELECT count(b.id) FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN hostels h ON h.id=r.hostel_id WHERE h.admin_id=$admin_id")->fetchColumn(),
        'revenue' => db()->query("SELECT COALESCE(SUM(p.amount), 0) FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN rooms r ON r.id=b.room_id JOIN hostels h ON h.id=r.hostel_id WHERE p.status='success' AND h.admin_id=$admin_id")->fetchColumn(),
        'occupancy' => db()->query("SELECT COALESCE(SUM(r.occupied)/NULLIF(SUM(r.capacity),0)*100, 0) FROM rooms r JOIN hostels h ON h.id=r.hostel_id WHERE h.admin_id=$admin_id")->fetchColumn()
    ];
    $chart_data = db()->query("SELECT DATE(b.created_at) as date, count(*) as count FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN hostels h ON h.id=r.hostel_id WHERE h.admin_id=$admin_id GROUP BY date ORDER BY date DESC LIMIT 30")->fetchAll();
}
$chart_labels = json_encode(array_reverse(array_column($chart_data, 'date')));
$chart_values = json_encode(array_reverse(array_column($chart_data, 'count')));
?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">Total Hostels</h5>
                <h1 class="display-4 fw-bold text-primary mb-0"><?=e((string)$metrics['hostels'])?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-success border-4">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">Total Rooms</h5>
                <h1 class="display-4 fw-bold text-success mb-0"><?=e((string)$metrics['rooms'])?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">Total Bookings</h5>
                <h1 class="display-4 fw-bold text-warning mb-0"><?=e((string)$metrics['bookings'])?></h1>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-start border-info border-4">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">Total Revenue</h5>
                <h1 class="display-5 fw-bold text-info mb-0">GHS <?=number_format((float)$metrics['revenue'], 2)?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">Occupancy Rate</h5>
                <h1 class="display-5 fw-bold text-danger mb-0"><?=number_format((float)$metrics['occupancy'], 1)?>%</h1>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-white pt-3 pb-2 border-bottom-0">
        <h5 class="card-title mb-0"><i class="fas fa-chart-line text-primary me-2"></i>Bookings (Last 30 Days)</h5>
    </div>
    <div class="card-body">
        <canvas id="bookingsChart" height="100"></canvas>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Quick Actions</h3>
    <a href="<?=url('export_payments.php')?>" class="btn btn-sm btn-outline-success"><i class="fas fa-file-excel me-1"></i> Export Payments (CSV)</a>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a class="card shadow-sm h-100 text-decoration-none" href="<?=url('manage_hostels.php')?>">
            <div class="card-body text-center d-flex flex-column justify-content-center py-4 hover-lift">
                <div class="mb-3 text-primary" style="font-size: 2.5rem;"><i class="fas fa-building"></i></div>
                <h4 class="card-title text-dark">Manage Hostels</h4>
                <p class="card-text text-muted mb-0">Create, update and manage hostel records.</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a class="card shadow-sm h-100 text-decoration-none" href="<?=url('manage_rooms.php')?>">
            <div class="card-body text-center d-flex flex-column justify-content-center py-4 hover-lift">
                <div class="mb-3 text-success" style="font-size: 2.5rem;"><i class="fas fa-bed"></i></div>
                <h4 class="card-title text-dark">Manage Rooms</h4>
                <p class="card-text text-muted mb-0">Control capacity and availability.</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a class="card shadow-sm h-100 text-decoration-none" href="<?=url('manage_bookings.php')?>">
            <div class="card-body text-center d-flex flex-column justify-content-center py-4 hover-lift">
                <div class="mb-3 text-warning" style="font-size: 2.5rem;"><i class="fas fa-clipboard-list"></i></div>
                <h4 class="card-title text-dark">Manage Bookings</h4>
                <p class="card-text text-muted mb-0">Approve or reject accommodation requests.</p>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a class="card shadow-sm h-100 text-decoration-none" href="<?=url('manage_maintenance.php')?>">
            <div class="card-body text-center d-flex flex-column justify-content-center py-4 hover-lift">
                <div class="mb-3 text-danger" style="font-size: 2.5rem;"><i class="fas fa-tools"></i></div>
                <h4 class="card-title text-dark">Maintenance</h4>
                <p class="card-text text-muted mb-0">View and resolve student tickets.</p>
            </div>
        </a>
    </div>
</div>
<?php if($u['role']==='system_admin'): ?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <a class="card shadow-sm h-100 text-decoration-none" href="<?=url('manage_coupons.php')?>">
            <div class="card-body text-center d-flex flex-column justify-content-center py-4 hover-lift">
                <div class="mb-3 text-info" style="font-size: 2.5rem;"><i class="fas fa-tags"></i></div>
                <h4 class="card-title text-dark">Coupons</h4>
                <p class="card-text text-muted mb-0">Create and manage discount codes.</p>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>
<style>
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.card:hover .hover-lift { transform: translateY(-5px); }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('bookingsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?=$chart_labels?>,
                    datasets: [{
                        label: 'New Bookings',
                        data: <?=$chart_values?>,
                        borderColor: 'rgba(41, 42, 102, 1)',
                        backgroundColor: 'rgba(41, 42, 102, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });
        }
    });
</script>
<?php endif;?><?php page_end();?>