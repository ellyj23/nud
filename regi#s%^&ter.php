<?php
require_once 'db.php';
require_once 'rbac.php';
require_once 'lib/EmailTemplates.php';
require_once 'lib/PasswordPolicy.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
        $errors[] = 'All fields except phone number are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Use enhanced password complexity validation
    $passwordValidation = PasswordPolicy::validateComplexity($password, $first_name, $last_name, $email);
    if (!$passwordValidation['valid']) {
        $errors = array_merge($errors, $passwordValidation['errors']);
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Username or email address already exists.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $otp = random_int(10000000, 99999999);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $sql = "INSERT INTO users (first_name, last_name, email, phone_number, username, password_hash, email_verification_otp, email_otp_expires_at, is_email_verified, password_last_changed_at) VALUES (:first_name, :last_name, :email, :phone_number, :username, :password_hash, :otp, :otp_expiry, 0, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':first_name' => $first_name, ':last_name' => $last_name, ':email' => $email, ':phone_number' => $phone_number, ':username' => $username, ':password_hash' => $password_hash, ':otp' => $otp, ':otp_expiry' => $otp_expiry]);
                
                // Assign default "Viewer" role to new user
                $newUserId = $pdo->lastInsertId();
                $viewerRoleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Viewer' LIMIT 1");
                $viewerRoleStmt->execute();
                $viewerRole = $viewerRoleStmt->fetch(PDO::FETCH_ASSOC);
                if ($viewerRole) {
                    $roleAssigned = assignRoleToUser($newUserId, $viewerRole['id']);
                    if (!$roleAssigned) {
                        error_log("Failed to assign Viewer role to new user ID: $newUserId");
                        // Note: User creation proceeds even if role assignment fails
                        // Admin should manually assign role later
                    }
                }
                
                // Use professional email verification template
                $subject = "Verify Your Email Address - Feza Logistics";
                $htmlMessage = EmailTemplates::emailVerificationEmail([
                    'recipient_name' => $first_name,
                    'otp_code' => $otp,
                    'expiry_minutes' => 15,
                    'purpose' => 'registration'
                ]);
                
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: Feza Logistics <no-reply@fezalogistics.com>\r\n";
                
                if (mail($email, $subject, $htmlMessage, $headers)) {
                    header("Location: verify_email.php?email=" . urlencode($email));
                    exit;
                } else {
                    $errors[] = 'Could not send verification email. Please contact support.';
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Feza Logistics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/design-system.css">
    <link rel="stylesheet" href="assets/css/application.css">
</head>
<body>
    <main class="auth-container">
        <div class="auth-panel">
            <img src="https://www.fezalogistics.com/wp-content/uploads/2025/06/SQUARE-SIZEXX-FEZA-LOGO.png" alt="Feza Logistics Logo" class="logo">
            <h2>Join Our Platform</h2>
            <p>Create an account to start managing your finances with the best tools in the industry.</p>
        </div>
        <div class="auth-form-section">
            <div class="form-box">
                <h1>Create Account</h1>
                <p class="form-subtitle">Fill out the form to get started.</p>
                <?php if (!empty($errors)): ?>
                    <ul class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <form action="register.php" method="post" class="auth-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="phone_number" class="form-label">Phone Number (Optional)</label>
                        <input type="tel" id="phone_number" name="phone_number" class="form-control">
                    </div>
                    <div class="form-group full-width">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group full-width">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               title="Password must be at least 8 characters and include uppercase, lowercase, number, and special character.">
                    </div>
                    <button type="submit" class="auth-button btn-success">Create Account</button>
                </form>
                <div class="bottom-link">
                    Already have an account? <a href="login.php">Log In</a>
                </div>
            </div>
        </div>
    </main>
    <footer class="auth-footer">
        All rights reserved 2025 by Joseph Devops; Tel: +250788827138
    </footer>
</body>
</html>