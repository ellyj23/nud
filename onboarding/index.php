<?php
/**
 * Onboarding Wizard - Step 1: Welcome
 * Guide new users through initial setup
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Check if onboarding is already completed
if (isset($_SESSION['onboarding_completed'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Feza Logistics - Setup Wizard</title>
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .wizard-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        [data-theme="dark"] .wizard-container {
            background: var(--bg-secondary);
        }
        
        .wizard-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .wizard-header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .wizard-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .progress-bar {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            margin-bottom: 40px;
            overflow: hidden;
        }
        
        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            width: 25%;
            transition: width 0.3s ease;
        }
        
        .welcome-content {
            text-align: center;
            margin: 40px 0;
        }
        
        .welcome-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .welcome-content h2 {
            color: var(--text-color);
            margin-bottom: 15px;
        }
        
        .welcome-content p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-item {
            padding: 20px;
            background: var(--secondary-color);
            border-radius: 8px;
            text-align: center;
        }
        
        .feature-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .feature-title {
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .feature-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .wizard-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-header">
            <h1>Welcome to Feza Logistics! 🎉</h1>
            <p>Let's get you set up in just a few steps</p>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: 25%;"></div>
        </div>
        
        <div class="welcome-content">
            <div class="welcome-icon">👋</div>
            <h2>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>We're excited to have you here. This quick setup wizard will help you configure your financial management system and get started quickly.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-icon">📊</div>
                <div class="feature-title">Analytics</div>
                <div class="feature-desc">Track revenue and expenses</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">👥</div>
                <div class="feature-title">Clients</div>
                <div class="feature-desc">Manage client relationships</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">📄</div>
                <div class="feature-title">Invoicing</div>
                <div class="feature-desc">Create professional invoices</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">💰</div>
                <div class="feature-title">Payments</div>
                <div class="feature-desc">Multiple payment methods</div>
            </div>
        </div>
        
        <div class="wizard-actions">
            <a href="../index.php?skip_onboarding=1" class="btn btn-secondary">Skip for now</a>
            <a href="step2_company.php" class="btn btn-primary">Get Started →</a>
        </div>
    </div>
</body>
</html>
