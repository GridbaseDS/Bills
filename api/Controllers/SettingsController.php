<?php
namespace App\Controllers;

use App\Models\Setting;

class SettingsController
{
    private Setting $model;

    public function __construct() { $this->model = new Setting(); }

    public function index(): void
    {
        jsonResponse($this->model->getAll());
    }

    public function update(array $input): void
    {
        if (empty($input)) jsonResponse(['error' => 'No settings provided'], 400);
        $this->model->updateBulk($input);
        jsonResponse(['success' => true, 'settings' => $this->model->getAll()]);
    }
}
