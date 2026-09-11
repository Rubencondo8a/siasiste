<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Periodo;

class EstudianteController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('estudiantes/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Estudiante();
        $programaId = Request::get('programa_id');

        $sql = "SELECT e.*,
                    pe.nombre AS programa_nombre,
                    m.semestre AS semestre_actual, m.estado AS matricula_estado
                FROM estudiantes e
                LEFT JOIN programas_estudio pe ON pe.id = e.programa_id
                LEFT JOIN matriculas m ON m.estudiante_id = e.id AND m.estado = 'activo'";

        if ($programaId) {
            $stmt = $model->db->prepare($sql . " WHERE e.programa_id = ? ORDER BY e.apellido ASC, e.nombre ASC");
            $stmt->execute([$programaId]);
        } else {
            $stmt = $model->db->query($sql . " ORDER BY e.apellido ASC, e.nombre ASC");
        }

        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $codigo = trim(Request::post('codigo', ''));
        $nombre = trim(Request::post('nombre', ''));
        $apellido = trim(Request::post('apellido', ''));

        if (empty($codigo) || empty($nombre) || empty($apellido)) {
            Response::error('Código, nombre y apellido son requeridos', 422);
        }

        $model = new Estudiante();
        if ($model->findByCodigo($codigo)) {
            Response::error('El código de estudiante ya existe', 409);
        }

        $activo = Request::post('activo', 1);
        if ($activo === 'Activo' || $activo === '1' || $activo === 1) $activo = 1;
        else if ($activo === 'Inactivo' || $activo === '0' || $activo === 0) $activo = 0;
        else $activo = (int)$activo ? 1 : 1;
        $id = $model->create([
            'codigo' => $codigo,
            'dni' => trim(Request::post('dni', '')) ?: null,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'grado' => trim(Request::post('grado', '')) ?: null,
            'seccion' => trim(Request::post('seccion', '')) ?: null,
            'programa_id' => Request::post('programa_id'),
            'email' => trim(Request::post('email', '')) ?: null,
            'telefono' => trim(Request::post('telefono', '')) ?: null,
            'activo' => $activo
        ]);

        if (!empty(Request::post('programa_id'))) {
            $periodo = (new Periodo())->findActivo();
            $semestre = 1;
            $grado = Request::post('grado', '');
            if ($grado) {
                preg_match('/(\d)/', $grado, $m);
                if ($m) $semestre = (int)$m[1];
            }
            if ($periodo) {
                (new Matricula())->create([
                    'estudiante_id' => $id,
                    'programa_id' => Request::post('programa_id'),
                    'periodo_id' => $periodo['id'],
                    'semestre' => $semestre,
                    'estado' => 'activo'
                ]);
                $model->update($id, ['grado' => "{$semestre}° Ciclo"]);
            }
        }

        Response::success(['id' => $id], 'Estudiante registrado exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Estudiante();
        $estudiante = $model->findById($id);
        if (!$estudiante) {
            Response::error('Estudiante no encontrado', 404);
        }

        $fields = [];
        $allowed = ['codigo', 'dni', 'nombre', 'apellido', 'grado', 'seccion', 'programa_id', 'email', 'telefono', 'activo'];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $val = $_POST[$field];
                if ($field === 'activo') {
                    if ($val === 'Activo' || $val === '1' || $val === 1) $val = 1;
                    else if ($val === 'Inactivo' || $val === '0' || $val === 0) $val = 0;
                    else if ($val === '' || $val === null) continue; // no actualizar si vacío
                    else $val = (int)$val ? 1 : 0;
                } else {
                    $val = $val === '' ? null : $val;
                }
                $fields[$field] = $val;
            }
        }

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        if (isset($fields['codigo'])) {
            $existing = $model->findByCodigo($fields['codigo']);
            if ($existing && $existing['id'] != $id) {
                Response::error('El código de estudiante ya existe', 409);
            }
        }

        $model->update($id, $fields);
        Response::success(null, 'Estudiante actualizado exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Estudiante();
        if (!$model->findById($id)) {
            Response::error('Estudiante no encontrado', 404);
        }

        $model->delete($id);
        Response::success(null, 'Estudiante eliminado exitosamente');
    }

    public function search($q)
    {
        $this->requireAuth();
        $model = new Estudiante();
        $estudiantes = $model->searchByQuery($q);
        Response::json(['data' => $estudiantes]);
    }
}
