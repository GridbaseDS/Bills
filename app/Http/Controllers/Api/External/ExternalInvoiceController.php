<?php

namespace App\Http\Controllers\Api\External;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Setting;
use App\Services\CurrencyConverter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ExternalInvoiceController extends Controller
{
    /**
     * List invoices (paginated, with optional filters).
     *
     * GET /api/v1/invoices?page=1&per_page=20&status=sent&client_id=5
     */
    public function index(Request $request)
    {
        $query = Invoice::with('client');

        // Filters
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
        $invoices = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $invoices->items(),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * Create an invoice via external API.
     *
     * POST /api/v1/invoices
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
            'notes' => 'sometimes|string|max:2000',
            'terms' => 'sometimes|string|max:2000',
            'issue_date' => 'sometimes|date',
            'due_date' => 'sometimes|date',
            // Client identification (at least one required)
            'client_id' => 'sometimes|integer|exists:clients,id',
            'client' => 'sometimes|array',
            'client.tax_id' => 'sometimes|string|max:20',
            'client.company_name' => 'sometimes|string|max:255',
            'client.contact_name' => 'sometimes|string|max:255',
            'client.email' => 'sometimes|email|max:255',
            'client.phone' => 'sometimes|string|max:30',
            'client.whatsapp' => 'sometimes|string|max:30',
            'client.address_line1' => 'sometimes|string|max:255',
            // ECF fields
            'is_ecf' => 'sometimes|boolean',
            'ecf_type' => 'sometimes|integer',
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
            return $clientId; // Error response
        }
        $data['client_id'] = $clientId;

        // ── Currency & Exchange Rate ──
        $data['currency'] = $data['currency'] ?? 'DOP';
        if (empty($data['exchange_rate'])) {
            $data['exchange_rate'] = CurrencyConverter::getConversionRate($data['currency'], 'DOP');
        }

        // ── Generate invoice number ──
        $prefix = Setting::where('setting_key', 'invoice_prefix')->value('setting_value') ?? 'FAC-';
        $nextNum = (int)(Setting::where('setting_key', 'invoice_next_number')->value('setting_value') ?? 1);
        $invoiceNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);

        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $nextNum++;
            $invoiceNumber = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        }

        $data['invoice_number'] = $invoiceNumber;
        Setting::where('setting_key', 'invoice_next_number')->update(['setting_value' => $nextNum + 1]);

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
        $data['due_date'] = $data['due_date'] ?? now()->addDays(
            (int)(Setting::where('setting_key', 'default_due_days')->value('setting_value') ?: 30)
        )->toDateString();

        // ── Create invoice ──
        $invoice = Invoice::create($data);

        foreach ($data['items'] as $idx => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'amount' => $item['quantity'] * $item['unit_price'],
                'sort_order' => $idx,
            ]);
        }

        // ── ECF processing if applicable ──
        if (!empty($data['is_ecf']) && $data['is_ecf']) {
            try {
                $ecfManager = app(\App\Services\Dgii\EcfManagerService::class);
                $ecfManager->processInvoice($invoice);
                $invoice->refresh();
            } catch (\Exception $e) {
                Log::error("External API: DGII auto-processing failed for {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        $invoice->load(['client', 'items']);

        $apiKey = $request->attributes->get('api_key');
        Log::info("External API: Invoice {$invoice->invoice_number} created via API key '{$apiKey->name}' (ID: {$apiKey->id})");

        return response()->json([
            'success' => true,
            'message' => 'Factura creada exitosamente.',
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'status' => $invoice->status,
                'currency' => $invoice->currency,
                'subtotal' => $invoice->subtotal,
                'discount_amount' => $invoice->discount_amount,
                'tax_amount' => $invoice->tax_amount,
                'total' => $invoice->total,
                'issue_date' => $invoice->issue_date?->format('Y-m-d'),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'items' => $invoice->items,
                'client' => $invoice->client,
                'created_at' => $invoice->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Show a single invoice.
     */
    public function show($id)
    {
        $invoice = Invoice::with(['client', 'items', 'payments'])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'error' => 'Factura no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    /**
     * Download or stream the invoice PDF.
     */
    public function pdf($id)
    {
        $invoice = Invoice::with(['client', 'items', 'payments'])->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'error' => 'Factura no encontrada.',
            ], 404);
        }

        $settings = Setting::getAll();

        $data = [
            'invoice' => $invoice->toArray(),
            'company' => \App\Http\Controllers\Api\InvoiceController::buildCompanyData($settings),
            'client' => $invoice->client->toArray(),
            'items' => $invoice->items->toArray(),
            'settings' => $settings,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', $data);

        return $pdf->download('Factura-' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Update an existing invoice.
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'error' => 'Factura no encontrada.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:draft,sent,paid,cancelled',
            'notes' => 'sometimes|string|max:2000',
            'terms' => 'sometimes|string|max:2000',
            'due_date' => 'sometimes|date',
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
        $invoice->fill($request->only(['status', 'notes', 'terms', 'due_date', 'currency', 'tax_rate', 'discount_type', 'discount_value']));

        // Recalculate totals if items or discount/tax are modified
        if ($request->has('items') || $request->has('discount_value') || $request->has('tax_rate')) {
            $items = $request->has('items') ? $request->input('items') : $invoice->items->toArray();
            
            $subtotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $discountValue = $request->input('discount_value', $invoice->discount_value ?? 0);
            $discountType = $request->input('discount_type', $invoice->discount_type ?? 'percentage');
            $discountAmount = $discountType === 'percentage' ? ($subtotal * ($discountValue / 100)) : $discountValue;
            
            $taxRate = $request->input('tax_rate', $invoice->tax_rate ?? 0);
            $taxAmount = ($subtotal - $discountAmount) * ($taxRate / 100);
            $total = $subtotal - $discountAmount + $taxAmount;

            $invoice->subtotal = $subtotal;
            $invoice->discount_amount = $discountAmount;
            $invoice->tax_amount = $taxAmount;
            $invoice->total = $total;
        }

        if ($request->has('currency') && empty($data['exchange_rate'])) {
            $invoice->exchange_rate = CurrencyConverter::getConversionRate($request->input('currency'), 'DOP');
        }

        $invoice->save();

        // Sync items if provided
        if ($request->has('items')) {
            $invoice->items()->delete();
            foreach ($request->input('items') as $idx => $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['quantity'] * $item['unit_price'],
                    'sort_order' => $idx,
                ]);
            }
        }

        $invoice->load(['client', 'items']);

        return response()->json([
            'success' => true,
            'message' => 'Factura actualizada exitosamente.',
            'data' => $invoice,
        ]);
    }

    /**
     * Resolve client_id from the request data.
     * Supports: direct client_id, upsert by tax_id, or create new.
     *
     * @return int|\Illuminate\Http\JsonResponse
     */
    private function resolveClient(array $data)
    {
        // Direct client_id
        if (!empty($data['client_id'])) {
            return (int) $data['client_id'];
        }

        // Client object provided
        if (!empty($data['client'])) {
            $clientData = $data['client'];

            // Try to find by tax_id (RNC/Cédula)
            if (!empty($clientData['tax_id'])) {
                $existing = Client::where('tax_id', $clientData['tax_id'])->first();
                if ($existing) {
                    return $existing->id;
                }
            }

            // Try to find by email
            if (!empty($clientData['email'])) {
                $existing = Client::where('email', $clientData['email'])->first();
                if ($existing) {
                    return $existing->id;
                }
            }

            // Create new client
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
            'error' => 'Se requiere client_id o un objeto client para crear la factura.',
        ], 422);
    }
}
