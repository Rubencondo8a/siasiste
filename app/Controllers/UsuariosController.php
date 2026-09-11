<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Usuario;
use App\Models\Role;

class UsuariosController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('usuarios/index');
    }

    public function all()
    {
        $this->requireAuth();

        $model = new Usuario();
        $usuarios = $model->findAll();

        foreach ($usuarios as &$u) {
            $u['rol_nombre'] = $u['rol_nombre'] ?? $u['rol'] ?? '—';
        }

        Response::json(['data' => $usuarios]);
    }

    public function roles()
    {
        $this->requireAdmin();
        $model = new Role();
        Response::json(['data' => $model->findActivos()]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $usuario = trim(Request::post('usuario', ''));
        $contrasena = Request::post('contrasena', '');
        $nombre = trim(Request::post('nombre', ''));

        if (empty($usuario) || empty($contrasena) || empty($nombre)) {
            Response::error('Usuario, contraseña y nombre son requeridos', 422);
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
            'role_id' => (int) Request::post('role_id', 0),
            'email' => trim(Request::post('email', '')) ?: null,
            'activo' => 1
        ]);

        Response::success(['id' => $id], 'Usuario creado exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Usuario();
        $usuario = $model->findById($id);
        if (!$usuario) {
            Response::error('Usuario no encontrado', 404);
        }

        $fields = [];
        $allowed = ['usuario', 'nombre', 'email', 'role_id', 'activo'];

        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $fields[$field] = $_POST[$field];
            }
        }

        if (isset($_POST['contrasena']) && !empty(trim($_POST['contrasena']))) {
            if (strlen($_POST['contrasena']) < 6) {
                Response::error('La contraseña debe tener al menos 6 caracteres', 422);
            }
            $fields['contrasena'] = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        if (isset($fields['usuario'])) {
            $stmt = $model->db->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
            $stmt->execute([$fields['usuario'], $id]);
            if ($stmt->fetch()) {
                Response::error('El nombre de usuario ya existe', 409);
            }
        }

        $model->update($id, $fields);
        Response::success(null, 'Usuario actualizado exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Usuario();
        if (!$model->findById($id)) {
            Response::error('Usuario no encontrado', 404);
        }

        if ($id == ($_SESSION['user_id'] ?? 0)) {
            Response::error('No puedes eliminar tu propio usuario', 403);
        }

        $model->delete($id);
        Response::success(null, 'Usuario eliminado exitosamente');
    }
}
