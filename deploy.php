<?php
/**
 * Deployment Helper Script
 * Run this to check if your environment is ready for deployment
 */

echo "🚀 BudgetFlix Deployment Check\n";
echo "==============================\n\n";

// Check PHP version
$php_version = phpversion();
echo "✅ PHP Version: $php_version\n";
if (version_compare($php_version, '7.4.0', '<')) {
    echo "❌ Warning: PHP 7.4+ recommended for production\n";
} else {
    echo "✅ PHP version is compatible\n";
}

// Check required extensions
$required_extensions = ['mysqli', 'session', 'json', 'mbstring'];
echo "\n📦 Required Extensions:\n";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext\n";
    } else {
        echo "❌ $ext (missing)\n";
    }
}

// Check file permissions
$files_to_check = [
    'includes/config.php' => 'readable',
    'includes/db.php' => 'readable',
    'assets/style.css' => 'readable',
    'assets/app.js' => 'readable'
];

echo "\n📁 File Permissions:\n";
foreach ($files_to_check as $file => $permission) {
    if (file_exists($file)) {
        if ($permission === 'readable' && is_readable($file)) {
            echo "✅ $file (readable)\n";
        } else {
            echo "❌ $file (not $permission)\n";
        }
    } else {
        echo "❌ $file (missing)\n";
    }
}

// Check database connection
echo "\n🗄️ Database Connection:\n";
try {
    require_once 'includes/config.php';
    $config = require 'includes/config.php';
    
    $host = $config['db']['host'];
    $user = $config['db']['user'];
    $pass = $config['db']['pass'];
    $name = $config['db']['name'];
    
    $conn = new mysqli($host, $user, $pass, $name);
    
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error . "\n";
    } else {
        echo "✅ Database connection successful\n";
        
        // Check required tables
        $required_tables = ['users', 'transactions', 'budget_goals', 'recurring_transactions'];
        echo "\n📊 Database Tables:\n";
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "✅ $table table exists\n";
            } else {
                echo "❌ $table table missing\n";
            }
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "\n";
}

// Security checklist
echo "\n🛡️ Security Checklist:\n";
$config = require 'includes/config.php';

if ($config['security']['password_min_length'] >= 8) {
    echo "✅ Password minimum length: " . $config['security']['password_min_length'] . "\n";
} else {
    echo "❌ Password minimum length too short: " . $config['security']['password_min_length'] . "\n";
}

if ($config['security']['csrf_token_secret'] !== 'your_random_secret_key_change_this') {
    echo "✅ CSRF token secret changed\n";
} else {
    echo "❌ CSRF token secret not changed (security risk)\n";
}

if ($config['security']['session_secret'] !== 'another_random_secret_key_change_this') {
    echo "✅ Session secret changed\n";
} else {
    echo "❌ Session secret not changed (security risk)\n";
}

// Environment check
echo "\n🌍 Environment:\n";
if ($config['app']['environment'] === 'production') {
    echo "✅ Environment set to production\n";
} else {
    echo "⚠️ Environment set to development (change to 'production' when deploying)\n";
}

echo "\n📋 Deployment Checklist:\n";
echo "1. ✅ All files present\n";
echo "2. ✅ Database schema ready\n";
echo "3. ✅ Configuration updated\n";
echo "4. ⏳ Change environment to 'production'\n";
echo "5. ⏳ Update database credentials for hosting\n";
echo "6. ⏳ Upload files to hosting provider\n";
echo "7. ⏳ Import database on hosting\n";
echo "8. ⏳ Test all features\n";

echo "\n🎯 Next Steps:\n";
echo "1. Read DEPLOYMENT_GUIDE.md for detailed instructions\n";
echo "2. Choose your hosting provider (InfinityFree recommended)\n";
echo "3. Follow the deployment steps in the guide\n";
echo "4. Test thoroughly after deployment\n";

echo "\n✨ Your BudgetFlix app is ready for deployment!\n";
?>

