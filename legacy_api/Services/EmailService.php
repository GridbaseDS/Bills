<?php
namespace App\Services;

use App\Models\Setting;

/**
 * Email Service using PHPMailer for sending styled invoice/quote emails.
 * Falls back to config files, but prefers DB settings for runtime flexibility.
 */
class EmailService
{
    private array $config;

    public function __construct()
    {
        $fileConfig = require __DIR__ . '/../../config/mail.php';
        // Override with DB settings if available
        try {
            $setting = new Setting();
            $dbConfig = $setting->getGroup('email');
            $this->config = [
                'host'       => $dbConfig['smtp_host'] ?: $fileConfig['host'],
                'port'       => (int)($dbConfig['smtp_port'] ?: $fileConfig['port']),
                'username'   => $dbConfig['smtp_username'] ?: $fileConfig['username'],
                'password'   => $dbConfig['smtp_password'] ?: $fileConfig['password'],
                'encryption' => $dbConfig['smtp_encryption'] ?: $fileConfig['encryption'],
                'from_name'  => $dbConfig['smtp_from_name'] ?: $fileConfig['from_name'],
                'from_email' => $dbConfig['smtp_from_email'] ?: $fileConfig['from_email'],
            ];
        } catch (\Exception $e) {
            $this->config = $fileConfig;
        }
    }

    /**
     * Send an invoice email with PDF attachment.
     */
    public function sendInvoice(array $invoice, string $pdfPath): array
    {
        $subject = "Invoice {$invoice['invoice_number']} from Gridbase Digital Solutions";
        $body = $this->renderTemplate('invoice-email', [
            'invoice' => $invoice,
            'company' => $this->getCompanyInfo(),
        ]);

        return $this->send(
            $invoice['client_email'],
            $invoice['contact_name'],
            $subject,
            $body,
            $pdfPath,
            "Invoice-{$invoice['invoice_number']}.pdf"
        );
    }

    /**
     * Send a quote email with PDF attachment.
     */
    public function sendQuote(array $quote, string $pdfPath): array
    {
        $subject = "Quote {$quote['quote_number']} from Gridbase Digital Solutions";
        $body = $this->renderTemplate('quote-email', [
            'quote'   => $quote,
            'company' => $this->getCompanyInfo(),
        ]);

        return $this->send(
            $quote['client_email'],
            $quote['contact_name'],
            $subject,
            $body,
            $pdfPath,
            "Quote-{$quote['quote_number']}.pdf"
        );
    }

    /**
     * Send a payment reminder email.
     */
    public function sendReminder(array $invoice): array
    {
        $subject = "Payment Reminder: Invoice {$invoice['invoice_number']}";
        $body = $this->renderTemplate('reminder-email', [
            'invoice' => $invoice,
            'company' => $this->getCompanyInfo(),
        ]);

        $pdfPath = $invoice['pdf_path'] ?? null;
        return $this->send(
            $invoice['client_email'],
            $invoice['contact_name'],
            $subject,
            $body,
            $pdfPath,
            $pdfPath ? "Invoice-{$invoice['invoice_number']}.pdf" : null
        );
    }

    /**
     * Core send method using PHPMailer.
     */
    private function send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $attachPath = null, ?string $attachName = null): array
    {
        // PHPMailer must be loaded via Composer autoload
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'] === 'ssl'
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->config['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            if ($attachPath && file_exists($attachPath)) {
                $mail->addAttachment($attachPath, $attachName ?? basename($attachPath));
            }

            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            return ['success' => false, 'message' => 'Email error: ' . $e->getMessage()];
        }
    }

    /**
     * Render an email template with variables.
     */
    private function renderTemplate(string $templateName, array $vars): string
    {
        $templatePath = __DIR__ . "/../../templates/{$templateName}.php";
        if (!file_exists($templatePath)) {
            return '<p>Template not found.</p>';
        }
        extract($vars);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    private function getCompanyInfo(): array
    {
        try {
            return (new Setting())->getCompanyInfo();
        } catch (\Exception $e) {
            return ['company_name' => 'Gridbase Digital Solutions'];
        }
    }
}
