-- Migration: Force password reset for all existing users (except admin)
-- This migration marks all existing user passwords as expired (except admin user)

-- Set password_must_be_reset flag for all users except 'admin'
UPDATE users 
SET password_must_be_reset = 1 
WHERE username != 'admin';

-- Log the number of affected users
SELECT CONCAT('Forced password reset for ', COUNT(*), ' user(s).') AS migration_result
FROM users 
WHERE username != 'admin' AND password_must_be_reset = 1;
