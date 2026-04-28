<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Setting;
use Carbon\Carbon;
use App\Services\EmailService;
use App\Services\WhatsAppService;

class SendPaymentReminders extends Command
{
    protected $signature = 'bills:send-reminders';
    protected $description = 'Send automatic payment reminders for due/overdue invoices';

    public function handle(EmailService $emailService, WhatsAppService $whatsAppService)
    {
        $enabled = Setting::where('setting_key', 'reminders_enabled')->value('setting_value');
        if (!$enabled) {
            $this->info('Reminders are disabled in settings. Exiting.');
            return;
        }

        $daysBefore = (int) Setting::where('setting_key', 'reminders_days_before')->value('setting_value') ?? 3;
        $overdueInterval = (int) Setting::where('setting_key', 'reminders_overdue_interval')->value('setting_value') ?? 7;

        $today = Carbon::today();
        $upcomingDate = $today->copy()->addDays($daysBefore);

        // Upcoming Invoices
        $upcomingInvoices = Invoice::with('client')
            ->whereIn('status', ['sent', 'viewed', 'partial'])
            ->where('due_date', $upcomingDate->format('Y-m-d'))
            ->get();

        foreach ($upcomingInvoices as $invoice) {
            $this->info("Sending upcoming reminder for {->invoice_number}");
            $emailService->sendReminder($invoice);
            if ($whatsAppService->isEnabled()) {
                $whatsAppService->sendPaymentReminder($invoice, $invoice->client->whatsapp ?? $invoice->client->phone);
            }
        }

        // Overdue Invoices
        $overdueInvoices = Invoice::with('client')
            ->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->where('due_date', '<', $today->format('Y-m-d'))
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = $today->diffInDays(Carbon::parse($invoice->due_date));
            if ($overdueInterval > 0 && $daysOverdue % $overdueInterval === 0 && $daysOverdue > 0) {
                $this->info("Sending overdue reminder for {->invoice_number} ({} days overdue)");
                $invoice->update(['status' => 'overdue']);
                $emailService->sendReminder($invoice);
                if ($whatsAppService->isEnabled()) {
                    $whatsAppService->sendPaymentReminder($invoice, $invoice->client->whatsapp ?? $invoice->client->phone);
                }
            }
        }

        $this->info('Reminders processed.');
    }
}
