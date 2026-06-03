<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DgiiLog;
use Illuminate\Http\Request;

class DgiiLogController extends Controller
{
    /**
     * List DGII logs with filtering and pagination.
     */
    public function index(Request $request)
    {
        $query = DgiiLog::query()->orderByDesc('created_at');

        // Filters
        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->invoice_id);
        }
        if ($request->filled('encf')) {
            $query->where('encf', 'like', '%' . $request->encf . '%');
        }
        if ($request->filled('ecf_type')) {
            $query->where('ecf_type', $request->ecf_type);
        }
        if ($request->filled('step')) {
            $query->where('step', 'like', '%' . $request->step . '%');
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('dgii_status')) {
            $query->where('dgii_status', $request->dgii_status);
        }
        if ($request->filled('qr_verified')) {
            $query->where('qr_verified', $request->qr_verified === 'true' ? true : false);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('message', 'like', "%{$s}%")
                  ->orWhere('encf', 'like', "%{$s}%")
                  ->orWhere('dgii_track_id', 'like', "%{$s}%")
                  ->orWhere('http_url', 'like', "%{$s}%")
                  ->orWhere('dgii_error_messages', 'like', "%{$s}%");
            });
        }

        $perPage = (int)($request->per_page ?? 50);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get a single log entry with full details.
     */
    public function show($id)
    {
        $log = DgiiLog::findOrFail($id);
        return response()->json($log);
    }

    /**
     * Get aggregated stats for the logs dashboard.
     */
    public function stats()
    {
        $total = DgiiLog::count();
        $byLevel = DgiiLog::selectRaw('level, COUNT(*) as count')->groupBy('level')->pluck('count', 'level');
        $byStep = DgiiLog::selectRaw('step, COUNT(*) as count')->groupBy('step')->orderByDesc('count')->limit(15)->pluck('count', 'step');

        $qrTotal = DgiiLog::where('step', 'qr_verify')->count();
        $qrOk = DgiiLog::where('step', 'qr_verify')->where('qr_verified', true)->count();
        $qrFail = DgiiLog::where('step', 'qr_verify')->where('qr_verified', false)->count();

        $recentErrors = DgiiLog::whereIn('level', ['error', 'critical'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'encf', 'step', 'level', 'message', 'created_at']);

        return response()->json([
            'total_entries' => $total,
            'by_level' => $byLevel,
            'by_step' => $byStep,
            'qr_verification' => [
                'total' => $qrTotal,
                'verified' => $qrOk,
                'failed' => $qrFail,
            ],
            'recent_errors' => $recentErrors,
        ]);
    }

    /**
     * Get the full timeline of logs for a specific invoice.
     */
    public function invoiceTimeline($invoiceId)
    {
        $logs = DgiiLog::where('invoice_id', $invoiceId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $logs]);
    }

    /**
     * Purge old logs (keep last N days).
     */
    public function purge(Request $request)
    {
        $days = (int)($request->days ?? 30);
        $deleted = DgiiLog::where('created_at', '<', now()->subDays($days))->delete();
        return response()->json(['deleted' => $deleted, 'kept_days' => $days]);
    }
}
