<?php
declare(strict_types=1);

function run_migrations(PDO $pdo): void {
    // Check if the core 'users' table exists to determine if we need to migrate
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt && $stmt->rowCount() > 0) {
            // Already migrated
            return;
        }
    } catch (Exception $e) {
        // Table doesn't exist or error, proceed to migrate
    }

    $sqlFile = dirname(__DIR__) . '/database/hostel_cloud.sql';
    if (!is_file($sqlFile)) {
        return;
    }

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
