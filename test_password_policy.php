<?php
/**
 * Test script to verify Password Policy functionality
 * This script tests password complexity validation and expiration logic
 * 
 * Usage: php test_password_policy.php
 */

require_once 'lib/PasswordPolicy.php';

echo "=== Password Policy Test ===\n\n";

// Test 1: Password Complexity Validation - Valid Password
// Using password with NO alphanumeric characters from "Bob", "Xyz", or "bob@uvw.xy"
// Name chars (alphanumeric only): b, o, x, y, z
// Email chars (alphanumeric only): b, o, u, v, w, x, y (excluding @ and .)
// Password must avoid: b, o, x, y, z, u, v, w
// Safe characters to use: a, c, d, e, f, g, h, i, j, k, l, m, n, p, q, r, s, t
// Plus all special characters are allowed
echo "Test 1: Valid password with all requirements...\n";
$result = PasswordPolicy::validateComplexity('Qdm9!Rfp8@Ljt3#', 'Bob', 'Xyz', 'bob@uvw.xy');
if ($result['valid']) {
    echo "✓ Valid password accepted!\n\n";
} else {
    echo "✗ Valid password rejected: " . implode(', ', $result['errors']) . "\n\n";
}

// Test 2: Password too short
echo "Test 2: Password too short...\n";
$result = PasswordPolicy::validateComplexity('Short1!', 'John', 'Doe', 'test@example.com');
if (!$result['valid'] && in_array("Password must be at least 8 characters long.", $result['errors'])) {
    echo "✓ Correctly rejected short password!\n\n";
} else {
    echo "✗ Failed to reject short password\n\n";
}

// Test 3: Missing uppercase letter
echo "Test 3: Missing uppercase letter...\n";
$result = PasswordPolicy::validateComplexity('password123!', 'John', 'Doe', 'test@example.com');
if (!$result['valid'] && in_array("Password must contain at least one uppercase letter.", $result['errors'])) {
    echo "✓ Correctly rejected password without uppercase!\n\n";
} else {
    echo "✗ Failed to reject password without uppercase\n\n";
}

// Test 4: Missing lowercase letter
echo "Test 4: Missing lowercase letter...\n";
$result = PasswordPolicy::validateComplexity('PASSWORD123!', 'John', 'Doe', 'test@example.com');
if (!$result['valid'] && in_array("Password must contain at least one lowercase letter.", $result['errors'])) {
    echo "✓ Correctly rejected password without lowercase!\n\n";
} else {
    echo "✗ Failed to reject password without lowercase\n\n";
}

// Test 5: Missing digit
echo "Test 5: Missing digit...\n";
$result = PasswordPolicy::validateComplexity('Password!@#', 'John', 'Doe', 'test@example.com');
if (!$result['valid'] && in_array("Password must contain at least one digit.", $result['errors'])) {
    echo "✓ Correctly rejected password without digit!\n\n";
} else {
    echo "✗ Failed to reject password without digit\n\n";
}

// Test 6: Missing special character
echo "Test 6: Missing special character...\n";
$result = PasswordPolicy::validateComplexity('Password123', 'John', 'Doe', 'test@example.com');
if (!$result['valid'] && in_array("Password must contain at least one special character.", $result['errors'])) {
    echo "✓ Correctly rejected password without special character!\n\n";
} else {
    echo "✗ Failed to reject password without special character\n\n";
}

// Test 7: Contains user's first name character
echo "Test 7: Password contains characters from user's name...\n";
$result = PasswordPolicy::validateComplexity('MyNameIsJohn123!', 'John', 'Doe', 'test@example.com');
if (!$result['valid'] && in_array("Password must not contain any characters from your name or email address.", $result['errors'])) {
    echo "✓ Correctly rejected password with name characters!\n\n";
} else {
    echo "✗ Failed to reject password with name characters\n\n";
}

// Test 8: Contains email characters
echo "Test 8: Password contains characters from user's email...\n";
$result = PasswordPolicy::validateComplexity('TestEmail123!', 'John', 'Doe', 'test@example.com');
if (!$result['valid'] && in_array("Password must not contain any characters from your name or email address.", $result['errors'])) {
    echo "✓ Correctly rejected password with email characters!\n\n";
} else {
    echo "✗ Failed to reject password with email characters\n\n";
}

// Test 9: Password Expiration - User with expired password (null date)
echo "Test 9: Password expiration check (null date)...\n";
$user = [
    'id' => 1,
    'username' => 'testuser',
    'password_last_changed_at' => null,
    'password_must_be_reset' => 0
];
if (PasswordPolicy::isPasswordExpired($user)) {
    echo "✓ Correctly identified expired password (null date)!\n\n";
} else {
    echo "✗ Failed to identify expired password (null date)\n\n";
}

// Test 10: Password Expiration - Admin user is exempt
echo "Test 10: Password expiration check (admin user exempt)...\n";
$user = [
    'id' => 1,
    'username' => 'admin',
    'password_last_changed_at' => null,
    'password_must_be_reset' => 0
];
if (!PasswordPolicy::isPasswordExpired($user)) {
    echo "✓ Correctly exempted admin user from expiration!\n\n";
} else {
    echo "✗ Failed to exempt admin user from expiration\n\n";
}

// Test 11: Password Expiration - Recent password change (not expired)
echo "Test 11: Password expiration check (recent change)...\n";
$user = [
    'id' => 1,
    'username' => 'testuser',
    'password_last_changed_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
    'password_must_be_reset' => 0
];
if (!PasswordPolicy::isPasswordExpired($user)) {
    echo "✓ Correctly identified non-expired password (30 days old)!\n\n";
} else {
    echo "✗ Failed to identify non-expired password (30 days old)\n\n";
}

// Test 12: Password Expiration - Old password change (expired - 91 days)
echo "Test 12: Password expiration check (91 days old)...\n";
$user = [
    'id' => 1,
    'username' => 'testuser',
    'password_last_changed_at' => date('Y-m-d H:i:s', strtotime('-91 days')),
    'password_must_be_reset' => 0
];
if (PasswordPolicy::isPasswordExpired($user)) {
    echo "✓ Correctly identified expired password (91 days old)!\n\n";
} else {
    echo "✗ Failed to identify expired password (91 days old)\n\n";
}

// Test 13: Password Expiration - Force reset flag set
echo "Test 13: Password expiration check (must_be_reset flag)...\n";
$user = [
    'id' => 1,
    'username' => 'testuser',
    'password_last_changed_at' => date('Y-m-d H:i:s'),
    'password_must_be_reset' => 1
];
if (PasswordPolicy::isPasswordExpired($user)) {
    echo "✓ Correctly identified forced password reset!\n\n";
} else {
    echo "✗ Failed to identify forced password reset\n\n";
}

// Test 14: Complex valid password that doesn't contain name/email
// For "Jane" "Doe" "jane@doe.com", forbidden chars (alphanumeric only) are: j, a, n, e, d, o, c, m
// Note: @ and . are not forbidden as they are special characters
// Safe chars: b, f, g, h, i, k, l, p, q, r, s, t, u, v, w, x, y, z
echo "Test 14: Complex valid password (no name/email characters)...\n";
$result = PasswordPolicy::validateComplexity('Bfg9!Hik8@Lpq7#', 'Jane', 'Doe', 'jane@doe.com');
if ($result['valid']) {
    echo "✓ Valid complex password accepted!\n\n";
} else {
    echo "✗ Valid complex password rejected: " . implode(', ', $result['errors']) . "\n\n";
}

echo "=== All tests completed ===\n";
