<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class GeneratePaymentLink extends Command
{
    protected $signature = 'invoice:payment-link {invoice_id} {--days=30}';

    protected $description = 'Generate a unique payment link for an invoice';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoiceId = $this->argument('invoice_id');
        $days = $this->option('days');

        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            $this->error("Invoice with ID {$invoiceId} not found.");
            return 1;
        }

        // Generate payment token
        $invoice->generatePaymentToken($days);

        $this->info("Payment link generated successfully!");
        $this->newLine();
        
        $this->table(
            ['Field', 'Value'],
            [
                ['Invoice Number', $invoice->invoice_number],
                ['Client', $invoice->client->company_name ?: $invoice->client->contact_name],
                ['Amount', "{$invoice->currency} " . number_format($invoice->getRemainingBalance(), 2)],
                ['Status', $invoice->status],
                ['Expires At', $invoice->payment_token_expires_at->format('Y-m-d H:i:s')],
            ]
        );
        
        $this->newLine();
        $this->line("<fg=green>Payment URL:</>");
        $this->line("<href={$invoice->getPaymentUrl()}>{$invoice->getPaymentUrl()}</>");
        $this->newLine();

        return 0;
    }
}
