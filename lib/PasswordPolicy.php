<?php
/**
 * PasswordPolicy.php
 * 
 * Handles password complexity validation and expiration logic
 * for the application's security policy.
 */

class PasswordPolicy {
    
    // Password complexity requirements
    const MIN_LENGTH = 8;
    const PASSWORD_EXPIRY_DAYS = 90;
    const EXEMPT_USERNAMES = ['admin']; // Usernames exempt from expiration
    
    // Special characters allowed in passwords
    // Includes: @ $ ! % * ? & # ^ ( ) _ + - = [ ] { } ; : ' " , . < > / \ | ` ~
    const SPECIAL_CHAR_PATTERN = '/[@$!%*?&#^()_+\-=\[\]{};:\'",.<>\/\\|`~]/';
    
    /**
     * Validates password complexity requirements
     * 
     * Requirements:
     * - Minimum 8 characters
     * - At least 1 lowercase letter
     * - At least 1 uppercase letter
     * - At least 1 digit
     * - At least 1 special character
     * - Must not contain any letter, digit, or character from user's name or email
     * 
     * @param string $password The password to validate
     * @param string $firstName User's first name
     * @param string $lastName User's last name
     * @param string $email User's email address
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateComplexity($password, $firstName = '', $lastName = '', $email = '') {
        $errors = [];
        
        // Check minimum length
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = "Password must be at least " . self::MIN_LENGTH . " characters long.";
        }
        
        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter.";
        }
        
        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        
        // Check for digit
        if (!preg_match('/\d/', $password)) {
            $errors[] = "Password must contain at least one digit.";
        }
        
        // Check for special character
        if (!preg_match(self::SPECIAL_CHAR_PATTERN, $password)) {
            $errors[] = "Password must contain at least one special character.";
        }
        
        // Check that password doesn't contain user info
        // The policy requirement is to not contain any LETTER or DIGIT from name/email
        // Special characters from email (like @ or .) are allowed in passwords
        $forbiddenChars = [];
        
        // Extract all characters from first name
        if (!empty($firstName)) {
            $firstNameChars = preg_split('//u', strtolower($firstName), -1, PREG_SPLIT_NO_EMPTY);
            $forbiddenChars = array_merge($forbiddenChars, $firstNameChars);
        }
        
        // Extract all characters from last name
        if (!empty($lastName)) {
            $lastNameChars = preg_split('//u', strtolower($lastName), -1, PREG_SPLIT_NO_EMPTY);
            $forbiddenChars = array_merge($forbiddenChars, $lastNameChars);
        }
        
        // Extract characters from email (local part and domain, excluding special chars like @ and .)
        if (!empty($email)) {
            $emailParts = explode('@', strtolower($email));
            if (count($emailParts) > 0) {
                $emailLocalPart = preg_split('//u', $emailParts[0], -1, PREG_SPLIT_NO_EMPTY);
                $forbiddenChars = array_merge($forbiddenChars, $emailLocalPart);
                
                if (count($emailParts) > 1) {
                    // Also check domain (excluding TLD to avoid very common letters)
                    $domainParts = explode('.', $emailParts[1]);
                    if (count($domainParts) > 0) {
                        $domainChars = preg_split('//u', $domainParts[0], -1, PREG_SPLIT_NO_EMPTY);
                        $forbiddenChars = array_merge($forbiddenChars, $domainChars);
                    }
                }
            }
        }
        
        // Remove duplicates and filter to keep only alphanumeric characters
        // This filters out any special characters (like spaces, dots, etc.) from the forbidden list
        $forbiddenChars = array_unique($forbiddenChars);
        $forbiddenChars = array_filter($forbiddenChars, function($char) {
            return preg_match('/[a-z0-9]/i', $char);
        });
        
        // Check if password contains any forbidden characters
        $passwordLower = strtolower($password);
        $foundForbidden = [];
        foreach ($forbiddenChars as $char) {
            if (strpos($passwordLower, $char) !== false) {
                $foundForbidden[] = $char;
            }
        }
        
        if (!empty($foundForbidden)) {
            $errors[] = "Password must not contain any characters from your name or email address.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Checks if a user's password has expired
     * 
     * @param array $user User data array with username and password_last_changed_at
     * @return bool True if password has expired
     */
    public static function isPasswordExpired($user) {
        // Admin and other exempt users never have expired passwords
        if (in_array($user['username'], self::EXEMPT_USERNAMES)) {
            return false;
        }
        
        // If password_must_be_reset flag is set, password is expired
        if (isset($user['password_must_be_reset']) && $user['password_must_be_reset'] == 1) {
            return true;
        }
        
        // If password_last_changed_at is null, password has never been changed (expired)
        if (empty($user['password_last_changed_at'])) {
            return true;
        }
        
        // Check if password is older than expiry days
        $lastChanged = new DateTime($user['password_last_changed_at']);
        $now = new DateTime();
        $daysSinceChange = $now->diff($lastChanged)->days;
        
        return $daysSinceChange >= self::PASSWORD_EXPIRY_DAYS;
    }
    
    /**
     * Updates password and resets expiration tracking
     * 
     * @param PDO $pdo Database connection
     * @param int $userId User ID
     * @param string $newPasswordHash Hashed password
     * @return bool Success status
     */
    public static function updatePassword($pdo, $userId, $newPasswordHash) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password_hash = :password_hash,
                    password_last_changed_at = NOW(),
                    password_must_be_reset = 0
                WHERE id = :user_id
            ");
            return $stmt->execute([
                ':password_hash' => $newPasswordHash,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Failed to update password: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Forces all active users (except exempt) to reset their password
     * 
     * @param PDO $pdo Database connection
     * @return array ['success' => bool, 'affected' => int, 'message' => string]
     */
    public static function forcePasswordResetForAllUsers($pdo) {
        try {
            // Build list of exempt usernames for SQL
            $exemptPlaceholders = [];
            $exemptParams = [];
            foreach (self::EXEMPT_USERNAMES as $index => $username) {
                $placeholder = ":exempt_$index";
                $exemptPlaceholders[] = $placeholder;
                $exemptParams[$placeholder] = $username;
            }
            $exemptCondition = implode(', ', $exemptPlaceholders);
            
            $sql = "UPDATE users SET password_must_be_reset = 1 WHERE username NOT IN ($exemptCondition)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($exemptParams);
            
            $affected = $stmt->rowCount();
            
            return [
                'success' => true,
                'affected' => $affected,
                'message' => "Forced password reset for $affected user(s)."
            ];
        } catch (PDOException $e) {
            error_log("Failed to force password reset: " . $e->getMessage());
            return [
                'success' => false,
                'affected' => 0,
                'message' => "Database error: " . $e->getMessage()
            ];
        }
    }
}
