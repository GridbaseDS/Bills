<?php
namespace App\Controllers;

use App\Models\RecurringInvoice;

class RecurringController
{
    private RecurringInvoice $model;

    public function __construct() { $this->model = new RecurringInvoice(); }

    public function index(array $params): void
    {
        $result = $this->model->getAll($params, (int)($params['page'] ?? 1), (int)($params['per_page'] ?? 20));
        jsonResponse($result);
    }

    public function show(int $id): void
    {
        $rec = $this->model->getById($id);
        if (!$rec) jsonResponse(['error' => 'Recurring invoice not found'], 404);
        jsonResponse($rec);
    }

    public function store(array $input): void
    {
        if (empty($input['client_id']) || empty($input['items'])) {
            jsonResponse(['error' => 'Client and items are required'], 400);
        }
        $user = \App\Middleware\AuthMiddleware::check();
        $input['created_by'] = $user['id'] ?? null;
        $id = $this->model->create($input, $input['items']);
        jsonResponse(['success' => true, 'recurring' => $this->model->getById($id)], 201);
    }

    public function update(int $id, array $input): void
    {
        $existing = $this->model->getById($id);
        if (!$existing) jsonResponse(['error' => 'Recurring invoice not found'], 404);
        $this->model->update($id, $input, $input['items'] ?? []);
        jsonResponse(['success' => true, 'recurring' => $this->model->getById($id)]);
    }

    public function toggle(int $id, array $input): void
    {
        $status = $input['status'] ?? 'paused';
        $this->model->toggleStatus($id, $status);
        jsonResponse(['success' => true]);
    }

    public function destroy(int $id): void
    {
        $this->model->delete($id);
        jsonResponse(['success' => true]);
    }
}
