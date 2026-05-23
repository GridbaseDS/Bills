<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReceivedInvoice;
use App\Services\Dgii\AcecfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReceivedInvoiceController extends Controller
{
    /**
     * List all received invoices with optional filters.
     */
    public function index(Request $request)
    {
        $query = ReceivedInvoice::query()->orderBy('created_at', 'desc');

        if ($status = $request->query('status')) {
            $query->where('approval_status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('rnc_emisor', 'like', "%{$search}%")
                  ->orWhere('razon_social_emisor', 'like', "%{$search}%")
                  ->orWhere('encf', 'like', "%{$search}%");
            });
        }

        $invoices = $query->paginate(25);

        return response()->json($invoices);
    }

    /**
     * Show a single received invoice.
     */
    public function show($id)
    {
        $invoice = ReceivedInvoice::findOrFail($id);
        return response()->json($invoice);
    }

    /**
     * Approve a received invoice commercially.
     * Generates ACECF, signs, and sends to DGII + emisor.
     */
    public function approve($id, AcecfService $acecfService)
    {
        $invoice = ReceivedInvoice::findOrFail($id);

        if ($invoice->approval_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Esta factura ya fue procesada (estado: {$invoice->approval_status})",
            ], 422);
        }

        if (!$invoice->requiresApproval()) {
            return response()->json([
                'success' => false,
                'message' => "Este tipo de e-CF ({$invoice->ecf_type}) no requiere aprobación comercial",
            ], 422);
        }

        $result = $acecfService->sendAprobacionComercial($invoice, 1);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Aprobación Comercial enviada exitosamente a la DGII'
                : 'Error al enviar Aprobación Comercial: ' . $result['errors'],
            'dgii_response' => $result['dgii_response'],
            'emisor_response' => $result['emisor_response'],
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Reject a received invoice commercially.
     */
    public function reject($id, Request $request, AcecfService $acecfService)
    {
        $request->validate([
            'reason' => 'required|string|max:250',
        ]);

        $invoice = ReceivedInvoice::findOrFail($id);

        if ($invoice->approval_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Esta factura ya fue procesada (estado: {$invoice->approval_status})",
            ], 422);
        }

        $result = $acecfService->sendAprobacionComercial($invoice, 2, $request->input('reason'));

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Rechazo Comercial enviado exitosamente a la DGII'
                : 'Error al enviar Rechazo Comercial: ' . $result['errors'],
            'dgii_response' => $result['dgii_response'],
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Get summary counts for dashboard.
     */
    public function summary()
    {
        return response()->json([
            'pending' => ReceivedInvoice::where('approval_status', 'pending')->count(),
            'approved' => ReceivedInvoice::where('approval_status', 'approved')->count(),
            'rejected' => ReceivedInvoice::where('approval_status', 'rejected')->count(),
            'total' => ReceivedInvoice::count(),
        ]);
    }
}
