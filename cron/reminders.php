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
    
    // Remind for invoices due in 3 days
    $dueSoon = $invoiceModel->getDueSoon(3);
    $sentCount = 0;
    
    foreach ($dueSoon as $invoice) {
        // Only send if due exactly in 3 days to avoid spamming every day
        $dueDays = (strtotime($invoice['due_date']) - time()) / 86400;
        if ($dueDays > 2 && $dueDays <= 3) {
            echo "Sending upcoming reminder for Invoice {$invoice['invoice_number']}...\n";
            $emailService->sendReminder($invoice);
            if (!empty($invoice['client_whatsapp'])) {
                $waService->sendPaymentReminder($invoice, $invoice['client_whatsapp']);
            }
            $sentCount++;
        }
    }
    
    // Remind for overdue invoices (send once a week if overdue)
    $overdue = $invoiceModel->getOverdue();
    
    foreach ($overdue as $invoice) {
        $overdueDays = floor((time() - strtotime($invoice['due_date'])) / 86400);
        // Send on day 1, day 7, day 14, etc.
        if ($overdueDays > 0 && ($overdueDays % 7 === 1 || $overdueDays === 1)) {
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
