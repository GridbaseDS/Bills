<?php
/**
 * Gridbase Digital Solutions - Recurring Invoices Cron
 * Run this every hour via cPanel Cron Jobs
 * Example: 0 * * * * /usr/local/bin/php /home/user/public_html/cron/recurring.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Avoid timeout for large batches
set_time_limit(0);
ignore_user_abort(true);

echo "Starting recurring invoice generation...\n";

try {
    $service = new \App\Services\RecurringService();
    $results = $service->processDueInvoices();
    
    $generated = 0;
    $sent = 0;
    $errors = 0;
    
    foreach ($results as $res) {
        if (!empty($res['success'])) {
            $generated++;
            echo "Successfully generated invoice #{$res['invoice_id']} from recurring #{$res['recurring_id']}\n";
            if (!empty($res['sent'])) {
                $sent++;
                echo " - Sent automatically to client\n";
            }
        } else {
            $errors++;
            echo "Error generating for recurring #{$res['recurring_id']}: {$res['error']}\n";
        }
    }
    
    echo "\nSummary:\n";
    echo "- Generated: $generated\n";
    echo "- Auto-sent: $sent\n";
    echo "- Errors: $errors\n";
    echo "Done.\n";
    
} catch (\Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
