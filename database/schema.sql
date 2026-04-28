-- ============================================================
-- Gridbase Digital Solutions - Invoice System
-- Database Schema
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "-04:00";

CREATE DATABASE IF NOT EXISTS `grupaqgl_bills` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `grupaqgl_bills`;

-- -----------------------------------------------------------
-- Table: users
-- -----------------------------------------------------------
CREATE TABLE `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','editor','viewer') NOT NULL DEFAULT 'admin',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: clients
-- -----------------------------------------------------------
CREATE TABLE `clients` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_name` VARCHAR(200) DEFAULT NULL,
    `contact_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `whatsapp` VARCHAR(30) DEFAULT NULL,
    `tax_id` VARCHAR(50) DEFAULT NULL,
    `address_line1` VARCHAR(255) DEFAULT NULL,
    `address_line2` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'Republica Dominicana',
    `notes` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_clients_email` (`email`),
    KEY `idx_clients_company` (`company_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: invoices
-- -----------------------------------------------------------
CREATE TABLE `invoices` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_number` VARCHAR(30) NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `status` ENUM('draft','sent','viewed','paid','partial','overdue','cancelled') NOT NULL DEFAULT 'draft',
    `issue_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_type` ENUM('percentage','fixed') DEFAULT NULL,
    `discount_value` DECIMAL(12,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
    `notes` TEXT DEFAULT NULL,
    `terms` TEXT DEFAULT NULL,
    `pdf_path` VARCHAR(255) DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `sent_via` VARCHAR(20) DEFAULT NULL,
    `viewed_at` DATETIME DEFAULT NULL,
    `paid_at` DATETIME DEFAULT NULL,
    `recurring_id` INT UNSIGNED DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_invoices_number` (`invoice_number`),
    KEY `idx_invoices_client` (`client_id`),
    KEY `idx_invoices_status` (`status`),
    KEY `idx_invoices_due` (`due_date`),
    KEY `idx_invoices_recurring` (`recurring_id`),
    CONSTRAINT `fk_invoices_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_invoices_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: invoice_items
-- -----------------------------------------------------------
CREATE TABLE `invoice_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` INT UNSIGNED NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `sort_order` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_items_invoice` (`invoice_id`),
    CONSTRAINT `fk_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: quotes
-- -----------------------------------------------------------
CREATE TABLE `quotes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `quote_number` VARCHAR(30) NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `status` ENUM('draft','sent','viewed','accepted','rejected','expired','converted') NOT NULL DEFAULT 'draft',
    `issue_date` DATE NOT NULL,
    `expiry_date` DATE NOT NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_type` ENUM('percentage','fixed') DEFAULT NULL,
    `discount_value` DECIMAL(12,2) DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) DEFAULT 0.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
    `notes` TEXT DEFAULT NULL,
    `terms` TEXT DEFAULT NULL,
    `converted_invoice_id` INT UNSIGNED DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `sent_via` VARCHAR(20) DEFAULT NULL,
    `viewed_at` DATETIME DEFAULT NULL,
    `pdf_path` VARCHAR(255) DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_quotes_number` (`quote_number`),
    KEY `idx_quotes_client` (`client_id`),
    KEY `idx_quotes_status` (`status`),
    CONSTRAINT `fk_quotes_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_quotes_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: quote_items
-- -----------------------------------------------------------
CREATE TABLE `quote_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `quote_id` INT UNSIGNED NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `sort_order` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_qitems_quote` (`quote_id`),
    CONSTRAINT `fk_qitems_quote` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: recurring_invoices
-- -----------------------------------------------------------
CREATE TABLE `recurring_invoices` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_id` INT UNSIGNED NOT NULL,
    `frequency` ENUM('weekly','biweekly','monthly','quarterly','semiannual','annual') NOT NULL DEFAULT 'monthly',
    `status` ENUM('active','paused','completed','cancelled') NOT NULL DEFAULT 'active',
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `next_issue_date` DATE NOT NULL,
    `occurrences_limit` INT DEFAULT NULL,
    `occurrences_count` INT NOT NULL DEFAULT 0,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `tax_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
    `auto_send` TINYINT(1) NOT NULL DEFAULT 0,
    `send_via` ENUM('email','whatsapp','both') DEFAULT 'email',
    `notes` TEXT DEFAULT NULL,
    `terms` TEXT DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_recurring_client` (`client_id`),
    KEY `idx_recurring_status` (`status`),
    KEY `idx_recurring_next` (`next_issue_date`),
    CONSTRAINT `fk_recurring_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_recurring_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: recurring_invoice_items
-- -----------------------------------------------------------
CREATE TABLE `recurring_invoice_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recurring_id` INT UNSIGNED NOT NULL,
    `description` VARCHAR(500) NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `sort_order` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_ritems_recurring` (`recurring_id`),
    CONSTRAINT `fk_ritems_recurring` FOREIGN KEY (`recurring_id`) REFERENCES `recurring_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: payments
-- -----------------------------------------------------------
CREATE TABLE `payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` ENUM('bank_transfer','cash','check','credit_card','paypal','other') NOT NULL DEFAULT 'bank_transfer',
    `payment_date` DATE NOT NULL,
    `reference` VARCHAR(100) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payments_invoice` (`invoice_id`),
    CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: activity_log
-- -----------------------------------------------------------
CREATE TABLE `activity_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` ENUM('invoice','quote','client','recurring','payment','system') NOT NULL,
    `entity_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` VARCHAR(500) DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_entity` (`entity_type`, `entity_id`),
    KEY `idx_activity_date` (`created_at`),
    CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Table: settings
-- -----------------------------------------------------------
CREATE TABLE `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_key` (`setting_key`),
    KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- Add FK for recurring_id in invoices
-- -----------------------------------------------------------
ALTER TABLE `invoices`
    ADD CONSTRAINT `fk_invoices_recurring` FOREIGN KEY (`recurring_id`) REFERENCES `recurring_invoices` (`id`) ON DELETE SET NULL;

-- -----------------------------------------------------------
-- Default settings
-- -----------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('company_name', 'Gridbase Digital Solutions', 'company'),
('company_email', 'bills@gridbase.com.do', 'company'),
('company_phone', '', 'company'),
('company_address', '', 'company'),
('company_city', '', 'company'),
('company_country', '', 'company'),
('company_tax_id', '', 'company'),
('company_website', 'https://gridbase.com.do', 'company'),
('default_currency', 'USD', 'invoice'),
('default_tax_rate', '0.00', 'invoice'),
('tax_label', 'Tax', 'invoice'),
('invoice_prefix', 'GBS-', 'invoice'),
('invoice_next_number', '1001', 'invoice'),
('quote_prefix', 'QUO-', 'invoice'),
('quote_next_number', '1001', 'invoice'),
('default_due_days', '30', 'invoice'),
('default_quote_validity', '15', 'invoice'),
('default_notes', '', 'invoice'),
('default_terms', 'Payment is due within the specified due date.', 'invoice'),
('smtp_host', '', 'email'),
('smtp_port', '587', 'email'),
('smtp_username', '', 'email'),
('smtp_password', '', 'email'),
('smtp_encryption', 'tls', 'email'),
('smtp_from_name', 'Gridbase Digital Solutions', 'email'),
('smtp_from_email', '', 'email'),
('whatsapp_access_token', '', 'whatsapp'),
('whatsapp_phone_id', '', 'whatsapp'),
('whatsapp_business_id', '', 'whatsapp'),
('whatsapp_enabled', '0', 'whatsapp');

-- -----------------------------------------------------------
-- Default admin user (password: admin123 -- CHANGE ON FIRST LOGIN)
-- -----------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin', 'admin@gridbase.com.do', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

COMMIT;
