<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\EmailService;
use App\Services\Dgii\EcfManagerService;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('client')->orderBy('created_at', 'desc')->limit(200)->get();
        $invoices->transform(function ($i) {
            $i->company_name = $i->client ? ($i->client->company_name ?? $i->client->contact_name) : 'Sin cliente';
            return $i;
        });
        return response()->json(['success' => true, 'data' => $invoices]);
    }

    public function store(Request $request, EcfManagerService $ecfManager)
    {
        $data = $request->all();
        $clientId = $data['client_id'] ?? null;
        $isEcf = (int)($data['is_ecf'] ?? 0) === 1;
        $ecfType = (int)($data['ecf_type'] ?? 0);

        if (empty($clientId)) {
            if ($isEcf && $ecfType === 32) {
                $defaultClient = \App\Models\Client::firstOrCreate(
                    ['email' => 'consumidorfinal@bills.gridbase.com.do'],
                    [
                        'company_name' => 'Consumidor Final',
                        'contact_name' => 'Consumidor Final',
                        'phone' => '',
                        'tax_id' => '',
                        'address_line1' => 'Santo Domingo, Rep. Dom.',
                        'country' => 'Republica Dominicana',
                        'is_active' => true,
                    ]
                );
                $clientId = $defaultClient->id;
            } else {
                return response()->json(['success' => false, 'error' => 'El cliente es obligatorio para este tipo de factura.'], 400);
            }
        }
        $data['client_id'] = $clientId;

        if (empty($data['exchange_rate'])) {
            $data['exchange_rate'] = \App\Services\CurrencyConverter::getConversionRate($data['currency'] ?? 'DOP', 'DOP');
        }
        $prefix = Setting::where('setting_key', 'invoice_prefix')->value('setting_value') ?? 'FAC-';
        $nextNum = (int)(Setting::where('setting_key', 'invoice_next_number')->value('setting_value') ?? 1);
        $invoiceNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        
        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $nextNum++;
            $invoiceNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        }
        
        $data['invoice_number'] = $invoiceNumber;
        Setting::where('setting_key', 'invoice_next_number')->update(['setting_value' => $nextNum + 1]);
        
        $subtotal = collect($data['items'])->sum(function($i) { return $i['quantity'] * $i['unit_price']; });
        $discountValue = $data['discount_value'] ?? 0;
        $discountType = $data['discount_type'] ?? 'percentage';
        $discountAmount = $discountType === 'percentage' ? ($subtotal * ($discountValue/100)) : $discountValue;
        $taxRate = $data['tax_rate'] ?? 0;
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate/100);
        $total = $subtotal - $discountAmount + $taxAmount;
        
        $data['subtotal'] = $subtotal;
        $data['discount_amount'] = $discountAmount;
        $data['tax_amount'] = $taxAmount;
        $data['total'] = $total;
        $data['status'] = 'sent';
        if ($request->user()) { $data['created_by'] = $request->user()->id; }

        $invoice = Invoice::create($data);

        foreach ($data['items'] as $idx => $item) {
            $item['amount'] = $item['quantity'] * $item['unit_price'];
            $item['sort_order'] = $idx;
            $invoice->items()->create($item);
        }

        if ($invoice->is_ecf) {
            try {
                $ecfManager->processInvoice($invoice);
                $invoice->refresh();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("DGII auto-processing failed on store: " . $e->getMessage());
            }
        }

        // Auto-send to client
        $sent = false;
        $invoice->load('client');
        if ($invoice->client && !empty($invoice->client->email)) {
            try {
                $this->autoSendInvoiceEmail($invoice);
                $sent = true;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Auto-send failed for {$invoice->invoice_number}: " . $e->getMessage());
            }
        } elseif ($invoice->client && !empty($invoice->client->whatsapp)) {
            // Client has no email but has WhatsApp - send via WhatsApp only
            try {
                $whatsappService = new \App\Services\WhatsAppService();
                if ($whatsappService->isEnabled()) {
                    $invoice->load('items');
                    if (!$invoice->isPaymentTokenValid()) {
                        $invoice->generatePaymentToken();
                    }
                    $paymentLink = $invoice->getPaymentUrl();

                    // Generate PDF for WhatsApp
                    $settings = Setting::getAll();
                    $pdfData = [
                        'invoice' => $invoice->toArray(),
                        'company' => self::buildCompanyData($settings),
                        'client'  => $invoice->client->toArray(),
                        'items'   => $invoice->items->toArray(),
                        'settings' => $settings,
                    ];
                    $template = request()->query('template', $settings['invoice_pdf_template'] ?? 'normal');
                    $view = ($template === 'thermal') ? 'pdf.invoice_thermal' : 'pdf.invoice';
                    $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $pdfData)->output();
                    $pdfFilename = "Factura-{$invoice->invoice_number}.pdf";

                    $waResult = $whatsappService->sendInvoice($invoice, $invoice->client->whatsapp, $paymentLink, $pdfContent, $pdfFilename);
                    if ($waResult['success']) {
                        $invoice->update(['sent_at' => now(), 'sent_via' => 'whatsapp', 'status' => 'sent']);
                        $sent = true;
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("WhatsApp auto-send failed for {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'invoice' => $invoice, 'email_sent' => $sent], 201);
    }

    /**
     * Auto-send invoice email on creation or conversion.
     */
    public function autoSendInvoiceEmail(Invoice $invoice): void
    {
        $invoice->load(['client', 'items']);
        $settings = Setting::getAll();
        EmailService::applySmtpConfig($settings);

        $companyData = self::buildCompanyData($settings);
        $companyName = $settings['company_name'] ?? 'GridBase';
        
        $docNum = $invoice->is_ecf ? ($invoice->encf ?: $invoice->invoice_number) : $invoice->invoice_number;
        if ($invoice->ecf_type == 34) {
            $subject = "Nota de Crédito {$docNum} de {$companyName}";
            $filename = "NotaDeCredito-{$docNum}.pdf";
        } elseif ($invoice->ecf_type == 33) {
            $subject = "Nota de Débito {$docNum} de {$companyName}";
            $filename = "NotaDeDebito-{$docNum}.pdf";
        } else {
            $subject = "Factura {$invoice->invoice_number} de {$companyName}";
            $filename = "Factura-{$invoice->invoice_number}.pdf";
        }

        $pdfData = [
            'invoice' => $invoice->toArray(), 'company' => $companyData,
            'client' => $invoice->client->toArray(), 'items' => $invoice->items->toArray(),
            'settings' => $settings,
        ];
        $template = Setting::get('invoice_pdf_template', 'normal');
        $view = ($template === 'thermal') ? 'pdf.invoice_thermal' : 'pdf.invoice';
        $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $pdfData)->output();

        // Generate payment link
        if (!$invoice->isPaymentTokenValid()) {
            $invoice->generatePaymentToken();
        }

        $emailData = [
            'subject' => $subject,
            'logoUrl' => 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png',
            'clientName' => $invoice->client->contact_name,
            'companyName' => $companyName, 'companyEmail' => $settings['company_email'] ?? '',
            'companyPhone' => $settings['company_phone'] ?? '', 'companyWebsite' => $settings['company_website'] ?? '',
            'isQuote' => false, 
            'isCreditNote' => $invoice->ecf_type == 34,
            'isDebitNote' => $invoice->ecf_type == 33,
            'docNumber' => $docNum, 
            'status' => $invoice->status,
            'issueDate' => date('d/m/Y', strtotime($invoice->issue_date)),
            'dueDate' => date('d/m/Y', strtotime($invoice->due_date)),
            'items' => $invoice->items->toArray(), 'subtotal' => $invoice->subtotal,
            'discountAmount' => $invoice->discount_amount ?? 0, 'taxRate' => $invoice->tax_rate ?? 0,
            'taxAmount' => $invoice->tax_amount ?? 0, 'total' => $invoice->total,
            'currency' => $invoice->currency ?? 'USD', 'notes' => $invoice->notes ?? '',
            'paymentUrl' => $invoice->getPaymentUrl(),
            'paymentExpiresAt' => $invoice->payment_token_expires_at ? $invoice->payment_token_expires_at->format('d/m/Y') : null,
        ];

        $htmlBody = view('emails.document', $emailData)->render();
        \Illuminate\Support\Facades\Mail::html($htmlBody, function ($message) use ($invoice, $subject, $pdfContent, $filename) {
            $message->to($invoice->client->email)->subject($subject)
                    ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
        });

        $sentVia = 'email';
        
        // Also send via WhatsApp if client has WhatsApp number
        if (!empty($invoice->client->whatsapp)) {
            try {
                $whatsappService = new \App\Services\WhatsAppService();
                if ($whatsappService->isEnabled()) {
                    $paymentLink = $invoice->getPaymentUrl();
                    $whatsappResult = $whatsappService->sendInvoice($invoice, $invoice->client->whatsapp, $paymentLink, $pdfContent, $filename);
                    if ($whatsappResult['success']) {
                        $sentVia = 'email,whatsapp';
                        \Illuminate\Support\Facades\Log::info("Invoice {$invoice->invoice_number} auto-sent via WhatsApp to {$invoice->client->whatsapp}");
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("WhatsApp auto-send error for invoice {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        $invoice->update(['sent_at' => now(), 'sent_via' => $sentVia, 'status' => 'sent']);
        \Illuminate\Support\Facades\Log::info("Invoice {$invoice->invoice_number} auto-sent to {$invoice->client->email}");
    }

    public function show($id)
    {
        $invoice = Invoice::with(['client', 'items', 'payments'])->findOrFail($id);
        $invoice->company_name = $invoice->client->company_name ?? $invoice->client->contact_name;
        $invoice->email = $invoice->client->email;
        return response()->json($invoice);
    }

    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        $data = $request->all();
        $clientId = $data['client_id'] ?? null;
        $isEcf = (int)($data['is_ecf'] ?? 0) === 1;
        $ecfType = (int)($data['ecf_type'] ?? 0);

        if (empty($clientId)) {
            if ($isEcf && $ecfType === 32) {
                $defaultClient = \App\Models\Client::firstOrCreate(
                    ['email' => 'consumidorfinal@bills.gridbase.com.do'],
                    [
                        'company_name' => 'Consumidor Final',
                        'contact_name' => 'Consumidor Final',
                        'phone' => '',
                        'tax_id' => '',
                        'address_line1' => 'Santo Domingo, Rep. Dom.',
                        'country' => 'Republica Dominicana',
                        'is_active' => true,
                    ]
                );
                $clientId = $defaultClient->id;
            } else {
                return response()->json(['success' => false, 'error' => 'El cliente es obligatorio para este tipo de factura.'], 400);
            }
        }
        $data['client_id'] = $clientId;

        $subtotal = collect($data['items'])->sum(function($i) { return $i['quantity'] * $i['unit_price']; });
        $discountValue = $data['discount_value'] ?? 0;
        $discountType = $data['discount_type'] ?? 'percentage';
        $discountAmount = $discountType === 'percentage' ? ($subtotal * ($discountValue/100)) : $discountValue;
        $taxRate = $data['tax_rate'] ?? 0;
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate/100);
        $total = $subtotal - $discountAmount + $taxAmount;

        $exchangeRate = $data['exchange_rate'] ?? null;
        if (empty($exchangeRate)) {
            $exchangeRate = \App\Services\CurrencyConverter::getConversionRate($data['currency'] ?? 'DOP', 'DOP');
        }

        $invoice->update([
            'client_id' => $clientId, 'currency' => $data['currency'],
            'exchange_rate' => $exchangeRate,
            'issue_date' => $data['issue_date'], 'due_date' => $data['due_date'],
            'discount_type' => $data['discount_type'] ?? 'percentage',
            'discount_value' => $discountValue, 'discount_amount' => $discountAmount,
            'tax_rate' => $taxRate, 'tax_amount' => $taxAmount,
            'subtotal' => $subtotal, 'total' => $total,
            'notes' => $data['notes'] ?? null,
            'is_ecf' => $data['is_ecf'] ?? $invoice->is_ecf,
            'ecf_type' => $data['ecf_type'] ?? $invoice->ecf_type,
            'modified_ncf' => $data['modified_ncf'] ?? $invoice->modified_ncf,
            'modification_code' => $data['modification_code'] ?? $invoice->modification_code,
        ]);

        // Rebuild items
        $invoice->items()->delete();
        foreach ($data['items'] as $idx => $item) {
            $item['amount'] = $item['quantity'] * $item['unit_price'];
            $item['sort_order'] = $idx;
            $invoice->items()->create($item);
        }

        return response()->json(['success' => true, 'invoice' => $invoice->fresh()]);
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

        // Send payment confirmation email for full and partial payments
        if (!empty($invoice->client->email)) {
            try {
                $this->sendPaymentConfirmationEmail($invoice, $amount);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send payment confirmation for {$invoice->invoice_number}: " . $e->getMessage());
            }
        } elseif (!empty($invoice->client->whatsapp)) {
            // Client has no email but has WhatsApp - send confirmation via WhatsApp only
            try {
                $whatsappService = new \App\Services\WhatsAppService();
                if ($whatsappService->isEnabled()) {
                    $whatsappService->sendPaymentConfirmation($invoice, $invoice->client->whatsapp, $amount);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("WhatsApp payment confirmation error for {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        return response()->json(['success' => true, 'paid' => $wasPaid]);
    }

    /**
     * Send a payment confirmation email with the updated PDF (showing PAGADA or PARTIAL status).
     */
    private function sendPaymentConfirmationEmail(Invoice $invoice, $paymentAmount = null): void
    {
        $invoice->load(['client', 'items']);
        $settings = Setting::getAll();
        EmailService::applySmtpConfig($settings);

        $companyData = self::buildCompanyData($settings);
        $companyName = $settings['company_name'] ?? 'GridBase';

        // Generate PDF
        $pdfData = [
            'invoice'  => $invoice->toArray(),
            'company'  => $companyData,
            'client'   => $invoice->client->toArray(),
            'items'    => $invoice->items->toArray(),
            'settings' => $settings,
        ];
        $template = Setting::get('invoice_pdf_template', 'normal');
        $view = ($template === 'thermal') ? 'pdf.invoice_thermal' : 'pdf.invoice';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $pdfData);
        $pdfContent = $pdf->output();

        $statusStr = strtoupper($invoice->status);
        $subject = "✅ Pago registrado — Factura {$invoice->invoice_number}";
        $filename = "Factura-{$invoice->invoice_number}-{$statusStr}.pdf";

        $notes = '¡Gracias por su pago! ';
        if ($invoice->status === 'paid') {
            $notes .= 'Esta factura ha sido saldada en su totalidad.';
        } else {
            $formattedAmount = number_format((float)$paymentAmount, 2);
            $pendingAmount = number_format((float)($invoice->total - $invoice->amount_paid), 2);
            $notes .= "Hemos registrado su abono de {$invoice->currency} {$formattedAmount}. Pendiente a pagar: {$invoice->currency} {$pendingAmount}.";
        }

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
            'status'         => $invoice->status,
            'issueDate'      => date('d/m/Y', strtotime($invoice->issue_date)),
            'dueDate'        => date('d/m/Y', strtotime($invoice->due_date)),
            'items'          => $invoice->items->toArray(),
            'subtotal'       => $invoice->subtotal,
            'discountAmount' => $invoice->discount_amount ?? 0,
            'taxRate'        => $invoice->tax_rate ?? 0,
            'taxAmount'      => $invoice->tax_amount ?? 0,
            'total'          => $invoice->total,
            'currency'       => $invoice->currency ?? 'USD',
            'notes'          => $notes,
        ];

        $htmlBody = view('emails.document', $emailData)->render();

        \Illuminate\Support\Facades\Mail::html($htmlBody, function ($message) use ($invoice, $subject, $pdfContent, $filename) {
            $message->to($invoice->client->email)
                    ->subject($subject)
                    ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
        });

        $sentVia = 'email';

        // Also send payment confirmation via WhatsApp
        if (!empty($invoice->client->whatsapp)) {
            try {
                $whatsappService = new \App\Services\WhatsAppService();
                if ($whatsappService->isEnabled()) {
                    $waResult = $whatsappService->sendPaymentConfirmation($invoice, $invoice->client->whatsapp, $paymentAmount);
                    if ($waResult['success']) {
                        $sentVia = 'email,whatsapp';
                        \Illuminate\Support\Facades\Log::info("Payment confirmation for {$invoice->invoice_number} also sent via WhatsApp to {$invoice->client->whatsapp}");
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("WhatsApp payment confirmation error for {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        \Illuminate\Support\Facades\Log::info("Payment confirmation sent for {$invoice->invoice_number} to {$invoice->client->email} (via: {$sentVia})");
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

        $template = request()->query('template', $settings['invoice_pdf_template'] ?? 'normal');
        $view = ($template === 'thermal') ? 'pdf.invoice_thermal' : 'pdf.invoice';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data);
        
        if (request()->has('download')) {
            return $pdf->download('Factura-' . $invoice->invoice_number . '.pdf');
        }
        return $pdf->stream('Factura-' . $invoice->invoice_number . '.pdf');
    }

    public function sendEmail($id)
    {
        $invoice = Invoice::with(['client', 'items'])->findOrFail($id);
        $settings = Setting::getAll();

        if (empty($invoice->client->email) && empty($invoice->client->whatsapp)) {
            return response()->json(['success' => false, 'error' => 'El cliente no tiene un correo electrónico ni WhatsApp configurado.'], 400);
        }

        // WhatsApp-only send (no email)
        if (empty($invoice->client->email) && !empty($invoice->client->whatsapp)) {
            try {
                $whatsappService = new \App\Services\WhatsAppService();
                if (!$whatsappService->isEnabled()) {
                    return response()->json(['success' => false, 'error' => 'El cliente no tiene email y WhatsApp no está habilitado.'], 400);
                }
                if (!$invoice->isPaymentTokenValid()) {
                    $invoice->generatePaymentToken();
                }
                $paymentLink = $invoice->getPaymentUrl();

                // Generate PDF for WhatsApp
                $companyData = self::buildCompanyData($settings);
                $pdfData = [
                    'invoice' => $invoice->toArray(),
                    'company' => $companyData,
                    'client'  => $invoice->client->toArray(),
                    'items'   => $invoice->items->toArray(),
                    'settings' => $settings,
                ];
                $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $pdfData)->output();
                $pdfFilename = "Factura-{$invoice->invoice_number}.pdf";

                $waResult = $whatsappService->sendInvoice($invoice, $invoice->client->whatsapp, $paymentLink, $pdfContent, $pdfFilename);
                if ($waResult['success']) {
                    $invoice->update(['sent_at' => now(), 'sent_via' => 'whatsapp', 'status' => 'sent']);
                    return response()->json(['success' => true, 'sent_via' => 'whatsapp']);
                }
                return response()->json(['success' => false, 'error' => $waResult['message'] ?? 'Error al enviar por WhatsApp'], 500);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
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
            $template = Setting::get('invoice_pdf_template', 'normal');
            $view = ($template === 'thermal') ? 'pdf.invoice_thermal' : 'pdf.invoice';
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $pdfData);
            $pdfContent = $pdf->output();

            // Build styled email data
            $companyName = $settings['company_name'] ?? 'GridBase';
            $docNum = $invoice->is_ecf ? ($invoice->encf ?: $invoice->invoice_number) : $invoice->invoice_number;
            if ($invoice->ecf_type == 34) {
                $subject = "Nota de Crédito {$docNum} de {$companyName}";
                $filename = "NotaDeCredito-{$docNum}.pdf";
            } elseif ($invoice->ecf_type == 33) {
                $subject = "Nota de Débito {$docNum} de {$companyName}";
                $filename = "NotaDeDebito-{$docNum}.pdf";
            } else {
                $subject = "Factura {$invoice->invoice_number} de {$companyName}";
                $filename = "Factura-{$invoice->invoice_number}.pdf";
            }

            $emailData = [
                'subject' => $subject,
                'logoUrl' => 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png',
                'clientName' => $invoice->client->contact_name,
                'companyName' => $companyName,
                'companyEmail' => $settings['company_email'] ?? '',
                'companyPhone' => $settings['company_phone'] ?? '',
                'companyWebsite' => $settings['company_website'] ?? '',
                'isQuote' => false,
                'isCreditNote' => $invoice->ecf_type == 34,
                'isDebitNote' => $invoice->ecf_type == 33,
                'docNumber' => $docNum,
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

            $sentVia = 'email';
            
            // Also send via WhatsApp if client has WhatsApp number
            if (!empty($invoice->client->whatsapp)) {
                try {
                    $whatsappService = new \App\Services\WhatsAppService();
                    if ($whatsappService->isEnabled()) {
                        // Generate payment link if invoice is unpaid
                        $paymentLink = null;
                        if ($invoice->status !== 'paid' && !empty($invoice->payment_token)) {
                            $paymentLink = url("/pay/{$invoice->payment_token}");
                        }
                        
                        $whatsappResult = $whatsappService->sendInvoice($invoice, $invoice->client->whatsapp, $paymentLink, $pdfContent, $filename);
                        if ($whatsappResult['success']) {
                            $sentVia = 'email,whatsapp';
                            \Illuminate\Support\Facades\Log::info("Invoice {$invoice->invoice_number} also sent via WhatsApp to {$invoice->client->whatsapp}");
                        } else {
                            \Illuminate\Support\Facades\Log::warning("Failed to send invoice {$invoice->invoice_number} via WhatsApp: " . ($whatsappResult['message'] ?? 'Unknown error'));
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("WhatsApp send error for invoice {$invoice->invoice_number}: " . $e->getMessage());
                }
            }

            if (!in_array($invoice->status, ['paid', 'partial'])) {
                $invoice->status = 'sent';
            }
            $invoice->sent_at = now();
            $invoice->sent_via = $sentVia;
            $invoice->save();

            \Illuminate\Support\Facades\Log::info("Invoice {$invoice->invoice_number} sent to {$invoice->client->email}");

            return response()->json(['success' => true, 'sent_via' => $sentVia]);
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

    /**
     * Duplicate an invoice.
     */
    public function duplicate($id)
    {
        $original = Invoice::with('items')->findOrFail($id);
        $prefix = Setting::where('setting_key', 'invoice_prefix')->value('setting_value') ?? 'FAC-';
        $nextNum = (int)(Setting::where('setting_key', 'invoice_next_number')->value('setting_value') ?? 1);
        $newNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        
        while (Invoice::where('invoice_number', $newNumber)->exists()) {
            $nextNum++;
            $newNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        }
        
        Setting::where('setting_key', 'invoice_next_number')->update(['setting_value' => $nextNum + 1]);

        $new = $original->replicate();
        $new->invoice_number = $newNumber;
        $new->status = 'draft';
        $new->issue_date = now();
        $new->due_date = now()->addDays(30);
        $new->amount_paid = 0;
        $new->sent_at = null;
        $new->sent_via = null;
        $new->viewed_at = null;
        $new->paid_at = null;
        $new->save();

        foreach ($original->items as $item) {
            $new->items()->create($item->only(['description', 'quantity', 'unit_price', 'amount', 'sort_order']));
        }

        return response()->json(['success' => true, 'invoice' => $new->load('client')]);
    }

    /**
     * Export all invoices as a CSV file compatible with Excel.
     */
    public function exportCsv(Request $request)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="facturas_export_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");
            
            // CSV Headers
            fputcsv($file, [
                'No. Factura',
                'RNC/Cédula Cliente',
                'Cliente',
                'Fecha Emisión',
                'Subtotal',
                'Descuento',
                'ITBIS',
                'Total',
                'Estado',
                'e-CF',
                'NCF/e-NCF'
            ], ',');

            $invoices = Invoice::with('client')->orderBy('created_at', 'desc')->get();

            foreach ($invoices as $i) {
                $clientName = $i->client ? ($i->client->company_name ?: $i->client->contact_name) : 'Sin cliente';
                $clientTaxId = $i->client ? $i->client->tax_id : '';
                
                fputcsv($file, [
                    $i->invoice_number,
                    $clientTaxId,
                    $clientName,
                    $i->issue_date ? $i->issue_date->format('Y-m-d') : $i->created_at->format('Y-m-d'),
                    number_format($i->subtotal, 2, '.', ''),
                    number_format($i->discount_amount, 2, '.', ''),
                    number_format($i->tax_amount, 2, '.', ''),
                    number_format($i->total, 2, '.', ''),
                    __($i->status),
                    $i->is_ecf ? 'Sí' : 'No',
                    $i->encf ?: ($i->ncf ?: '')
                ], ',');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete an invoice.
     */
    public function destroy(Request $request, $id)
    {
        if ($request->user() && $request->user()->role === 'vendedor') {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado.',
                'message' => 'Los vendedores no tienen permisos para eliminar facturas.'
            ], 403);
        }

        $invoice = Invoice::findOrFail($id);
        $invoice->items()->delete();
        $invoice->payments()->delete();
        $invoice->delete();
        return response()->json(['success' => true, 'message' => 'Factura eliminada.']);
    }

    /**
     * Cancel/void an invoice.
     */
    public function cancel(Request $request, $id)
    {
        if ($request->user() && $request->user()->role === 'vendedor') {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado.',
                'message' => 'Los vendedores no tienen permisos para anular facturas.'
            ], 403);
        }

        $invoice = Invoice::findOrFail($id);
        $invoice->update(['status' => 'cancelled']);
        return response()->json(['success' => true, 'message' => 'Factura anulada con éxito.', 'invoice' => $invoice->fresh()]);
    }

    /**
     * Manually process or retry electronic invoicing (e-CF) validation on the DGII.
     */
    public function processEcf($id, EcfManagerService $ecfManager)
    {
        $invoice = Invoice::findOrFail($id);
        
        if (!$invoice->is_ecf) {
            return response()->json(['success' => false, 'error' => 'Esta factura no está marcada como e-CF.'], 400);
        }
        
        // If already processed, retry instead
        if (in_array($invoice->dgii_status, ['contingency', 'rejected', 'signed'])) {
            $result = $ecfManager->retryInvoice($invoice);
        } else {
            $result = $ecfManager->processInvoice($invoice);
        }
        
        return response()->json([
            'success' => $result['success'],
            'status' => $result['status'],
            'track_id' => $result['track_id'],
            'error' => $result['error'],
            'invoice' => $invoice->fresh()
        ]);
    }

    /**
     * Check the DGII status of a pending e-CF.
     */
    public function checkEcfStatus($id, EcfManagerService $ecfManager)
    {
        $invoice = Invoice::findOrFail($id);
        
        if (!$invoice->is_ecf) {
            return response()->json(['success' => false, 'error' => 'No es e-CF.'], 400);
        }

        $result = $ecfManager->checkStatus($invoice);
        
        return response()->json([
            'success' => true,
            'status' => $result['status'],
            'errors' => $result['errors'],
            'invoice' => $invoice->fresh()
        ]);
    }

    public function downloadXml($id)
    {
        $invoice = Invoice::findOrFail($id);
        
        if (!$invoice->signed_xml_path || !\Illuminate\Support\Facades\Storage::exists($invoice->signed_xml_path)) {
            return response()->json(['success' => false, 'error' => 'No se encontro el archivo XML.'], 404);
        }

        $xml = \Illuminate\Support\Facades\Storage::get($invoice->signed_xml_path);
        $filename = ($invoice->encf ?: $invoice->invoice_number) . '.xml';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function bulkAction(Request $request, EcfManagerService $ecfManager)
    {
        $ids = $request->input('ids', []);
        $action = $request->input('action');
        
        if (empty($ids)) {
            return response()->json(['success' => false, 'error' => 'No se seleccionaron facturas.'], 400);
        }
        
        switch ($action) {
            case 'cancel':
                if ($request->user() && $request->user()->role === 'vendedor') {
                    return response()->json([
                        'success' => false,
                        'error' => 'No autorizado.',
                        'message' => 'Los vendedores no tienen permisos para anular facturas.'
                    ], 403);
                }

                foreach ($ids as $id) {
                    $invoice = Invoice::find($id);
                    if ($invoice) {
                        $invoice->update(['status' => 'cancelled']);
                    }
                }
                return response()->json(['success' => true, 'message' => count($ids) . ' facturas anuladas exitosamente.']);

            case 'delete':
                if ($request->user() && $request->user()->role === 'vendedor') {
                    return response()->json([
                        'success' => false,
                        'error' => 'No autorizado.',
                        'message' => 'Los vendedores no tienen permisos para eliminar facturas.'
                    ], 403);
                }

                foreach ($ids as $id) {
                    $invoice = Invoice::find($id);
                    if ($invoice) {
                        $invoice->items()->delete();
                        $invoice->payments()->delete();
                        $invoice->delete();
                    }
                }
                return response()->json(['success' => true, 'message' => count($ids) . ' facturas eliminadas exitosamente.']);
                
            case 'mark_as_paid':
                foreach ($ids as $id) {
                    $invoice = Invoice::find($id);
                    if ($invoice && $invoice->status !== 'paid') {
                        $pendingAmount = $invoice->total - $invoice->amount_paid;
                        if ($pendingAmount > 0) {
                            $invoice->payments()->create([
                                'amount' => $pendingAmount,
                                'payment_method' => 'other',
                                'payment_date' => now()
                            ]);
                            $invoice->amount_paid = $invoice->total;
                        }
                        $invoice->status = 'paid';
                        $invoice->paid_at = now();
                        $invoice->save();
                    }
                }
                return response()->json(['success' => true, 'message' => count($ids) . ' facturas marcadas como pagadas.']);
                
            case 'send_email':
                $sentCount = 0;
                $failedCount = 0;
                foreach ($ids as $id) {
                    $invoice = Invoice::with(['client', 'items'])->find($id);
                    if ($invoice && $invoice->client && (!empty($invoice->client->email) || !empty($invoice->client->whatsapp))) {
                        try {
                            $this->autoSendInvoiceEmail($invoice);
                            $sentCount++;
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Bulk send failed for invoice {$invoice->invoice_number}: " . $e->getMessage());
                            $failedCount++;
                        }
                    } else {
                        $failedCount++;
                    }
                }
                return response()->json([
                    'success' => true, 
                    'message' => "Proceso completado. Enviadas: {$sentCount}, Errores: {$failedCount}."
                ]);
                
            case 'process_ecf':
                $processedCount = 0;
                $failedCount = 0;
                foreach ($ids as $id) {
                    $invoice = Invoice::find($id);
                    if ($invoice && $invoice->is_ecf) {
                        try {
                            if (in_array($invoice->dgii_status, ['contingency', 'rejected', 'signed'])) {
                                $result = $ecfManager->retryInvoice($invoice);
                            } else {
                                $result = $ecfManager->processInvoice($invoice);
                            }
                            if ($result['success']) {
                                $processedCount++;
                            } else {
                                $failedCount++;
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Bulk e-CF failed for invoice {$invoice->invoice_number}: " . $e->getMessage());
                            $failedCount++;
                        }
                    } else {
                        $failedCount++;
                    }
                }
                return response()->json([
                    'success' => true,
                    'message' => "Proceso e-CF completado. Aprobados/Enviados: {$processedCount}, Errores/Omitidos: {$failedCount}."
                ]);
                
            default:
                return response()->json(['success' => false, 'error' => 'Acción no válida.'], 400);
        }
    }
}
