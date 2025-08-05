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
        'password_min_length' => 6,
        'session_timeout' => 3600, // 1 hour
        'max_login_attempts' => 5,
        'lockout_time' => 300 // 5 minutes
    ],
    'app' => [
        'site_url' => 'http://localhost',
        'app_name' => 'BudgetFlix',
        'default_currency' => 'PHP',
        'max_file_size' => 5242880, // 5MB for InfinityFree
        'allowed_file_types' => ['csv', 'txt']
    ]
];

// Error reporting (disable in production)
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if ($http_host === 'localhost' || strpos($http_host, '.local') !== false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

return $config;
