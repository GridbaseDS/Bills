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
        // Use app timezone so month boundaries match the local business day
        $tz = config('app.timezone', 'America/Santo_Domingo');
        $now = Carbon::now($tz);
        $startOfMonth     = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth   = $now->copy()->subMonth()->endOfMonth();

        // Plain date strings for DATE column comparisons (avoids Carbon object type issues)
        $dateThisMonthStart  = $startOfMonth->toDateString();      // e.g. 2026-06-01
        $dateLastMonthStart  = $startOfLastMonth->toDateString();  // e.g. 2026-05-01
        $dateLastMonthEnd    = $endOfLastMonth->toDateString();     // e.g. 2026-05-31

        // ── Helper: revenue = amount collected MINUS the tax portion ──────────
        // tax_amount is ITBIS collected on behalf of the government — not profit.
        // We compute: net_paid = amount_paid * (subtotal / total)  when total > 0.
        // Simplified as: amount_paid - (tax_amount * amount_paid / total)
        // Using a DB expression for performance:
        $netPaidExpr = DB::raw(
            'SUM(amount_paid - IF(total > 0, tax_amount * amount_paid / total, 0))'
        );

        // Core stats
        $totalClients = Client::where('is_active', true)->count();

        $revenue = Invoice::whereIn('status', ['paid', 'partial'])
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->selectRaw('SUM(amount_paid - IF(total > 0, tax_amount * amount_paid / total, 0)) as net')
            ->value('net') ?? 0;

        $pending = Invoice::whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->sum(DB::raw('total - amount_paid'));

        $overdue = Invoice::where('status', 'overdue')
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->sum(DB::raw('total - amount_paid'));

        $overdueCount = Invoice::where('status', 'overdue')
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->count();

        // This month vs last month — net revenue (excl. tax) + count
        $revenueThisMonth = Invoice::whereIn('status', ['paid', 'partial'])
            ->where('paid_at', '>=', $startOfMonth)
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->selectRaw('SUM(amount_paid - IF(total > 0, tax_amount * amount_paid / total, 0)) as net')
            ->value('net') ?? 0;

        $revenueLastMonth = Invoice::whereIn('status', ['paid', 'partial'])
            ->whereBetween('paid_at', [$startOfLastMonth, $endOfLastMonth])
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->selectRaw('SUM(amount_paid - IF(total > 0, tax_amount * amount_paid / total, 0)) as net')
            ->value('net') ?? 0;

        // Count invoices issued this / last month using issue_date as plain date string
        $invoicesThisMonth = Invoice::where('status', '!=', 'cancelled')
            ->where('issue_date', '>=', $dateThisMonthStart)
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->count();

        $invoicesLastMonth = Invoice::where('status', '!=', 'cancelled')
            ->whereBetween('issue_date', [$dateLastMonthStart, $dateLastMonthEnd])
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->count();

        // ── ITBIS / Tax summary ─────────────────────────────────────────────
        // Tax collected this month (from issued invoices, regardless of payment)
        $taxCollectedThisMonth = (float) Invoice::where('status', '!=', 'cancelled')
            ->where('issue_date', '>=', $dateThisMonthStart)
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->sum('tax_amount');
        // Tax collected last month
        $taxCollectedLastMonth = (float) Invoice::where('status', '!=', 'cancelled')
            ->whereBetween('issue_date', [$dateLastMonthStart, $dateLastMonthEnd])
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->sum('tax_amount');
        // Total tax collected all time (pending to declare/pay to DGII)
        $taxCollectedTotal = (float) Invoice::where('status', '!=', 'cancelled')
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->sum('tax_amount');
        // Tax from unpaid invoices (not yet received by the business)
        $taxPending = (float) Invoice::whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->sum('tax_amount');

        // Quotes stats
        $totalQuotes     = Quote::count();
        $quotesConverted = Quote::where('status', 'converted')->count();
        $conversionRate  = $totalQuotes > 0 ? round(($quotesConverted / $totalQuotes) * 100) : 0;
        $quotesPending   = Quote::whereIn('status', ['draft', 'sent'])->sum('total');

        // Monthly stats chart (last 12 months) — net revenue excl. tax
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd   = $month->copy()->endOfMonth();

            $monthRevenue = (float) Invoice::whereIn('status', ['paid', 'partial'])
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->where(function($q) {
                    $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
                })
                ->selectRaw('SUM(amount_paid - IF(total > 0, tax_amount * amount_paid / total, 0)) as net')
                ->value('net');

            $expense = (float) \App\Models\Expense::whereBetween('expense_date', [$monthStart, $monthEnd])
                ->sum('total');

            // Add purchase e-CFs (E41, E43, E47) to expenses
            $purchaseInvoicesSum = (float) Invoice::whereIn('ecf_type', [41, 43, 47])
                ->where('status', '!=', 'cancelled')
                ->whereBetween('issue_date', [$monthStart, $monthEnd])
                ->sum('total');

            $expense += $purchaseInvoicesSum;

            $label = ucfirst($month->translatedFormat('M'));
            $label = rtrim($label, '.');

            $monthlyData[] = [
                'month'   => ucfirst($month->translatedFormat('F Y')),
                'label'   => $label,
                'revenue' => $monthRevenue,
                'expense' => $expense,
            ];
        }

        // Recent invoices (sales only)
        $recentInvoices = Invoice::with('client')
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function($inv) {
                return [
                    'id'           => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'company_name' => $inv->client->company_name ?? '',
                    'contact_name' => $inv->client->contact_name ?? '',
                    'total'        => $inv->total,
                    'currency'     => $inv->currency,
                    'status'       => $inv->status,
                    'issue_date'   => $inv->issue_date ? $inv->issue_date->format('Y-m-d') : null,
                    'sent_at'      => $inv->sent_at,
                ];
            });

        // Overdue invoices (sales only)
        $overdueInvoices = Invoice::with('client')
            ->where('status', 'overdue')
            ->where(function($q) {
                $q->whereNull('ecf_type')->orWhereNotIn('ecf_type', [41, 43, 47]);
            })
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get()
            ->map(function($inv) {
                return [
                    'id'           => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'company_name' => $inv->client->company_name ?? '',
                    'contact_name' => $inv->client->contact_name ?? '',
                    'total'        => $inv->total,
                    'currency'     => $inv->currency,
                    'status'       => $inv->status,
                    'due_date'     => $inv->due_date ? $inv->due_date->format('Y-m-d') : null,
                    'balance'      => $inv->total - $inv->amount_paid,
                ];
            });

        $bpdRates = \App\Services\CurrencyConverter::fetchBpdRates();
        $bpdTime = \Illuminate\Support\Facades\Cache::get('exchange_rates_bpd_time');

        return response()->json([
            'stats' => [
                'total_clients'            => $totalClients,
                'total_revenue'            => round((float)$revenue, 2),
                'pending_amount'           => $pending,
                'overdue_amount'           => $overdue,
                'overdue_count'            => $overdueCount,
                'revenue_this_month'       => round((float)$revenueThisMonth, 2),
                'revenue_last_month'       => round((float)$revenueLastMonth, 2),
                'invoices_this_month'      => $invoicesThisMonth,
                'invoices_last_month'      => $invoicesLastMonth,
                'total_quotes'             => $totalQuotes,
                'quotes_converted'         => $quotesConverted,
                'conversion_rate'          => $conversionRate,
                'quotes_pending'           => $quotesPending,
                // ITBIS / tax fields
                'tax_collected_this_month' => round($taxCollectedThisMonth, 2),
                'tax_collected_last_month' => round($taxCollectedLastMonth, 2),
                'tax_collected_total'      => round($taxCollectedTotal, 2),
                'tax_pending'              => round($taxPending, 2),
            ],
            'monthly_data'    => $monthlyData,
            'monthly_revenue' => $monthlyData,
            'recent_invoices' => $recentInvoices,
            'overdue_invoices' => $overdueInvoices,
            'exchange_rates' => [
                'USD' => (float)($bpdRates['USD'] ?? 60.35),
                'EUR' => (float)($bpdRates['EUR'] ?? 65.90),
                'updated_at' => $bpdTime ? date('d/m/Y h:i A', strtotime($bpdTime)) : date('d/m/Y h:i A')
            ]
        ]);
    }
}
