<?php
namespace App\Middleware;

/**
 * Authentication middleware using PHP sessions.
 */
class AuthMiddleware
{
    /**
     * Start session and verify user is authenticated.
     * Returns user data if authenticated, or sends 401 and exits.
     */
    public static function authenticate(): array
    {
        self::startSession();

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['user_role'] ?? 'viewer',
        ];
    }

    /**
     * Check if user is authenticated without blocking.
     */
    public static function check(): ?array
    {
        self::startSession();

        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role'  => $_SESSION['user_role'] ?? 'viewer',
        ];
    }

    /**
     * Set session data after successful login.
     */
    public static function login(array $user): void
    {
        self::startSession();
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['logged_in_at'] = time();
    }

    /**
     * Destroy session on logout.
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Start session with secure settings.
     */
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../config/app.php';
            $sessionConfig = $config['session'] ?? [];

            session_name($sessionConfig['name'] ?? 'gridbase_session');
            session_set_cookie_params([
                'lifetime' => $sessionConfig['lifetime'] ?? 7200,
                'path'     => '/',
                'secure'   => $sessionConfig['secure'] ?? false,
                'httponly'  => $sessionConfig['httponly'] ?? true,
                'samesite' => $sessionConfig['samesite'] ?? 'Strict',
            ]);

            session_start();
        }
    }
}
