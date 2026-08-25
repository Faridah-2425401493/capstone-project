<?php
require_once __DIR__ . '/../src/bootstrap.php';

$db = db();

$hostels = [
    'Sunrise Elite Hostel' => 'assets/images/sunrise_elite_hostel.jpg',
    'Legacy Hostel' => 'assets/images/legacy_hostel.jpg',
    'Palace Hostel' => 'assets/images/palace_hostel.jpg',
    'De-rio Hostel' => 'assets/images/derio_hostel.jpg',
    'Delicious Hostel' => 'assets/images/delicious_hostel.jpg',
    'Zack-B Hostel' => 'assets/images/zack_b_hostel.jpg'
];

foreach ($hostels as $name => $image) {
    // Check if it exists
    $exists = $db->query("SELECT id FROM hostels WHERE name='$name'")->fetchColumn();
    if ($exists) {
        $db->exec("UPDATE hostels SET image_path='$image' WHERE id=$exists");
    } else {
        $adminId = $db->query("SELECT id FROM users WHERE role='system_admin' LIMIT 1")->fetchColumn();
        if ($adminId) {
            $stmt = $db->prepare("INSERT INTO hostels (admin_id, name, location, description, total_floors, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$adminId, $name, 'Tesano', 'Premium hostel located at Tesano.', 3, $image]);
        }
    }
}

// Ensure the first room has an image
$hostelId = $db->query("SELECT id FROM hostels WHERE name='Sunrise Elite Hostel' LIMIT 1")->fetchColumn();
if ($hostelId) {
    $db->exec("UPDATE rooms SET image_path='assets/images/interior_double.jpg' WHERE hostel_id=$hostelId AND room_number='101'");
    $db->exec("UPDATE rooms SET image_path='assets/images/interior_quad.jpg' WHERE hostel_id=$hostelId AND room_number='102'");
}

echo "Hostels updated with images.";
