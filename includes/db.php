<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/autoload.php';

use WildTrail\Database;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly'=>true,
        'samesite'=>'Lax',
        'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off')
    ]);
    session_start();
}

try {
    $database = new Database('localhost','wildtrail_db','root','');
    $pdo = $database->connection(); // backward-compatible PDO handle for legacy view pages
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Import sql/schema.sql and check includes/db.php.');
}
