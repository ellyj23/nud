<?php
session_start();

// Check if user came from password expiration flow
if (!isset($_SESSION['password_expired']) || $_SESSION['password_expired'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';
require_once 'lib/PasswordPolicy.php';

$user_id = $_SESSION['user_id'];
$error = '';
$message = '';

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error. Please try again later.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || empty($password_confirm)) {
        $error = 'Please enter and confirm your new password.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Use enhanced password complexity validation
        $passwordValidation = PasswordPolicy::validateComplexity($password, $user['first_name'], $user['last_name'], $user['email']);
        if (!$passwordValidation['valid']) {
            $error = implode(' ', $passwordValidation['errors']);
        } else {
            // All checks passed, update the password with expiration tracking
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if (PasswordPolicy::updatePassword($pdo, $user['id'], $password_hash)) {
                // Clear the password expired flag
                unset($_SESSION['password_expired']);
                unset($_SESSION['password_expired_message']);
                
                // Set success message
                $_SESSION['password_change_success'] = true;
                
                // Redirect to main page
                header('Location: index.php');
                exit;
            } else {
                $error = "Failed to update password. Please try again.";
            }
        }
    }
}

$expiration_message = $_SESSION['password_expired_message'] ?? 'Your password has expired. Please set a new password to continue.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Expired - Feza Logistics</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary-color: #28a745; --primary-hover: #218838; --secondary-color: #f4f7f6; --text-color: #333; --light-text-color: #777; --border-color: #ddd; --error-bg: #f8d7da; --error-text: #721c24; --success-bg: #d4edda; --success-text: #155724; --footer-bg: #ffffff; --footer-text: #555555; --warning-bg: #fff3cd; --warning-text: #856404; }
        body { font-family: 'Poppins', sans-serif; margin: 0; display: flex; flex-direction: column; min-height: 100vh; background-color: var(--secondary-color); }
        main.auth-container { flex-grow: 1; display: flex; width: 100%; }
        .auth-panel { flex: 1; background: linear-gradient(135deg, #0052cc, #007bff); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 50px; text-align: center; }
        .auth-panel .logo { max-width: 120px; margin-bottom: 30px; background: #fff; border-radius:10px; padding:10px; }
        .auth-panel h2 { font-size: 2rem; margin-bottom: 15px; }
        .auth-panel p { font-size: 1.1rem; line-height: 1.6; max-width: 350px; }
        .auth-form-section { flex: 1; display: flex; align-items: center; justify-content: center; padding: 50px; background: #fff; }
        .form-box { width: 100%; max-width: 400px; text-align: center; }
        .form-box h1 { color: var(--text-color); margin-bottom: 10px; font-size: 2.2rem; }
        .form-box .form-subtitle { color: var(--light-text-color); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; text-align: left; margin-bottom: 8px; font-weight: 600; color: var(--text-color); }
        .form-group input { width: 100%; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 5px; box-sizing: border-box; font-size: 1rem; }
        .auth-button { width: 100%; padding: 14px; background-color: var(--primary-color); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1rem; font-weight: 700; }
        .message-area { margin-bottom: 20px; }
        .message { padding: 15px; border-radius: 5px; }
        .error-message { color: var(--error-text); background-color: var(--error-bg); }
        .warning-message { color: var(--warning-text); background-color: var(--warning-bg); border: 1px solid var(--warning-text); }
        .success-message { color: var(--success-text); background-color: var(--success-bg); }
        .password-requirements { text-align: left; font-size: 0.85rem; color: var(--light-text-color); background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px; }
        .password-requirements ul { margin: 10px 0; padding-left: 20px; }
        .password-requirements li { margin-bottom: 5px; }
        .auth-footer { text-align: center; padding: 20px; background-color: var(--footer-bg); color: var(--footer-text); font-size: 0.9rem; flex-shrink: 0; border-top: 1px solid var(--border-color); }
        @media (max-width: 992px) { .auth-panel { display: none; } }
    </style>
</head>
<body>
    <main class="auth-container">
        <div class="auth-panel">
            <img src="https://www.fezalogistics.com/wp-content/uploads/2025/06/SQUARE-SIZEXX-FEZA-LOGO.png" alt="Feza Logistics Logo" class="logo">
            <h2>Password Update Required</h2>
            <p>For your security, please create a new password to continue using your account.</p>
        </div>
        <div class="auth-form-section">
            <div class="form-box">
                <h1>Change Password</h1>
                <div class="message-area">
                    <div class="message warning-message"><?php echo htmlspecialchars($expiration_message); ?></div>
                    <?php if ($error): ?><div class="message error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                </div>

                <form action="change_expired_password.php" method="post">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Confirm New Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                    
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>Minimum 8 characters</li>
                            <li>At least 1 lowercase letter</li>
                            <li>At least 1 uppercase letter</li>
                            <li>At least 1 digit</li>
                            <li>At least 1 special character</li>
                            <li>Must not contain any characters from your name or email</li>
                        </ul>
                    </div>
                    
                    <button type="submit" class="auth-button">Update Password</button>
                </form>
            </div>
        </div>
    </main>
    <footer class="auth-footer">
        All rights reserved 2025 by Joseph Devops; Tel: +250788827138
    </footer>
</body>
</html>
