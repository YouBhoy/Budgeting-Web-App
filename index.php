<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BudgetFlix - Welcome</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Personal & Family Finance Tracker with a Modern Netflix-Inspired Experience">
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="assets/app.js" defer></script>
</head>
<body>
    <!-- Skip to main content for screen readers -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="index.php" aria-current="page">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
            <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
                <span class="icon">☀️</span>
                <span>Theme</span>
            </button>
        </div>
    </nav>
    
    <main id="main-content" class="container">
        <header style="text-align:center; margin-bottom:40px;" class="fade-in-up">
            <h1 style="font-size:2.5rem; margin-bottom:10px; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">BudgetFlix</h1>
            <p style="font-size:1.2rem; color: var(--text-secondary); margin-bottom:32px;">Personal & Family Finance Tracker<br>with a Modern Netflix-Inspired Experience</p>
            <div style="display:flex; gap:16px; justify-content:center; margin-bottom:32px; flex-wrap:wrap;">
                <a href="login.php" class="action-btn btn-success">🚀 Get Started</a>
                <a href="register.php" class="action-btn btn-info">📝 Create Account</a>
            </div>
        </header>
        
        <section class="card-grid">
            <div class="card fade-in-up" data-scroll-animate>
                <div style="font-size:2rem; margin-bottom:8px;">🔒</div>
                <strong>Secure Login</strong><br>
                Password hashing & session protection
            </div>
            <div class="card fade-in-up" data-scroll-animate>
                <div style="font-size:2rem; margin-bottom:8px;">📊</div>
                <strong>Real-Time Analytics</strong><br>
                Track income, expenses, and savings
            </div>
            <div class="card fade-in-up" data-scroll-animate>
                <div style="font-size:2rem; margin-bottom:8px;">👨‍👩‍👧‍👦</div>
                <strong>Family & Individual Modes</strong><br>
                Assign transactions, set goals, and more
            </div>
            <div class="card fade-in-up" data-scroll-animate>
                <div style="font-size:2rem; margin-bottom:8px;">💡</div>
                <strong>Smart Budgeting</strong><br>
                Recurring transactions & savings goals
            </div>
        </section>
        
        <!-- New Features Section -->
        <section style="margin-top: 60px;" class="fade-in-up">
            <h2 style="text-align: center; margin-bottom: 40px; color: var(--primary-color);">✨ New Features</h2>
            <div class="card-grid">
                <div class="card" style="border-left: 5px solid var(--accent-color);">
                    <div style="font-size:2rem; margin-bottom:8px;">🌙</div>
                    <strong>Dark/Light Theme</strong><br>
                    Switch between themes for your comfort
                </div>
                <div class="card" style="border-left: 5px solid var(--warning-color);">
                    <div style="font-size:2rem; margin-bottom:8px;">📱</div>
                    <strong>Mobile Optimized</strong><br>
                    Perfect experience on all devices
                </div>
                <div class="card" style="border-left: 5px solid var(--success-color);">
                    <div style="font-size:2rem; margin-bottom:8px;">⚡</div>
                    <strong>Enhanced Performance</strong><br>
                    Faster loading and smooth animations
                </div>
                <div class="card" style="border-left: 5px solid var(--danger-color);">
                    <div style="font-size:2rem; margin-bottom:8px;">🔍</div>
                    <strong>Smart Search</strong><br>
                    Find transactions quickly and easily
                </div>
            </div>
        </section>
        
        <!-- Call to Action -->
        <section style="text-align: center; margin-top: 60px; padding: 40px; background: var(--bg-card); border-radius: var(--border-radius); border: 2px solid var(--border-color);" class="fade-in-up">
            <h2 style="margin-bottom: 20px; color: var(--primary-color);">Ready to Take Control of Your Finances?</h2>
            <p style="font-size: 1.1rem; margin-bottom: 30px; color: var(--text-secondary);">
                Join thousands of users who are already managing their money better with BudgetFlix
            </p>
            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="register.php" class="action-btn btn-success" style="font-size: 1.2rem; padding: 20px 40px;">
                    🚀 Start Free Today
                </a>
                <a href="help.php" class="action-btn btn-info" style="font-size: 1.2rem; padding: 20px 40px;">
                    📖 Learn More
                </a>
            </div>
        </section>
    </main>
    
    <!-- Footer -->
    <footer style="text-align: center; padding: 40px 20px; margin-top: 60px; background: var(--bg-secondary); border-top: 2px solid var(--border-color);">
        <p style="color: var(--text-secondary); margin-bottom: 20px;">
            © 2024 BudgetFlix. Built with ❤️ for better financial management.
        </p>
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="help.php" style="color: var(--primary-color); text-decoration: none;">Help & Support</a>
            <a href="settings.php" style="color: var(--primary-color); text-decoration: none;">Settings</a>
            <a href="backup.php" style="color: var(--primary-color); text-decoration: none;">Backup Data</a>
        </div>
    </footer>
</body>
</html> 