<?php
// Input Validation and Sanitization Functions

function sanitizeInput($input, $type = 'string') {
    if (empty($input)) return '';
    
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateAmount($amount) {
    return is_numeric($amount) && $amount > 0 && $amount <= 999999999.99;
}

function validateTransactionType($type) {
    return in_array($type, ['income', 'expense']);
}

function validatePassword($password) {
    return strlen($password) >= 6 && strlen($password) <= 255;
}

function validateUsername($username) {
    return preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username);
}

function sanitizeFilename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
}

function rateLimitCheck($identifier, $max_attempts = 5, $time_window = 300) {
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $now = time();
    $key = $identifier;
    
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = ['count' => 1, 'first_attempt' => $now];
        return true;
    }
    
    $rate_limit = $_SESSION['rate_limits'][$key];
    
    // Reset if time window has passed
    if ($now - $rate_limit['first_attempt'] > $time_window) {
        $_SESSION['rate_limits'][$key] = ['count' => 1, 'first_attempt' => $now];
        return true;
    }
    
    // Check if limit exceeded
    if ($rate_limit['count'] >= $max_attempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION['rate_limits'][$key]['count']++;
    return true;
}
