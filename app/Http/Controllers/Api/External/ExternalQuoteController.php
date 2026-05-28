<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quote;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Setting;
use App\Services\CurrencyConverter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ExternalQuoteController extends Controller
{
    /**
     * List quotes (paginated, with optional filters).
     *
     * GET /api/v1/quotes?page=1&per_page=20&status=draft
     */
    public function index(Request $request)
    {
        $query = Quote::with('client');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        $perPage = min((int) ($request->per_page ?? 20), 100);
        $quotes = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $quotes->items(),
            'pagination' => [
                'current_page' => $quotes->currentPage(),
                'last_page' => $quotes->lastPage(),
                'per_page' => $quotes->perPage(),
                'total' => $quotes->total(),
            ],
        ]);
    }

    /**
     * Create a quote via external API.
     *
     * POST /api/v1/quotes
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'currency' => 'sometimes|string|in:DOP,USD,EUR',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'discount_type' => 'sometimes|string|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'notes' => 'sometimes|nullable|string|max:2000',
            'terms' => 'sometimes|nullable|string|max:2000',
            'issue_date' => 'sometimes|date',
            'expiry_date' => 'sometimes|date',
            'client_id' => 'sometimes|integer|exists:clients,id',
            'client' => 'sometimes|array',
            'client.tax_id' => 'sometimes|string|max:20',
            'client.company_name' => 'sometimes|string|max:255',
            'client.contact_name' => 'sometimes|string|max:255',
            'client.email' => 'sometimes|nullable|email|max:255',
            'client.phone' => 'sometimes|nullable|string|max:30',
            'client.whatsapp' => 'sometimes|nullable|string|max:30',
            'client.address_line1' => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de validación inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // ── Resolve client ──
        $clientId = $this->resolveClient($data);
        if ($clientId instanceof \Illuminate\Http\JsonResponse) {
            return $clientId;
        }
        $data['client_id'] = $clientId;

        // ── Currency & Exchange Rate ──
        $data['currency'] = $data['currency'] ?? 'DOP';
        if (empty($data['exchange_rate'])) {
            $data['exchange_rate'] = CurrencyConverter::getConversionRate($data['currency'], 'DOP');
        }

        // ── Generate quote number ──
        $prefix = Setting::where('setting_key', 'quote_prefix')->value('setting_value') ?? 'COT-';
        $nextNum = (int)(Setting::where('setting_key', 'quote_next_number')->value('setting_value') ?? 1);
        $quoteNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);

        while (Quote::where('quote_number', $quoteNumber)->exists()) {
            $nextNum++;
            $quoteNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        }

        $data['quote_number'] = $quoteNumber;
        Setting::where('setting_key', 'quote_next_number')->update(['setting_value' => $nextNum + 1]);

        // ── Calculate totals ──
        $subtotal = collect($data['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $discountValue = $data['discount_value'] ?? 0;
        $discountType = $data['discount_type'] ?? 'percentage';
        $discountAmount = $discountType === 'percentage' ? ($subtotal * ($discountValue / 100)) : $discountValue;
        $taxRate = $data['tax_rate'] ?? 0;
        $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
        $total = $subtotal - $discountAmount + $taxAmount;

        $data['subtotal'] = $subtotal;
        $data['discount_amount'] = $discountAmount;
        $data['tax_amount'] = $taxAmount;
        $data['total'] = $total;
        $data['status'] = $data['status'] ?? 'draft';
        $data['issue_date'] = $data['issue_date'] ?? now()->toDateString();
        $data['expiry_date'] = $data['expiry_date'] ?? now()->addDays(30)->toDateString();

        // ── Create quote ──
        $quote = Quote::create($data);

        foreach ($data['items'] as $idx => $item) {
            $quote->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['quantity'] * $item['unit_price'],
                'sort_order' => $idx,
            ]);
        }

        $quote->load(['client', 'items']);

        $apiKey = $request->attributes->get('api_key');
        Log::info("External API: Quote {$quote->quote_number} created via API key '{$apiKey->name}' (ID: {$apiKey->id})");

        return response()->json([
            'success' => true,
            'message' => 'Cotización creada exitosamente.',
            'data' => [
                'id' => $quote->id,
                'quote_number' => $quote->quote_number,
                'client_id' => $quote->client_id,
                'status' => $quote->status,
                'currency' => $quote->currency,
                'subtotal' => $quote->subtotal,
                'discount_amount' => $quote->discount_amount,
                'tax_amount' => $quote->tax_amount,
                'total' => $quote->total,
                'issue_date' => $quote->issue_date?->format('Y-m-d'),
                'expiry_date' => $quote->expiry_date?->format('Y-m-d'),
                'items' => $quote->items,
                'client' => $quote->client,
                'created_at' => $quote->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Show a single quote.
     */
    public function show($id)
    {
        $quote = Quote::with(['client', 'items'])->find($id);

        if (!$quote) {
            return response()->json([
                'success' => false,
                'error' => 'Cotización no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $quote,
        ]);
    }

    /**
     * Download or stream the quote PDF.
     */
    public function pdf($id)
    {
        $quote = Quote::with(['client', 'items'])->find($id);

        if (!$quote) {
            return response()->json([
                'success' => false,
                'error' => 'Cotización no encontrada.',
            ], 404);
        }

        $settings = Setting::getAll();

        $data = [
            'invoice' => $quote->toArray(),
            'is_quote' => true,
            'company' => \App\Http\Controllers\Api\InvoiceController::buildCompanyData($settings),
            'client' => $quote->client->toArray(),
            'items' => $quote->items->toArray(),
            'settings' => $settings,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $data);

        return $pdf->download('Cotizacion-' . $quote->quote_number . '.pdf');
    }

    /**
     * Update an existing quote.
     */
    public function update(Request $request, $id)
    {
        $quote = Quote::find($id);

        if (!$quote) {
            return response()->json([
                'success' => false,
                'error' => 'Cotización no encontrada.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:draft,sent,accepted,declined,converted',
            'notes' => 'sometimes|string|max:2000',
            'terms' => 'sometimes|string|max:2000',
            'expiry_date' => 'sometimes|date',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'currency' => 'sometimes|string|in:DOP,USD,EUR',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'discount_type' => 'sometimes|string|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de validación inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Update fields if provided
        $quote->fill($request->only(['status', 'notes', 'terms', 'expiry_date', 'currency', 'tax_rate', 'discount_type', 'discount_value']));

        // Recalculate totals if items or discount/tax are modified
        if ($request->has('items') || $request->has('discount_value') || $request->has('tax_rate')) {
            $items = $request->has('items') ? $request->input('items') : $quote->items->toArray();
            
            $subtotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $discountValue = $request->input('discount_value', $quote->discount_value ?? 0);
            $discountType = $request->input('discount_type', $quote->discount_type ?? 'percentage');
            $discountAmount = $discountType === 'percentage' ? ($subtotal * ($discountValue / 100)) : $discountValue;
            
            $taxRate = $request->input('tax_rate', $quote->tax_rate ?? 0);
            $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
            $total = $subtotal - $discountAmount + $taxAmount;

            $quote->subtotal = $subtotal;
            $quote->discount_amount = $discountAmount;
            $quote->tax_amount = $taxAmount;
            $quote->total = $total;
        }

        if ($request->has('currency') && empty($data['exchange_rate'])) {
            $quote->exchange_rate = CurrencyConverter::getConversionRate($request->input('currency'), 'DOP');
        }

        $quote->save();

        // Sync items if provided
        if ($request->has('items')) {
            $quote->items()->delete();
            foreach ($request->input('items') as $idx => $item) {
                $quote->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['quantity'] * $item['unit_price'],
                    'sort_order' => $idx,
                ]);
            }
        }

        $quote->load(['client', 'items']);

        return response()->json([
            'success' => true,
            'message' => 'Cotización actualizada exitosamente.',
            'data' => $quote,
        ]);
    }

    /**
     * Convert a quote to an invoice.
     */
    public function convertToInvoice(Request $request, $id)
    {
        $quote = Quote::with('items')->find($id);

        if (!$quote) {
            return response()->json([
                'success' => false,
                'error' => 'Cotización no encontrada.',
            ], 404);
        }

        if ($quote->status === 'converted') {
            $invoiceNumber = \App\Models\Invoice::where('id', $quote->converted_invoice_id)->value('invoice_number');
            return response()->json([
                'success' => false,
                'error' => 'Esta cotización ya fue convertida a factura.',
                'invoice_id' => $quote->converted_invoice_id,
                'invoice_number' => $invoiceNumber,
            ], 409);
        }

        // Generate invoice number
        $prefix = Setting::where('setting_key', 'invoice_prefix')->value('setting_value') ?? 'FAC-';
        $nextNum = (int)(Setting::where('setting_key', 'invoice_next_number')->value('setting_value') ?? 1);
        $invoiceNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);

        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $nextNum++;
            $invoiceNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        }

        Setting::where('setting_key', 'invoice_next_number')->update(['setting_value' => $nextNum + 1]);

        $invoice = Invoice::create([
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
            'exchange_rate' => $quote->exchange_rate,
            'notes' => $quote->notes,
            'terms' => $quote->terms,
            // e-CF fields
            'is_ecf' => $request->boolean('is_ecf', false),
            'ecf_type' => $request->input('ecf_type'),
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

        // ── ECF processing if applicable ──
        if (!empty($invoice->is_ecf) && $invoice->is_ecf) {
            try {
                $ecfManager = app(\App\Services\Dgii\EcfManagerService::class);
                $ecfManager->processInvoice($invoice);
                $invoice->refresh();
            } catch (\Exception $e) {
                Log::error("External API: DGII auto-processing failed for converted {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        $quote->status = 'converted';
        $quote->converted_invoice_id = $invoice->id;
        $quote->save();

        return response()->json([
            'success' => true,
            'message' => 'Cotización convertida a factura exitosamente.',
            'data' => [
                'quote_id' => $quote->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'encf' => $invoice->encf,
            ],
        ]);
    }

    /**
     * Resolve client_id from the request data.
     */
    private function resolveClient(array $data)
    {
        if (!empty($data['client_id'])) {
            return (int) $data['client_id'];
        }

        if (!empty($data['client'])) {
            $clientData = $data['client'];

            if (!empty($clientData['tax_id'])) {
                $existing = Client::where('tax_id', $clientData['tax_id'])->first();
                if ($existing) {
                    return $existing->id;
                }
            }

            if (!empty($clientData['email'])) {
                $existing = Client::where('email', $clientData['email'])->first();
                if ($existing) {
                    return $existing->id;
                }
            }

            if (empty($clientData['company_name']) && empty($clientData['contact_name'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Se requiere al menos company_name o contact_name para crear un cliente.',
                ], 422);
            }

            $client = Client::create([
                'company_name' => $clientData['company_name'] ?? null,
                'contact_name' => $clientData['contact_name'] ?? null,
                'email' => $clientData['email'] ?? null,
                'phone' => $clientData['phone'] ?? null,
                'whatsapp' => $clientData['whatsapp'] ?? null,
                'tax_id' => $clientData['tax_id'] ?? null,
                'address_line1' => $clientData['address_line1'] ?? null,
                'address_line2' => $clientData['address_line2'] ?? null,
                'city' => $clientData['city'] ?? null,
                'state' => $clientData['state'] ?? null,
                'postal_code' => $clientData['postal_code'] ?? null,
                'country' => $clientData['country'] ?? 'DO',
                'is_active' => true,
            ]);

            return $client->id;
        }

        return response()->json([
            'success' => false,
            'error' => 'Se requiere client_id o un objeto client para crear la cotización.',
        ], 422);
    }
}
