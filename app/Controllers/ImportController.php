<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\Programa;
use App\Models\Especialidad;

class ImportController extends Controller
{
    public function template()
    {
        $this->requireAuth();
        $type = Request::get('type', '');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="plantilla_' . $type . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($type === 'estudiantes') {
            fputcsv($output, ['codigo', 'dni', 'apellido_paterno', 'apellido_materno', 'nombre', 'grado', 'seccion', 'programa', 'email', 'telefono']);
            fputcsv($output, ['EST-2026-001', '12345678', 'Garcia', 'Lopez', 'Juan Carlos', '1° Ciclo', 'A', 'Educacion Secundaria', 'juan@mail.com', '951123456']);
        } elseif ($type === 'docentes') {
            fputcsv($output, ['dni', 'apellido', 'nombre', 'email', 'telefono', 'especialidad']);
            fputcsv($output, ['12345678', 'Martinez', 'Carlos', 'carlos@mail.com', '951123456', 'Matematicas']);
        }

        fclose($output);
        exit;
    }

    public function importEstudiantes()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            Response::error('No se recibió el archivo CSV', 422);
        }

        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$handle) {
            Response::error('No se pudo leer el archivo', 422);
        }

        $model = new Estudiante();
        $matriculaModel = new Matricula();
        $programaModel = new Programa();

        $programas = $programaModel->findAll();
        $progMap = [];
        foreach ($programas as $p) {
            $progMap[strtolower($p['nombre'])] = $p['id'];
            $norm = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', strtolower($p['nombre']));
            $norm = preg_replace('/[^a-z0-9]+/', '', $norm);
            $progMap[$norm] = $p['id'];
        }

        $periodo = (new Periodo())->findActivo();

        $insertados = 0;
        $duplicados = 0;
        $errores = [];
        $linea = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $linea++;
            if ($linea === 1) continue;

            if (count($row) < 5) {
                $errores[] = ['fila' => $linea, 'error' => 'Faltan columnas mínimas'];
                continue;
            }

            [$codigo, $dni, $apeP, $apeM, $nombre, $grado, $seccion, $progNombre, $email, $telefono] = array_pad($row, 10, '');

            $codigo = trim($codigo);
            $nombre = trim($nombre);
            $apellido = trim(implode(' ', array_filter([$apeP, $apeM])));

            if (empty($codigo) || empty($nombre) || empty($apellido)) {
                $errores[] = ['fila' => $linea, 'error' => 'Código, nombre y apellido son requeridos'];
                continue;
            }

            if ($model->findByCodigo($codigo)) {
                $duplicados++;
                continue;
            }

            try {
                $progId = null;
                $progNombre = trim($progNombre);
                if ($progNombre) {
                    $normInput = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', strtolower($progNombre));
                    $normInput = preg_replace('/[^a-z0-9]+/', '', $normInput);
                    $progId = $progMap[$normInput] ?? $progMap[strtolower($progNombre)] ?? null;
                }

                $id = $model->create([
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'grado' => trim($grado) ?: null,
                    'seccion' => trim($seccion) ?: null,
                    'programa_id' => $progId,
                    'email' => trim($email) ?: null,
                    'telefono' => trim($telefono) ?: null,
                    'activo' => 1
                ]);

                if ($periodo && $progId) {
                    $semestreNum = 1;
                    if ($grado) {
                        preg_match('/(\d)/', $grado, $m);
                        if ($m) $semestreNum = (int) $m[1];
                    }
                    $matriculaModel->create([
                        'estudiante_id' => $id,
                        'programa_id' => $progId,
                        'periodo_id' => $periodo['id'],
                        'semestre' => $semestreNum,
                        'estado' => 'activo'
                    ]);
                    $model->update($id, ['grado' => "{$semestreNum}° Ciclo"]);
                }

                $insertados++;
            } catch (\Exception $e) {
                $errores[] = ['fila' => $linea, 'error' => $e->getMessage()];
            }
        }

        fclose($handle);
        Response::json([
            'success' => true,
            'message' => "Importación completada: {$insertados} insertados, {$duplicados} duplicados",
            'insertados' => $insertados,
            'duplicados' => $duplicados,
            'errores' => $errores
        ]);
    }

    public function importDocentes()
    {
        $this->requireAdmin();
        $this->requireMethod('POST');

        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            Response::error('No se recibió el archivo CSV', 422);
        }

        $handle = fopen($_FILES['csv']['tmp_name'], 'r');
        if (!$handle) {
            Response::error('No se pudo leer el archivo', 422);
        }

        $model = new Docente();
        $especialidadModel = new Especialidad();

        $especMap = [];
        foreach ($especialidadModel->findAll() as $e) {
            $especMap[strtolower($e['nombre'])] = $e['id'];
        }

        $insertados = 0;
        $duplicados = 0;
        $errores = [];
        $linea = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $linea++;
            if ($linea === 1) continue;

            if (count($row) < 3) {
                $errores[] = ['fila' => $linea, 'error' => 'Faltan columnas mínimas'];
                continue;
            }

            [$dni, $apellido, $nombre, $email, $telefono, $especialidad] = array_pad($row, 6, '');

            $apellido = trim($apellido);
            $nombre = trim($nombre);

            if (empty($nombre) || empty($apellido)) {
                $errores[] = ['fila' => $linea, 'error' => 'Nombre y apellido son requeridos'];
                continue;
            }

            try {
                $especId = null;
                if (trim($especialidad)) {
                    $especId = $especMap[strtolower(trim($especialidad))] ?? null;
                }
                $model->create([
                    'usuario_id' => null,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'email' => trim($email) ?: null,
                    'telefono' => trim($telefono) ?: null,
                    'especialidad_id' => $especId,
                    'activo' => 1
                ]);
                $insertados++;
            } catch (\Exception $e) {
                $errores[] = ['fila' => $linea, 'error' => $e->getMessage()];
            }
        }

        fclose($handle);
        Response::json([
            'success' => true,
            'message' => "Importación completada: {$insertados} insertados, {$duplicados} duplicados",
            'insertados' => $insertados,
            'duplicados' => $duplicados,
            'errores' => $errores
        ]);
    }
}
