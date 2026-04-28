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
}
