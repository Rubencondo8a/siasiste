<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Asistencia;
use App\Models\AsistenciaDetalle;
use App\Models\Estudiante;
use App\Models\Unidad;
use App\Models\Docente;
use App\Models\Motivo;

class AsistenciaController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->view('asistencia/index');
    }

    public function all()
    {
        $this->requireAuth();

        $model = new Asistencia();
        $fecha = Request::get('fecha', date('Y-m-d'));
        $programaId = Request::get('programa_id');
        $unidadId = Request::get('unidad_id');
        $docenteId = null;

        if (($_SESSION['user_role'] ?? '') === 'docente') {
            $docenteModel = new Docente();
            $docente = $docenteModel->findByUsuarioId($_SESSION['user_id']);
            $docenteId = $docente ? $docente['id'] : 0;
        }

        $asistencias = $model->findByFechaFiltered($fecha, $programaId, $unidadId, $docenteId);
        Response::json(['data' => $asistencias, 'fecha' => $fecha]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->requireMethod('POST');

        $estudianteId = (int) Request::post('estudiante_id', 0);
        $fecha = trim(Request::post('fecha', date('Y-m-d')));
        $estado = Request::post('estado', 'presente');

        if (empty($estudianteId) || empty($fecha)) {
            Response::error('Estudiante y fecha son requeridos', 422);
        }

        $estudiante = (new Estudiante())->findById($estudianteId);
        if (!$estudiante) {
            Response::error('Estudiante no encontrado', 404);
        }

        $estadosValidos = ['presente', 'ausente', 'justificado'];
        if (!in_array($estado, $estadosValidos)) {
            Response::error('Estado no válido. Use: presente, ausente, justificado', 422);
        }

        $model = new Asistencia();
        try {
            $id = $model->create([
                'estudiante_id' => $estudianteId,
                'unidad_id' => Request::post('unidad_id'),
                'fecha' => $fecha,
                'estado' => $estado,
                'registrado_por' => $_SESSION['user_id'] ?? null,
                'synced' => 1,
                'registro_tardio' => isset($_POST['registro_tardio']) ? (int)$_POST['registro_tardio'] : 0,
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                Response::error('La asistencia para este estudiante ya fue registrada', 409);
            }
            throw $e;
        }

        Response::success(['id' => $id], 'Asistencia registrada exitosamente', 201);
    }

    public function update($id)
    {
        $this->requireAuth();
        if (!in_array(Request::method(), ['POST', 'PUT', 'PATCH'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Asistencia();
        $asistencia = $model->findById($id);
        if (!$asistencia) {
            Response::error('Registro de asistencia no encontrado', 404);
        }

        $estado = Request::post('estado');
        $estadosValidos = ['presente', 'tardanza', 'ausente', 'justificado'];
        if (!$estado || !in_array($estado, $estadosValidos)) {
            Response::error('Estado no válido', 422);
        }

        $model->update($id, ['estado' => $estado]);

        $detalleModel = new AsistenciaDetalle();
        if ($estado === 'presente') {
            $detalleModel->deleteByAsistenciaId($id);
        } else {
            $detalleModel->upsert($id, [
                'estado' => $estado,
                'motivo_id' => Request::post('motivo_id'),
                'otro_motivo' => Request::post('otro_motivo'),
                'observaciones' => Request::post('observaciones')
            ]);
        }

        Response::success(null, 'Asistencia actualizada exitosamente');
    }

    public function destroy($id)
    {
        $this->requireAdmin();
        if (!in_array(Request::method(), ['POST', 'DELETE'])) {
            Response::error('Método no permitido', 405);
        }

        $model = new Asistencia();
        if (!$model->findById($id)) {
            Response::error('Registro de asistencia no encontrado', 404);
        }

        $model->db->prepare("UPDATE asistencias SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
        Response::success(null, 'Registro de asistencia eliminado exitosamente');
    }

    public function iniciarJornada()
    {
        $this->requireAuth();
        $this->requireMethod('POST');

        $unidadId = (int) Request::post('unidad_id', 0);
        $fecha = trim(Request::post('fecha', ''));

        if (!$unidadId || !$fecha) {
            Response::error('Unidad y fecha son requeridos', 422);
        }

        if (($_SESSION['user_role'] ?? '') === 'docente') {
            $docenteModel = new Docente();
            $docente = $docenteModel->findByUsuarioId($_SESSION['user_id']);
            if (!$docente) Response::error('Docente no encontrado', 404);
            $db = \Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT 1 FROM unidad_docente WHERE unidad_id = ? AND docente_id = ?");
            $stmt->execute([$unidadId, $docente['id']]);
            if (!$stmt->fetch()) {
                Response::error('No tienes permiso para registrar asistencia en esta unidad', 403);
            }
        }

        $unidadModel = new Unidad();
        $unidad = $unidadModel->findById($unidadId);
        if (!$unidad) Response::error('Unidad no encontrada', 404);

        $programas = $unidadModel->getProgramas($unidadId);
        if (empty($programas)) {
            Response::error('Esta unidad no tiene programas asignados', 422);
        }

        $programaIds = array_column($programas, 'id');
        $placeholders = implode(',', array_fill(0, count($programaIds), '?'));

        $hoy = (new \DateTime('now', new \DateTimeZone('America/Lima')))->format('Y-m-d');
        if ($fecha > $hoy) {
            Response::error('No se puede registrar asistencia para una fecha futura', 422);
        }
        $registroTardio = $fecha < $hoy ? 1 : 0;

        $estudianteModel = new Estudiante();
        $stmt = $estudianteModel->db->prepare(
            "SELECT * FROM estudiantes WHERE programa_id IN ($placeholders) AND activo = 1"
        );
        $stmt->execute($programaIds);
        $estudiantes = $stmt->fetchAll();

        $db = \Database::getInstance()->getConnection();
        $model = new Asistencia();
        $count = 0;

        $db->beginTransaction();
        try {
            foreach ($estudiantes as $est) {
                try {
                    $model->create([
                        'estudiante_id' => $est['id'],
                        'unidad_id' => $unidadId,
                        'fecha' => $fecha,
                        'estado' => 'presente',
                        'registrado_por' => $_SESSION['user_id'] ?? null,
                        'synced' => 1,
                        'registro_tardio' => $registroTardio
                    ]);
                    $count++;
                } catch (\PDOException $e) {
                    if ($e->getCode() != 23000) throw $e;
                }
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }

        $msg = "Jornada iniciada — {$count} estudiantes registrados como Presente";
        if ($registroTardio) $msg .= ' (registro tardío)';

        Response::json([
            'success' => true,
            'message' => $msg,
            'count' => $count,
            'registro_tardio' => $registroTardio
        ]);
    }

    public function motivos()
    {
        $this->requireAuth();
        $motivos = (new Motivo())->findWhere('activo', 1);
        Response::json(['data' => $motivos]);
    }

    public function porUnidad($unidadId)
    {
        $this->requireAuth();
        $fecha = Request::get('fecha', date('Y-m-d'));
        $model = new Asistencia();
        $asistencias = $model->findByUnidadAndFecha($unidadId, $fecha);
        Response::json(['data' => $asistencias, 'unidad_id' => (int) $unidadId, 'fecha' => $fecha]);
    }

    public function ultimaFecha()
    {
        $this->requireAuth();
        $unidadId = (int) Request::get('unidad_id', 0);
        if (!$unidadId) Response::error('unidad_id requerido', 422);

        if (($_SESSION['user_role'] ?? '') === 'docente') {
            $docente = (new Docente())->findByUsuarioId($_SESSION['user_id']);
            if (!$docente) Response::error('Docente no encontrado', 404);
            $db = \Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT 1 FROM unidad_docente WHERE unidad_id = ? AND docente_id = ?");
            $stmt->execute([$unidadId, $docente['id']]);
            if (!$stmt->fetch()) Response::error('No tienes permiso para esta unidad', 403);
        }

        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT MAX(fecha) as ultima FROM asistencias WHERE unidad_id = ? AND deleted_at IS NULL");
        $stmt->execute([$unidadId]);
        $row = $stmt->fetch();
        Response::json(['ultima_fecha' => $row['ultima'] ?? null, 'unidad_id' => $unidadId]);
    }
}
