<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\EmailService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RecurringController extends Controller
{
    /**
     * List all recurring invoices with client info.
     */
    public function index()
    {
        $recs = RecurringInvoice::with(['client', 'items'])
            ->orderBy('created_at', 'desc')
            ->get();

        $recs->transform(function ($r) {
            $r->company_name = $r->client->company_name ?? $r->client->contact_name ?? '';
            $r->contact_name = $r->client->contact_name ?? '';
            $r->items_count  = $r->items->count();
            // Calculate total for display
            $subtotal = $r->items->sum(function ($i) { return $i->quantity * $i->unit_price; });
            $r->calculated_total = $subtotal + ($subtotal * ($r->tax_rate / 100));
            return $r;
        });

        return response()->json(['success' => true, 'data' => $recs]);
    }

    /**
     * Create a new recurring invoice with items.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'frequency'   => 'required|in:weekly,biweekly,monthly,quarterly,semiannual,annual',
            'start_date'  => 'required|date',
            'items'       => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $data = $request->only([
            'client_id', 'frequency', 'start_date', 'end_date',
            'occurrences_limit', 'tax_rate', 'currency',
            'ecf_type', 'tipo_ingresos',
            'auto_send', 'send_via', 'notes', 'terms'
        ]);

        $subtotal = collect($request->items)->sum(function ($i) {
            return $i['quantity'] * $i['unit_price'];
        });

        $taxRate = $request->tax_rate ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        $data['subtotal']          = $subtotal;
        $data['next_issue_date']   = $request->start_date;
        $data['status']            = 'active';
        $data['tax_rate']          = $taxRate;
        $data['currency']          = $request->currency ?? 'USD';
        $data['ecf_type']          = $request->ecf_type ? (int)$request->ecf_type : null;
        $data['tipo_ingresos']     = $request->tipo_ingresos ?? '01';
        $data['auto_send']         = $request->auto_send ?? false;
        $data['send_via']          = $request->send_via ?? 'email';
        $data['occurrences_count'] = 0;

        if ($request->user()) {
            $data['created_by'] = $request->user()->id;
        }

        $recurring = RecurringInvoice::create($data);

        foreach ($request->items as $idx => $item) {
            $recurring->items()->create([
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'amount'      => $item['quantity'] * $item['unit_price'],
                'sort_order'  => $idx,
            ]);
        }

        $recurring->load(['client', 'items']);

        // ── Generate the first invoice immediately ──
        $firstInvoice = null;
        $emailSent = false;
        try {
            $firstInvoice = $this->generateInvoiceFromRecurring($recurring);

            // Send the invoice by email
            if ($recurring->client && !empty($recurring->client->email)) {
                $emailSent = $this->sendInvoiceEmail($firstInvoice);
            }
        } catch (\Exception $e) {
            Log::error('Error generating first invoice for recurring #' . $recurring->id . ': ' . $e->getMessage());
        }

        return response()->json([
            'success'       => true,
            'data'          => $recurring,
            'first_invoice' => $firstInvoice,
            'email_sent'    => $emailSent,
            'message'       => 'Suscripción creada' . ($firstInvoice ? '. Primera factura generada' : '') . ($emailSent ? ' y enviada por email.' : '.')
        ], 201);
    }

    /**
     * Generate an invoice from a recurring template.
     */
    private function generateInvoiceFromRecurring(RecurringInvoice $recurring): Invoice
    {
        $settings = Setting::getAll();
        $issueDate = Carbon::today();
        $defaultDueDays = (int)($settings['default_due_days'] ?? 30);
        $dueDate = $issueDate->copy()->addDays($defaultDueDays);

        $invoiceNumber = ($settings['invoice_prefix'] ?? 'FAC-')
            . str_pad($settings['invoice_next_number'] ?? '1', 4, '0', STR_PAD_LEFT);
        Setting::where('setting_key', 'invoice_next_number')->increment('setting_value');

        $subtotal = $recurring->items->sum(fn($i) => $i->quantity * $i->unit_price);
        $taxRate = $recurring->tax_rate ?? 0;
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        $hasEcf = !empty($recurring->ecf_type);

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'client_id'      => $recurring->client_id,
            'status'         => 'sent',
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
            // e-CF fields — propagate from the recurring template
            'is_ecf'         => $hasEcf,
            'ecf_type'       => $recurring->ecf_type ?: null,
            'tipo_ingresos'  => $recurring->tipo_ingresos ?? '01',
        ]);

        foreach ($recurring->items as $item) {
            $invoice->items()->create([
                'description' => $item->description,
                'quantity'    => $item->quantity,
                'unit_price'  => $item->unit_price,
                'amount'      => $item->quantity * $item->unit_price,
                'sort_order'  => $item->sort_order,
            ]);
        }

        // Update recurring: advance next_issue_date and increment count
        $nextDate = $issueDate->copy();
        switch ($recurring->frequency) {
            case 'weekly':     $nextDate->addWeek(); break;
            case 'biweekly':   $nextDate->addWeeks(2); break;
            case 'monthly':    $nextDate->addMonth(); break;
            case 'quarterly':  $nextDate->addMonths(3); break;
            case 'semiannual': $nextDate->addMonths(6); break;
            case 'annual':     $nextDate->addYear(); break;
        }

        $recurring->update([
            'next_issue_date'  => $nextDate,
            'occurrences_count' => $recurring->occurrences_count + 1,
        ]);

        $invoice->load(['client', 'items']);

        // Auto-process e-CF if the recurring has ecf_type configured
        if ($hasEcf) {
            try {
                $ecfManager = app(\App\Services\Dgii\EcfManagerService::class);
                $result = $ecfManager->processInvoice($invoice);
                Log::info("[Recurring→ECF] Factura {$invoice->invoice_number} procesada: " . json_encode($result));
            } catch (\Exception $e) {
                Log::error("[Recurring→ECF] Error procesando e-CF para factura {$invoice->invoice_number}: " . $e->getMessage());
            }
        }


        return $invoice;
    }

    /**
     * Send an invoice by email using the styled template.
     */
    private function sendInvoiceEmail(Invoice $invoice): bool
    {
        try {
            $settings = Setting::getAll();
            EmailService::applySmtpConfig($settings);

            $companyData = InvoiceController::buildCompanyData($settings);

            // Generate PDF
            $pdfData = [
                'invoice'  => $invoice->toArray(),
                'company'  => $companyData,
                'client'   => $invoice->client->toArray(),
                'items'    => $invoice->items->toArray(),
                'settings' => $settings,
            ];
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $pdfData);
            $pdfContent = $pdf->output();

            $companyName = $settings['company_name'] ?? 'GridBase';
            $subject = "Factura {$invoice->invoice_number} de {$companyName}";
            $filename = "Factura-{$invoice->invoice_number}.pdf";

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
                'notes'          => $invoice->notes ?? '',
            ];

            $htmlBody = view('emails.document', $emailData)->render();

            Mail::html($htmlBody, function ($message) use ($invoice, $subject, $pdfContent, $filename) {
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
                        // Generate payment link
                        if (!$invoice->isPaymentTokenValid()) {
                            $invoice->generatePaymentToken();
                        }
                        $paymentLink = $invoice->getPaymentUrl();
                        $whatsappResult = $whatsappService->sendInvoice($invoice, $invoice->client->whatsapp, $paymentLink, $pdfContent, $filename);
                        if ($whatsappResult['success']) {
                            $sentVia = 'email,whatsapp';
                            Log::info("Recurring invoice {$invoice->invoice_number} also sent via WhatsApp to {$invoice->client->whatsapp}");
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("WhatsApp send error for recurring invoice {$invoice->invoice_number}: " . $e->getMessage());
                }
            }

            $invoice->update([
                'sent_at'  => now(),
                'sent_via' => $sentVia,
            ]);

            Log::info("First invoice {$invoice->invoice_number} sent to {$invoice->client->email} (via: {$sentVia}, recurring #{$invoice->recurring_id})");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send first invoice {$invoice->invoice_number}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Show a single recurring invoice with all details.
     */
    public function show($id)
    {
        $rec = RecurringInvoice::with(['client', 'items', 'invoices' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(20);
        }])->findOrFail($id);

        $rec->company_name  = $rec->client->company_name ?? $rec->client->contact_name ?? '';
        $rec->contact_name  = $rec->client->contact_name ?? '';
        $rec->client_email  = $rec->client->email ?? '';

        // Calculate totals
        $subtotal = $rec->items->sum(function ($i) { return $i->quantity * $i->unit_price; });
        $taxAmount = $subtotal * (($rec->tax_rate ?? 0) / 100);
        $rec->calculated_subtotal = $subtotal;
        $rec->calculated_tax      = $taxAmount;
        $rec->calculated_total    = $subtotal + $taxAmount;

        return response()->json($rec);
    }

    /**
     * Update a recurring invoice and its items.
     */
    public function update(Request $request, $id)
    {
        $recurring = RecurringInvoice::findOrFail($id);

        $request->validate([
            'client_id'   => 'sometimes|exists:clients,id',
            'frequency'   => 'sometimes|in:weekly,biweekly,monthly,quarterly,semiannual,annual',
            'items'       => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.quantity'    => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'  => 'required_with:items|numeric|min:0',
        ]);

        $data = $request->only([
            'client_id', 'frequency', 'start_date', 'end_date',
            'next_issue_date', 'occurrences_limit', 'tax_rate',
            'currency', 'ecf_type', 'tipo_ingresos',
            'auto_send', 'send_via', 'notes', 'terms', 'status'
        ]);

        // Recalculate subtotal if items are provided
        if ($request->has('items')) {
            $subtotal = collect($request->items)->sum(function ($i) {
                return $i['quantity'] * $i['unit_price'];
            });
            $data['subtotal'] = $subtotal;

            // Replace items
            $recurring->items()->delete();
            foreach ($request->items as $idx => $item) {
                $recurring->items()->create([
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'amount'      => $item['quantity'] * $item['unit_price'],
                    'sort_order'  => $idx,
                ]);
            }
        }

        $recurring->update($data);
        $recurring->load(['client', 'items']);

        return response()->json([
            'success' => true,
            'data'    => $recurring,
            'message' => 'Suscripción actualizada exitosamente.'
        ]);
    }

    /**
     * Delete a recurring invoice (does NOT delete generated invoices).
     */
    public function destroy($id)
    {
        $recurring = RecurringInvoice::findOrFail($id);
        $recurring->delete();

        return response()->json([
            'success' => true,
            'message' => 'Suscripción eliminada. Las facturas generadas anteriormente no fueron afectadas.'
        ]);
    }

    /**
     * Manually generate the current period's invoice for a recurring subscription.
     *
     * After generation the next_issue_date is advanced exactly as the cron would do,
     * so the scheduler will NOT create a duplicate for this cycle.
     */
    public function generateNow(Request $request, $id)
    {
        $recurring = RecurringInvoice::with(['client', 'items'])->findOrFail($id);

        if ($recurring->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'error'   => 'No se puede generar una factura para una suscripción cancelada.',
            ], 422);
        }

        if ($recurring->occurrences_limit && $recurring->occurrences_count >= $recurring->occurrences_limit) {
            return response()->json([
                'success' => false,
                'error'   => 'Esta suscripción ya alcanzó el límite máximo de ocurrencias.',
            ], 422);
        }

        try {
            $invoice   = $this->generateInvoiceFromRecurring($recurring);
            $emailSent = false;

            if ($recurring->client && !empty($recurring->client->email)) {
                $emailSent = $this->sendInvoiceEmail($invoice);
            }

            Log::info("[RecurringController] Factura {$invoice->invoice_number} generada manualmente para suscripción #{$recurring->id}");

            return response()->json([
                'success'    => true,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'email_sent' => $emailSent,
                'message'    => "Factura {$invoice->invoice_number} generada exitosamente."
                    . ($emailSent ? ' Enviada por email al cliente.' : ''),
            ]);
        } catch (\Exception $e) {
            Log::error("[RecurringController] Error generando factura manual para suscripción #{$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Error al generar la factura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active/paused status.
     */
    public function toggleStatus(Request $request, $id)

    {
        $recurring = RecurringInvoice::findOrFail($id);

        $newStatus = $request->status;
        if (!in_array($newStatus, ['active', 'paused', 'cancelled'])) {
            return response()->json(['success' => false, 'error' => 'Estado no válido.'], 422);
        }

        // When reactivating, ensure next_issue_date is in the future
        if ($newStatus === 'active' && $recurring->next_issue_date && Carbon::parse($recurring->next_issue_date)->isPast()) {
            $recurring->next_issue_date = Carbon::today();
        }

        $recurring->status = $newStatus;
        $recurring->save();

        return response()->json([
            'success' => true,
            'data'    => $recurring,
            'message' => 'Estado actualizado a: ' . $newStatus
        ]);
    }
}
