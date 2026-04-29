<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\EmailService;

class QuoteController extends Controller
{
    public function index()
    {
        $quotes = Quote::with('client')->orderBy('created_at', 'desc')->get();
        $quotes->transform(function ($q) {
            $q->company_name = $q->client->company_name ?? $q->client->contact_name;
            return $q;
        });
        return response()->json(['success' => true, 'data' => $quotes]);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['quote_number'] = Setting::where('setting_key', 'quote_prefix')->value('setting_value') 
                              . Setting::where('setting_key', 'quote_next_number')->value('setting_value');
        
        Setting::where('setting_key', 'quote_next_number')->increment('setting_value');
        
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

        $quote = Quote::create($data);

        foreach ($data['items'] as $idx => $item) {
            $item['amount'] = $item['quantity'] * $item['unit_price'];
            $item['sort_order'] = $idx;
            $quote->items()->create($item);
        }

        return response()->json(['success' => true, 'quote' => $quote], 201);
    }

    public function show($id)
    {
        $quote = Quote::with(['client', 'items'])->findOrFail($id);
        $quote->company_name = $quote->client->company_name ?? $quote->client->contact_name;
        $quote->email = $quote->client->email;
        return response()->json($quote);
    }

    public function convertToInvoice($id)
    {
        $quote = Quote::with(['items'])->findOrFail($id);
        
        $invoiceNumber = Setting::where('setting_key', 'invoice_prefix')->value('setting_value') 
                       . Setting::where('setting_key', 'invoice_next_number')->value('setting_value');
        Setting::where('setting_key', 'invoice_next_number')->increment('setting_value');

        $invoice = \App\Models\Invoice::create([
            'invoice_number' => $invoiceNumber,
            'client_id' => $quote->client_id,
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays((int)Setting::where('setting_key', 'default_due_days')->value('setting_value') ?: 30),
            'subtotal' => $quote->subtotal,
            'tax_rate' => $quote->tax_rate,
            'tax_amount' => $quote->tax_amount,
            'discount_type' => $quote->discount_type,
            'discount_value' => $quote->discount_value,
            'discount_amount' => $quote->discount_amount,
            'total' => $quote->total,
            'currency' => $quote->currency,
            'notes' => $quote->notes,
            'terms' => $quote->terms,
        ]);

        foreach ($quote->items as $item) {
            $invoice->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'amount' => $item->amount,
                'sort_order' => $item->sort_order,
            ]);
        }

        $quote->status = 'converted';
        $quote->converted_invoice_id = $invoice->id;
        $quote->save();

        return response()->json(['success' => true, 'invoice_id' => $invoice->id]);
    }

    public function pdf($id)
    {
        $quote = Quote::with(['client', 'items'])->findOrFail($id);
        $settings = Setting::getAll();
        
        $data = [
            'invoice' => $quote->toArray(),
            'is_quote' => true,
            'company' => InvoiceController::buildCompanyData($settings),
            'client' => $quote->client->toArray(),
            'items' => $quote->items->toArray(),
            'settings' => $settings
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $data);
        
        if (request()->has('download')) {
            return $pdf->download('Cotizacion-' . $quote->quote_number . '.pdf');
        }
        return $pdf->stream('Cotizacion-' . $quote->quote_number . '.pdf');
    }

    public function sendEmail($id)
    {
        $quote = Quote::with(['client', 'items'])->findOrFail($id);
        $settings = Setting::getAll();

        if (empty($quote->client->email)) {
            return response()->json(['success' => false, 'error' => 'El cliente no tiene un correo electrónico configurado.'], 400);
        }

        try {
            // Apply SMTP settings using the centralized method
            EmailService::applySmtpConfig($settings);

            $companyData = InvoiceController::buildCompanyData($settings);

            // Generate PDF
            $pdfData = [
                'invoice' => $quote->toArray(),
                'is_quote' => true,
                'company' => $companyData,
                'client' => $quote->client->toArray(),
                'items' => $quote->items->toArray(),
                'settings' => $settings
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $pdfData);
            $pdfContent = $pdf->output();

            // Build styled email data
            $subject = "Cotización {$quote->quote_number} de " . ($settings['company_name'] ?? 'GridBase');
            $filename = "Cotizacion-{$quote->quote_number}.pdf";

            $emailData = [
                'subject' => $subject,
                'logoUrl' => 'https://gridbase.com.do/wp-content/uploads/2025/02/imagen_2026-03-16_154236217-1024x228.png',
                'clientName' => $quote->client->contact_name,
                'companyName' => $settings['company_name'] ?? 'GridBase',
                'companyEmail' => $settings['company_email'] ?? '',
                'companyPhone' => $settings['company_phone'] ?? '',
                'companyWebsite' => $settings['company_website'] ?? '',
                'isQuote' => true,
                'docNumber' => $quote->quote_number,
                'status' => $quote->status ?? 'draft',
                'issueDate' => date('d/m/Y', strtotime($quote->issue_date ?? $quote->created_at)),
                'dueDate' => date('d/m/Y', strtotime($quote->expiry_date ?? $quote->due_date ?? now()->addDays(30))),
                'items' => $quote->items->toArray(),
                'subtotal' => $quote->subtotal,
                'discountAmount' => $quote->discount_amount ?? 0,
                'taxRate' => $quote->tax_rate ?? 0,
                'taxAmount' => $quote->tax_amount ?? 0,
                'total' => $quote->total,
                'currency' => $quote->currency ?? 'USD',
                'notes' => $quote->notes ?? '',
            ];

            $htmlBody = view('emails.document', $emailData)->render();

            \Illuminate\Support\Facades\Mail::html($htmlBody, function ($message) use ($quote, $subject, $pdfContent, $filename) {
                $message->to($quote->client->email)
                        ->subject($subject)
                        ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
            });

            $quote->status = 'sent';
            $quote->sent_at = now();
            $quote->sent_via = 'email';
            $quote->save();

            \Illuminate\Support\Facades\Log::info("Quote {$quote->quote_number} sent to {$quote->client->email}");

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send quote {$quote->quote_number}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

