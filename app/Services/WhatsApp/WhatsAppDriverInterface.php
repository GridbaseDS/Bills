<?php

namespace App\Services\WhatsApp;

interface WhatsAppDriverInterface
{
    /**
     * Whether this driver is properly configured and ready to send.
     */
    public function isEnabled(): bool;

    /**
     * Send a plain text message.
     */
    public function sendTextMessage(string $recipientPhone, string $message): array;

    /**
     * Send a document (PDF) with a caption.
     * $fileContent is the raw binary content of the file.
     */
    public function sendDocument(string $recipientPhone, string $fileContent, string $filename, string $caption = ''): array;

    /**
     * Send an invoice notification (with optional PDF and payment link).
     */
    public function sendInvoice($invoice, string $recipientPhone, ?string $paymentLink = null, ?string $pdfContent = null, ?string $pdfFilename = null): array;

    /**
     * Send a quote notification (with optional PDF).
     */
    public function sendQuote($quote, string $recipientPhone, ?string $pdfContent = null, ?string $pdfFilename = null): array;

    /**
     * Send a payment reminder for an overdue invoice.
     */
    public function sendPaymentReminder($invoice, string $recipientPhone, ?string $paymentLink = null): array;

    /**
     * Send a payment confirmation after a payment is received.
     */
    public function sendPaymentConfirmation($invoice, string $recipientPhone, $paymentAmount = null): array;
}
