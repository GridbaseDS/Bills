<?php
namespace App\Controllers;

use App\Models\Quote;
use App\Services\PDFService;
use App\Services\EmailService;
use App\Services\WhatsAppService;

class QuoteController
{
    private Quote $model;

    public function __construct() { $this->model = new Quote(); }

    public function index(array $params): void
    {
        $result = $this->model->getAll($params, (int)($params['page'] ?? 1), (int)($params['per_page'] ?? 20));
        jsonResponse($result);
    }

    public function show(int $id): void
    {
        $quote = $this->model->getById($id);
        if (!$quote) jsonResponse(['error' => 'Quote not found'], 404);
        jsonResponse($quote);
    }

    public function store(array $input): void
    {
        if (empty($input['client_id']) || empty($input['items'])) {
            jsonResponse(['error' => 'Client and items are required'], 400);
        }
        $user = \App\Middleware\AuthMiddleware::check();
        $input['created_by'] = $user['id'] ?? null;
        $id = $this->model->create($input, $input['items']);
        jsonResponse(['success' => true, 'quote' => $this->model->getById($id)], 201);
    }

    public function update(int $id, array $input): void
    {
        $existing = $this->model->getById($id);
        if (!$existing) jsonResponse(['error' => 'Quote not found'], 404);
        $this->model->update($id, $input, $input['items'] ?? []);
        jsonResponse(['success' => true, 'quote' => $this->model->getById($id)]);
    }

    public function destroy(int $id): void
    {
        $this->model->delete($id);
        jsonResponse(['success' => true]);
    }

    public function convert(int $id): void
    {
        $invoiceId = $this->model->convertToInvoice($id);
        $invoiceModel = new \App\Models\Invoice();
        jsonResponse(['success' => true, 'invoice_id' => $invoiceId, 'invoice' => $invoiceModel->getById($invoiceId)]);
    }

    public function pdf(int $id): void
    {
        $quote = $this->model->getById($id);
        if (!$quote) jsonResponse(['error' => 'Quote not found'], 404);
        $pdfService = new PDFService();
        $path = $quote['pdf_path'] && file_exists($quote['pdf_path']) ? $quote['pdf_path'] : $pdfService->generateQuote($quote);
        $pdfService->streamPDF($path, "Quote-{$quote['quote_number']}.pdf");
    }

    public function sendEmail(int $id): void
    {
        $quote = $this->model->getById($id);
        if (!$quote) jsonResponse(['error' => 'Quote not found'], 404);
        $pdfPath = (new PDFService())->generateQuote($quote);
        $result = (new EmailService())->sendQuote($quote, $pdfPath);
        if ($result['success']) {
            $this->model->update($id, ['status' => 'sent']);
            \App\Models\Database::getInstance()->update('quotes', ['sent_at' => date('Y-m-d H:i:s'), 'sent_via' => 'email'], ['id' => $id]);
        }
        jsonResponse($result);
    }

    public function sendWhatsApp(int $id): void
    {
        $quote = $this->model->getById($id);
        if (!$quote) jsonResponse(['error' => 'Quote not found'], 404);
        if (empty($quote['client_whatsapp'])) jsonResponse(['error' => 'Client has no WhatsApp number'], 400);
        $result = (new WhatsAppService())->sendQuoteNotification($quote, $quote['client_whatsapp']);
        if ($result['success']) {
            $this->model->update($id, ['status' => 'sent']);
        }
        jsonResponse($result);
    }
}
