<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
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
            'auto_send', 'send_via', 'notes', 'terms'
        ]);

        // Calculate subtotal from items
        $subtotal = collect($request->items)->sum(function ($i) {
            return $i['quantity'] * $i['unit_price'];
        });

        $data['subtotal']        = $subtotal;
        $data['next_issue_date'] = $request->start_date;
        $data['status']          = 'active';
        $data['tax_rate']        = $request->tax_rate ?? 0;
        $data['currency']        = $request->currency ?? 'USD';
        $data['auto_send']       = $request->auto_send ?? false;
        $data['send_via']        = $request->send_via ?? 'email';
        $data['occurrences_count'] = 0;

        if ($request->user()) {
            $data['created_by'] = $request->user()->id;
        }

        $recurring = RecurringInvoice::create($data);

        // Create items
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

        return response()->json([
            'success' => true,
            'data'    => $recurring,
            'message' => 'Suscripción recurrente creada exitosamente.'
        ], 201);
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
            'currency', 'auto_send', 'send_via', 'notes', 'terms', 'status'
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
