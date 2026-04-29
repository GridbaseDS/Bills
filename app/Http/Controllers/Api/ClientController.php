<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('company_name')->get()->map(function ($c) {
            $c->total_invoiced = Invoice::where('client_id', $c->id)->sum('total');
            $c->total_pending = Invoice::where('client_id', $c->id)
                ->whereIn('status', ['sent', 'partial', 'overdue'])
                ->sum(DB::raw('total - amount_paid'));
            $c->invoice_count = Invoice::where('client_id', $c->id)->count();
            return $c;
        });
        return response()->json(['success' => true, 'data' => $clients]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:200',
            'contact_name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'tax_id' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
        ]);

        $client = Client::create($validated);
        return response()->json(['success' => true, 'client' => $client], 201);
    }

    public function show($id)
    {
        $client = Client::findOrFail($id);
        return response()->json($client);
    }

    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $client->update($request->all());
        return response()->json(['success' => true, 'client' => $client]);
    }

    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $invoiceCount = Invoice::where('client_id', $id)->count();
        $quoteCount = Quote::where('client_id', $id)->count();
        if ($invoiceCount > 0 || $quoteCount > 0) {
            return response()->json([
                'success' => false,
                'error' => "No se puede eliminar: tiene {$invoiceCount} factura(s) y {$quoteCount} cotización(es) asociadas."
            ], 400);
        }
        $client->delete();
        return response()->json(['success' => true, 'message' => 'Cliente eliminado.']);
    }

    /**
     * Client profile with full history.
     */
    public function profile($id)
    {
        $client = Client::findOrFail($id);

        $invoices = Invoice::where('client_id', $id)
            ->orderBy('created_at', 'desc')->take(20)->get()
            ->map(fn($i) => [
                'id' => $i->id, 'invoice_number' => $i->invoice_number,
                'status' => $i->status, 'total' => $i->total,
                'amount_paid' => $i->amount_paid, 'currency' => $i->currency,
                'issue_date' => $i->issue_date?->format('Y-m-d'),
                'due_date' => $i->due_date?->format('Y-m-d'),
                'sent_at' => $i->sent_at,
            ]);

        $quotes = Quote::where('client_id', $id)
            ->orderBy('created_at', 'desc')->take(20)->get()
            ->map(fn($q) => [
                'id' => $q->id, 'quote_number' => $q->quote_number,
                'status' => $q->status, 'total' => $q->total, 'currency' => $q->currency,
                'issue_date' => $q->issue_date?->format('Y-m-d'),
                'expiry_date' => $q->expiry_date?->format('Y-m-d'),
            ]);

        $stats = [
            'total_invoiced' => Invoice::where('client_id', $id)->sum('total'),
            'total_paid' => Invoice::where('client_id', $id)->sum('amount_paid'),
            'total_pending' => Invoice::where('client_id', $id)
                ->whereIn('status', ['sent', 'partial', 'overdue'])
                ->sum(DB::raw('total - amount_paid')),
            'invoice_count' => $invoices->count(),
            'quote_count' => $quotes->count(),
        ];

        return response()->json([
            'client' => $client,
            'invoices' => $invoices,
            'quotes' => $quotes,
            'stats' => $stats,
        ]);
    }
}

