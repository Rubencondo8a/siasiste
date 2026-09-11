<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Especialidad;

class EspecialidadController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('especialidades/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Especialidad();
        $items = $model->findAll('nombre ASC');
        Response::json(['data' => $items]);
    }

    public function activas()
    {
        $this->requireAuth();
        $model = new Especialidad();
        $items = $model->findActivas();
        Response::json(['data' => $items]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $nombre = trim(Request::post('nombre', ''));
        if (empty($nombre)) {
            Response::error('El nombre es requerido', 422);
        }

        $model = new Especialidad();
        $existing = $model->db->prepare("SELECT id FROM especialidades WHERE nombre = ?");
        $existing->execute([$nombre]);
        if ($existing->fetch()) {
            Response::error('Ya existe una especialidad con ese nombre', 422);
        }

        $id = $model->create([
            'nombre' => $nombre,
            'activo' => Request::post('activo', 1)
        ]);

        Response::success(['id' => $id], 'Especialidad registrada exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Especialidad();
        $item = $model->findById($id);
        if (!$item) {
            Response::error('Especialidad no encontrada', 404);
        }

        $fields = [];
        if (isset($_POST['nombre'])) $fields['nombre'] = trim(Request::post('nombre'));
        if (isset($_POST['activo'])) $fields['activo'] = (int) Request::post('activo');

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        if (isset($fields['nombre']) && $fields['nombre'] !== $item['nombre']) {
            $existing = $model->db->prepare("SELECT id FROM especialidades WHERE nombre = ? AND id != ?");
            $existing->execute([$fields['nombre'], $id]);
            if ($existing->fetch()) {
                Response::error('Ya existe una especialidad con ese nombre', 422);
            }
        }

        $model->update($id, $fields);
        Response::success(null, 'Especialidad actualizada exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Especialidad();
        if (!$model->findById($id)) {
            Response::error('Especialidad no encontrada', 404);
        }

        $model->delete($id);
        Response::success(null, 'Especialidad eliminada exitosamente');
    }

    public function roles()
    {
        $this->requireAuth();
        $items = (new \App\Models\Especialidad())->findAll('nombre ASC');
        Response::json(['data' => $items]);
    }
}
