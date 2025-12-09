# Deployment Guide - Password Expiration and Complexity Policy

## Pre-Deployment Checklist

- [ ] Review all changes in this PR
- [ ] Backup production database
- [ ] Schedule maintenance window (users will need to reset passwords)
- [ ] Prepare user communication about password reset requirement
- [ ] Test in staging environment first

## Deployment Steps

### 1. Database Backup
```bash
# Backup your database before making any changes
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Deploy Code Files
Copy the following files to production:
- `lib/PasswordPolicy.php` (NEW)
- `change_expired_password.php` (NEW)
- `regi#s%^&ter.php` (MODIFIED)
- `reset_pa$ss!@w%ord.php` (MODIFIED)
- `profile.php` (MODIFIED)
- `verify_login.php` (MODIFIED)

### 3. Run Database Migration 024
This adds the required columns to the users table:

```bash
mysql -u [username] -p [database_name] < migrations/024_add_password_expiration_fields.sql
```

**Expected Output:**
```
Query OK, 0 rows affected
Query OK, 0 rows affected
```

**Verify the migration:**
```sql
DESCRIBE users;
```
You should see:
- `password_last_changed_at` (datetime)
- `password_must_be_reset` (tinyint)

### 4. Run Database Migration 025
This forces all existing users (except admin) to reset their passwords:

```bash
mysql -u [username] -p [database_name] < migrations/025_force_password_reset_existing_users.sql
```

**Expected Output:**
```
Query OK, [N] rows affected
```
Where [N] is the number of users (excluding admin).

**Verify the migration:**
```sql
SELECT username, password_must_be_reset FROM users;
```
All users except 'admin' should have `password_must_be_reset = 1`.

### 5. Test the Implementation

#### Test 1: Admin User Login
1. Log in as admin user
2. Verify: Admin should NOT be forced to reset password
3. Verify: Admin can access the system normally

#### Test 2: Regular User Login (Existing Password)
1. Log in as a regular user
2. Enter OTP when prompted
3. Verify: User should be redirected to password change page
4. Verify: Message states "Your password has expired"
5. Create a new password meeting all requirements
6. Verify: User is logged in successfully after password change

#### Test 3: New User Registration
1. Try to register with a weak password (e.g., "password")
2. Verify: Should show appropriate error messages
3. Register with a strong password (e.g., "Xy9!Zw8@Qp7#" for user "Bob" with email "bob@test.com")
4. Verify: Registration succeeds
5. Verify: User can log in immediately without forced password reset

#### Test 4: Password Reset Flow
1. Use "Forgot Password" link
2. Enter email and request reset link
3. Click reset link from email
4. Try weak passwords - verify they are rejected
5. Set strong password meeting all requirements
6. Verify: Can log in with new password

#### Test 5: Profile Password Change
1. Log in to application
2. Go to profile settings
3. Change password
4. Try using characters from your name/email - verify rejection
5. Use valid password meeting all requirements
6. Verify: Password change succeeds

### 6. Verify Password Expiration After 90 Days

**For testing purposes only**, you can manually set a user's password_last_changed_at to 91 days ago:

```sql
UPDATE users 
SET password_last_changed_at = DATE_SUB(NOW(), INTERVAL 91 DAY)
WHERE username = 'testuser';
```

Then log in as that user and verify they are forced to reset their password.

**Remember to reset this for real users:**
```sql
UPDATE users 
SET password_last_changed_at = NOW()
WHERE username = 'testuser';
```

## Post-Deployment

### 1. Monitor Logs
Check application logs for any errors related to:
- Password validation
- Database updates
- User redirects

```bash
tail -f /var/log/php/error.log
# or wherever your PHP error logs are stored
```

### 2. User Support
Be prepared to help users who have trouble with:
- Understanding password requirements
- Creating valid passwords
- Questions about why they need to reset

**Common Issues:**
- "Password contains characters from name/email" - Explain they can't use any letters/numbers from their name or email
- "Special character required" - Show examples: @ $ ! % * ? & # etc.

### 3. Communication Template

**Email to Users:**
```
Subject: Important: Password Update Required

Dear [User],

As part of our ongoing commitment to security, we have implemented enhanced password policies for your account.

When you next log in, you will be required to create a new password that meets these requirements:
- At least 8 characters long
- Contains at least 1 lowercase letter
- Contains at least 1 uppercase letter
- Contains at least 1 number
- Contains at least 1 special character (@ $ ! % * ? & # etc.)
- Does not contain any letters or numbers from your name or email address

Going forward, passwords will expire every 90 days for security purposes.

If you have any questions or need assistance, please contact support.

Thank you,
[Your Team]
```

## Rollback Procedure

If issues arise, you can rollback:

### 1. Restore Code
Replace modified files with previous versions from backup

### 2. Rollback Database (if necessary)
```bash
mysql -u [username] -p [database_name] < backup_[timestamp].sql
```

Or manually rollback:
```sql
-- Remove forced password reset
UPDATE users SET password_must_be_reset = 0;

-- Optional: Remove columns if needed
ALTER TABLE users 
DROP COLUMN password_last_changed_at,
DROP COLUMN password_must_be_reset;
```

## Troubleshooting

### Issue: Users Cannot Create Valid Password
**Solution:** Provide examples of valid passwords that don't contain their name/email characters.
Example: For "John Doe" with email "john@example.com", a valid password would be "Wxy9!Kpq8#Rst"

### Issue: Admin User Still Forced to Reset
**Check:** Verify username is exactly 'admin' (case-sensitive in database check)
**Fix:** Update EXEMPT_USERNAMES in lib/PasswordPolicy.php if admin has different username

### Issue: Database Migration Fails
**Check:** Ensure columns don't already exist
**Fix:** Run `DESCRIBE users;` to check schema, manually add columns if needed

### Issue: DateTime Parse Error in Logs
**Resolution:** This is expected for invalid dates and handled gracefully by treating as expired

## Success Criteria

- ✅ All database migrations run successfully
- ✅ Admin user can log in without forced password reset
- ✅ Regular users are prompted to reset password on login
- ✅ New registrations work with enhanced validation
- ✅ Password reset flow works correctly
- ✅ No PHP errors in logs
- ✅ Users receive clear error messages for invalid passwords

## Support Contacts

For technical issues during deployment:
- Review: PASSWORD_POLICY_IMPLEMENTATION.md
- Run tests: `php test_password_policy.php`
- Check logs for specific error messages

---

**Deployment Date:** _______________
**Deployed By:** _______________
**Verified By:** _______________
**Rollback Plan Available:** Yes ✓
