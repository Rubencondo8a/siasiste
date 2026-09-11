<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Asistencia;
use App\Models\Alerta;

class DashboardController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('dashboard/index');
    }

    public function getStats()
    {
        $this->requireAuth();

        $estudiantes = new Estudiante();
        $docentes = new Docente();
        $asistencias = new Asistencia();
        $alertas = new Alerta();

        $totalEstudiantes = $estudiantes->count('activo = 1');
        $totalDocentes = $docentes->count('activo = 1');
        $asistenciasHoy = $asistencias->findByFecha(date('Y-m-d'));
        $alertasActivas = $alertas->count("estado = 'activa'");

        Response::json([
            'total_estudiantes' => $totalEstudiantes,
            'total_docentes' => $totalDocentes,
            'asistencias_hoy' => count($asistenciasHoy),
            'alertas_activas' => $alertasActivas
        ]);
    }

    public function getChartData()
    {
        $this->requireAuth();

        $asistencias = new Asistencia();
        $meses = [];
        $year = (int) date('Y');

        for ($m = 1; $m <= 12; $m++) {
            $meses[] = [
                'mes' => $m,
                'nombre' => ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Setiembre','Octubre','Noviembre','Diciembre'][$m],
                'total' => (int) $asistencias->countByMonth($m, $year)
            ];
        }

        Response::json(['year' => $year, 'data' => $meses]);
    }
}
