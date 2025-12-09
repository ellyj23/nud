# Password Expiration and Complexity Policy Implementation

## Overview

This document describes the implementation of password expiration and complexity policies for the application, ensuring enhanced security for user accounts.

## Requirements Implemented

### 1. Password Expiration
- **90-Day Expiration**: User passwords expire every 90 days and must be reset
- **Admin Exemption**: The 'admin' user is exempt from password expiration
- **Forced Reset**: All existing active users (except admin) are required to reset their passwords on next login

### 2. Password Complexity Requirements
New passwords must meet the following criteria:
- Minimum length: **8 characters**
- At least **1 lowercase letter** (a-z)
- At least **1 uppercase letter** (A-Z)
- At least **1 digit** (0-9)
- At least **1 special character** (e.g., @, $, !, %, *, ?, &, #, etc.)
- Must **NOT contain any letter or digit** from the user's first name, last name, or email address

### 3. Reset Flow
The password reset functionality is fully implemented and integrated with the new complexity requirements.

## Technical Implementation

### Database Changes

#### Migration 024: Add Password Expiration Fields
File: `migrations/024_add_password_expiration_fields.sql`

Adds two new columns to the `users` table:
- `password_last_changed_at` (DATETIME): Timestamp of last password change
- `password_must_be_reset` (TINYINT): Flag to force immediate password reset

#### Migration 025: Force Password Reset
File: `migrations/025_force_password_reset_existing_users.sql`

Sets the `password_must_be_reset` flag to 1 for all existing users except 'admin'.

### Core Components

#### PasswordPolicy Class
File: `lib/PasswordPolicy.php`

Central utility class that provides:
- **`validateComplexity()`**: Validates password against all complexity requirements
- **`isPasswordExpired()`**: Checks if a user's password has expired (90 days or forced reset)
- **`updatePassword()`**: Updates password and resets expiration tracking
- **`forcePasswordResetForAllUsers()`**: Administrative function to force password resets

Key constants:
- `MIN_LENGTH = 8`: Minimum password length
- `PASSWORD_EXPIRY_DAYS = 90`: Days until password expiration
- `EXEMPT_USERNAMES = ['admin']`: Users exempt from expiration

### Updated Files

#### 1. Registration (`regi#s%^&ter.php`)
- Uses `PasswordPolicy::validateComplexity()` for password validation
- Sets `password_last_changed_at` to NOW() on account creation

#### 2. Password Reset (`reset_pa$ss!@w%ord.php`)
- Uses `PasswordPolicy::validateComplexity()` with user's name and email
- Uses `PasswordPolicy::updatePassword()` to properly update password with expiration tracking

#### 3. Profile Password Change (`profile.php`)
- Uses `PasswordPolicy::validateComplexity()` with current user data
- Uses `PasswordPolicy::updatePassword()` to track password changes

#### 4. Login Verification (`verify_login.php`)
- After successful OTP verification, checks if password has expired using `PasswordPolicy::isPasswordExpired()`
- Redirects to `change_expired_password.php` if password is expired
- Admin users are automatically exempted

#### 5. Expired Password Change (`change_expired_password.php`)
- New page for users with expired passwords
- Enforces password complexity requirements
- Clears expiration flags after successful password change
- Redirects to main application after completion

## Testing

### Test Suite
File: `test_password_policy.php`

Comprehensive test suite with 14 tests covering:
- Valid password acceptance
- Password length validation
- Uppercase letter requirement
- Lowercase letter requirement
- Digit requirement
- Special character requirement
- Name/email character rejection
- Expiration logic (null date, 30 days, 91 days)
- Admin user exemption
- Forced reset flag

**All tests pass successfully.**

To run tests:
```bash
php test_password_policy.php
```

## User Experience Flow

### New User Registration
1. User provides name, email, and password
2. Password is validated against complexity requirements including name/email check
3. If valid, account is created with `password_last_changed_at` set to current time

### Existing User (First Login After Implementation)
1. User enters credentials and receives OTP
2. User enters correct OTP
3. System detects `password_must_be_reset = 1`
4. User is redirected to `change_expired_password.php`
5. User must set a new password meeting all complexity requirements
6. Upon success, user is redirected to main application

### Regular User (90+ Days Since Last Password Change)
1. User enters credentials and receives OTP
2. User enters correct OTP
3. System detects password is older than 90 days
4. User is redirected to `change_expired_password.php`
5. User must set a new password meeting all complexity requirements
6. Upon success, user is redirected to main application

### Admin User
1. Admin user is exempt from all password expiration checks
2. Admin can log in regardless of password age
3. Admin is still subject to complexity requirements when changing password

## Deployment Instructions

1. **Backup Database**: Always backup before running migrations
2. **Run Migration 024**: Adds required columns to users table
   ```bash
   mysql -u [user] -p [database] < migrations/024_add_password_expiration_fields.sql
   ```
3. **Run Migration 025**: Forces password reset for existing users
   ```bash
   mysql -u [user] -p [database] < migrations/025_force_password_reset_existing_users.sql
   ```
4. **Deploy Code**: Deploy all updated PHP files
5. **Test**: Verify the complete flow works as expected
6. **Communicate**: Inform users they will need to reset their passwords on next login

## Security Considerations

- **No Backward Compatibility Issues**: Old passwords remain valid until expiration
- **Admin Access Preserved**: Admin account cannot be locked out due to expiration
- **Strong Validation**: Comprehensive character-level checking prevents weak passwords
- **Clear Error Messages**: Users receive specific feedback on password requirements
- **Session Security**: Password expiration check happens after OTP verification

## Configuration

To modify the password policy:
- Edit constants in `lib/PasswordPolicy.php`
- Update `MIN_LENGTH` for different minimum length
- Update `PASSWORD_EXPIRY_DAYS` for different expiration period
- Add usernames to `EXEMPT_USERNAMES` array to exempt additional accounts

## Support

For users having trouble with password requirements:
1. Ensure password is at least 8 characters
2. Check for at least 1 lowercase, 1 uppercase, 1 digit, 1 special character
3. Avoid using any letters or numbers from your name or email
4. Example valid password (if name is "Bob Xyz" and email is "bob@uvw.xy"): `Qdm9!Rfp8@Ljt3#`

## Future Enhancements

Potential improvements:
- Password history tracking to prevent reuse of recent passwords
- Configurable expiration periods per user role
- Email notifications before password expiration
- Password strength meter in UI
- Two-factor authentication integration
