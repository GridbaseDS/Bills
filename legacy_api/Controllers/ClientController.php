<?php
namespace App\Controllers;

use App\Models\Client;

class ClientController
{
    private Client $model;

    public function __construct() { $this->model = new Client(); }

    public function index(array $params): void
    {
        $result = $this->model->getAll($params, (int)($params['page'] ?? 1), (int)($params['per_page'] ?? 20));
        jsonResponse($result);
    }

    public function show(int $id): void
    {
        $client = $this->model->getById($id);
        if (!$client) jsonResponse(['error' => 'Client not found'], 404);
        $client['invoices'] = $this->model->getInvoiceHistory($id);
        $client['quotes'] = $this->model->getQuoteHistory($id);
        jsonResponse($client);
    }

    public function store(array $input): void
    {
        if (empty($input['contact_name']) || empty($input['email'])) {
            jsonResponse(['error' => 'Contact name and email are required'], 400);
        }
        $id = $this->model->create($input);
        jsonResponse(['success' => true, 'client' => $this->model->getById($id)], 201);
    }

    public function update(int $id, array $input): void
    {
        $existing = $this->model->getById($id);
        if (!$existing) jsonResponse(['error' => 'Client not found'], 404);
        $this->model->update($id, $input);
        jsonResponse(['success' => true, 'client' => $this->model->getById($id)]);
    }

    public function destroy(int $id): void
    {
        $this->model->delete($id);
        jsonResponse(['success' => true]);
    }

    public function selectList(): void
    {
        jsonResponse($this->model->getSelectList());
    }
}
