<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\PaymentLinkService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentLinkController extends Controller
{
    protected $paymentLinkService;

    public function __construct(PaymentLinkService $paymentLinkService)
    {
        $this->paymentLinkService = $paymentLinkService;
    }

    /**
     * Generate payment link for an invoice
     */
    public function generate(Request $request, $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $expiresInDays = $request->input('expires_in_days', 30);

        $paymentUrl = $this->paymentLinkService->generatePaymentLink($invoice, $expiresInDays);

        return response()->json([
            'success' => true,
            'data' => [
                'payment_url' => $paymentUrl,
                'payment_token' => $invoice->payment_token,
                'expires_at' => $invoice->payment_token_expires_at->toISOString(),
            ]
        ]);
    }

    /**
     * Send payment link via email
     */
    public function sendEmail($id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $result = $this->paymentLinkService->sendPaymentLinkEmail($invoice);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Link de pago enviado por email exitosamente'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al enviar el link de pago por email'
        ], 500);
    }

    /**
     * Send payment link via WhatsApp
     */
    public function sendWhatsApp($id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $result = $this->paymentLinkService->sendPaymentLinkWhatsApp($invoice);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Link de pago enviado por WhatsApp exitosamente'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al enviar el link de pago por WhatsApp'
        ], 500);
    }

    /**
     * Send payment link via both email and WhatsApp
     */
    public function sendBoth($id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $results = $this->paymentLinkService->sendPaymentLinkBoth($invoice);

        return response()->json([
            'success' => true,
            'data' => $results,
            'message' => 'Links de pago enviados'
        ]);
    }

    /**
     * Check if payment link is valid
     */
    public function checkValidity($id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $isValid = $this->paymentLinkService->isPaymentLinkValid($invoice);

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $isValid,
                'payment_token' => $invoice->payment_token,
                'expires_at' => $invoice->payment_token_expires_at?->toISOString(),
                'payment_url' => $isValid ? $invoice->getPaymentUrl() : null,
            ]
        ]);
    }

    /**
     * Regenerate payment link
     */
    public function regenerate(Request $request, $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $expiresInDays = $request->input('expires_in_days', 30);

        $paymentUrl = $this->paymentLinkService->regeneratePaymentLink($invoice, $expiresInDays);

        return response()->json([
            'success' => true,
            'data' => [
                'payment_url' => $paymentUrl,
                'payment_token' => $invoice->payment_token,
                'expires_at' => $invoice->payment_token_expires_at->toISOString(),
            ],
            'message' => 'Link de pago regenerado exitosamente'
        ]);
    }

    /**
     * Get invoice payment information
     */
    public function getPaymentInfo($id): JsonResponse
    {
        $invoice = Invoice::with(['client', 'payments'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'amount_paid' => $invoice->amount_paid,
                'remaining_balance' => $invoice->getRemainingBalance(),
                'currency' => $invoice->currency,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date->toISOString(),
                'payment_link' => [
                    'is_valid' => $invoice->isPaymentTokenValid(),
                    'url' => $invoice->isPaymentTokenValid() ? $invoice->getPaymentUrl() : null,
                    'expires_at' => $invoice->payment_token_expires_at?->toISOString(),
                ],
                'client' => [
                    'name' => $invoice->client->company_name ?: $invoice->client->contact_name,
                    'email' => $invoice->client->email,
                    'phone' => $invoice->client->phone,
                    'whatsapp' => $invoice->client->whatsapp,
                ],
                'payments' => $invoice->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'payment_date' => $payment->payment_date->toISOString(),
                        'reference' => $payment->reference,
                    ];
                }),
            ]
        ]);
    }
}
