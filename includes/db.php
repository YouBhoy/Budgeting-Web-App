<?php
// Localhost Database connection with error handling

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'budgeting_app'); // Change if your local DB name is different

try {
    // Create connection with error handling
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        error_log('Database connection failed: ' . $conn->connect_error);
        die('Database service temporarily unavailable. Please try again later.');
    }

    // Set charset for security
    $conn->set_charset('utf8mb4');
    
} catch (Exception $e) {
    error_log('Database error: ' . $e->getMessage());
    die('Database service temporarily unavailable. Please try again later.');
}
?>