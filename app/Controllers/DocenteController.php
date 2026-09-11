<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Docente;

class DocenteController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('docentes/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Docente();
        $stmt = $model->db->query(
            "SELECT d.*, e.nombre AS especialidad_nombre
             FROM docentes d
             LEFT JOIN especialidades e ON e.id = d.especialidad_id
             ORDER BY d.apellido ASC, d.nombre ASC"
        );
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $nombre = trim(Request::post('nombre', ''));
        $apellido = trim(Request::post('apellido', ''));

        if (empty($nombre) || empty($apellido)) {
            Response::error('Nombre y apellido son requeridos', 422);
        }

        $model = new Docente();
        $id = $model->create([
            'usuario_id' => Request::post('usuario_id'),
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => trim(Request::post('dni', '')) ?: null,
            'email' => trim(Request::post('email', '')) ?: null,
            'telefono' => trim(Request::post('telefono', '')) ?: null,
            'especialidad_id' => Request::post('especialidad_id'),
            'activo' => 1
        ]);

        Response::success(['id' => $id], 'Docente registrado exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Docente();
        $docente = $model->findById($id);
        if (!$docente) {
            Response::error('Docente no encontrado', 404);
        }

        $fields = [];
        $allowed = ['nombre', 'apellido', 'dni', 'email', 'telefono', 'especialidad_id', 'activo', 'usuario_id'];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $fields[$field] = $_POST[$field];
            }
        }

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        $model->update($id, $fields);
        Response::success(null, 'Docente actualizado exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Docente();
        if (!$model->findById($id)) {
            Response::error('Docente no encontrado', 404);
        }

        $model->delete($id);
        Response::success(null, 'Docente eliminado exitosamente');
    }

    public function search($q)
    {
        $this->requireAuth();
        $model = new Docente();
        $docentes = $model->searchByQuery($q);
        Response::json(['data' => $docentes]);
    }
}
