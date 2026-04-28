<?php
namespace App\Controllers;

use App\Models\User;
use App\Middleware\AuthMiddleware;

class AuthController
{
    public function login(array $input): void
    {
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            jsonResponse(['error' => 'Email and password are required'], 400);
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(['error' => 'Invalid credentials'], 401);
        }

        AuthMiddleware::login($user);
        $userModel->updateLastLogin($user['id']);

        jsonResponse([
            'success' => true,
            'user' => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
    }

    public function logout(): void
    {
        AuthMiddleware::logout();
        jsonResponse(['success' => true]);
    }

    public function session(): void
    {
        $user = AuthMiddleware::check();
        if ($user) {
            jsonResponse(['authenticated' => true, 'user' => $user]);
        } else {
            jsonResponse(['authenticated' => false], 401);
        }
    }
}
