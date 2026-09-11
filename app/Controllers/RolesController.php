<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Role;

class RolesController extends Controller
{
    public function index()
    {
        $this->requireAdmin();
        Response::json(['data' => (new Role())->findAll()]);
    }

    public function all()
    {
        $this->requireAdmin();
        Response::json(['data' => (new Role())->findAll()]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $nombre = trim(Request::post('nombre', ''));
        $nombreMostrar = trim(Request::post('nombre_mostrar', ''));
        if (empty($nombre) || empty($nombreMostrar)) {
            Response::error('Nombre y nombre_mostrar son requeridos', 422);
        }

        $model = new Role();
        $id = $model->create([
            'nombre' => slugify($nombre),
            'nombre_mostrar' => $nombreMostrar,
            'descripcion' => trim(Request::post('descripcion', '')) ?: null,
            'activo' => (int) Request::post('activo', 1)
        ]);
        Response::success(['id' => $id], 'Rol creado exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $model = new Role();
        if (!$model->findById($id)) {
            Response::error('Rol no encontrado', 404);
        }

        $fields = [];
        foreach (['nombre', 'nombre_mostrar', 'descripcion', 'activo'] as $k) {
            if (isset($_POST[$k])) {
                $fields[$k] = $k === 'activo' ? (int) $_POST[$k] : trim($_POST[$k]);
            }
        }

        if (isset($fields['nombre'])) {
            $fields['nombre'] = slugify($fields['nombre']);
        }

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        $model->update($id, $fields);
        Response::success(null, 'Rol actualizado exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $model = new Role();
        if (!$model->findById($id)) {
            Response::error('Rol no encontrado', 404);
        }

        $stmt = $model->db->prepare("SELECT COUNT(*) FROM usuarios WHERE role_id = ?");
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            Response::error('No se puede eliminar un rol con usuarios asignados. Desactívalo en su lugar.', 409);
        }

        $model->db->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
        Response::success(null, 'Rol eliminado exitosamente');
    }
}
