-- ============================================================
-- Gridbase Digital Solutions - Seed Data
-- Import this file AFTER schema.sql
-- ============================================================

USE `grupaqgl_bills`;

-- 1. Dummy Clients
INSERT INTO `clients` (`company_name`, `contact_name`, `email`, `phone`, `whatsapp`, `tax_id`, `address_line1`, `city`, `country`, `is_active`) VALUES
('Acme Corp', 'John Doe', 'john@example.com', '+18095550100', '18095550100', '101-123456-1', '123 Business Ave', 'Santo Domingo', 'Republica Dominicana', 1),
('TechStart Inc.', 'Jane Smith', 'jane@techstart.local', '+18095550200', '18095550200', '101-987654-2', '456 Innovation Blvd', 'Santiago', 'Republica Dominicana', 1),
(NULL, 'Freelancer Bob', 'bob@example.com', NULL, '18295550300', NULL, NULL, 'Punta Cana', 'Republica Dominicana', 1);

-- 2. Dummy Invoices
INSERT INTO `invoices` (`invoice_number`, `client_id`, `status`, `issue_date`, `due_date`, `subtotal`, `tax_rate`, `tax_amount`, `total`, `amount_paid`, `currency`, `created_by`) VALUES
('GBS-1001', 1, 'paid', '2026-04-01', '2026-04-15', 1500.00, 18.00, 270.00, 1770.00, 1770.00, 'USD', 1),
('GBS-1002', 2, 'sent', '2026-04-20', '2026-05-20', 2500.00, 18.00, 450.00, 2950.00, 0.00, 'USD', 1),
('GBS-1003', 3, 'overdue', '2026-03-15', '2026-04-15', 500.00, 0.00, 0.00, 500.00, 0.00, 'USD', 1);

INSERT INTO `invoice_items` (`invoice_id`, `description`, `quantity`, `unit_price`, `amount`, `sort_order`) VALUES
(1, 'Web Development - Landing Page', 1.00, 1500.00, 1500.00, 0),
(2, 'E-commerce Setup', 1.00, 2000.00, 2000.00, 0),
(2, 'Payment Gateway Integration', 1.00, 500.00, 500.00, 1),
(3, 'SEO Audit', 1.00, 500.00, 500.00, 0);

-- 3. Payments
INSERT INTO `payments` (`invoice_id`, `amount`, `payment_method`, `payment_date`, `reference`) VALUES
(1, 1770.00, 'bank_transfer', '2026-04-10', 'TRX-998877');

-- 4. Activity Log
INSERT INTO `activity_log` (`entity_type`, `entity_id`, `action`, `description`, `user_id`) VALUES
('invoice', 1, 'created', 'Invoice GBS-1001 created', 1),
('invoice', 1, 'payment_received', 'Payment of 1770.00 received for invoice #1', 1),
('invoice', 2, 'created', 'Invoice GBS-1002 created', 1),
('invoice', 3, 'created', 'Invoice GBS-1003 created', 1);

-- Update next number setting
UPDATE `settings` SET `setting_value` = '1004' WHERE `setting_key` = 'invoice_next_number';
