<?php
declare(strict_types=1);

function run_migrations(PDO $pdo): void {
    // 1. Check if the core 'users' table exists to determine if we need to migrate the schema
    $tablesExist = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt && $stmt->rowCount() > 0) {
            $tablesExist = true;
        }
    } catch (Exception $e) {
        $tablesExist = false;
    }

    if (!$tablesExist) {
        $sqlFile = dirname(__DIR__) . '/database/hostel_cloud.sql';
        if (is_file($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Strip out CREATE DATABASE and USE statements to allow dynamic DB names from .env (e.g. AWS RDS)
            $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
            $sql = preg_replace('/USE [a-zA-Z0-9_-]+;/i', '', $sql);

            try {
                $pdo->exec($sql);
                if (function_exists('log_event')) {
                    log_event('database_migrated', ['status' => 'success']);
                }
            } catch (PDOException $e) {
                if (function_exists('log_event')) {
                    log_event('database_migration_failed', ['error' => $e->getMessage()]);
                }
                throw new RuntimeException("Automatic Database Migration Failed: " . $e->getMessage());
            }
        }
    }

    // 2. Check if there is any data inside, if not, load the seeds
    try {
        $userCount = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        if ($userCount === 0) {
            // Seed system admin
            $pass = password_hash('password123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (name, email, phone, role, password) VALUES 
                ('Admin User', 'admin@example.com', '1234567890', 'system_admin', '{$pass}')");
            
            // Seed test student
            $pdo->exec("INSERT INTO users (name, email, phone, role, password) VALUES 
                ('Student User', 'student@example.com', '0987654321', 'student', '{$pass}')");
                
            // Seed a hostel
            $adminId = $pdo->query("SELECT id FROM users WHERE role='system_admin' LIMIT 1")->fetchColumn();
            if ($adminId) {
                // Seed all hostels with images
                $hostels = [
                    ['Sunrise Elite Hostel', 'assets/images/sunrise_elite_hostel.jpg'],
                    ['Legacy Hostel', 'assets/images/legacy_hostel.jpg'],
                    ['Palace Hostel', 'assets/images/palace_hostel.jpg'],
                    ['De-rio Hostel', 'assets/images/derio_hostel.jpg'],
                    ['Delicious Hostel', 'assets/images/delicious_hostel.jpg'],
                    ['Zack-B Hostel', 'assets/images/zack_b_hostel.jpg']
                ];
                
                foreach ($hostels as $h) {
                    $stmt = $pdo->prepare("INSERT INTO hostels (admin_id, name, location, description, total_floors, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$adminId, $h[0], 'Tesano', 'Premium hostel located at Tesano.', 3, $h[1]]);
                }
                
                $hostelId = $pdo->query("SELECT id FROM hostels WHERE name='Sunrise Elite Hostel' LIMIT 1")->fetchColumn();
                
                if ($hostelId) {
                    // Seed some rooms with interior images
                    $pdo->exec("INSERT INTO rooms (hostel_id, room_number, floor_number, equipment, capacity, price, gender, image_path) VALUES 
                        ($hostelId, '101', 1, 'Air Conditioner, Study Desk, Mini Fridge, Wardrobe', 2, 500.00, 'any', 'assets/images/interior_double.jpg')");
                    $pdo->exec("INSERT INTO rooms (hostel_id, room_number, floor_number, equipment, capacity, price, gender, image_path) VALUES 
                        ($hostelId, '102', 1, 'Fan, Study Desk, Wardrobe', 4, 300.00, 'any', 'assets/images/interior_quad.jpg')");
                }
            }
            
            if (function_exists('log_event')) {
                log_event('database_seeded', ['status' => 'success']);
            }
        }
    } catch (Exception $e) {
        if (function_exists('log_event')) {
            log_event('database_seeding_failed', ['error' => $e->getMessage()]);
        }
    }
}
