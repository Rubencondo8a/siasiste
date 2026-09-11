<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\Estudiante;

class ReporteController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('reportes/index');
    }

    public function getResumen()
    {
        $this->requireAuth();

        $model = new Asistencia();
        $year = (int) Request::get('year', date('Y'));
        $resumen = [];

        for ($m = 1; $m <= 12; $m++) {
            $total = $model->countByMonth($m, $year);
            $stmt = $model->db->prepare(
                "SELECT estado, COUNT(*) AS total
                 FROM asistencias
                 WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?
                 GROUP BY estado"
            );
            $stmt->execute([$m, $year]);
            $detalles = $stmt->fetchAll();

            $resumen[] = [
                'mes' => $m,
                'nombre' => ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Setiembre','Octubre','Noviembre','Diciembre'][$m],
                'total' => (int) $total,
                'detalles' => $detalles
            ];
        }

        Response::json(['year' => $year, 'resumen' => $resumen]);
    }

    public function exportCSV()
    {
        $this->requireAdmin();
        $type = Request::get('type', '');

        $filename = "reporte_{$type}_" . date('Y-m-d') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        switch ($type) {
            case 'asistencias':
                $this->exportAsistencias($output);
                break;
            case 'docentes':
                $this->exportDocentes($output);
                break;
            case 'estudiantes':
                $this->exportEstudiantes($output);
                break;
            default:
                fclose($output);
                Response::error('Tipo de exportación no válido', 400);
        }

        fclose($output);
        exit;
    }

    private function exportAsistencias($output)
    {
        fputcsv($output, ['ID', 'Estudiante', 'Codigo', 'Unidad', 'Fecha', 'Estado']);

        $model = new Asistencia();
        $desde = Request::get('desde', date('Y-m-01'));
        $hasta = Request::get('hasta', date('Y-m-d'));

        $stmt = $model->db->prepare(
            "SELECT a.*, CONCAT(e.nombre, ' ', e.apellido) AS estudiante_nombre,
                    e.codigo AS estudiante_codigo, u.nombre AS unidad_nombre
             FROM asistencias a
             INNER JOIN estudiantes e ON a.estudiante_id = e.id
             LEFT JOIN unidades_didacticas u ON a.unidad_id = u.id
             WHERE a.fecha BETWEEN ? AND ?
             ORDER BY a.fecha, e.apellido"
        );
        $stmt->execute([$desde, $hasta]);

        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'], $row['estudiante_nombre'], $row['estudiante_codigo'],
                $row['unidad_nombre'] ?? 'N/A', $row['fecha'], $row['estado']
            ]);
        }
    }

    private function exportDocentes($output)
    {
        fputcsv($output, ['ID', 'Nombre', 'Apellido', 'Email', 'Telefono', 'Especialidad', 'Estado']);
        $model = new Docente();
        $stmt = $model->db->query(
            "SELECT d.*, e.nombre AS especialidad_nombre
             FROM docentes d
             LEFT JOIN especialidades e ON d.especialidad_id = e.id
             ORDER BY d.apellido ASC"
        );
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'], $row['nombre'], $row['apellido'],
                $row['email'] ?? '', $row['telefono'] ?? '',
                $row['especialidad_nombre'] ?? '', $row['activo'] ? 'Activo' : 'Inactivo'
            ]);
        }
    }

    private function exportEstudiantes($output)
    {
        fputcsv($output, ['ID', 'Codigo', 'Nombre', 'Apellido', 'Grado', 'Seccion', 'Programa', 'Estado']);
        $model = new Estudiante();
        $stmt = $model->db->query(
            "SELECT e.*, p.nombre AS programa_nombre
             FROM estudiantes e
             LEFT JOIN programas_estudio p ON e.programa_id = p.id
             ORDER BY e.apellido ASC"
        );
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['id'], $row['codigo'], $row['nombre'], $row['apellido'],
                $row['grado'] ?? '', $row['seccion'] ?? '',
                $row['programa_nombre'] ?? '', $row['activo'] ? 'Activo' : 'Inactivo'
            ]);
        }
    }
}
