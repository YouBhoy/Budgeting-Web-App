<?php
// Security Configuration

// Database configuration (move from db.php)
$config = [
    'db' => [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'budgeting_app'
    ],
    'security' => [
        'password_min_length' => 8, // Increased for production
        'session_timeout' => 3600, // 1 hour
        'max_login_attempts' => 5,
        'lockout_time' => 300, // 5 minutes
        'csrf_token_secret' => 'your_random_secret_key_change_this', // CHANGE THIS!
        'session_secret' => 'another_random_secret_key_change_this' // CHANGE THIS!
    ],
    'app' => [
        'site_url' => 'http://localhost',
        'app_name' => 'BudgetFlix',
        'default_currency' => 'PHP',
        'max_file_size' => 5242880, // 5MB for InfinityFree
        'allowed_file_types' => ['csv', 'txt'],
        'version' => '2.0.0',
        'environment' => 'development' // Change to 'production' when deployed
    ]
];

// Error reporting (disable in production)
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($http_host === 'localhost' || strpos($http_host, '.local') !== false || $config['app']['environment'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/path/to/error.log'); // Set your error log path
}

// Security headers for production
if ($config['app']['environment'] === 'production') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Force HTTPS in production
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit();
    }
}

return $config;
