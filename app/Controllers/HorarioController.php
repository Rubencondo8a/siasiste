<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Horario;

class HorarioController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('horarios/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Horario();
        $dia = Request::get('dia');

        if ($dia) {
            $horarios = $model->findByDia($dia);
        } else {
            $horarios = $model->findAll('dia ASC, hora_inicio ASC');
        }

        Response::json(['data' => $horarios]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $dia = trim(Request::post('dia', ''));
        $horaInicio = trim(Request::post('hora_inicio', ''));
        $horaFin = trim(Request::post('hora_fin', ''));
        $unidadId = Request::post('unidad_id');
        $docenteId = Request::post('docente_id');
        $aulaId = Request::post('aula_id');

        if (empty($dia) || empty($horaInicio) || empty($horaFin) || empty($unidadId) || empty($docenteId)) {
            Response::error('Día, hora inicio, hora fin, unidad y docente son requeridos', 422);
        }

        $model = new Horario();
        if ($model->tieneConflictoDocente($docenteId, $dia, $horaInicio, $horaFin)) {
            Response::error('El docente ya tiene un horario en ese día y horario.', 409);
        }
        if ($aulaId && $model->tieneConflictoAula($aulaId, $dia, $horaInicio, $horaFin)) {
            Response::error('El aula ya está ocupada en ese día y horario.', 409);
        }

        $id = $model->create([
            'dia' => $dia,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'aula_id' => $aulaId,
            'unidad_id' => $unidadId,
            'docente_id' => $docenteId,
            'activo' => 1
        ]);

        Response::success(['id' => $id], 'Horario registrado exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Horario();
        $horario = $model->findById($id);
        if (!$horario) {
            Response::error('Horario no encontrado', 404);
        }

        $fields = [];
        $allowed = ['dia', 'hora_inicio', 'hora_fin', 'aula_id', 'unidad_id', 'docente_id', 'activo'];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $fields[$field] = $_POST[$field] === '' ? null : $_POST[$field];
            }
        }

        if (empty($fields)) {
            Response::error('No hay datos para actualizar', 422);
        }

        $dia = Request::post('dia', $horario['dia']);
        $horaInicio = Request::post('hora_inicio', $horario['hora_inicio']);
        $horaFin = Request::post('hora_fin', $horario['hora_fin']);
        $docenteId = Request::post('docente_id', $horario['docente_id']);
        $aulaId = array_key_exists('aula_id', $_POST) ? $_POST['aula_id'] : $horario['aula_id'];

        if ($model->tieneConflictoDocente($docenteId, $dia, $horaInicio, $horaFin, $id)) {
            Response::error('El docente ya tiene un horario en ese día y horario.', 409);
        }
        if ($aulaId && $model->tieneConflictoAula($aulaId, $dia, $horaInicio, $horaFin, $id)) {
            Response::error('El aula ya está ocupada en ese día y horario.', 409);
        }

        $model->update($id, $fields);
        Response::success(null, 'Horario actualizado exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Horario();
        if (!$model->findById($id)) {
            Response::error('Horario no encontrado', 404);
        }

        $model->delete($id);
        Response::success(null, 'Horario eliminado exitosamente');
    }

    public function search($q)
    {
        $this->requireAuth();
        $model = new Horario();
        $horarios = $model->search(['dia', 'hora_inicio', 'hora_fin'], $q);
        Response::json(['data' => $horarios]);
    }
}
