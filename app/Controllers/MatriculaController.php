<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Programa;
use App\Models\Estudiante;

class MatriculaController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('matriculas/index');
    }

    public function all()
    {
        $this->requireAuth();
        $model = new Matricula();
        $stmt = $model->db->query(
            "SELECT m.*, pa.nombre AS periodo_nombre, pe.nombre AS programa_nombre,
                    e.codigo, e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido
             FROM matriculas m
             LEFT JOIN periodos_academicos pa ON pa.id = m.periodo_id
             LEFT JOIN programas_estudio pe ON pe.id = m.programa_id
             LEFT JOIN estudiantes e ON e.id = m.estudiante_id
             ORDER BY e.apellido ASC, m.semestre ASC"
        );
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function store()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $estudianteId = Request::post('estudiante_id');
        $programaId = Request::post('programa_id');
        $periodoId = Request::post('periodo_id');
        $semestre = (int) Request::post('semestre', 1);

        if (!$estudianteId || !$programaId || !$periodoId) {
            Response::error('Estudiante, programa y periodo son requeridos', 422);
        }

        $model = new Matricula();
        $id = $model->create([
            'estudiante_id' => $estudianteId,
            'programa_id' => $programaId,
            'periodo_id' => $periodoId,
            'semestre' => $semestre,
            'estado' => 'activo'
        ]);

        Response::success(['id' => $id], 'Matrícula registrada exitosamente', 201);
    }

    public function avanzar($estudianteId)
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $nuevoPeriodoId = Request::post('periodo_id');
        $model = new Matricula();
        $result = $model->avanzarSemestre($estudianteId, $nuevoPeriodoId);

        if (isset($result['error'])) {
            Response::error($result['error'], 422);
        }
        Response::json($result);
    }

    public function retirar($id)
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $model = new Matricula();
        if (!$model->findById($id)) {
            Response::error('Matrícula no encontrada', 404);
        }

        $model->retirar($id);
        Response::success(null, 'Estudiante retirado exitosamente');
    }

    public function historial($estudianteId)
    {
        $this->requireAuth();
        $model = new Matricula();
        $historial = $model->getHistorial($estudianteId);
        Response::json(['data' => $historial]);
    }

    public function porSemestre()
    {
        $this->requireAuth();
        $periodoId = Request::get('periodo_id');
        $semestre = Request::get('semestre');

        if (!$periodoId || !$semestre) {
            Response::error('periodo_id y semestre son requeridos', 422);
        }

        $model = new Matricula();
        $estudiantes = $model->getEstudiantesPorSemestre($periodoId, $semestre);
        Response::json(['data' => $estudiantes]);
    }

    public function programas()
    {
        $this->requireAuth();
        $model = new Programa();
        $programas = $model->findAll('nombre ASC');
        Response::json(['data' => $programas]);
    }

    public function periodos()
    {
        $this->requireAuth();
        $model = new Periodo();
        $periodos = $model->findAll('fecha_inicio DESC');
        Response::json(['data' => $periodos]);
    }

    public function setSemestre()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $estudianteId = Request::post('estudiante_id');
        $semestre = (int) Request::post('semestre', 0);

        if (!$estudianteId || $semestre < 1 || $semestre > 12) {
            Response::error('Estudiante y semestre (1-12) son requeridos', 422);
        }

        $matriculaModel = new Matricula();
        $estudianteModel = new Estudiante();

        $activa = $matriculaModel->findActiva($estudianteId);

        if ($activa) {
            $matriculaModel->update($activa['id'], ['semestre' => $semestre]);
        } else {
            $periodo = (new Periodo())->findActivo();
            $progId = isset($activa['programa_id']) ? $activa['programa_id'] : null;
            if (!$progId) {
                $est = $estudianteModel->findById($estudianteId);
                $progId = $est['programa_id'] ?? null;
            }
            if ($periodo && $progId) {
                $matriculaModel->create([
                    'estudiante_id' => $estudianteId,
                    'programa_id' => $progId,
                    'periodo_id' => $periodo['id'],
                    'semestre' => $semestre,
                    'estado' => 'activo'
                ]);
            }
        }

        $estudianteModel->update($estudianteId, ['grado' => "{$semestre}° Ciclo"]);
        Response::success(null, "Semestre actualizado a {$semestre}° Ciclo");
    }
}
