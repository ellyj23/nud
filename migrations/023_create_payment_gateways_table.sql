-- Migration: Create Payment Gateways Configuration Table
-- Stores payment gateway settings and credentials

CREATE TABLE IF NOT EXISTS `payment_gateways` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `gateway_name` VARCHAR(50) NOT NULL,
  `gateway_type` ENUM('stripe', 'paypal', 'mobile_money', 'bank_transfer') NOT NULL,
  `is_active` BOOLEAN DEFAULT FALSE,
  `is_test_mode` BOOLEAN DEFAULT TRUE,
  `public_key` TEXT,
  `secret_key` TEXT,
  `webhook_secret` TEXT,
  `config_data` TEXT, -- JSON configuration
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_gateway_name` (`gateway_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `gateway_id` INT(11) NOT NULL,
  `invoice_id` INT(11),
  `client_id` INT(11),
  `transaction_ref` VARCHAR(255) NOT NULL,
  `gateway_transaction_id` VARCHAR(255),
  `amount` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'RWF',
  `status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `payment_method` VARCHAR(50),
  `customer_email` VARCHAR(255),
  `customer_phone` VARCHAR(50),
  `metadata` TEXT, -- JSON metadata
  `error_message` TEXT,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_ref` (`transaction_ref`),
  KEY `idx_gateway_transaction_id` (`gateway_transaction_id`),
  KEY `idx_invoice_id` (`invoice_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_payment_gateway` FOREIGN KEY (`gateway_id`) REFERENCES `payment_gateways` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_payment_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default payment gateway configurations
INSERT INTO `payment_gateways` (`gateway_name`, `gateway_type`, `is_active`, `is_test_mode`) VALUES
('Stripe', 'stripe', FALSE, TRUE),
('PayPal', 'paypal', FALSE, TRUE),
('MTN Mobile Money', 'mobile_money', FALSE, TRUE),
('Airtel Money', 'mobile_money', FALSE, TRUE);
