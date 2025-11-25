<?php
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';
require_once 'lib/EmailTemplates.php';

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_identifier = $_POST['login_identifier'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    if (empty($login_identifier) || empty($password)) {
        $login_error = 'Username/Email and password are required.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $login_identifier, ':email' => $login_identifier]);
            $user = $stmt->fetch();
            
            // Check if account is locked
            if ($user) {
                $now = new DateTime();
                $locked_until = $user['locked_until'] ? new DateTime($user['locked_until']) : null;
                
                if ($locked_until && $locked_until > $now) {
                    $remaining_seconds = $locked_until->getTimestamp() - $now->getTimestamp();
                    $hours = floor($remaining_seconds / 3600);
                    $minutes = floor(($remaining_seconds % 3600) / 60);
                    
                    if ($user['locked_by_admin']) {
                        $login_error = 'Your account has been locked by an administrator. Please contact support.';
                    } else {
                        $login_error = "Your account is locked due to multiple failed login attempts. Please try again in {$hours}h {$minutes}m or contact an administrator.";
                    }
                } elseif ($user['failed_login_attempts'] >= 3 && $user['last_failed_attempt_at']) {
                    // Check if 24 hours have passed since the last failed attempt
                    $last_failed = new DateTime($user['last_failed_attempt_at']);
                    $seconds_since_last_failed = $now->getTimestamp() - $last_failed->getTimestamp();
                    $hours_since_last_failed = floor($seconds_since_last_failed / 3600);
                    
                    if ($hours_since_last_failed >= 24) {
                        // Reset failed attempts if 24 hours have passed
                        $reset_stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, last_failed_attempt_at = NULL, locked_until = NULL WHERE id = :id");
                        $reset_stmt->execute([':id' => $user['id']]);
                        $user['failed_login_attempts'] = 0;
                    } else {
                        $login_error = "Your account is locked due to multiple failed login attempts. Please try again in " . (24 - $hours_since_last_failed) . " hours or contact an administrator.";
                    }
                }
            }
            
            if (empty($login_error) && $user && password_verify($password, $user['password_hash'])) {
                if ($user['is_email_verified'] != 1) {
                    $login_error = 'Your email is not verified. Please complete the email verification process.';
                } else {
                    // Reset failed login attempts on successful authentication
                    $reset_stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, last_failed_attempt_at = NULL, locked_until = NULL WHERE id = :id");
                    $reset_stmt->execute([':id' => $user['id']]);
                    
                    $login_otp = random_int(100000, 999999);
                    $otp_expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                    $update_stmt = $pdo->prepare("UPDATE users SET login_otp = :otp, login_otp_expires_at = :expires WHERE id = :id");
                    $update_stmt->execute([':otp' => $login_otp, ':expires' => $otp_expiry, ':id' => $user['id']]);
                    
                    // Use professional OTP email template
                    $subject = "Your Login Verification Code - Feza Logistics";
                    $deviceInfo = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device';
                    $htmlMessage = EmailTemplates::otpEmail([
                        'recipient_name' => $user['first_name'],
                        'otp_code' => $login_otp,
                        'expiry_minutes' => 10,
                        'purpose' => 'login',
                        'device_info' => substr($deviceInfo, 0, 100),
                        'location_info' => ''
                    ]);
                    
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: Feza Logistics <no-reply@fezalogistics.com>\r\n";
                    
                    if (mail($user['email'], $subject, $htmlMessage, $headers)) {
                        header("Location: verify_login.php?user_id=" . $user['id']);
                        exit;
                    } else {
                        $login_error = 'Could not send verification code. Please contact support.';
                    }
                }
            } elseif (empty($login_error)) {
                // Failed login attempt - increment counter
                if ($user) {
                    $failed_attempts = $user['failed_login_attempts'] + 1;
                    $lock_until = null;
                    
                    if ($failed_attempts >= 3) {
                        // Lock account for 24 hours
                        $lock_until = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    }
                    
                    $update_stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = :attempts, last_failed_attempt_at = NOW(), locked_until = :locked_until WHERE id = :id");
                    $update_stmt->execute([
                        ':attempts' => $failed_attempts,
                        ':locked_until' => $lock_until,
                        ':id' => $user['id']
                    ]);
                    
                    // Log failed attempt
                    $log_stmt = $pdo->prepare("INSERT INTO failed_login_attempts (username_or_email, ip_address, user_agent, attempt_type, user_id) VALUES (:username, :ip, :user_agent, 'password', :user_id)");
                    $log_stmt->execute([
                        ':username' => $login_identifier,
                        ':ip' => $ip_address,
                        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                        ':user_id' => $user['id']
                    ]);
                    
                    $remaining_attempts = 3 - $failed_attempts;
                    if ($remaining_attempts > 0) {
                        $login_error = "Invalid username, email, or password. You have {$remaining_attempts} attempt(s) remaining before your account is locked for 24 hours.";
                    } else {
                        $login_error = "Your account has been locked for 24 hours due to multiple failed login attempts. Please contact an administrator to unlock it.";
                    }
                } else {
                    // Log failed attempt for unknown user
                    $log_stmt = $pdo->prepare("INSERT INTO failed_login_attempts (username_or_email, ip_address, user_agent, attempt_type) VALUES (:username, :ip, :user_agent, 'password')");
                    $log_stmt->execute([
                        ':username' => $login_identifier,
                        ':ip' => $ip_address,
                        ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                    ]);
                    $login_error = 'Invalid username, email, or password.';
                }
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $login_error = 'A database error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Financial Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100vh;
        }

        /* Left Panel - Branding with City Background */
        .branding-panel {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.9) 100%),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800"><defs><linearGradient id="night" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:%23001a33;stop-opacity:1"/><stop offset="100%" style="stop-color:%23003366;stop-opacity:1"/></linearGradient></defs><rect fill="url(%23night)" width="1200" height="800"/><g opacity="0.3"><rect x="50" y="400" width="60" height="200" fill="%231e293b"/><rect x="150" y="350" width="80" height="250" fill="%23334155"/><rect x="270" y="300" width="70" height="300" fill="%231e293b"/><rect x="380" y="250" width="90" height="350" fill="%23475569"/><rect x="510" y="280" width="75" height="320" fill="%23334155"/><rect x="625" y="320" width="85" height="280" fill="%231e293b"/><rect x="750" y="270" width="95" height="330" fill="%23475569"/><rect x="885" y="340" width="70" height="260" fill="%23334155"/><rect x="995" y="290" width="80" height="310" fill="%231e293b"/><circle cx="100" cy="380" r="3" fill="%23fbbf24" opacity="0.8"/><circle cx="180" cy="330" r="2" fill="%23fbbf24" opacity="0.6"/><circle cx="305" cy="280" r="3" fill="%23fbbf24" opacity="0.9"/><circle cx="425" cy="230" r="2" fill="%23fbbf24" opacity="0.7"/></g></svg>');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 4rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .branding-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(59, 130, 246, 0.15) 0%, transparent 50%);
            pointer-events: none;
        }

        .branding-content {
            position: relative;
            z-index: 1;
            max-width: 500px;
            text-align: center;
        }

        .logo-section {
            margin-bottom: 3rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3);
        }

        .logo-icon svg {
            width: 48px;
            height: 48px;
            color: white;
        }

        .brand-title {
            font-size: 2.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
            background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            font-size: 1.25rem;
            font-weight: 300;
            line-height: 1.6;
            color: #cbd5e1;
            margin-bottom: 3rem;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            text-align: left;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 1rem;
            color: #e2e8f0;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(59, 130, 246, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon svg {
            width: 20px;
            height: 20px;
            color: #3b82f6;
        }

        /* Right Panel - Login Form */
        .form-panel {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .form-wrapper {
            width: 100%;
            max-width: 420px;
        }

        .form-header {
            margin-bottom: 2.5rem;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .form-description {
            font-size: 1rem;
            color: #64748b;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .error-icon {
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            color: #dc2626;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #94a3b8;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            font-size: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-input::placeholder {
            color: #cbd5e1;
        }

        .forgot-link {
            text-align: right;
            margin-bottom: 1.5rem;
        }

        .forgot-link a {
            color: #3b82f6;
            font-size: 0.875rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link a:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .submit-button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .submit-button:active {
            transform: translateY(0);
        }

        .terms-notice {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.5;
        }

        .terms-notice .terms-link {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }

        .terms-notice .terms-link:hover {
            text-decoration: underline;
        }

        .signup-link {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .signup-link a {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        /* Security Badge */
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 9999px;
            font-size: 0.75rem;
            color: #059669;
            font-weight: 500;
            margin-top: 1rem;
        }

        .security-badge svg {
            width: 14px;
            height: 14px;
        }

        /* Password Toggle */
        .password-input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.375rem;
            color: #9ca3af;
            transition: color 0.2s;
            z-index: 10;
        }

        .password-toggle:hover {
            color: #6b7280;
        }

        .password-toggle svg {
            width: 20px;
            height: 20px;
        }

        .password-input-wrapper .form-input {
            padding-right: 3rem;
        }

        /* Caps Lock Warning */
        .caps-lock-warning {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.75rem;
            background: #fef3c7;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            color: #92400e;
            margin-top: 0.5rem;
        }

        .caps-lock-warning svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

        /* Remember Me & Forgot Password Row */
        .remember-forgot-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            color: #64748b;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
        }

        .forgot-link {
            color: #3b82f6;
            font-size: 0.875rem;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        /* Submit Button Loading State */
        .submit-button {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-loading {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .spinner {
            width: 18px;
            height: 18px;
            animation: spin 1s linear infinite;
        }

        .spinner-circle {
            stroke: white;
            stroke-dasharray: 60;
            stroke-dashoffset: 45;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .is-loading .form-input {
            pointer-events: none;
            opacity: 0.7;
        }

        /* Validation Feedback Container */
        .validation-feedback-container {
            min-height: 1.25rem;
            margin-top: 0.25rem;
        }

        /* Input Focus Animation */
        .input-wrapper.focused .input-icon {
            color: #3b82f6;
        }

        .input-wrapper {
            transition: all 0.2s ease;
        }

        .input-wrapper.focused {
            transform: translateY(-1px);
        }

        .form-input.is-valid {
            border-color: #10b981;
        }

        .form-input.is-valid:focus {
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .form-input.is-invalid {
            border-color: #ef4444;
        }

        .form-input.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        /* Trust Badges */
        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .trust-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.688rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .trust-badge svg {
            width: 20px;
            height: 20px;
            fill: #d1d5db;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .login-container {
                grid-template-columns: 1fr;
            }

            .branding-panel {
                display: none;
            }

            .form-panel {
                padding: 1.5rem;
            }
        }

        @media (max-width: 640px) {
            .brand-title {
                font-size: 2rem;
            }

            .form-title {
                font-size: 1.5rem;
            }

            .branding-panel {
                padding: 2rem;
            }

            .trust-badges {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .remember-forgot-row {
                flex-direction: column;
                gap: 0.75rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel - Branding -->
        <div class="branding-panel">
            <div class="branding-content">
                <div class="logo-section">
                    <div class="logo-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <h1 class="brand-title">Sign In to<br>Financial Management</h1>
                <p class="brand-subtitle">State of the Art Financial Experience At Your Fingertips</p>
                
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span>Real-time financial insights and analytics</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span>Secure client and transaction management</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span>AI-powered financial assistant</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="form-panel">
            <div class="form-wrapper">
                <div class="form-header">
                    <h2 class="form-title">Welcome Back</h2>
                    <p class="form-description">Please sign in to your account to continue</p>
                    <!-- Security Badge -->
                    <div class="security-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                        </svg>
                        <span>Secure Connection</span>
                    </div>
                </div>

                <?php if (!empty($login_error)): ?>
                    <div class="error-message" role="alert" aria-live="polite">
                        <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?php echo htmlspecialchars($login_error); ?></span>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post" id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="login_identifier" class="form-label">Username or Email</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <input 
                                type="text" 
                                id="login_identifier" 
                                name="login_identifier" 
                                class="form-input" 
                                placeholder="Enter your username or email"
                                required 
                                autofocus
                                autocomplete="username"
                                aria-describedby="login_identifier_feedback"
                                data-validate="username-email">
                        </div>
                        <div id="login_identifier_feedback" class="validation-feedback-container" aria-live="polite"></div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrapper password-input-wrapper">
                            <svg class="input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                                aria-describedby="password_feedback"
                                data-validate="password"
                                data-no-strength="true">
                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Toggle password visibility" tabindex="-1">
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        <div id="password_feedback" class="validation-feedback-container" aria-live="polite"></div>
                        <div id="capsLockWarning" class="caps-lock-warning" style="display: none;">
                            <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                            </svg>
                            <span>Caps Lock is ON</span>
                        </div>
                    </div>

                    <div class="remember-forgot-row">
                        <label class="remember-me">
                            <input type="checkbox" name="remember_me" id="remember_me">
                            <span class="checkmark"></span>
                            <span>Remember me</span>
                        </label>
                        <a href="forgot_p!as$s$wor$d.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="submit-button" id="submitBtn">
                        <span class="btn-text">Sign In</span>
                        <span class="btn-loading" style="display: none;">
                            <svg class="spinner" viewBox="0 0 24 24">
                                <circle class="spinner-circle" cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3"></circle>
                            </svg>
                            <span>Signing in...</span>
                        </span>
                    </button>
                    
                    <div class="terms-notice">
                        By signing in you agree to our <a href="#" class="terms-link">Terms and Conditions</a>.
                    </div>
                </form>

                <div class="signup-link">
                    Don't have an account? <a href="regi#s%^&ter.php">Create Account</a>
                </div>

                <!-- Trust badges -->
                <div class="trust-badges">
                    <div class="trust-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                        </svg>
                        <span>Secure Login</span>
                    </div>
                    <div class="trust-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                        </svg>
                        <span>Encrypted</span>
                    </div>
                    <div class="trust-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                        <span>Verified</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/form-validation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginInput = document.getElementById('login_identifier');
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const capsLockWarning = document.getElementById('capsLockWarning');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');

            // Initialize form validation if library is loaded
            if (window.FezaFormValidation) {
                const validator = FezaFormValidation.init(loginForm, {
                    validateOnInput: true,
                    validateOnBlur: true,
                    showPasswordStrength: false, // Disable for login page
                    enablePasswordToggle: false, // We'll handle this manually
                    enableCapsLockWarning: false // We'll handle this manually
                });
            }

            // Password visibility toggle
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                
                const eyeOpen = togglePassword.querySelector('.eye-open');
                const eyeClosed = togglePassword.querySelector('.eye-closed');
                
                if (type === 'text') {
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'block';
                } else {
                    eyeOpen.style.display = 'block';
                    eyeClosed.style.display = 'none';
                }
            });

            // Caps Lock detection
            passwordInput.addEventListener('keyup', function(e) {
                const isCapsLock = e.getModifierState && e.getModifierState('CapsLock');
                capsLockWarning.style.display = isCapsLock ? 'flex' : 'none';
            });

            passwordInput.addEventListener('blur', function() {
                capsLockWarning.style.display = 'none';
            });

            // Form submission with loading state
            loginForm.addEventListener('submit', function(e) {
                // Show loading state
                btnText.style.display = 'none';
                btnLoading.style.display = 'flex';
                submitBtn.disabled = true;
                loginForm.classList.add('is-loading');
            });

            // Focus animations
            const inputs = loginForm.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.closest('.input-wrapper').classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    this.closest('.input-wrapper').classList.remove('focused');
                });
            });
        });
    </script>
</body>
</html>