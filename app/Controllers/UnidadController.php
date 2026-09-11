<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Unidad;

class UnidadController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('unidades/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Unidad();
        $docenteId = Request::get('docente_id');
        $programaId = Request::get('programa_id');

        if ($docenteId) {
            $unidades = $model->findByDocente($docenteId);
        } elseif ($programaId) {
            $unidades = $model->findByPrograma($programaId);
        } else {
            $unidades = $model->findAll('u.nombre ASC');
        }

        $unidadModel = new Unidad();
        foreach ($unidades as &$u) {
            $u['docentes'] = $unidadModel->getDocentes($u['id']);
            $u['programas'] = $unidadModel->getProgramas($u['id']);
            $u['programa'] = implode(', ', array_column($u['programas'], 'nombre'));
        }

        Response::json(['data' => $unidades]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $nombre = trim(Request::post('nombre', ''));
        if (empty($nombre) || empty(Request::post('programas'))) {
            Response::error('Nombre y programa(s) son requeridos', 422);
        }

        $model = new Unidad();
        $id = $model->create([
            'nombre' => $nombre,
            'descripcion' => trim(Request::post('descripcion', '')) ?: null,
            'semestre' => (int) Request::post('semestre', 1),
            'seccion' => trim(Request::post('seccion', '')) ?: null,
            'horas_semanales' => Request::post('horas_semanales') ? (int) Request::post('horas_semanales') : null,
            'activo' => 1,
            'docentes' => Request::post('docentes', []),
            'programas' => Request::post('programas', [])
        ]);

        Response::success(['id' => $id], 'Unidad creada exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Unidad();
        if (!$model->findById($id)) {
            Response::error('Unidad no encontrada', 404);
        }

        $fields = [];
        $allowed = ['nombre', 'descripcion', 'semestre', 'seccion', 'horas_semanales', 'activo'];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $fields[$field] = $_POST[$field] === '' ? null : $_POST[$field];
            }
        }
        if (isset($_POST['docentes'])) $fields['docentes'] = $_POST['docentes'];
        if (isset($_POST['programas'])) $fields['programas'] = $_POST['programas'];

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        $model->update($id, $fields);
        Response::success(null, 'Unidad actualizada exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Unidad();
        if (!$model->findById($id)) {
            Response::error('Unidad no encontrada', 404);
        }

        $model->delete($id);
        Response::success(null, 'Unidad eliminada exitosamente');
    }

    public function search($q)
    {
        $this->requireAuth();
        $model = new Unidad();
        $unidades = $model->search(['nombre', 'descripcion'], $q);
        Response::json(['data' => $unidades]);
    }
}
