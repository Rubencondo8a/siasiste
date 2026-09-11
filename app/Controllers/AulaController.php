<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Aula;

class AulaController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('aulas/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Aula();
        $aulas = $model->findAll('nombre ASC');
        Response::json(['data' => $aulas]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $codigo = trim(Request::post('codigo', ''));
        $nombre = trim(Request::post('nombre', ''));

        if (empty($codigo) || empty($nombre)) {
            Response::error('Código y nombre son requeridos', 422);
        }

        $model = new Aula();
        $stmt = $model->db->prepare("SELECT id FROM aulas WHERE codigo = ?");
        $stmt->execute([$codigo]);
        if ($stmt->fetch()) {
            Response::error('El código de aula ya existe', 409);
        }

        $id = $model->create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'capacidad' => (int) Request::post('capacidad', 40),
            'edificio' => trim(Request::post('edificio', '')) ?: null,
            'tipo' => trim(Request::post('tipo', '')) ?: null,
            'activo' => 1
        ]);

        Response::success(['id' => $id], 'Aula registrada exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Aula();
        if (!$model->findById($id)) {
            Response::error('Aula no encontrada', 404);
        }

        $fields = [];
        $allowed = ['codigo', 'nombre', 'capacidad', 'edificio', 'tipo', 'activo'];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $fields[$field] = $_POST[$field];
            }
        }

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        if (isset($fields['codigo'])) {
            $stmt = $model->db->prepare("SELECT id FROM aulas WHERE codigo = ? AND id != ?");
            $stmt->execute([$fields['codigo'], $id]);
            if ($stmt->fetch()) {
                Response::error('El código de aula ya existe', 409);
            }
        }

        $model->update($id, $fields);
        Response::success(null, 'Aula actualizada exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Aula();
        if (!$model->findById($id)) {
            Response::error('Aula no encontrada', 404);
        }

        $model->delete($id);
        Response::success(null, 'Aula eliminada exitosamente');
    }

    public function search($q)
    {
        $this->requireAuth();
        $model = new Aula();
        $aulas = $model->search(['codigo', 'nombre', 'edificio'], $q);
        Response::json(['data' => $aulas]);
    }
}
