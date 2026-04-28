<?php
/**
 * Gridbase Digital Solutions - Reminders Cron
 * Run this daily at 8am via cPanel Cron Jobs
 * Example: 0 8 * * * /usr/local/bin/php /home/user/public_html/cron/reminders.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

set_time_limit(0);
ignore_user_abort(true);

echo "Starting reminder processing...\n";

try {
    $invoiceModel = new \App\Models\Invoice();
    $emailService = new \App\Services\EmailService();
    $waService = new \App\Services\WhatsAppService();
    $settingsModel = new \App\Models\Setting();
    
    // Get Reminder Settings
    $remindersEnabled = (bool) ($settingsModel->get('reminders_enabled') ?? true);
    $daysBefore = (int) ($settingsModel->get('reminders_days_before') ?? 3);
    $overdueInterval = (int) ($settingsModel->get('reminders_overdue_interval') ?? 7);

    if (!$remindersEnabled) {
        echo "Reminders are globally disabled in settings.\n";
        exit;
    }

    // Remind for invoices due in X days
    $dueSoon = $invoiceModel->getDueSoon($daysBefore);
    $sentCount = 0;
    
    foreach ($dueSoon as $invoice) {
        // Only send if due exactly in X days to avoid spamming every day
        $dueDays = (strtotime($invoice['due_date']) - time()) / 86400;
        if ($dueDays > ($daysBefore - 1) && $dueDays <= $daysBefore) {
            echo "Sending upcoming reminder for Invoice {$invoice['invoice_number']}...\n";
            $emailService->sendReminder($invoice);
            if (!empty($invoice['client_whatsapp'])) {
                $waService->sendPaymentReminder($invoice, $invoice['client_whatsapp']);
            }
            $sentCount++;
        }
    }
    
    // Remind for overdue invoices
    $overdue = $invoiceModel->getOverdue();
    
    foreach ($overdue as $invoice) {
        $overdueDays = floor((time() - strtotime($invoice['due_date'])) / 86400);
        // Send on day 1, and then every $overdueInterval days. Prevent division by zero.
        $interval = max(1, $overdueInterval);
        if ($overdueDays > 0 && ($overdueDays % $interval === 1 || $overdueDays === 1)) {
            echo "Sending overdue reminder for Invoice {$invoice['invoice_number']}...\n";
            $emailService->sendReminder($invoice);
            if (!empty($invoice['client_whatsapp'])) {
                $waService->sendPaymentReminder($invoice, $invoice['client_whatsapp']);
            }
            $sentCount++;
        }
    }
    
    echo "\nSummary: Sent $sentCount reminders.\n";
    echo "Done.\n";
    
} catch (\Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
