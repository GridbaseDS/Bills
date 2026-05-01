<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class PaymentLinkService
{
    /**
     * Generate and send payment link via email
     */
    public function sendPaymentLinkEmail(Invoice $invoice, int $expiresInDays = 30): bool
    {
        try {
            // Generate payment token if not exists or expired
            if (!$invoice->isPaymentTokenValid()) {
                $invoice->generatePaymentToken($expiresInDays);
            }

            $paymentUrl = $invoice->getPaymentUrl();
            $client = $invoice->client;

            // Send email (integrate with your email service)
            $emailService = new EmailService();
            
            $subject = "Link de Pago - Factura #{$invoice->invoice_number}";
            
            $body = "
                <h2>Hola {$client->contact_name},</h2>
                
                <p>Puede pagar su factura #{$invoice->invoice_number} de forma segura usando el siguiente enlace:</p>
                
                <p style='margin: 20px 0;'>
                    <a href='{$paymentUrl}' 
                       style='background: #667eea; color: white; padding: 12px 24px; 
                              text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Pagar Ahora
                    </a>
                </p>
                
                <p><strong>Detalles de la factura:</strong></p>
                <ul>
                    <li>Número: {$invoice->invoice_number}</li>
                    <li>Fecha de emisión: {$invoice->issue_date->format('d/m/Y')}</li>
                    <li>Fecha de vencimiento: {$invoice->due_date->format('d/m/Y')}</li>
                    <li>Monto total: {$invoice->currency} " . number_format($invoice->total, 2) . "</li>
                    <li>Saldo pendiente: {$invoice->currency} " . number_format($invoice->getRemainingBalance(), 2) . "</li>
                </ul>
                
                <p>Este enlace es válido hasta el {$invoice->payment_token_expires_at->format('d/m/Y')}.</p>
                
                <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
                
                <p>Gracias,<br>Su equipo de facturación</p>
            ";

            // Use reflection to call private send method
            $reflection = new \ReflectionClass($emailService);
            $method = $reflection->getMethod('send');
            $method->setAccessible(true);
            $result = $method->invoke($emailService, $client->email, $client->contact_name, $subject, $body);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info("Payment link email sent for invoice #{$invoice->invoice_number} to {$client->email}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send payment link email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate and send payment link via WhatsApp
     */
    public function sendPaymentLinkWhatsApp(Invoice $invoice, int $expiresInDays = 30): bool
    {
        try {
            // Generate payment token if not exists or expired
            if (!$invoice->isPaymentTokenValid()) {
                $invoice->generatePaymentToken($expiresInDays);
            }

            $paymentUrl = $invoice->getPaymentUrl();
            $client = $invoice->client;

            if (!$client->whatsapp) {
                Log::warning("Client #{$client->id} has no WhatsApp number");
                return false;
            }

            // Send WhatsApp message (integrate with your WhatsApp service)
            $whatsappService = new WhatsAppService();
            
            $message = "Hola {$client->contact_name},\n\n";
            $message .= "Puede pagar su factura #{$invoice->invoice_number} de forma segura usando este enlace:\n\n";
            $message .= "{$paymentUrl}\n\n";
            $message .= "📋 *Detalles de la factura:*\n";
            $message .= "• Número: {$invoice->invoice_number}\n";
            $message .= "• Monto: {$invoice->currency} " . number_format($invoice->getRemainingBalance(), 2) . "\n";
            $message .= "• Vence: {$invoice->due_date->format('d/m/Y')}\n\n";
            $message .= "El enlace es válido hasta el {$invoice->payment_token_expires_at->format('d/m/Y')}.\n\n";
            $message .= "Muchas gracias! 🙏";

            $result = $whatsappService->sendTextMessage($client->whatsapp, $message);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            Log::info("Payment link WhatsApp sent for invoice #{$invoice->invoice_number} to {$client->whatsapp}");

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send payment link WhatsApp: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send payment link via both email and WhatsApp
     */
    public function sendPaymentLinkBoth(Invoice $invoice, int $expiresInDays = 30): array
    {
        return [
            'email' => $this->sendPaymentLinkEmail($invoice, $expiresInDays),
            'whatsapp' => $this->sendPaymentLinkWhatsApp($invoice, $expiresInDays),
        ];
    }

    /**
     * Generate payment link without sending
     */
    public function generatePaymentLink(Invoice $invoice, int $expiresInDays = 30): string
    {
        if (!$invoice->isPaymentTokenValid()) {
            $invoice->generatePaymentToken($expiresInDays);
        }

        return $invoice->getPaymentUrl();
    }

    /**
     * Check if payment link is still valid
     */
    public function isPaymentLinkValid(Invoice $invoice): bool
    {
        return $invoice->isPaymentTokenValid();
    }

    /**
     * Regenerate payment link
     */
    public function regeneratePaymentLink(Invoice $invoice, int $expiresInDays = 30): string
    {
        $invoice->generatePaymentToken($expiresInDays);
        return $invoice->getPaymentUrl();
    }
}
