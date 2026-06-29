<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\WhatsApp\WhatsAppDriverInterface;
use App\Services\WhatsApp\MetaWhatsAppDriver;
use App\Services\WhatsApp\EvolutionWhatsAppDriver;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Service — public facade.
 *
 * Selects the active driver from the 'whatsapp_driver' setting:
 *   - 'meta'      → Meta Cloud API (graph.facebook.com)
 *   - 'evolution' → Evolution API (self-hosted)
 *
 * All controllers and jobs that used the old WhatsAppService continue
 * working without changes — the public method signatures are preserved.
 */
class WhatsAppService
{
    private WhatsAppDriverInterface $driver;
    private string $driverName;

    public function __construct()
    {
        $settings = Setting::getAll();

        $this->driverName = $settings['whatsapp_driver'] ?? env('WHATSAPP_DRIVER', 'meta');

        $this->driver = match ($this->driverName) {
            'evolution' => new EvolutionWhatsAppDriver($settings),
            default     => new MetaWhatsAppDriver($settings),
        };

        Log::debug("WhatsAppService using driver: {$this->driverName}");
    }

    // ─────────────────────────────────────────────────────────────
    // Delegated public methods (unchanged interface)
    // ─────────────────────────────────────────────────────────────

    public function isEnabled(): bool
    {
        return $this->driver->isEnabled();
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function getDriver(): WhatsAppDriverInterface
    {
        return $this->driver;
    }

    public function sendInvoice($invoice, string $recipientPhone, ?string $paymentLink = null, ?string $pdfContent = null, ?string $pdfFilename = null): array
    {
        return $this->driver->sendInvoice($invoice, $recipientPhone, $paymentLink, $pdfContent, $pdfFilename);
    }

    public function sendQuote($quote, string $recipientPhone, ?string $pdfContent = null, ?string $pdfFilename = null): array
    {
        return $this->driver->sendQuote($quote, $recipientPhone, $pdfContent, $pdfFilename);
    }

    public function sendPaymentReminder($invoice, string $recipientPhone, ?string $paymentLink = null): array
    {
        return $this->driver->sendPaymentReminder($invoice, $recipientPhone, $paymentLink);
    }

    public function sendPaymentConfirmation($invoice, string $recipientPhone, $paymentAmount = null): array
    {
        return $this->driver->sendPaymentConfirmation($invoice, $recipientPhone, $paymentAmount);
    }

    public function sendTextMessage(string $recipientPhone, string $message): array
    {
        return $this->driver->sendTextMessage($recipientPhone, $message);
    }

    public function sendDocument(string $recipientPhone, string $fileContent, string $filename, string $caption = ''): array
    {
        return $this->driver->sendDocument($recipientPhone, $fileContent, $filename, $caption);
    }

    // ─────────────────────────────────────────────────────────────
    // Legacy method aliases (backward compatibility)
    // ─────────────────────────────────────────────────────────────

    public function sendInvoiceNotification($invoice, string $recipientPhone): array
    {
        return $this->sendInvoice($invoice, $recipientPhone);
    }

    public function sendQuoteNotification($quote, string $recipientPhone): array
    {
        return $this->sendQuote($quote, $recipientPhone);
    }
}
