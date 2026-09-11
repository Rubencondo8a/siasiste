<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Asistencia;

class TrazabilidadController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('trazabilidad/index');
    }

    public function perfil($id)
    {
        $this->requireAuth();

        $model = new Estudiante();
        $stmt = $model->db->prepare(
            "SELECT e.*, pe.nombre AS programa_nombre,
                    m.semestre AS semestre_actual, m.estado AS matricula_estado
             FROM estudiantes e
             LEFT JOIN programas_estudio pe ON pe.id = e.programa_id
             LEFT JOIN matriculas m ON m.estudiante_id = e.id AND m.estado = 'activo'
             WHERE e.id = ?"
        );
        $stmt->execute([$id]);
        $est = $stmt->fetch();
        if (!$est) Response::error('Estudiante no encontrado', 404);
        Response::json($est);
    }

    public function historial($id)
    {
        $this->requireAuth();
        $matriculaModel = new Matricula();
        $historial = $matriculaModel->getHistorial($id);
        Response::json(['data' => $historial]);
    }

    public function asistencias($id)
    {
        $this->requireAuth();

        $asistenciaModel = new Asistencia();
        $asistencias = $asistenciaModel->findByEstudiante($id);

        $stats = ['presente' => 0, 'ausente' => 0, 'justificado' => 0, 'tardanza' => 0];
        foreach ($asistencias as $a) {
            $estado = $a['estado'] ?? 'presente';
            if (isset($stats[$estado])) $stats[$estado]++;
        }
        $total = array_sum($stats);
        $porcentaje = $total > 0 ? round(($stats['presente'] / $total) * 100, 1) : 0;

        Response::json([
            'data' => $asistencias,
            'stats' => $stats,
            'total' => $total,
            'porcentaje_asistencia' => $porcentaje
        ]);
    }

    public function buscar()
    {
        $this->requireAuth();
        $q = Request::get('q', '');

        if (strlen($q) < 2) Response::json(['data' => []]);

        $model = new Estudiante();
        $stmt = $model->db->prepare(
            "SELECT e.id, e.codigo, e.nombre, e.apellido, e.grado, e.seccion,
                    pe.nombre AS programa_nombre,
                    m.semestre AS semestre_actual, m.estado AS matricula_estado
             FROM estudiantes e
             LEFT JOIN programas_estudio pe ON pe.id = e.programa_id
             LEFT JOIN matriculas m ON m.estudiante_id = e.id AND m.estado = 'activo'
             WHERE e.codigo LIKE ? OR e.nombre LIKE ? OR e.apellido LIKE ?
             ORDER BY e.apellido ASC, e.nombre ASC
             LIMIT 20"
        );
        $term = "%{$q}%";
        $stmt->execute([$term, $term, $term]);
        Response::json(['data' => $stmt->fetchAll()]);
    }

    public function avanzar($estudianteId)
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $nuevoPeriodoId = Request::post('periodo_id');
        $matriculaModel = new Matricula();
        $result = $matriculaModel->avanzarSemestre($estudianteId, $nuevoPeriodoId);

        if (isset($result['error'])) {
            Response::error($result['error'], 422);
        }
        Response::json($result);
    }

    public function retirar($estudianteId)
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        $matriculaModel = new Matricula();
        $activa = $matriculaModel->findActiva($estudianteId);
        if (!$activa) {
            Response::error('No hay matrícula activa para retirar', 404);
        }
        $matriculaModel->retirar($activa['id']);
        Response::success(null, 'Estudiante retirado exitosamente');
    }
}
