<?php
require_once 'includes/session.php';

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Help & Tips - BudgetFlix</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .help-section {
            background: #2d2d2d;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            border-left: 5px solid #4CAF50;
        }
        .tip-box {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border: 2px solid #444;
        }
        .step-number {
            background: #ff6b6b;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <nav class="navbar" role="navigation">
        <div class="navbar-logo">BudgetFlix</div>
        <div class="navbar-links">
            <a href="dashboard.php">My Dashboard</a>
            <a href="transactions.php">View All Transactions</a>
            <a href="add_transaction.php">Add New Transaction</a>
            <a href="help.php" aria-current="page">Help & Tips</a>
            <a href="logout.php">Sign Out</a>
        </div>
    </nav>
    
    <main id="main-content" class="container">
        <header style="text-align: center; margin-bottom: 40px;">
            <h1>💡 Help & Tips</h1>
            <p style="font-size: 1.2rem; color: #cccccc;">Learn how to use BudgetFlix easily</p>
        </header>

        <!-- Getting Started -->
        <section class="help-section">
            <h2 style="color: #4CAF50; margin-top: 0;">🚀 Getting Started</h2>
            <div style="display: flex; align-items: flex-start; margin-bottom: 20px;">
                <span class="step-number">1</span>
                <div>
                    <h3 style="margin-top: 0;">Add Your First Transaction</h3>
                    <p style="font-size: 1.1rem; line-height: 1.6;">
                        Click "Add New Transaction" to record money coming in (like your pension or salary) 
                        or money going out (like groceries or bills).
                    </p>
                </div>
            </div>
            
            <div style="display: flex; align-items: flex-start; margin-bottom: 20px;">
                <span class="step-number">2</span>
                <div>
                    <h3 style="margin-top: 0;">Check Your Dashboard</h3>
                    <p style="font-size: 1.1rem; line-height: 1.6;">
                        Your dashboard shows you how much money you have coming in, going out, 
                        and how much you have left over.
                    </p>
                </div>
            </div>
            
            <div style="display: flex; align-items: flex-start;">
                <span class="step-number">3</span>
                <div>
                    <h3 style="margin-top: 0;">Save Your Information</h3>
                    <p style="font-size: 1.1rem; line-height: 1.6;">
                        Use the "Save My Data" button to download a backup of all your information 
                        to keep it safe.
                    </p>
                </div>
            </div>
        </section>

        <!-- Common Questions -->
        <section class="help-section">
            <h2 style="color: #2196F3; margin-top: 0;">❓ Common Questions</h2>
            
            <div class="tip-box">
                <h3 style="color: #ff6b6b;">What's the difference between Income and Expense?</h3>
                <p style="font-size: 1.1rem;">
                    <strong>Income (Money Coming In):</strong> Salary, pension, gifts, money you receive<br>
                    <strong>Expense (Money Going Out):</strong> Bills, groceries, medicine, things you buy
                </p>
            </div>
            
            <div class="tip-box">
                <h3 style="color: #ff6b6b;">What should I put in Category?</h3>
                <p style="font-size: 1.1rem;">
                    Categories help you organize your money. Examples:<br>
                    • Food & Groceries<br>
                    • Bills & Utilities<br>
                    • Healthcare & Medicine<br>
                    • Transportation<br>
                    • Entertainment
                </p>
            </div>
            
            <div class="tip-box">
                <h3 style="color: #ff6b6b;">How do I see all my transactions?</h3>
                <p style="font-size: 1.1rem;">
                    Click "View All Transactions" to see a list of everything you've recorded. 
                    You can also download this information as a file.
                </p>
            </div>
        </section>

        <!-- Tips for Success -->
        <section class="help-section">
            <h2 style="color: #FF9800; margin-top: 0;">⭐ Tips for Success</h2>
            
            <div class="tip-box">
                <h3 style="color: #4CAF50;">💡 Tip #1: Record transactions right away</h3>
                <p style="font-size: 1.1rem;">
                    When you spend money or receive money, add it to BudgetFlix right away 
                    so you don't forget.
                </p>
            </div>
            
            <div class="tip-box">
                <h3 style="color: #4CAF50;">💡 Tip #2: Use simple categories</h3>
                <p style="font-size: 1.1rem;">
                    Keep your categories simple and consistent. Use the same category names 
                    each time (like "Groceries" instead of sometimes "Food" and sometimes "Groceries").
                </p>
            </div>
            
            <div class="tip-box">
                <h3 style="color: #4CAF50;">💡 Tip #3: Save your data regularly</h3>
                <p style="font-size: 1.1rem;">
                    Click "Save My Data" once a week to download a backup. Keep these files 
                    safe on your computer or phone.
                </p>
            </div>
        </section>

        <!-- Emergency Help -->
        <section class="help-section" style="border-left-color: #f44336;">
            <h2 style="color: #f44336; margin-top: 0;">🆘 Need More Help?</h2>
            
            <div class="tip-box" style="border-color: #f44336;">
                <h3 style="color: #f44336;">If something isn't working:</h3>
                <ol style="font-size: 1.1rem; line-height: 1.8;">
                    <li>Try refreshing the page (press F5 or the refresh button)</li>
                    <li>Close the browser and open it again</li>
                    <li>Make sure all information is filled in correctly</li>
                    <li>Check that your internet connection is working</li>
                </ol>
            </div>
            
            <div class="tip-box" style="border-color: #2196F3;">
                <h3 style="color: #2196F3;">Remember your login information:</h3>
                <p style="font-size: 1.1rem;">
                    Write down your email and password in a safe place. You'll need these 
                    to sign in to your account.
                </p>
            </div>
        </section>

        <div style="text-align: center; margin-top: 40px;">
            <a href="dashboard.php" class="action-btn" style="font-size: 1.3rem;">
                🏠 Back to My Dashboard
            </a>
        </div>
    </main>
</body>
</html>
