<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Client;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalInvoices = Invoice::count();
        $totalClients = Client::count();
        
        $revenue = Invoice::whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');
            
        $pending = Invoice::whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->sum(Invoice::raw('total - amount_paid'));

        $recentInvoices = Invoice::with('client')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'client_name' => $inv->client->company_name ?? $inv->client->contact_name,
                    'total' => $inv->total,
                    'currency' => $inv->currency,
                    'status' => $inv->status,
                    'issue_date' => $inv->issue_date->format('Y-m-d')
                ];
            });

        return response()->json([
            'stats' => [
                'total_invoices' => $totalInvoices,
                'total_clients' => $totalClients,
                'revenue_collected' => $revenue,
                'pending_balance' => $pending,
            ],
            'recent_invoices' => $recentInvoices
        ]);
    }
}
