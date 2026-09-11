<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function loginForm()
    {
        if (Request::isPost()) {
            return $this->login();
        }

        if (isAuthenticated()) {
            $this->redirect('/public/index.php?route=dashboard');
        }
        $this->viewOnly('auth/login');
    }

    public function login()
    {
        self::checkRateLimit();

        $usuario = trim(Request::post('usuario', ''));
        $contrasena = Request::post('contrasena', '');

        if (empty($usuario) || empty($contrasena)) {
            Response::error('Usuario y contraseña son requeridos', 422);
        }

        $model = new Usuario();
        $user = $model->findByUsuario($usuario);

        if (!$user || !password_verify($contrasena, $user['contrasena'])) {
            $_SESSION['login_attempts'][] = time();
            Response::error('Credenciales incorrectas', 401);
        }

        if (!$user['activo']) {
            $_SESSION['login_attempts'][] = time();
            Response::error('Cuenta desactivada. Contacte al administrador.', 403);
        }

        session_regenerate_id(true);
        $_SESSION['login_attempts'] = [];

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_usuario'] = $user['usuario'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['user_role'] = $user['rol'];
        $_SESSION['user_role_id'] = $user['role_id'];

        _logAudit('login', 'usuarios', $user['id'], "Inicio de sesión: {$user['usuario']}");

        if (!Request::isAjax()) {
            Response::redirect('/public/index.php?route=dashboard');
        }

        Response::json([
            'success' => true,
            'redirect' => BASE_URL . '/public/index.php?route=dashboard',
            'user' => ['id' => $user['id'], 'nombre' => $user['nombre'], 'rol' => $user['rol']]
        ]);
    }

    private static function checkRateLimit()
    {
        $maxAttempts = 5;
        $window = 900;
        $now = time();
        $attempts = $_SESSION['login_attempts'] ?? [];

        $attempts = array_values(array_filter($attempts, fn($t) => $now - $t < $window));
        $_SESSION['login_attempts'] = $attempts;

        if (count($attempts) >= $maxAttempts) {
            $retryAfter = $window - ($now - min($attempts));
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            Response::error("Demasiados intentos. Espere " . ceil($retryAfter / 60) . " minutos.", 429);
        }
    }

    public function logout()
    {
        _logAudit('logout', 'usuarios', $_SESSION['user_id'] ?? null, "Cierre de sesión: " . ($_SESSION['user_usuario'] ?? 'desconocido'));
        session_destroy();
        Response::redirect('/public/index.php?route=login');
    }

    public function register()
    {
        AuthMiddleware::admin();
        $this->requireMethod('POST');

        $usuario = trim(Request::post('usuario', ''));
        $contrasena = Request::post('contrasena', '');
        $nombre = trim(Request::post('nombre', ''));
        $email = trim(Request::post('email', ''));
        $roleId = Request::post('role_id');

        if (empty($usuario) || empty($contrasena) || empty($nombre) || empty($roleId)) {
            Response::error('Usuario, contraseña, nombre y rol son requeridos', 422);
        }

        if (strlen($contrasena) < 6) {
            Response::error('La contraseña debe tener al menos 6 caracteres', 422);
        }

        $model = new Usuario();
        if ($model->findByUsuario($usuario)) {
            Response::error('El nombre de usuario ya existe', 409);
        }

        $id = $model->create([
            'usuario' => $usuario,
            'contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
            'nombre' => $nombre,
            'email' => $email ?: null,
            'role_id' => (int) $roleId,
            'activo' => 1
        ]);

        Response::success(['id' => $id], 'Usuario creado exitosamente', 201);
    }
}
