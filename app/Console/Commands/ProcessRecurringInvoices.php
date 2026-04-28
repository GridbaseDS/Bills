<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecurringInvoice;
use App\Models\Setting;
use App\Models\Invoice;
use Carbon\Carbon;
use App\Services\EmailService;
use App\Services\WhatsAppService;

class ProcessRecurringInvoices extends Command
{
    protected $signature = 'bills:process-recurring';
    protected $description = 'Process all active recurring invoices due today';

    public function handle(EmailService $emailService, WhatsAppService $whatsAppService)
    {
        $this->info('Starting recurring invoices processing...');
        $today = Carbon::today();

        $dueInvoices = RecurringInvoice::with('items')
            ->where('status', 'active')
            ->where('next_issue_date', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $today);
            })
            ->get();

        foreach ($dueInvoices as $recurring) {
            // Check occurrence limits
            if ($recurring->occurrences_limit && $recurring->occurrences_count >= $recurring->occurrences_limit) {
                $recurring->update(['status' => 'completed']);
                continue;
            }

            // Create new invoice
            $issueDate = Carbon::parse($recurring->next_issue_date);
            $dueDate = $issueDate->copy()->addDays(Setting::where('setting_key', 'default_due_days')->value('setting_value') ?? 30);
            
            $invoiceNumber = Setting::where('setting_key', 'invoice_prefix')->value('setting_value') 
                . Setting::where('setting_key', 'invoice_next_number')->value('setting_value');
            Setting::where('setting_key', 'invoice_next_number')->increment('setting_value');

            // Calculate total
            $subtotal = $recurring->items->sum(function($i) { return $i->quantity * $i->unit_price; });
            $taxRate = $recurring->tax_rate ?? 0;
            $taxAmount = $subtotal * ($taxRate / 100);
            $total = $subtotal + $taxAmount;

            $newInvoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'client_id'      => $recurring->client_id,
                'status'         => 'draft',
                'issue_date'     => $issueDate,
                'due_date'       => $dueDate,
                'subtotal'       => $subtotal,
                'tax_rate'       => $taxRate,
                'tax_amount'     => $taxAmount,
                'total'          => $total,
                'currency'       => $recurring->currency,
                'notes'          => $recurring->notes,
                'terms'          => $recurring->terms,
                'recurring_id'   => $recurring->id,
                'created_by'     => $recurring->created_by,
            ]);

            // Copy items
            foreach ($recurring->items as $item) {
                $newInvoice->items()->create([
                    'description' => $item->description,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unit_price,
                    'amount'      => $item->amount,
                    'sort_order'  => $item->sort_order,
                ]);
            }

            // Automate sending?
            if ($recurring->auto_send) {
                $newInvoice->update(['status' => 'sent']);
                // Assuming PDF is generated on the fly, but we can call EmailService to send it
                if (in_array($recurring->send_via, ['email', 'both'])) {
                    // $emailService->sendInvoice($newInvoice, ''); // Skipping PDF for now or generate manually
                }
                if (in_array($recurring->send_via, ['whatsapp', 'both'])) {
                    if ($whatsAppService->isEnabled()) {
                        $whatsAppService->sendInvoiceNotification($newInvoice, $newInvoice->client->whatsapp ?? $newInvoice->client->phone);
                    }
                }
            }

            // Update recurring schedule
            $nextDate = $issueDate->copy();
            switch ($recurring->frequency) {
                case 'weekly': $nextDate->addWeek(); break;
                case 'biweekly': $nextDate->addWeeks(2); break;
                case 'monthly': $nextDate->addMonth(); break;
                case 'quarterly': $nextDate->addMonths(3); break;
                case 'semiannual': $nextDate->addMonths(6); break;
                case 'annual': $nextDate->addYear(); break;
            }
            
            $recurring->update([
                'next_issue_date' => $nextDate,
                'occurrences_count' => $recurring->occurrences_count + 1
            ]);

            $this->info("Created invoice $invoiceNumber for recurring #id $recurring->id");
        }

        $this->info('Done processing recurring invoices.');
    }
}
