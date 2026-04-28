<?php
namespace App\Controllers;

use App\Models\Invoice;
use App\Services\PDFService;
use App\Services\EmailService;
use App\Services\WhatsAppService;

class InvoiceController
{
    private Invoice $model;

    public function __construct() { $this->model = new Invoice(); }

    public function index(array $params): void
    {
        $page = (int)($params['page'] ?? 1);
        $perPage = (int)($params['per_page'] ?? 20);
        $result = $this->model->getAll($params, $page, $perPage);
        jsonResponse($result);
    }

    public function show(int $id): void
    {
        $invoice = $this->model->getById($id);
        if (!$invoice) jsonResponse(['error' => 'Invoice not found'], 404);
        jsonResponse($invoice);
    }

    public function store(array $input): void
    {
        if (empty($input['client_id']) || empty($input['items'])) {
            jsonResponse(['error' => 'Client and items are required'], 400);
        }
        $user = \App\Middleware\AuthMiddleware::check();
        $input['created_by'] = $user['id'] ?? null;

        $id = $this->model->create($input, $input['items']);
        $invoice = $this->model->getById($id);
        jsonResponse(['success' => true, 'invoice' => $invoice], 201);
    }

    public function update(int $id, array $input): void
    {
        $existing = $this->model->getById($id);
        if (!$existing) jsonResponse(['error' => 'Invoice not found'], 404);
        $this->model->update($id, $input, $input['items'] ?? []);
        jsonResponse(['success' => true, 'invoice' => $this->model->getById($id)]);
    }

    public function destroy(int $id): void
    {
        $this->model->delete($id);
        jsonResponse(['success' => true]);
    }

    public function updateStatus(int $id, array $input): void
    {
        $status = $input['status'] ?? '';
        if (!$status) jsonResponse(['error' => 'Status is required'], 400);
        $this->model->updateStatus($id, $status);
        jsonResponse(['success' => true]);
    }

    public function addPayment(int $id, array $input): void
    {
        if (empty($input['amount'])) jsonResponse(['error' => 'Amount is required'], 400);
        $paymentId = $this->model->addPayment($id, $input);
        jsonResponse(['success' => true, 'payment_id' => $paymentId, 'invoice' => $this->model->getById($id)]);
    }

    public function pdf(int $id, string $download = '0'): void
    {
        $invoice = $this->model->getById($id);
        if (!$invoice) jsonResponse(['error' => 'Invoice not found'], 404);

        $pdfService = new PDFService();
        $path = $invoice['pdf_path'] && file_exists($invoice['pdf_path'])
            ? $invoice['pdf_path']
            : $pdfService->generateInvoice($invoice);

        $pdfService->streamPDF($path, "Invoice-{$invoice['invoice_number']}.pdf", $download === '1');
    }

    public function sendEmail(int $id): void
    {
        $invoice = $this->model->getById($id);
        if (!$invoice) jsonResponse(['error' => 'Invoice not found'], 404);

        $pdfService = new PDFService();
        $pdfPath = $pdfService->generateInvoice($invoice);

        $emailService = new EmailService();
        $result = $emailService->sendInvoice($invoice, $pdfPath);

        if ($result['success']) {
            $this->model->markSent($id, 'email');
        }
        jsonResponse($result);
    }

    public function sendWhatsApp(int $id): void
    {
        $invoice = $this->model->getById($id);
        if (!$invoice) jsonResponse(['error' => 'Invoice not found'], 404);
        if (empty($invoice['client_whatsapp'])) jsonResponse(['error' => 'Client has no WhatsApp number'], 400);

        $waService = new WhatsAppService();
        $result = $waService->sendInvoiceNotification($invoice, $invoice['client_whatsapp']);

        if ($result['success']) {
            $this->model->markSent($id, 'whatsapp');
        }
        jsonResponse($result);
    }
}
