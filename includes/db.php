<?php
// Enhanced Database connection with error handling
$config = require_once 'config.php';

// Database connection settings
define('DB_HOST', $config['db']['host']);
define('DB_USER', $config['db']['user']); 
define('DB_PASS', $config['db']['pass']);
define('DB_NAME', $config['db']['name']);

try {
    // Create connection with error handling
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        error_log('Database connection failed: ' . $conn->connect_error);
        
        // Show user-friendly error in production
        if ($_SERVER['HTTP_HOST'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'], '.local') === false) {
            die('Database service temporarily unavailable. Please try again later.');
        } else {
            die('Database connection failed: ' . $conn->connect_error);
        }
    }

    // Set charset for security
    $conn->set_charset('utf8mb4');
    
} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    die('Database service temporarily unavailable. Please try again later.');
} 