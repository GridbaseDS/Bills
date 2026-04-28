<?php
namespace App\Services;

use App\Models\Setting;

/**
 * PDF Service using DomPDF to generate styled invoice/quote PDFs.
 */
class PDFService
{
    private string $storagePath;

    public function __construct()
    {
        $appConfig = require __DIR__ . '/../../config/app.php';
        $this->storagePath = $appConfig['storage']['invoices'];
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Generate an invoice PDF and return the file path.
     */
    public function generateInvoice(array $invoice): string
    {
        $company = (new Setting())->getCompanyInfo();
        $html = $this->renderTemplate('invoice-pdf', [
            'invoice' => $invoice,
            'company' => $company,
        ]);

        $filename = "invoice-{$invoice['invoice_number']}.pdf";
        $filepath = $this->storagePath . $filename;

        $this->generatePDF($html, $filepath);

        // Update invoice record with PDF path
        $db = \App\Models\Database::getInstance();
        $db->update('invoices', ['pdf_path' => $filepath], ['id' => $invoice['id']]);

        return $filepath;
    }

    /**
     * Generate a quote PDF and return the file path.
     */
    public function generateQuote(array $quote): string
    {
        $company = (new Setting())->getCompanyInfo();
        $html = $this->renderTemplate('invoice-pdf', [
            'invoice' => $quote,
            'company' => $company,
            'document_type' => 'quote',
        ]);

        $filename = "quote-{$quote['quote_number']}.pdf";
        $filepath = $this->storagePath . $filename;

        $this->generatePDF($html, $filepath);

        $db = \App\Models\Database::getInstance();
        $db->update('quotes', ['pdf_path' => $filepath], ['id' => $quote['id']]);

        return $filepath;
    }

    /**
     * Generate PDF from HTML string using DomPDF.
     */
    private function generatePDF(string $html, string $outputPath): void
    {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        file_put_contents($outputPath, $dompdf->output());
    }

    /**
     * Render a PHP template with variables.
     */
    private function renderTemplate(string $templateName, array $vars): string
    {
        $path = __DIR__ . "/../../templates/{$templateName}.php";
        if (!file_exists($path)) {
            throw new \Exception("Template not found: $templateName");
        }
        extract($vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Stream a PDF to the browser for preview/download.
     */
    public function streamPDF(string $filepath, string $filename = 'document.pdf', bool $download = false): void
    {
        if (!file_exists($filepath)) {
            http_response_code(404);
            echo json_encode(['error' => 'PDF not found']);
            return;
        }

        $disposition = $download ? 'attachment' : 'inline';
        header('Content-Type: application/pdf');
        header("Content-Disposition: {$disposition}; filename=\"{$filename}\"");
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($filepath);
        exit;
    }
}
