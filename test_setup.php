<?php
// Local testing script
echo "=== BudgetFlix Local Testing ===\n";

// Test 1: Database Connection
echo "1. Testing database connection...\n";
try {
    require_once 'includes/db.php';
    if (isset($conn) && !$conn->connect_error) {
        echo "   ✅ Database connection: SUCCESS\n";
        
        // Test 2: Check if main tables exist
        $tables = ['users', 'transactions', 'family_members', 'savings_goals', 'recurring_transactions'];
        echo "2. Checking database tables...\n";
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "   ✅ Table '$table': EXISTS\n";
            } else {
                echo "   ❌ Table '$table': MISSING\n";
            }
        }
        
        // Test 3: Check for indexes
        echo "3. Checking database optimization...\n";
        $result = $conn->query("SHOW INDEX FROM transactions WHERE Key_name = 'idx_transactions_user_id'");
        if ($result && $result->num_rows > 0) {
            echo "   ✅ Database indexes: APPLIED\n";
        } else {
            echo "   ⚠️  Database indexes: NOT APPLIED (run optimization script)\n";
        }
        
    } else {
        echo "   ❌ Database connection: FAILED\n";
        if ($conn->connect_error) {
            echo "   Error: " . $conn->connect_error . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Database connection: ERROR - " . $e->getMessage() . "\n";
}

// Test 4: Security Features
echo "4. Testing security features...\n";

// Test CSRF functions
require_once 'includes/csrf.php';
if (function_exists('generateCSRFToken')) {
    echo "   ✅ CSRF protection: LOADED\n";
} else {
    echo "   ❌ CSRF protection: NOT LOADED\n";
}

// Test validation functions
require_once 'includes/validation.php';
if (function_exists('validateEmail')) {
    echo "   ✅ Input validation: LOADED\n";
} else {
    echo "   ❌ Input validation: NOT LOADED\n";
}

// Test 5: File Permissions
echo "5. Testing file permissions...\n";
$test_files = ['includes/config.php', 'includes/csrf.php', 'includes/validation.php', '.htaccess'];
foreach ($test_files as $file) {
    if (file_exists($file) && is_readable($file)) {
        echo "   ✅ File '$file': ACCESSIBLE\n";
    } else {
        echo "   ❌ File '$file': NOT ACCESSIBLE\n";
    }
}

echo "\n=== Testing Complete ===\n";
echo "🌐 Local URL: http://localhost/Clean-up/Budgeting-Web-App/\n";
echo "\n📋 Next steps:\n";
echo "1. Open browser and go to: http://localhost/Clean-up/Budgeting-Web-App/\n";
echo "2. Register a new test account\n";
echo "3. Test all functionality (login, add transactions, etc.)\n";
echo "4. Try the backup feature\n";
echo "5. Test security (try submitting forms without CSRF tokens)\n";
?>
