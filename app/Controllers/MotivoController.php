<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Motivo;

class MotivoController extends Controller
{
    public function index()
    {
        $this->requireAdmin();
        $this->view('motivos/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Motivo();
        Response::json(['data' => $model->findAll('nombre ASC')]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $nombre = trim(Request::post('nombre', ''));
        if (empty($nombre)) {
            Response::error('El nombre es requerido', 422);
        }

        $model = new Motivo();
        $stmt = $model->db->prepare("INSERT INTO motivos (nombre, descripcion, activo) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, trim(Request::post('descripcion', '')) ?: null, Request::post('activo', 1)]);
        Response::success(['id' => $model->db->lastInsertId()], 'Motivo creado exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Motivo();
        if (!$model->findById($id)) {
            Response::error('Motivo no encontrado', 404);
        }

        $fields = [];
        foreach (['nombre', 'descripcion', 'activo'] as $f) {
            if (isset($_POST[$f])) $fields[$f] = $_POST[$f] === '' ? null : $_POST[$f];
        }
        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        [$set, $params] = $model->buildUpdate($fields);
        $params[] = $id;
        $model->db->prepare("UPDATE motivos SET {$set} WHERE id = ?")->execute($params);
        Response::success(null, 'Motivo actualizado exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Motivo();
        if (!$model->findById($id)) {
            Response::error('Motivo no encontrado', 404);
        }

        $model->delete($id);
        Response::success(null, 'Motivo eliminado exitosamente');
    }
}
