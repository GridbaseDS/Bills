<?php
namespace App\Services;

use App\Models\Database;
use App\Models\Invoice;
use App\Models\RecurringInvoice;

/**
 * Service to process recurring invoices - called by cron job.
 */
class RecurringService
{
    private RecurringInvoice $recurringModel;
    private Invoice $invoiceModel;
    private EmailService $emailService;
    private WhatsAppService $whatsAppService;
    private PDFService $pdfService;

    public function __construct()
    {
        $this->recurringModel  = new RecurringInvoice();
        $this->invoiceModel    = new Invoice();
        $this->emailService    = new EmailService();
        $this->whatsAppService = new WhatsAppService();
        $this->pdfService      = new PDFService();
    }

    /**
     * Process all due recurring invoices. Returns a summary of actions taken.
     */
    public function processDueInvoices(): array
    {
        $due = $this->recurringModel->getDueForGeneration();
        $results = [];

        foreach ($due as $recurring) {
            try {
                $result = $this->generateFromRecurring($recurring);
                $results[] = $result;
            } catch (\Exception $e) {
                $results[] = [
                    'recurring_id' => $recurring['id'],
                    'success'      => false,
                    'error'        => $e->getMessage(),
                ];
                $this->logError("Recurring #{$recurring['id']}: {$e->getMessage()}");
            }
        }

        return $results;
    }

    /**
     * Generate a single invoice from a recurring configuration.
     */
    private function generateFromRecurring(array $recurring): array
    {
        // Get items from recurring template
        $items = Database::getInstance()->fetchAll(
            "SELECT description, quantity, unit_price FROM recurring_invoice_items WHERE recurring_id = ? ORDER BY sort_order",
            [$recurring['id']]
        );

        if (empty($items)) {
            throw new \Exception('No items found for recurring invoice');
        }

        // Create the invoice
        $invoiceId = $this->invoiceModel->create([
            'client_id'  => $recurring['client_id'],
            'tax_rate'   => $recurring['tax_rate'],
            'currency'   => $recurring['currency'],
            'notes'      => $recurring['notes'],
            'terms'      => $recurring['terms'],
            'created_by' => $recurring['created_by'],
        ], $items);

        // Link to recurring
        Database::getInstance()->update('invoices', ['recurring_id' => $recurring['id']], ['id' => $invoiceId]);

        // Calculate next date and increment
        $nextDate = $this->recurringModel->calculateNextDate($recurring['frequency'], $recurring['next_issue_date']);
        $this->recurringModel->incrementCount($recurring['id'], $nextDate);

        // Check if limit reached
        if ($recurring['occurrences_limit'] && ($recurring['occurrences_count'] + 1) >= $recurring['occurrences_limit']) {
            $this->recurringModel->toggleStatus($recurring['id'], 'completed');
        }

        $result = [
            'recurring_id' => $recurring['id'],
            'invoice_id'   => $invoiceId,
            'success'      => true,
            'sent'         => false,
        ];

        // Auto-send if configured
        if ($recurring['auto_send']) {
            $invoice = $this->invoiceModel->getById($invoiceId);
            $pdfPath = $this->pdfService->generateInvoice($invoice);

            $sendVia = $recurring['send_via'] ?? 'email';

            if ($sendVia === 'email' || $sendVia === 'both') {
                $emailResult = $this->emailService->sendInvoice($invoice, $pdfPath);
                $result['email'] = $emailResult;
            }

            if (($sendVia === 'whatsapp' || $sendVia === 'both') && !empty($recurring['client_whatsapp'])) {
                $waResult = $this->whatsAppService->sendInvoiceNotification($invoice, $recurring['client_whatsapp']);
                $result['whatsapp'] = $waResult;
            }

            if (!empty($emailResult['success']) || !empty($waResult['success'])) {
                $this->invoiceModel->markSent($invoiceId, $sendVia);
                $result['sent'] = true;
            }
        }

        // Log activity
        Database::getInstance()->insert('activity_log', [
            'entity_type' => 'recurring',
            'entity_id'   => $recurring['id'],
            'action'      => 'invoice_generated',
            'description' => "Recurring: Invoice #$invoiceId generated automatically",
        ]);

        return $result;
    }

    private function logError(string $message): void
    {
        $logPath = __DIR__ . '/../../storage/logs/recurring.log';
        $dir = dirname($logPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n", FILE_APPEND);
    }
}
