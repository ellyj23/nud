-- Migration: Create Email Templates Table
-- Stores customizable email templates for various communications

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `template_name` VARCHAR(100) NOT NULL UNIQUE,
  `template_type` ENUM('invoice', 'payment_reminder', 'receipt', 'welcome', 'password_reset', 'custom') NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_html` TEXT NOT NULL,
  `body_text` TEXT,
  `variables` TEXT, -- JSON array of available variables
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_template_type` (`template_type`),
  CONSTRAINT `fk_email_template_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default email templates
INSERT INTO `email_templates` (`template_name`, `template_type`, `subject`, `body_html`, `body_text`, `variables`) VALUES
(
  'invoice_notification',
  'invoice',
  'Invoice #{invoice_number} from Feza Logistics',
  '<h2>Invoice #{invoice_number}</h2><p>Dear {client_name},</p><p>Please find attached your invoice for {amount} {currency}.</p><p>Due Date: {due_date}</p><p>Thank you for your business!</p>',
  'Invoice #{invoice_number}\n\nDear {client_name},\n\nPlease find attached your invoice for {amount} {currency}.\n\nDue Date: {due_date}\n\nThank you for your business!',
  '["invoice_number", "client_name", "amount", "currency", "due_date"]'
),
(
  'payment_reminder',
  'payment_reminder',
  'Payment Reminder - Invoice #{invoice_number}',
  '<h2>Payment Reminder</h2><p>Dear {client_name},</p><p>This is a friendly reminder that invoice #{invoice_number} for {amount} {currency} is due on {due_date}.</p><p>Please make payment at your earliest convenience.</p>',
  'Payment Reminder\n\nDear {client_name},\n\nThis is a friendly reminder that invoice #{invoice_number} for {amount} {currency} is due on {due_date}.\n\nPlease make payment at your earliest convenience.',
  '["invoice_number", "client_name", "amount", "currency", "due_date"]'
),
(
  'payment_receipt',
  'receipt',
  'Payment Receipt - {receipt_number}',
  '<h2>Payment Received</h2><p>Dear {client_name},</p><p>Thank you for your payment of {amount} {currency}.</p><p>Receipt Number: {receipt_number}</p><p>Date: {payment_date}</p>',
  'Payment Received\n\nDear {client_name},\n\nThank you for your payment of {amount} {currency}.\n\nReceipt Number: {receipt_number}\nDate: {payment_date}',
  '["receipt_number", "client_name", "amount", "currency", "payment_date"]'
);
