<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budgeting Web App - Welcome</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #e0e7ef 0%, #f5f7fa 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .hero {
            max-width: 480px;
            margin: 80px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            padding: 40px 32px 32px 32px;
            text-align: center;
        }
        .bank-logo {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007bff 60%, #00c6ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: #fff;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,123,255,0.10);
        }
        .hero h1 {
            font-size: 2.2rem;
            color: #1a2a3a;
            margin-bottom: 10px;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .hero p {
            color: #4a5a6a;
            font-size: 1.1rem;
            margin-bottom: 32px;
        }
        .cta-btns {
            display: flex;
            gap: 16px;
            justify-content: center;
        }
        .cta-btn {
            background: linear-gradient(90deg, #007bff 60%, #00c6ff 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(0,123,255,0.10);
            text-decoration: none;
        }
        .cta-btn:hover {
            background: linear-gradient(90deg, #0056b3 60%, #007bff 100%);
        }
        .features {
            margin: 48px auto 0 auto;
            max-width: 600px;
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
        }
        .feature {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 20px 24px;
            min-width: 220px;
            flex: 1 1 220px;
            text-align: center;
        }
        .feature-icon {
            font-size: 1.7rem;
            margin-bottom: 8px;
            color: #007bff;
        }
        @media (max-width: 600px) {
            .hero { padding: 24px 8px; margin-top: 32px; }
            .features { flex-direction: column; gap: 16px; }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="bank-logo">💰</div>
        <h1>Budgeting Web App</h1>
        <p>Personal & Family Finance Tracker<br>with a Secure, Modern Banking Experience</p>
        <div class="cta-btns">
            <a href="login.php" class="cta-btn">Login</a>
            <a href="register.php" class="cta-btn" style="background:linear-gradient(90deg,#00c6ff 60%,#007bff 100%)">Register</a>
        </div>
    </div>
    <div class="features">
        <div class="feature">
            <div class="feature-icon">🔒</div>
            <strong>Secure Login</strong><br>
            Password hashing & session protection
        </div>
        <div class="feature">
            <div class="feature-icon">📊</div>
            <strong>Real-Time Analytics</strong><br>
            Track income, expenses, and savings
        </div>
        <div class="feature">
            <div class="feature-icon">👨‍👩‍👧‍👦</div>
            <strong>Family & Individual Modes</strong><br>
            Assign transactions, set goals, and more
        </div>
        <div class="feature">
            <div class="feature-icon">💡</div>
            <strong>Smart Budgeting</strong><br>
            Recurring transactions & savings goals
        </div>
    </div>
</body>
</html> 