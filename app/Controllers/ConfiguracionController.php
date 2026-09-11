<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Usuario;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('configuracion/index');
    }

    public function perfil()
    {
        $this->requireAuth();
        $this->requireMethod('POST');

        $userId = $_SESSION['user_id'];
        $nombre = trim(Request::post('nombre', ''));
        $email = trim(Request::post('email', ''));

        if (empty($nombre)) {
            Response::error('El nombre es requerido', 422);
        }

        $model = new Usuario();
        $model->update($userId, [
            'nombre' => $nombre,
            'email' => $email ?: null,
        ]);

        $user = $model->findById($userId);
        $_SESSION['user_nombre'] = $user['nombre'];

        Response::success(['nombre' => $user['nombre'], 'email' => $user['email']], 'Perfil actualizado exitosamente');
    }

    public function contrasena()
    {
        $this->requireAuth();
        $this->requireMethod('POST');

        $userId = $_SESSION['user_id'];
        $actual = Request::post('passActual', '');
        $nueva = Request::post('passNueva', '');

        if (empty($actual) || empty($nueva)) {
            Response::error('Todos los campos son requeridos', 422);
        }

        if (strlen($nueva) < 6) {
            Response::error('La nueva contraseña debe tener al menos 6 caracteres', 422);
        }

        $model = new Usuario();
        $user = $model->findById($userId);

        if (!password_verify($actual, $user['contrasena'])) {
            Response::error('La contraseña actual no es correcta', 403);
        }

        $model->update($userId, [
            'contrasena' => password_hash($nueva, PASSWORD_DEFAULT),
        ]);

        Response::success(null, 'Contraseña actualizada exitosamente');
    }
}
