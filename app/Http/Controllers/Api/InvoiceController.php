<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\EmailService;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('client')->orderBy('created_at', 'desc')->get();
        $invoices->transform(function ($i) {
            $i->company_name = $i->client->company_name ?? $i->client->contact_name;
            return $i;
        });
        return response()->json(['success' => true, 'data' => $invoices]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['invoice_number'] = Setting::where('setting_key', 'invoice_prefix')->value('setting_value') 
                                . Setting::where('setting_key', 'invoice_next_number')->value('setting_value');
        
        Setting::where('setting_key', 'invoice_next_number')->increment('setting_value');
        
        $subtotal = collect($data['items'])->sum(function($i) { return $i['quantity'] * $i['unit_price']; });
        $discountValue = $data['discount_value'] ?? 0;
        $discountAmount = $data['discount_type'] === 'percentage' ? ($subtotal * ($discountValue/100)) : $discountValue;
        $taxRate = $data['tax_rate'] ?? 0;
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate/100);
        $total = $subtotal - $discountAmount + $taxAmount;
        
        $data['subtotal'] = $subtotal;
        $data['discount_amount'] = $discountAmount;
        $data['tax_amount'] = $taxAmount;
        $data['total'] = $total;
        if ($request->user()) { $data['created_by'] = $request->user()->id; }

        $invoice = Invoice::create($data);

        foreach ($data['items'] as $idx => $item) {
            $item['amount'] = $item['quantity'] * $item['unit_price'];
            $item['sort_order'] = $idx;
            $invoice->items()->create($item);
        }

        return response()->json(['success' => true, 'invoice' => $invoice], 201);
    }

    public function show($id)
    {
        $invoice = Invoice::with(['client', 'items', 'payments'])->findOrFail($id);
        $invoice->company_name = $invoice->client->company_name ?? $invoice->client->contact_name;
        $invoice->email = $invoice->client->email;
        return response()->json($invoice);
    }

    public function addPayment(Request $request, $id)
    {
        $invoice = Invoice::with('client')->findOrFail($id);
        $amount = $request->amount;
        $invoice->payments()->create([
            'amount' => $amount,
            'payment_method' => $request->payment_method ?? 'other',
            'payment_date' => now()
        ]);
        
        $invoice->amount_paid += $amount;
        $wasPaid = false;
        if ($invoice->amount_paid >= $invoice->total) {
            $invoice->status = 'paid';
            $invoice->paid_at = now();
            $wasPaid = true;
        } else {
            $invoice->status = 'partial';
        }
        $invoice->save();

        // Send payment confirmation email when fully paid
        if ($wasPaid && !empty($invoice->client->email)) {
            try {
                $this->sendPaymentConfirmationEmail($invoice);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send payment confirmation for {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'paid' => $wasPaid]);
    }

    /**
     * Send a payment confirmation email with the updated PDF (showing PAGADA status).
     */
    private function sendPaymentConfirmationEmail(Invoice $invoice): void
    {
        $invoice->load(['client', 'items']);
        $settings = Setting::getAll();
        EmailService::applySmtpConfig($settings);

        $companyData = self::buildCompanyData($settings);
        $companyName = $settings['company_name'] ?? 'GridBase';

        // Generate PDF with "PAGADA" status
        $pdfData = [
            'invoice'  => $invoice->toArray(),
            'company'  => $companyData,
            'client'   => $invoice->client->toArray(),
            'items'    => $invoice->items->toArray(),
            'settings' => $settings,
        ];
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $pdfData);
        $pdfContent = $pdf->output();

        $subject = "✅ Pago confirmado — Factura {$invoice->invoice_number}";
        $filename = "Factura-{$invoice->invoice_number}-PAGADA.pdf";

        $emailData = [
            'subject'        => $subject,
            'logoUrl'        => 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png',
            'clientName'     => $invoice->client->contact_name,
            'companyName'    => $companyName,
            'companyEmail'   => $settings['company_email'] ?? '',
            'companyPhone'   => $settings['company_phone'] ?? '',
            'companyWebsite' => $settings['company_website'] ?? '',
            'isQuote'        => false,
            'docNumber'      => $invoice->invoice_number,
            'status'         => 'paid',
            'issueDate'      => date('d/m/Y', strtotime($invoice->issue_date)),
            'dueDate'        => date('d/m/Y', strtotime($invoice->due_date)),
            'items'          => $invoice->items->toArray(),
            'subtotal'       => $invoice->subtotal,
            'discountAmount' => $invoice->discount_amount ?? 0,
            'taxRate'        => $invoice->tax_rate ?? 0,
            'taxAmount'      => $invoice->tax_amount ?? 0,
            'total'          => $invoice->total,
            'currency'       => $invoice->currency ?? 'USD',
            'notes'          => '¡Gracias por su pago! Esta factura ha sido saldada en su totalidad.',
        ];

        $htmlBody = view('emails.document', $emailData)->render();

        \Illuminate\Support\Facades\Mail::html($htmlBody, function ($message) use ($invoice, $subject, $pdfContent, $filename) {
            $message->to($invoice->client->email)
                    ->subject($subject)
                    ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
        });

        \Illuminate\Support\Facades\Log::info("Payment confirmation sent for {$invoice->invoice_number} to {$invoice->client->email}");
    }

    public function pdf($id)
    {
        $invoice = Invoice::with(['client', 'items', 'payments'])->findOrFail($id);
        $settings = Setting::getAll();
        
        $data = [
            'invoice' => $invoice->toArray(),
            'company' => self::buildCompanyData($settings),
            'client' => $invoice->client->toArray(),
            'items' => $invoice->items->toArray(),
            'settings' => $settings
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $data);
        
        if (request()->has('download')) {
            return $pdf->download('Factura-' . $invoice->invoice_number . '.pdf');
        }
        return $pdf->stream('Factura-' . $invoice->invoice_number . '.pdf');
    }

    public function sendEmail($id)
    {
        $invoice = Invoice::with(['client', 'items'])->findOrFail($id);
        $settings = Setting::getAll();

        if (empty($invoice->client->email)) {
            return response()->json(['success' => false, 'error' => 'El cliente no tiene un correo electrónico configurado.'], 400);
        }

        try {
            // Apply SMTP settings from database using the centralized method
            EmailService::applySmtpConfig($settings);

            $companyData = self::buildCompanyData($settings);

            // Generate PDF
            $pdfData = [
                'invoice' => $invoice->toArray(),
                'company' => $companyData,
                'client' => $invoice->client->toArray(),
                'items' => $invoice->items->toArray(),
                'settings' => $settings
            ];
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $pdfData);
            $pdfContent = $pdf->output();

            // Build styled email data
            $subject = "Factura {$invoice->invoice_number} de " . ($settings['company_name'] ?? 'GridBase');
            $filename = "Factura-{$invoice->invoice_number}.pdf";

            $emailData = [
                'subject' => $subject,
                'logoUrl' => 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png',
                'clientName' => $invoice->client->contact_name,
                'companyName' => $settings['company_name'] ?? 'GridBase',
                'companyEmail' => $settings['company_email'] ?? '',
                'companyPhone' => $settings['company_phone'] ?? '',
                'companyWebsite' => $settings['company_website'] ?? '',
                'isQuote' => false,
                'docNumber' => $invoice->invoice_number,
                'status' => $invoice->status,
                'issueDate' => date('d/m/Y', strtotime($invoice->issue_date)),
                'dueDate' => date('d/m/Y', strtotime($invoice->due_date)),
                'items' => $invoice->items->toArray(),
                'subtotal' => $invoice->subtotal,
                'discountAmount' => $invoice->discount_amount ?? 0,
                'taxRate' => $invoice->tax_rate ?? 0,
                'taxAmount' => $invoice->tax_amount ?? 0,
                'total' => $invoice->total,
                'currency' => $invoice->currency ?? 'USD',
                'notes' => $invoice->notes ?? '',
            ];

            $htmlBody = view('emails.document', $emailData)->render();

            \Illuminate\Support\Facades\Mail::html($htmlBody, function ($message) use ($invoice, $subject, $pdfContent, $filename) {
                $message->to($invoice->client->email)
                        ->subject($subject)
                        ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
            });

            $invoice->status = 'sent';
            $invoice->sent_at = now();
            $invoice->sent_via = 'email';
            $invoice->save();

            \Illuminate\Support\Facades\Log::info("Invoice {$invoice->invoice_number} sent to {$invoice->client->email}");

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send invoice {$invoice->invoice_number}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Build company data array from settings for PDF/email rendering
     */
    public static function buildCompanyData(array $settings): array
    {
        return [
            'name' => $settings['company_name'] ?? 'GridBase',
            'email' => $settings['company_email'] ?? '',
            'phone' => $settings['company_phone'] ?? '',
            'address' => $settings['company_address'] ?? '',
            'city' => $settings['company_city'] ?? '',
            'country' => $settings['company_country'] ?? '',
            'tax_id' => $settings['company_tax_id'] ?? '',
            'website' => $settings['company_website'] ?? '',
        ];
    }
}
