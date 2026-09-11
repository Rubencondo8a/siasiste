<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Programa;

class ProgramaController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('programas/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Programa();
        $items = $model->findAll('nombre ASC');
        Response::json(['data' => $items]);
    }

    public function activos()
    {
        $this->requireAuth();
        $model = new Programa();
        $stmt = $model->db->query("SELECT * FROM programas_estudio WHERE activo = 1 ORDER BY nombre ASC");
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $nombre = trim(Request::post('nombre', ''));
        if (empty($nombre)) {
            Response::error('El nombre es requerido', 422);
        }

        $model = new Programa();
        $existing = $model->db->prepare("SELECT id FROM programas_estudio WHERE nombre = ?");
        $existing->execute([$nombre]);
        if ($existing->fetch()) {
            Response::error('Ya existe un programa con ese nombre', 422);
        }

        $id = $model->create([
            'nombre' => $nombre,
            'descripcion' => trim(Request::post('descripcion', '')) ?: null,
            'duracion_semestres' => (int) Request::post('duracion_semestres', 6),
            'activo' => Request::post('activo', 1)
        ]);

        Response::success(['id' => $id], 'Programa registrado exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Programa();
        $item = $model->findById($id);
        if (!$item) {
            Response::error('Programa no encontrado', 404);
        }

        $fields = [];
        if (isset($_POST['nombre'])) $fields['nombre'] = trim(Request::post('nombre'));
        if (isset($_POST['descripcion'])) $fields['descripcion'] = trim(Request::post('descripcion'));
        if (isset($_POST['duracion_semestres'])) $fields['duracion_semestres'] = (int) Request::post('duracion_semestres');
        if (isset($_POST['activo'])) $fields['activo'] = (int) Request::post('activo');

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        if (isset($fields['nombre']) && $fields['nombre'] !== $item['nombre']) {
            $existing = $model->db->prepare("SELECT id FROM programas_estudio WHERE nombre = ? AND id != ?");
            $existing->execute([$fields['nombre'], $id]);
            if ($existing->fetch()) {
                Response::error('Ya existe un programa con ese nombre', 422);
            }
        }

        $model->update($id, $fields);
        Response::success(null, 'Programa actualizado exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Programa();
        if (!$model->findById($id)) {
            Response::error('Programa no encontrado', 404);
        }

        $model->delete($id);
        Response::success(null, 'Programa eliminado exitosamente');
    }

    public function search($q)
    {
        $this->requireAuth();
        $model = new Programa();
        $items = $model->search(['nombre', 'descripcion'], $q);
        Response::json(['data' => $items]);
    }
}
