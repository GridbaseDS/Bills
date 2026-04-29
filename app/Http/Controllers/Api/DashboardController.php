<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Core stats
        $totalClients = Client::where('is_active', true)->count();
        $revenue = Invoice::whereIn('status', ['paid', 'partial'])->sum('amount_paid');
        $pending = Invoice::whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->sum(DB::raw('total - amount_paid'));
        $overdue = Invoice::where('status', 'overdue')
            ->sum(DB::raw('total - amount_paid'));
        $overdueCount = Invoice::where('status', 'overdue')->count();

        // This month vs last month
        $revenueThisMonth = Invoice::whereIn('status', ['paid', 'partial'])
            ->where('paid_at', '>=', $startOfMonth)->sum('amount_paid');
        $revenueLastMonth = Invoice::whereIn('status', ['paid', 'partial'])
            ->whereBetween('paid_at', [$startOfLastMonth, $endOfLastMonth])->sum('amount_paid');

        $invoicesThisMonth = Invoice::where('created_at', '>=', $startOfMonth)->count();
        $invoicesLastMonth = Invoice::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        // Quotes stats
        $totalQuotes = Quote::count();
        $quotesConverted = Quote::where('status', 'converted')->count();
        $conversionRate = $totalQuotes > 0 ? round(($quotesConverted / $totalQuotes) * 100) : 0;
        $quotesPending = Quote::whereIn('status', ['draft', 'sent'])->sum('total');

        // Monthly revenue chart (last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $monthlyRevenue[] = [
                'month' => $month->translatedFormat('M Y'),
                'label' => $month->format('M'),
                'revenue' => (float) Invoice::whereIn('status', ['paid', 'partial'])
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount_paid'),
                'invoiced' => (float) Invoice::whereBetween('created_at', [$monthStart, $monthEnd])->sum('total'),
            ];
        }

        // Recent invoices
        $recentInvoices = Invoice::with('client')->orderBy('created_at', 'desc')->take(5)->get()->map(function($inv) {
            return [
                'id' => $inv->id, 'invoice_number' => $inv->invoice_number,
                'company_name' => $inv->client->company_name ?? '', 'contact_name' => $inv->client->contact_name ?? '',
                'total' => $inv->total, 'currency' => $inv->currency, 'status' => $inv->status,
                'issue_date' => $inv->issue_date ? $inv->issue_date->format('Y-m-d') : null,
                'sent_at' => $inv->sent_at,
            ];
        });

        // Overdue invoices
        $overdueInvoices = Invoice::with('client')->where('status', 'overdue')
            ->orderBy('due_date', 'asc')->take(5)->get()->map(function($inv) {
            return [
                'id' => $inv->id, 'invoice_number' => $inv->invoice_number,
                'company_name' => $inv->client->company_name ?? '', 'contact_name' => $inv->client->contact_name ?? '',
                'total' => $inv->total, 'currency' => $inv->currency, 'status' => $inv->status,
                'due_date' => $inv->due_date ? $inv->due_date->format('Y-m-d') : null,
                'balance' => $inv->total - $inv->amount_paid,
            ];
        });

        return response()->json([
            'stats' => [
                'total_clients' => $totalClients,
                'total_revenue' => $revenue,
                'pending_amount' => $pending,
                'overdue_amount' => $overdue,
                'overdue_count' => $overdueCount,
                'revenue_this_month' => $revenueThisMonth,
                'revenue_last_month' => $revenueLastMonth,
                'invoices_this_month' => $invoicesThisMonth,
                'invoices_last_month' => $invoicesLastMonth,
                'total_quotes' => $totalQuotes,
                'quotes_converted' => $quotesConverted,
                'conversion_rate' => $conversionRate,
                'quotes_pending' => $quotesPending,
            ],
            'monthly_revenue' => $monthlyRevenue,
            'recent_invoices' => $recentInvoices,
            'overdue_invoices' => $overdueInvoices,
        ]);
    }
}
