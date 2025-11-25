-- Migration: 023_add_accountant_transaction_permissions.sql
-- Purpose: Add transaction-related permissions to the Accountant role
-- Date: 2025-11-25
-- Description: Enables Accountant role to create, edit, and optionally delete transactions

-- Step 1: Add transaction permissions if they don't exist
INSERT INTO `permissions` (`name`, `description`) VALUES
  ('create-transaction', 'Create new transactions in the system'),
  ('edit-transaction', 'Edit existing transactions'),
  ('delete-transaction', 'Delete transactions from the system')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- Step 2: Assign transaction permissions to Super Admin role (if not already assigned)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Super Admin'
  AND p.name IN ('create-transaction', 'edit-transaction', 'delete-transaction')
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp 
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

-- Step 3: Assign transaction permissions to Admin role (if not already assigned)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Admin'
  AND p.name IN ('create-transaction', 'edit-transaction', 'delete-transaction')
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp 
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

-- Step 4: Assign transaction permissions to Accountant role
-- This is the key change - enabling Accountant to manage transactions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id AS role_id, p.id AS permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.name = 'Accountant'
  AND p.name IN ('create-transaction', 'edit-transaction', 'view-transactions')
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp 
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

-- Note: delete-transaction is NOT assigned to Accountant by default for safety reasons.
-- Super Admin can manually assign it via manage_roles.php if needed.
