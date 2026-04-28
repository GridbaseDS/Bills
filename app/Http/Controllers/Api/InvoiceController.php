<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Setting;
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
        $invoice = Invoice::findOrFail($id);
        $amount = $request->amount;
        $invoice->payments()->create([
            'amount' => $amount,
            'payment_method' => $request->payment_method ?? 'other',
            'payment_date' => now()
        ]);
        
        $invoice->amount_paid += $amount;
        if ($invoice->amount_paid >= $invoice->total) {
            $invoice->status = 'paid';
            $invoice->paid_at = now();
        } else {
            $invoice->status = 'partial';
        }
        $invoice->save();

        return response()->json(['success' => true]);
    }

    public function pdf($id)
    {
        $invoice = Invoice::with(['client', 'items', 'payments'])->findOrFail($id);
        $settings = Setting::getAll();
        
        $data = [
            'invoice' => $invoice->toArray(),
            'company' => [
                'name' => $settings['company_name'] ?? 'GridBase',
                'email' => $settings['company_email'] ?? '',
                'phone' => $settings['company_phone'] ?? '',
                'address' => $settings['company_address'] ?? '',
                'city' => $settings['company_city'] ?? '',
                'country' => $settings['company_country'] ?? '',
                'tax_id' => $settings['company_tax_id'] ?? '',
                'website' => $settings['company_website'] ?? '',
            ],
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
            // Apply SMTP settings from database at runtime
            self::applyMailConfig($settings);

            $data = [
                'invoice' => $invoice->toArray(),
                'company' => [
                    'name' => $settings['company_name'] ?? 'GridBase',
                    'email' => $settings['company_email'] ?? '',
                    'phone' => $settings['company_phone'] ?? '',
                    'address' => $settings['company_address'] ?? '',
                    'city' => $settings['company_city'] ?? '',
                    'country' => $settings['company_country'] ?? '',
                    'tax_id' => $settings['company_tax_id'] ?? '',
                    'website' => $settings['company_website'] ?? '',
                ],
                'client' => $invoice->client->toArray(),
                'items' => $invoice->items->toArray(),
                'settings' => $settings
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $data);
            $pdfContent = $pdf->output();

            $subject = "Factura {$invoice->invoice_number} de " . ($settings['company_name'] ?? 'GridBase');
            $body = "Hola {$invoice->client->contact_name},\n\nAdjunto encontrarás la factura {$invoice->invoice_number} por el monto de {$invoice->currency} {$invoice->total}.\n\nSaludos cordiales.";
            $filename = "Factura-{$invoice->invoice_number}.pdf";

            \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($invoice, $subject, $pdfContent, $filename) {
                $message->to($invoice->client->email)
                        ->subject($subject)
                        ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
            });

            $invoice->status = 'sent';
            $invoice->sent_at = now();
            $invoice->sent_via = 'email';
            $invoice->save();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Apply SMTP settings from the database to Laravel's mail config at runtime.
     */
    public static function applyMailConfig(array $settings)
    {
        $host = trim($settings['smtp_host'] ?? '') ?: 'localhost';
        $port = (int)($settings['smtp_port'] ?? 25) ?: 25;
        $encryption = $settings['smtp_encryption'] ?? null;
        
        // For localhost, never use encryption
        if ($host === 'localhost' || $host === '127.0.0.1') {
            $encryption = null;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.mailers.smtp.username' => $settings['smtp_username'] ?? null,
            'mail.mailers.smtp.password' => $settings['smtp_password'] ?? null,
            'mail.mailers.smtp.timeout' => 15,
            'mail.mailers.smtp.stream' => [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
            'mail.from.address' => !empty($settings['smtp_from_email']) ? $settings['smtp_from_email'] : 'bills@gridbase.com.do',
            'mail.from.name' => !empty($settings['smtp_from_name']) ? $settings['smtp_from_name'] : 'Gridbase Bills',
        ]);

        // Force Laravel to rebuild the mailer with new config
        app()->forgetInstance('mail.manager');

        // Access the underlying Symfony transport and disable SSL verification directly
        try {
            $transport = app('mailer')->getSymfonyTransport();
            if (method_exists($transport, 'getStream')) {
                $transport->getStream()->setStreamOptions([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ]
                ]);
            }
        } catch (\Exception $e) {
            // Silently continue — the stream options in config may still work
        }
    }
}


