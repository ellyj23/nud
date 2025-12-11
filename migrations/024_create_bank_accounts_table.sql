-- Migration: Create Bank Accounts and Transactions Tables
-- For bank integration, reconciliation, and automated transaction imports

CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `account_name` VARCHAR(255) NOT NULL,
  `bank_name` VARCHAR(255) NOT NULL,
  `account_number` VARCHAR(100) NOT NULL,
  `account_type` ENUM('checking', 'savings', 'credit_card', 'other') DEFAULT 'checking',
  `currency` VARCHAR(3) DEFAULT 'RWF',
  `current_balance` DECIMAL(15,2) DEFAULT 0.00,
  `opening_balance` DECIMAL(15,2) DEFAULT 0.00,
  `opening_date` DATE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `last_sync_at` TIMESTAMP NULL,
  `integration_type` VARCHAR(50), -- 'plaid', 'yodlee', 'manual', etc.
  `integration_id` VARCHAR(255), -- External bank account ID
  `integration_data` TEXT, -- JSON configuration
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_account_number` (`account_number`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_bank_account_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bank_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bank_account_id` INT(11) NOT NULL,
  `transaction_date` DATE NOT NULL,
  `transaction_type` ENUM('credit', 'debit') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'RWF',
  `description` TEXT,
  `reference_number` VARCHAR(255),
  `payee_payer` VARCHAR(255),
  `category` VARCHAR(100),
  `is_reconciled` BOOLEAN DEFAULT FALSE,
  `reconciled_with_id` INT(11), -- ID from transactions table
  `reconciled_at` TIMESTAMP NULL,
  `reconciled_by` INT(11),
  `external_transaction_id` VARCHAR(255), -- From bank API
  `imported_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bank_account_id` (`bank_account_id`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_is_reconciled` (`is_reconciled`),
  KEY `idx_external_id` (`external_transaction_id`),
  CONSTRAINT `fk_bank_transaction_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bank_transaction_reconciler` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bank_reconciliation_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bank_account_id` INT(11) NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `statement_balance` DECIMAL(15,2),
  `book_balance` DECIMAL(15,2),
  `difference` DECIMAL(15,2),
  `status` ENUM('in_progress', 'completed', 'reviewed') DEFAULT 'in_progress',
  `reconciled_by` INT(11),
  `reviewed_by` INT(11),
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bank_account_id` (`bank_account_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_reconciliation_account` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reconciliation_user` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reconciliation_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
