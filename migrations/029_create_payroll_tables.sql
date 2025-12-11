-- Migration: Create Payroll Tables
-- Manages employee payroll, salary structures, and payslips

CREATE TABLE IF NOT EXISTS `payroll_employees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_code` VARCHAR(50) NOT NULL UNIQUE,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255),
  `phone` VARCHAR(50),
  `national_id` VARCHAR(50),
  `department` VARCHAR(100),
  `position` VARCHAR(100),
  `hire_date` DATE,
  `bank_account` VARCHAR(100),
  `bank_name` VARCHAR(100),
  `tax_number` VARCHAR(50),
  `social_security_number` VARCHAR(50),
  `status` ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_code` (`employee_code`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_employee_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `payroll_salary_structures` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) NOT NULL,
  `basic_salary` DECIMAL(15,2) NOT NULL,
  `housing_allowance` DECIMAL(15,2) DEFAULT 0.00,
  `transport_allowance` DECIMAL(15,2) DEFAULT 0.00,
  `meal_allowance` DECIMAL(15,2) DEFAULT 0.00,
  `other_allowances` DECIMAL(15,2) DEFAULT 0.00,
  `currency` VARCHAR(3) DEFAULT 'RWF',
  `effective_from` DATE NOT NULL,
  `effective_to` DATE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_effective_dates` (`effective_from`, `effective_to`),
  CONSTRAINT `fk_salary_employee` FOREIGN KEY (`employee_id`) REFERENCES `payroll_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `payroll_period` VARCHAR(50) NOT NULL, -- e.g., '2025-01'
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `payment_date` DATE,
  `total_gross` DECIMAL(15,2) DEFAULT 0.00,
  `total_deductions` DECIMAL(15,2) DEFAULT 0.00,
  `total_net` DECIMAL(15,2) DEFAULT 0.00,
  `currency` VARCHAR(3) DEFAULT 'RWF',
  `status` ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
  `processed_by` INT(11),
  `approved_by` INT(11),
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payroll_period` (`payroll_period`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_payroll_processor` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_payroll_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `payroll_payslips` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` INT(11) NOT NULL,
  `employee_id` INT(11) NOT NULL,
  `basic_salary` DECIMAL(15,2) NOT NULL,
  `allowances` DECIMAL(15,2) DEFAULT 0.00,
  `gross_salary` DECIMAL(15,2) NOT NULL,
  `paye_tax` DECIMAL(15,2) DEFAULT 0.00,
  `social_security` DECIMAL(15,2) DEFAULT 0.00,
  `other_deductions` DECIMAL(15,2) DEFAULT 0.00,
  `total_deductions` DECIMAL(15,2) DEFAULT 0.00,
  `net_salary` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'RWF',
  `payment_method` VARCHAR(50),
  `payment_reference` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payroll_run_id` (`payroll_run_id`),
  KEY `idx_employee_id` (`employee_id`),
  CONSTRAINT `fk_payslip_run` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payslip_employee` FOREIGN KEY (`employee_id`) REFERENCES `payroll_employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
