-- Migration: Add password expiration fields to users table
-- This migration adds fields to support password expiration policy

ALTER TABLE users 
ADD COLUMN password_last_changed_at DATETIME DEFAULT NULL COMMENT 'Timestamp when password was last changed',
ADD COLUMN password_must_be_reset TINYINT(1) DEFAULT 0 COMMENT 'Flag to force password reset on next login';

-- Add index for performance on password expiration queries
ALTER TABLE users 
ADD INDEX idx_password_last_changed (password_last_changed_at);
