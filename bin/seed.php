<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "Seeding the database with MVP data...\n";

try {
    $db = db();

    // Drop existing tables to recreate schema with new columns
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("DROP TABLE IF EXISTS coupons, wishlists, maintenance_tickets, reviews, documents, payments, bookings, password_resets, rooms, hostels, users;");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Recreate schema to apply new columns
    $sql = file_get_contents(__DIR__ . '/../database/hostel_cloud.sql');
    $db->exec($sql);

    // Seed system admin
    $pass = password_hash('password123', PASSWORD_DEFAULT);
    $db->exec("INSERT INTO users (name, email, phone, role, password) VALUES 
        ('Admin User', 'admin@example.com', '1234567890', 'system_admin', '{$pass}')
        ON DUPLICATE KEY UPDATE role='system_admin'");
    
    // Seed test student
    $db->exec("INSERT INTO users (name, email, phone, role, password) VALUES 
        ('Student User', 'student@example.com', '0987654321', 'student', '{$pass}')
        ON DUPLICATE KEY UPDATE role='student'");
        
    // Seed a hostel
    $adminId = $db->query("SELECT id FROM users WHERE role='system_admin' LIMIT 1")->fetchColumn();
    $db->exec("INSERT INTO hostels (admin_id, name, location, description, total_floors) VALUES 
        ($adminId, 'Sunrise Elite Hostel', 'North Campus, Block A', 'Premium hostel with modern amenities.', 3)
        ON DUPLICATE KEY UPDATE name='Sunrise Elite Hostel'");
    
    $hostelId = $db->query("SELECT id FROM hostels WHERE name='Sunrise Elite Hostel' LIMIT 1")->fetchColumn();
    
    // Seed some rooms
    $db->exec("INSERT INTO rooms (hostel_id, room_number, floor_number, equipment, capacity, price, gender) VALUES 
        ($hostelId, '101', 1, 'Air Conditioner, Study Desk, Mini Fridge, Wardrobe', 2, 500.00, 'any') 
        ON DUPLICATE KEY UPDATE equipment='Air Conditioner, Study Desk, Mini Fridge, Wardrobe'");
    $db->exec("INSERT INTO rooms (hostel_id, room_number, floor_number, equipment, capacity, price, gender) VALUES 
        ($hostelId, '102', 1, 'Fan, Study Desk, Wardrobe', 4, 300.00, 'any') 
        ON DUPLICATE KEY UPDATE equipment='Fan, Study Desk, Wardrobe'");

    echo "Seeding completed successfully!\n";
    echo "You can now login with:\n";
    echo "Admin: admin@example.com / password123\n";
    echo "Student: student@example.com / password123\n";
} catch (Exception $e) {
    echo "Error seeding database: " . $e->getMessage() . "\n";
}
