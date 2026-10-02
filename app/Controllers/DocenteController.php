<?php

declare(strict_types=1);

class DocenteController
{
    private PDO $db;
    private Curso $cursos;
    private Semana $semanas;
    private Criterio $criterios;
    private Calificacion $calificaciones;
    private Asignacion $asignaciones;
    private Equipo $equipos;
    /** @deprecated alias para compatibilidad */
    private Grupo $grupos;
    private Horario $horarios;
    private Ambiente $ambientes;
    private SolicitudExamen $solicitudes;
    private AyudaCompanero $ayudas;
    private Asistencia $asistencias;
    private Rostro $rostros;
    private PuntoParticipacion $puntos;
    private Apreciacion $apreciaciones;
    private NivelMago $nivelesMago;
    private Discusion $discusiones;
    private MensajeDiscusion $mensajesDiscusion;
    private DiscusionLeida $discusionLeida;
    private TypingIndicador $typing;
    private MensajeLike $likes;
    private MensajeAprobacion $aprobaciones;
    private NotificacionDebate $notifDebate;
    private MotivoPunto $motivosPuntos;
    private ParticipacionVivo $participacionVivo;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->cursos = new Curso($db);
        $this->semanas = new Semana($db);
        $this->criterios = new Criterio($db);
        $this->calificaciones = new Calificacion($db);
        $this->asignaciones = new Asignacion($db);
        $this->equipos = new Equipo($db);
        $this->grupos = $this->equipos;
        $this->horarios = new Horario($db);
        $this->ambientes = new Ambiente($db);
        $this->solicitudes = new SolicitudExamen($db);
        $this->ayudas = new AyudaCompanero($db);
        $this->asistencias = new Asistencia($db);
        $this->rostros = new Rostro($db);
        $this->puntos = new PuntoParticipacion($db);
        $this->apreciaciones = new Apreciacion($db);
        $this->nivelesMago = new NivelMago($db);
        $this->discusiones = new Discusion($db);
        $this->mensajesDiscusion = new MensajeDiscusion($db);
        $this->discusionLeida = new DiscusionLeida($db);
        $this->typing = new TypingIndicador($db);
        $this->likes = new MensajeLike($db);
        $this->aprobaciones = new MensajeAprobacion($db);
        $this->notifDebate = new NotificacionDebate($db);
        $this->motivosPuntos = new MotivoPunto($db);
        $this->participacionVivo = new ParticipacionVivo($db);
    }

    private function requireDocente(): void
    {
        if (!isLoggedIn() || !isDocente()) {
            setFlash('error', 'No tiene permiso para acceder a esta sección.');
            redirect(BASE_URL . 'auth/iniciarSesion');
        }
    }

    private function requirePerfil(): void
    {
        if (!isLoggedIn()) {
            setFlash('error', 'No tiene permiso para acceder a esta sección.');
            redirect(BASE_URL . 'auth/iniciarSesion');
            return;
        }
        if (!isDocente() && !isAdmin()) {
            setFlash('error', 'No tiene permiso para acceder a esta sección.');
            redirect(BASE_URL . 'auth/iniciarSesion');
        }
    }

    private function docenteId(): int
    {
        return (int) $_SESSION['user_id'];
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function inicio(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $cursos = [];
        $totalCursos = 0;
        $totalEstudiantes = 0;
        $totalSemanas = 0;
        $semanasEvaluadas = 0;
        $totalCalificaciones = 0;
        $alertaInasistencia = [];
        $umbralInasistencia = 30.0;

        try {
            $cursos = $this->cursos->getByDocenteActivos($docenteId);
            $totalCursos = count($cursos);

            $alertaInasistencia = $this->asistencias->getAlertaInasistencia($docenteId, $umbralInasistencia);

            if ($totalCursos > 0) {
                $cursoIds = array_column($cursos, 'id');

                $totalEstudiantes = $this->asignaciones->countDistinctEstudiantesByCursos($cursoIds);
                $totalSemanas = $this->semanas->countByDocenteAndCursos($docenteId, $cursoIds);
                $semanasEvaluadas = $this->semanas->countEvaluadasByDocenteAndCursos($docenteId, $cursoIds);
                $totalCalificaciones = $this->calificaciones->countByDocenteAndCursos($docenteId, $cursoIds);

                foreach ($cursos as &$curso) {
                    $curso['total_estudiantes'] = $this->asignaciones->countDistinctEstudiantesByCurso($curso['id']);
                    $curso['total_semanas'] = $this->semanas->countByCursoForDocente($curso['id'], $docenteId);
                    $curso['semanas_evaluadas'] = $this->semanas->countEvaluadasByCursoForDocente($curso['id'], $docenteId);
                }
                unset($curso);
            }
        } catch (PDOException $e) {
            $cursos = [];
            $alertaInasistencia = [];
        }

        require __DIR__ . '/../Views/docente/dashboard.php';
    }

    public function detalleInasistencia(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $estudianteId = (int) ($_GET['estudiante'] ?? 0);
        $cursoId      = (int) ($_GET['curso'] ?? 0);

        if ($estudianteId <= 0 || $cursoId <= 0) {
            setFlash('error', 'Solicitud incompleta: no se indico el alumno ni la unidad didactica.');
            redirect(BASE_URL . 'docente/inicio');
            return;
        }

        try {
            $detalle = $this->asistencias->getDetalleInasistencia($docenteId, $estudianteId, $cursoId);
        } catch (PDOException $e) {
            $detalle = null;
        }

        // El modelo ya filtra por docente_id: si el curso no es suyo,
        // no se filtra nada y se avisa en lugar de exponer el alumno.
        if (!$detalle) {
            setFlash('error', 'No se encontro la asistencia de ese alumno en esa unidad, o la unidad no le pertenece.');
            redirect(BASE_URL . 'docente/inicio');
            return;
        }

        $umbralInasistencia = 30.0;

        require __DIR__ . '/../Views/docente/detalle_inasistencia.php';
    }

    public function solicitudesExamen(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $solicitudes = [];
        $semanasPorCurso = [];
        $manaRecibido = [];
        $donantesMap = [];

        try {
            $solicitudes = $this->solicitudes->getPendientesPorDocente($docenteId);
            $manaRecibido = $this->ayudas->getManaRecibidoPorDocente($docenteId);

            // Mapa de donantes por receptor/curso/semana para vista rapida
            $donantesMap = [];
            $cargarDonantes = function ($receptorId, $cursoId, $semanaId) use (&$donantesMap) {
                $clave = $receptorId . ':' . $cursoId . ':' . (int) $semanaId;
                if (!isset($donantesMap[$clave])) {
                    $donantesMap[$clave] = $this->ayudas->getDonantes((int) $receptorId, (int) $cursoId, $semanaId !== null ? (int) $semanaId : null);
                }
                return $donantesMap[$clave];
            };
            foreach ($solicitudes as $sol) {
                $cargarDonantes($sol['estudiante_id'] ?? 0, $sol['curso_id'], $sol['semana_id'] ?? null);
            }
            foreach ($manaRecibido as $mr) {
                $cargarDonantes($mr['receptor_id'], $mr['curso_id'], $mr['semana_id']);
            }

            foreach ($solicitudes as $solicitud) {
                $cursoId = (int) $solicitud['curso_id'];
                if (isset($semanasPorCurso[$cursoId])) continue;
                $semanasPorCurso[$cursoId] = $this->semanas->getAllByCurso($cursoId, $docenteId);
            }
        } catch (PDOException $e) {
            $solicitudes = [];
            $manaRecibido = [];
        $donantesMap = [];
            $semanasPorCurso = [];
        }

        require __DIR__ . '/../Views/docente/solicitudes_examen.php';
    }

    public function atenderSolicitud(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $solicitudId = isset($_POST['solicitud_id']) ? (int) $_POST['solicitud_id'] : 0;

        if ($solicitudId <= 0) {
            setFlash('error', 'Solicitud inválida.');
            redirect(BASE_URL . 'docente/solicitudesExamen');
            return;
        }

        try {
            $solicitud = $this->solicitudes->getByIdParaDocente($solicitudId, $docenteId);
            if (!$solicitud) {
                setFlash('error', 'Solicitud no encontrada o ya atendida.');
                redirect(BASE_URL . 'docente/solicitudesExamen');
                return;
            }

            $this->solicitudes->atender($solicitudId, $docenteId);
            setFlash('success', 'Solicitud de examen atendida. Registre la nueva nota del estudiante.');
        } catch (PDOException $e) {
            setFlash('error', 'Error al atender la solicitud.');
        }

        redirect(BASE_URL . 'docente/solicitudesExamen');
    }

    public function rechazarSolicitud(): void
    {
        $this->requireDocente();

        $docenteId = $this->docenteId();
        $solicitudId = isset($_POST['solicitud_id']) ? (int) $_POST['solicitud_id'] : 0;
        $motivo = trim((string) ($_POST['motivo'] ?? ''));

        if ($solicitudId <= 0 || $motivo === '') {
            jsonResponse(['success' => false, 'message' => 'Debe indicar el motivo del rechazo.'], 400);
            return;
        }
        if (mb_strlen($motivo) > 255) {
            jsonResponse(['success' => false, 'message' => 'El motivo no puede exceder 255 caracteres.'], 400);
            return;
        }
        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'message' => 'Token de seguridad inv�lido.'], 403);
            return;
        }

        try {
            $solicitud = $this->solicitudes->getByIdParaDocente($solicitudId, $docenteId);
            if (!$solicitud) {
                jsonResponse(['success' => false, 'message' => 'Solicitud no encontrada o ya atendida.'], 404);
                return;
            }
            $ok = $this->solicitudes->rechazar($solicitudId, $docenteId, $motivo);
            if ($ok) {

            // Notificar rechazo: solicitante + donantes
            try {
                $mailerRechazo = new Mailer($this->db);
                if ($mailerRechazo->isConfigured()) {
                    $stInfoR = $this->db->prepare(
                        'SELECT c.nombre AS curso_nombre FROM cursos c WHERE c.id = :cid'
                    );
                    $stInfoR->execute([':cid' => (int) ($solicitud['curso_id'] ?? 0)]);
                    $infoR = $stInfoR->fetch(PDO::FETCH_ASSOC) ?: [];
                    $cursoNombreR = htmlspecialchars($infoR['curso_nombre'] ?? '');

                    // 1) Solicitante
                    $stSol = $this->db->prepare(
                        'SELECT u.id, u.nombres, u.apellidos, u.email FROM usuarios u WHERE u.id = :id'
                    );
                    $stSol->execute([':id' => (int) ($solicitud['estudiante_id'] ?? 0)]);
                    if ($sol = $stSol->fetch(PDO::FETCH_ASSOC)) {
                        if (!empty($sol['email'])) {
                            $asuntoS = 'Solicitud de examen rechazada';
                            $cuerpoS = '<p>Hola <strong>' . htmlspecialchars(trim($sol['nombres'] . ' ' . $sol['apellidos'])) . '</strong>,</p>'
                                . '<p>Tu solicitud de examen de oportunidad'
                                . ($cursoNombreR !== '' ? ' en el curso <strong>' . $cursoNombreR . '</strong>' : '')
                                . ' ha sido <strong>rechazada</strong> por el docente.</p>'
                                . '<p><strong>Motivo:</strong> ' . htmlspecialchars($motivo) . '</p>'
                                . '<p>El maná aportado por tus compañeros permanece activo y puede apoyar una nueva oportunidad.</p>';
                            $mailerRechazo->send($sol['email'], trim($sol['nombres'] . ' ' . $sol['apellidos']), $asuntoS, $cuerpoS);
                        }
                    }

                    // 2) Donantes
                    $stDonR = $this->ayudas->getDonantes(
                        (int) ($solicitud['estudiante_id'] ?? 0),
                        (int) ($solicitud['curso_id'] ?? 0),
                        !empty($solicitud['semana_id']) ? (int) $solicitud['semana_id'] : null
                    );
                    foreach ($stDonR as $don) {
                        if (empty($don['email'])) continue;
                        $asuntoD = 'Maná disponible — solicitud rechazada';
                        $cuerpoD = '<p>Hola <strong>' . htmlspecialchars(trim($don['nombres'] . ' ' . $don['apellidos'])) . '</strong>,</p>'
                            . '<p>La solicitud de examen que apoyaste con tu maná'
                            . ($cursoNombreR !== '' ? ' en el curso <strong>' . $cursoNombreR . '</strong>' : '')
                            . ' fue <strong>rechazada</strong> por el docente.</p>'
                            . '<p><strong>Tu maná sigue activo</strong> y puede apoyar una nueva oportunidad.</p>';
                        $mailerRechazo->send($don['email'], trim($don['nombres'] . ' ' . $don['apellidos']), $asuntoD, $cuerpoD);
                    }
                }
            } catch (Throwable $eNotifR) {
                logError('Error notificando rechazo de solicitud', ['message' => $eNotifR->getMessage(), 'id' => $solicitudId]);
            }
            }
            jsonResponse(['success' => $ok, 'message' => $ok ? 'Solicitud rechazada.' : 'No se pudo rechazar la solicitud.']);
        } catch (PDOException $e) {
            logError('Error rechazando solicitud', ['message' => $e->getMessage(), 'id' => $solicitudId]);
            jsonResponse(['success' => false, 'message' => 'Error al rechazar la solicitud.'], 500);
        }
    }
    public function registrarNotaOportunidad(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $solicitudId = isset($_POST['solicitud_id']) ? (int) $_POST['solicitud_id'] : 0;
        $semanaId = isset($_POST['semana_id']) ? (int) $_POST['semana_id'] : 0;
        $notaRaw = trim((string) ($_POST['nota'] ?? ''));

        if ($solicitudId <= 0 || $semanaId <= 0 || !is_numeric($notaRaw)) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
            return;
        }

        $nota = (float) $notaRaw;
        if ($nota < 0 || $nota > 20) {
            $this->jsonResponse(['success' => false, 'message' => 'La nota debe estar entre 0 y 20.'], 400);
            return;
        }

        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido.'], 403);
            return;
        }

        try {
            $solicitud = $this->solicitudes->getByIdParaDocente($solicitudId, $docenteId);
            if (!$solicitud) {
                $this->jsonResponse(['success' => false, 'message' => 'Solicitud no encontrada o ya atendida.'], 404);
                return;
            }

            $cursoId = (int) $solicitud['curso_id'];
            $semana = $this->semanas->getByIdForDocenteAndCurso($semanaId, $docenteId, $cursoId);
            if (!$semana) {
                $this->jsonResponse(['success' => false, 'message' => 'La semana elegida no pertenece al curso de la solicitud.'], 400);
                return;
            }

            $criterios = $this->criterios->getAllBySemana($semanaId);
            if (empty($criterios)) {
                $this->jsonResponse(['success' => false, 'message' => 'La semana elegida no tiene criterios definidos.'], 400);
                return;
            }

            $estudianteId = (int) $solicitud['estudiante_id'];

            $this->db->beginTransaction();
            foreach ($criterios as $criterio) {
                $this->calificaciones->createOrUpdate($estudianteId, (int) $criterio['id'], $nota);
            }
            $this->solicitudes->atender($solicitudId, $docenteId);
            $this->db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Nota ' . number_format($nota, 2) . ' registrada en la semana elegida y solicitud marcada como atendida.',
            ]);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'message' => 'Error al registrar la nota de oportunidad.'], 500);
        }
    }

    public function semanas(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();

        try {
            $cursos = $this->cursos->getByDocenteConConteos($docenteId);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los cursos.');
            $cursos = [];
        }

        require __DIR__ . '/../Views/docente/semanas.php';
    }

    public function semanasCurso(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $docenteId);

            if (!$curso) {
                setFlash('error', 'Curso no encontrado o no asignado a usted.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $semanas = $this->semanas->getAllByCurso($curso_id, $docenteId);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar las semanas.');
            $semanas = [];
        }

        require __DIR__ . '/../Views/docente/semanas_curso.php';
    }

    public function formularioSemana(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();
        $teacherId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $semana = null;

        try {
            $curso = $this->cursos->getByIdForDocenteSimple($curso_id, $teacherId);

            if (!$curso) {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Curso no encontrado.'], 404); return; }
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $periodo = $this->cursos->getPeriodoForDocente($curso_id, $teacherId);

            if ($id !== null) {
                $semana = $this->semanas->getByIdForDocenteAndCurso($id, $teacherId, $curso_id);

                if (!$semana) {
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Semana no encontrada.'], 404); return; }
                    setFlash('error', 'Semana no encontrada.');
                    redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
                    return;
                }
                if ($semana['cerrada']) {
                    setFlash('error', 'No se puede editar una semana cerrada.');
                    redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
                    return;
                }
            }
        } catch (PDOException $e) {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Error al cargar los datos.'], 500); return; }
            setFlash('error', 'Error al cargar los datos.');
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        require __DIR__ . '/../Views/docente/formulario_semana.php';
    }

    public function guardarSemana(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $teacherId = $this->docenteId();
        $curso_id = (int)($_POST['curso_id'] ?? 0);
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $nombre = sanitize($_POST['nombre'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $numero_semana = filter_input(INPUT_POST, 'numero_semana', FILTER_VALIDATE_INT);
        $fecha_inicio = sanitize($_POST['fecha_inicio'] ?? '');
        $fecha_fin = sanitize($_POST['fecha_fin'] ?? '');

        if (empty($nombre) || $numero_semana === false || $numero_semana < 1 || empty($fecha_inicio) || empty($fecha_fin) || $curso_id === 0) {
            $msg = 'Todos los campos son obligatorios.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
            return;
        }

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $teacherId)) {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Curso no válido.']); return; }
                setFlash('error', 'Curso no válido.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            if ($this->semanas->existsOverlap($teacherId, $curso_id, $fecha_inicio, $fecha_fin, $id)) {
                $msg = 'El rango de fechas se cruza con otra semana del curso.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
                return;
            }

            $periodo = $this->cursos->getPeriodoForDocente($curso_id, $teacherId);
            if (!$periodo || !$periodo['periodo_inicio'] || !$periodo['periodo_fin']) {
                $msg = 'Curso no tiene un periodo académico válido.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
                return;
            }
            if ($fecha_inicio < $periodo['periodo_inicio'] || $fecha_fin > $periodo['periodo_fin']) {
                $msg = 'La semana debe estar dentro del periodo académico (' . formatDate($periodo['periodo_inicio']) . ' a ' . formatDate($periodo['periodo_fin']) . ').';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
                return;
            }
            if ($fecha_inicio > $fecha_fin) {
                $msg = 'La fecha de inicio no puede ser posterior a la fecha de fin.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
                return;
            }

            $di = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
            $df = DateTime::createFromFormat('Y-m-d', $fecha_fin);
            if ($di === false || $df === false) {
                $msg = 'Formato de fecha inválido.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
                return;
            }

            $finEsperado = (clone $di)->modify('+4 days')->format('Y-m-d');
            if ($fecha_fin !== $finEsperado) {
                $msg = 'La semana debe cubrir exactamente 5 días (ej. lunes a viernes). Inicio: ' . formatDate($di->format('Y-m-d')) . ', fin esperado: ' . formatDate($finEsperado) . '.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
                return;
            }

            $ant = $this->semanas->getByNumeroForDocenteCurso($teacherId, $curso_id, $numero_semana - 1);
            if ($ant) {
                $esperado = (clone new DateTime($ant['fecha_inicio']))->modify('+7 days')->format('Y-m-d');
                if ($fecha_inicio !== $esperado) {
                    $msg = 'La semana #' . $numero_semana . ' debe iniciar el ' . formatDate($esperado) . ' (7 días después de la semana #' . $ant['numero_semana'] . ') para mantener la consecutividad.';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
                    return;
                }
            }

            $post = $this->semanas->getByNumeroForDocenteCurso($teacherId, $curso_id, $numero_semana + 1);
            if ($post) {
                $esperadoPost = (clone $di)->modify('+7 days')->format('Y-m-d');
                if ($post['fecha_inicio'] !== $esperadoPost) {
                    $msg = 'La semana #' . $numero_semana . ' debe iniciar el ' . formatDate($fecha_inicio) . ' y la semana #' . $post['numero_semana'] . ' debe iniciar el ' . formatDate($esperadoPost) . ' para mantener la consecutividad (7 días entre inicio de semanas).';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
                    return;
                }
            }

            if ($id !== null) {
                if ($this->semanas->isClosedForDocente($id, $teacherId)) {
                    $msg = 'No se puede editar una semana cerrada.';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
                    return;
                }
                $this->semanas->update($id, [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'numero_semana' => $numero_semana,
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin,
                ]);
                $msg = 'Semana actualizada correctamente.';
            } else {
                $this->semanas->create([
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'numero_semana' => $numero_semana,
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin,
                    'docente_id' => $teacherId,
                    'curso_id' => $curso_id,
                ]);
                $msg = 'Semana creada correctamente.';
            }

            if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
            setFlash('success', $msg);
        } catch (PDOException $e) {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Error al guardar la semana.'], 500); return; }
            setFlash('error', 'Error al guardar la semana.');
            redirect(BASE_URL . 'docente/formularioSemana?curso_id=' . $curso_id . ($id ? "&id={$id}" : ''));
            return;
        }

        redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
    }

    public function eliminarSemana(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();
        $teacherId = $this->docenteId();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            if ($this->semanas->isClosedForDocente($id, $teacherId)) {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'No se puede eliminar una semana cerrada.']); return; }
                setFlash('error', 'No se puede eliminar una semana cerrada.');
                redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
                return;
            }
            $deleted = $this->semanas->deleteForDocente($id, $teacherId, $curso_id);

            if ($deleted) {
                if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => 'Semana eliminada correctamente.']); return; }
                setFlash('success', 'Semana eliminada correctamente.');
            } else {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Semana no encontrada.']); return; }
                setFlash('error', 'Semana no encontrada.');
            }
        } catch (PDOException $e) {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar la semana.'], 500); return; }
            setFlash('error', 'Error al eliminar la semana.');
        }

        redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
    }

    public function criterios(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);

            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $criterios = $this->criterios->getAllBySemanaSimple($semana_id);

            $totalPorcentaje = 0.0;
            foreach ($criterios as $c) {
                $totalPorcentaje += (float) $c['porcentaje'];
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los criterios.');
            $semana = null;
            $criterios = [];
            $totalPorcentaje = 0;
        }

        require __DIR__ . '/../Views/docente/criterios.php';
    }

    public function formularioCriterio(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();
        $teacherId = $this->docenteId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $criterio = null;

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);

            if (!$semana) {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Semana no encontrada.'], 404); return; }
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . ($semana['curso_id'] ?? 0));
                return;
            }

            if ($semana['cerrada']) {
                $msg = $id === null ? 'No se pueden agregar criterios a una semana cerrada.' : 'No se puede editar un criterio de una semana cerrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $semana['curso_id']);
                return;
            }

            $totalAsignado = $this->semanas->getTotalPercentage($semana_id);

            if ($id !== null) {
                $criterio = $this->criterios->getByIdForSemana($id, $semana_id);

                if ($criterio) {
                    $totalAsignado -= (float) $criterio['porcentaje'];
                }
            }

            $porcentajeDisponible = 100 - $totalAsignado;
        } catch (PDOException $e) {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Error al cargar los datos.'], 500); return; }
            setFlash('error', 'Error al cargar los datos.');
            redirect(BASE_URL . 'docente/criterios?semana_id=' . $semana_id);
            return;
        }

        require __DIR__ . '/../Views/docente/formulario_criterios.php';
    }

    public function guardarCriterio(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $teacherId = $this->docenteId();
        $semana_id = (int)($_POST['semana_id'] ?? 0);
        $id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
        $nombre = sanitize($_POST['nombre'] ?? '');
        $porcentaje = filter_input(INPUT_POST, 'porcentaje', FILTER_VALIDATE_FLOAT);
        $tipo = sanitize($_POST['tipo'] ?? 'conceptual');
        $tiposPermitidos = ['procedimental', 'conceptual', 'actitudinal'];
        if (!in_array($tipo, $tiposPermitidos, true)) {
            $tipo = 'conceptual';
        }

        if (empty($nombre) || $porcentaje === false || $porcentaje <= 0) {
            $msg = 'Nombre y porcentaje válido son obligatorios.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/formularioCriterio?semana_id=' . $semana_id . ($id ? "&id={$id}" : ''));
            return;
        }

        if (mb_strlen($nombre, 'UTF-8') > 255) {
            $msg = 'El nombre del criterio no debe superar los 255 caracteres.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/formularioCriterio?semana_id=' . $semana_id . ($id ? "&id={$id}" : ''));
            return;
        }

        try {
            $weekClosed = $this->semanas->isClosedForDocente($semana_id, $teacherId);
            if ($weekClosed === null) {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Semana no encontrada.']); return; }
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($weekClosed) {
                $msg = 'No se puede modificar criterios de una semana cerrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/criterios?semana_id=' . $semana_id);
                return;
            }

            $currentTotal = $this->semanas->getTotalPercentage($semana_id);

            if ($id !== null) {
                $oldPorcentaje = $this->criterios->getPorcentajeByIdForSemana($id, $semana_id);
                if ($oldPorcentaje !== null) {
                    $currentTotal -= $oldPorcentaje;
                }
            }

            if (($currentTotal + $porcentaje) > 100) {
                $available = 100 - $currentTotal;
                $msg = "El porcentaje total no puede exceder 100%. Disponible: {$available}%";
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/formularioCriterio?semana_id=' . $semana_id . ($id ? "&id={$id}" : ''));
                return;
            }

            if ($this->criterios->existsByNombreForSemana($semana_id, $nombre, $id)) {
                $msg = "Ya existe un criterio con el nombre '" . trim($nombre) . "' en esta semana. Use otro nombre.";
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/formularioCriterio?semana_id=' . $semana_id . ($id ? "&id={$id}" : ''));
                return;
            }

            if ($id !== null) {
                $this->criterios->update($id, ['nombre' => $nombre, 'porcentaje' => $porcentaje, 'tipo' => $tipo]);
                $msg = 'Criterio actualizado correctamente.';
            } else {
                $this->criterios->create(['nombre' => $nombre, 'porcentaje' => $porcentaje, 'semana_id' => $semana_id, 'tipo' => $tipo]);
                $msg = 'Criterio creado correctamente.';
            }

            if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
            setFlash('success', $msg);
        } catch (PDOException $e) {
            logError('Error guardando criterio', ['message' => $e->getMessage(), 'code' => $e->getCode(), 'semana_id' => $semana_id, 'docente_id' => $teacherId, 'nombre' => $nombre]);
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Error al guardar el criterio.'], 500); return; }
            setFlash('error', 'Error al guardar el criterio.');
            redirect(BASE_URL . 'docente/formularioCriterio?semana_id=' . $semana_id . ($id ? "&id={$id}" : ''));
            return;
        }

        redirect(BASE_URL . 'docente/criterios?semana_id=' . $semana_id);
    }

    public function eliminarCriterio(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();
        $teacherId = $this->docenteId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        try {
            $weekClosed = $this->semanas->isClosedForDocente($semana_id, $teacherId);
            if ($weekClosed === null) {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Semana no encontrada.']); return; }
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($weekClosed) {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'No se puede eliminar criterios de una semana cerrada.']); return; }
                setFlash('error', 'No se puede eliminar criterios de una semana cerrada.');
                redirect(BASE_URL . 'docente/criterios?semana_id=' . $semana_id);
                return;
            }

            $deleted = $this->criterios->deleteForSemana($id, $semana_id);

            if ($deleted) {
                if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => 'Criterio eliminado correctamente.']); return; }
                setFlash('success', 'Criterio eliminado correctamente.');
            } else {
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Criterio no encontrado.']); return; }
                setFlash('error', 'Criterio no encontrado.');
            }
        } catch (PDOException $e) {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar el criterio.'], 500); return; }
            setFlash('error', 'Error al eliminar el criterio.');
        }

        redirect(BASE_URL . 'docente/criterios?semana_id=' . $semana_id);
    }

    // ==================== GRUPOS POR SEMANA ====================

    public function grupos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);

            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $curso = $this->cursos->getByIdForDocenteSimple((int) $semana['curso_id'], $teacherId);
            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple((int) $semana['curso_id']);
            $grupos = $this->grupos->getBySemanaConIntegrantes($semana_id);
            $criterioGrupal = $this->criterios->getGrupalBySemana($semana_id);

            $totalPorcentaje = $this->semanas->getTotalPercentage($semana_id);
            $porcentajeDisponible = 100 - $totalPorcentaje;
            if ($criterioGrupal) {
                $porcentajeDisponible += (float) $criterioGrupal['porcentaje'];
            }

            $sinGrupo = [];
            if (!empty($grupos)) {
                $conGrupo = $this->grupos->getEstudiantesConGrupo($semana_id);
                foreach ($estudiantes as $est) {
                    if (!isset($conGrupo[(int) $est['id']])) {
                        $sinGrupo[] = $est;
                    }
                }
            } else {
                $sinGrupo = $estudiantes;
            }

            $semanasConGrupos = [];
            $stmtSg = $this->db->prepare('
                SELECT s.id, s.numero_semana, s.nombre, COUNT(g.id) AS total_grupos
                FROM semanas s
                INNER JOIN equipos g ON g.semana_id = s.id
                WHERE s.curso_id = :curso_id AND s.docente_id = :docente_id AND s.id != :semana_id
                GROUP BY s.id
                ORDER BY s.numero_semana ASC
            ');
            $stmtSg->execute([':curso_id' => (int) $semana['curso_id'], ':docente_id' => $teacherId, ':semana_id' => $semana_id]);
            $semanasConGrupos = $stmtSg->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los grupos.');
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        // Si la vista equipos.php existe (refactor), usarla; si no, grupos.php (compatibilidad)
        $vistaEquipos = __DIR__ . '/../Views/docente/equipos.php';
        if (is_file($vistaEquipos)) {
            require $vistaEquipos;
        } else {
            require __DIR__ . '/../Views/docente/grupos.php';
        }
    }

    public function equipos(): void
    {
        $this->grupos();
    }

    public function generarEquipos(): void
    {
        $this->generarGrupos();
    }

    public function regenerarEquipos(): void
    {
        if (method_exists($this, 'regenerarGrupos')) { $this->regenerarGrupos(); return; }
        $this->generarGrupos();
    }

    public function eliminarEquipos(): void
    {
        if (method_exists($this, 'eliminarGrupos')) { $this->eliminarGrupos(); return; }
        $this->generarGrupos();
    }

    public function copiarEquipos(): void
    {
        if (method_exists($this, 'copiarGrupos')) { $this->copiarGrupos(); return; }
        $this->grupos();
    }

    public function moverEstudianteEquipo(): void
    {
        if (method_exists($this, 'moverEstudianteGrupo')) { $this->moverEstudianteGrupo(); return; }
        $this->grupos();
    }

    public function generarGrupos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $teacherId = $this->docenteId();
        $semana_id = (int) ($_POST['semana_id'] ?? 0);
        $num_grupos = (int) ($_POST['num_grupos'] ?? 0);
        $porcentaje = filter_input(INPUT_POST, 'porcentaje', FILTER_VALIDATE_FLOAT);
        $nombre_rubro = sanitize(trim((string) ($_POST['nombre_rubro'] ?? '')));

        if ($semana_id <= 0 || $num_grupos < 1) {
            $msg = 'Indique un número de grupos válido (mínimo 1).';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
            return;
        }
        if ($porcentaje === false || $porcentaje <= 0 || $porcentaje > 100) {
            $msg = 'Ingrese un porcentaje válido para el rubro grupal (0-100).';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
            return;
        }
        if (mb_strlen($nombre_rubro, 'UTF-8') > 255) {
            $msg = 'El nombre del rubro no debe superar los 255 caracteres.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
            return;
        }
        if ($nombre_rubro === '') {
            $nombre_rubro = 'Trabajo Grupal';
        }

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            if (!$semana) {
                $msg = 'Semana no encontrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 404); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($semana['cerrada']) {
                $msg = 'No se pueden generar grupos en una semana cerrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple((int) $semana['curso_id']);
            if (count($estudiantes) < 1) {
                $msg = 'El curso no tiene estudiantes asignados para formar grupos.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }
            if ($num_grupos > count($estudiantes)) {
                $msg = 'El número de grupos no puede ser mayor que la cantidad de estudiantes (' . count($estudiantes) . ').';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $criterioGrupal = $this->criterios->getGrupalBySemana($semana_id);

            $currentTotal = $this->semanas->getTotalPercentage($semana_id);
            if ($criterioGrupal) {
                $currentTotal -= (float) $criterioGrupal['porcentaje'];
            }
            if (($currentTotal + $porcentaje) > 100) {
                $available = 100 - $currentTotal;
                $msg = "El porcentaje total no puede exceder 100%. Disponible: " . number_format($available, 0) . "%";
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            if ($this->criterios->existsByNombreForSemana($semana_id, $nombre_rubro, $criterioGrupal ? (int) $criterioGrupal['id'] : null)) {
                $msg = "Ya existe un criterio individual con el nombre '" . $nombre_rubro . "' en esta semana. Use otro nombre para el rubro grupal.";
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $this->db->beginTransaction();

            if ($criterioGrupal) {
                $this->criterios->update((int) $criterioGrupal['id'], ['nombre' => $nombre_rubro, 'porcentaje' => $porcentaje]);
            } else {
                $this->criterios->create([
                    'nombre' => $nombre_rubro,
                    'porcentaje' => $porcentaje,
                    'semana_id' => $semana_id,
                    'es_grupal' => 1,
                ]);
            }

            $estudianteIds = array_column($estudiantes, 'id');
            $this->grupos->reemplazar($semana_id, $num_grupos, $estudianteIds);

            $this->db->commit();

            $msg = 'Grupos generados correctamente (' . $num_grupos . ' grupos con ' . count($estudiantes) . ' estudiantes). Rubro grupal "' . $nombre_rubro . '" (' . number_format($porcentaje, 0) . '%) creado.';
            if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
            setFlash('success', $msg);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Error generando grupos', ['message' => $e->getMessage(), 'semana_id' => $semana_id, 'docente_id' => $teacherId]);
            $msg = 'Error al generar los grupos.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
            setFlash('error', $msg);
        }

        redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
    }

    public function actualizarNombreRubro(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $semana_id = (int) ($_POST['semana_id'] ?? 0);
        $nombre = sanitize(trim((string) ($_POST['nombre_rubro'] ?? '')));
        $criterio_id = (int) ($_POST['criterio_id'] ?? 0);

        if ($semana_id <= 0 || $criterio_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Parámetros inválidos.']);
            return;
        }

        if ($nombre === '') {
            $nombre = 'Trabajo Grupal';
        }

        if (mb_strlen($nombre, 'UTF-8') > 255) {
            $this->jsonResponse(['success' => false, 'message' => 'El nombre no puede exceder 255 caracteres.']);
            return;
        }

        $semana = (new Semana($this->db))->getById($semana_id);
        if (!$semana || (int) ($semana['cerrada'] ?? 0)) {
            $this->jsonResponse(['success' => false, 'message' => 'La semana está cerrada o no existe.']);
            return;
        }

        $teacherId = (int) $_SESSION['user_id'];
        $curso = (new Curso($this->db))->getByIdForDocente((int) $semana['curso_id'], $teacherId);
        if (!$curso) {
            $this->jsonResponse(['success' => false, 'message' => 'No tiene acceso a este curso.']);
            return;
        }

        if ($this->criterios->existsByNombreForSemana($semana_id, $nombre, $criterio_id)) {
            $this->jsonResponse(['success' => false, 'message' => "Ya existe un criterio con el nombre '" . $nombre . "' en esta semana."]);
            return;
        }

        $this->criterios->update($criterio_id, ['nombre' => $nombre]);
        $this->jsonResponse(['success' => true, 'message' => 'Nombre del rubro actualizado a "' . $nombre . '".']);
    }

    public function regenerarGrupos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $teacherId = $this->docenteId();
        $semana_id = (int) ($_POST['semana_id'] ?? 0);

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            if (!$semana) {
                $msg = 'Semana no encontrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 404); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($semana['cerrada']) {
                $msg = 'No se pueden regenerar grupos en una semana cerrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $gruposActuales = $this->grupos->getBySemana($semana_id);
            if (empty($gruposActuales)) {
                $msg = 'Aún no hay grupos generados en esta semana.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple((int) $semana['curso_id']);
            if (count($estudiantes) < 1) {
                $msg = 'El curso no tiene estudiantes asignados.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $this->db->beginTransaction();
            $this->grupos->reemplazar($semana_id, count($gruposActuales), array_column($estudiantes, 'id'));
            $this->db->commit();

            $msg = 'Grupos re-generados aleatoriamente. Las notas grupales ya registradas se conservan.';
            if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
            setFlash('success', $msg);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $msg = 'Error al re-generar los grupos.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
            setFlash('error', $msg);
        }

        redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
    }

    public function eliminarGrupos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $teacherId = $this->docenteId();
        $semana_id = (int) ($_POST['semana_id'] ?? 0);

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            if (!$semana) {
                $msg = 'Semana no encontrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 404); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($semana['cerrada']) {
                $msg = 'No se pueden eliminar grupos en una semana cerrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $criterioGrupal = $this->criterios->getGrupalBySemana($semana_id);

            $this->db->beginTransaction();
            $this->grupos->eliminar($semana_id);
            if ($criterioGrupal) {
                $this->calificaciones->deleteByCriterio((int) $criterioGrupal['id']);
                $this->criterios->delete((int) $criterioGrupal['id']);
            }
            $this->db->commit();

            $msg = 'Grupos eliminados. El rubro grupal y sus calificaciones también fueron eliminados.';
            if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
            setFlash('success', $msg);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $msg = 'Error al eliminar los grupos.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
            setFlash('error', $msg);
        }

        redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
    }

    public function copiarGrupos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $teacherId = $this->docenteId();
        $semana_destino = (int) ($_POST['semana_destino'] ?? 0);
        $semana_origen = (int) ($_POST['semana_origen'] ?? 0);

        if ($semana_destino <= 0 || $semana_origen <= 0) {
            $msg = 'Debe seleccionar la semana origen y destino.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/semanas');
            return;
        }
        if ($semana_origen === $semana_destino) {
            $msg = 'La semana origen y destino no pueden ser la misma.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        try {
            $semDestino = $this->semanas->getBasicForDocente($semana_destino, $teacherId);
            if (!$semDestino) {
                $msg = 'Semana destino no encontrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 404); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($semDestino['cerrada']) {
                $msg = 'No se pueden modificar grupos en una semana cerrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_destino);
                return;
            }

            $semOrigen = $this->semanas->getBasicForDocente($semana_origen, $teacherId);
            if (!$semOrigen) {
                $msg = 'Semana origen no encontrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 404); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $curso_id = (int) $semDestino['curso_id'];
            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);

            $this->db->beginTransaction();
            $resultado = $this->grupos->copiar($semana_origen, $semana_destino, $estudiantes);

            $criterioOrigen = $this->criterios->getGrupalBySemana($semana_origen);
            $criterioDestino = $this->criterios->getGrupalBySemana($semana_destino);
            if ($criterioOrigen && !$criterioDestino) {
                $this->criterios->create([
                    'semana_id' => $semana_destino,
                    'nombre' => $criterioOrigen['nombre'],
                    'porcentaje' => $criterioOrigen['porcentaje'],
                    'es_grupal' => 1,
                ]);
            } elseif ($criterioOrigen && $criterioDestino) {
                $this->criterios->update((int) $criterioDestino['id'], [
                    'nombre' => $criterioOrigen['nombre'],
                    'porcentaje' => $criterioOrigen['porcentaje'],
                ]);
            }

            $this->db->commit();

            $msg = $resultado['mensaje'];
            if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg, 'resultado' => $resultado]); return; }
            setFlash('success', $msg);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $msg = 'Error al copiar los grupos.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
            setFlash('error', $msg);
        }

        redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_destino);
    }

    public function moverEstudiante(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $teacherId = $this->docenteId();
        $semana_id = (int) ($_POST['semana_id'] ?? 0);
        $estudiante_id = (int) ($_POST['estudiante_id'] ?? 0);
        $grupo_id = (int) ($_POST['grupo_id'] ?? 0);

        if ($semana_id <= 0 || $estudiante_id <= 0 || $grupo_id <= 0) {
            $msg = 'Datos inválidos.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 400); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
            return;
        }

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            if (!$semana) {
                $msg = 'Semana no encontrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 404); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($semana['cerrada']) {
                $msg = 'No se pueden modificar grupos en una semana cerrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $grupoSemana = $this->grupos->getSemanaIdByGrupo($grupo_id);
            if ($grupoSemana !== $semana_id) {
                $msg = 'El grupo de destino no pertenece a esta semana.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 400); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $estudiantesCurso = $this->asignaciones->getEstudianteIdsByCurso((int) $semana['curso_id']);
            if (!in_array($estudiante_id, $estudiantesCurso, true)) {
                $msg = 'El estudiante no pertenece al curso de la semana.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 400); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
                return;
            }

            $this->grupos->moverEstudiante($semana_id, $estudiante_id, $grupo_id);

            $msg = 'Estudiante reasignado de grupo correctamente.';
            if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
            setFlash('success', $msg);
        } catch (PDOException $e) {
            $msg = 'Error al mover al estudiante.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
            setFlash('error', $msg);
        }

        redirect(BASE_URL . 'docente/grupos?semana_id=' . $semana_id);
    }

    public function asignarPuntos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
            if (!$curso) {
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
                return;
            }

            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);
            $puntosExistentes = $this->puntos->getPorSemana($semana_id, $curso_id);
            $puntosMap = [];
            foreach ($puntosExistentes as $p) {
                $puntosMap[(int) $p['estudiante_id']] = $p;
            }

            $resumenPorEstudiante = [];
            foreach ($estudiantes as $est) {
                $resumenPorEstudiante[(int) $est['id']] = $this->puntos->getResumenEstudiante($curso_id, (int) $est['id']);
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los datos.');
            redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
            return;
        }

        require __DIR__ . '/../Views/docente/asignar_puntos.php';
    }

    public function guardarPuntos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $teacherId = $this->docenteId();
        $curso_id = (int) ($_POST['curso_id'] ?? 0);
        $semana_id = (int) ($_POST['semana_id'] ?? 0);
        $datos = $_POST['puntos'] ?? [];

        if ($curso_id <= 0 || $semana_id <= 0 || empty($datos)) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
            return;
        }

        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
            if (!$curso) {
                $this->jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            if (!$semana) {
                $this->jsonResponse(['success' => false, 'message' => 'Semana no encontrada.'], 404);
                return;
            }

            $this->db->beginTransaction();

            foreach ($datos as $estudianteId => $item) {
                $estudianteId = (int) $estudianteId;
                $puntos = (int) ($item['puntos'] ?? 0);
                $motivo = trim((string) ($item['motivo'] ?? ''));
                $motivoId = isset($item['motivo_id']) && $item['motivo_id'] !== '' ? (int) $item['motivo_id'] : null;

                if ($motivoId !== null && $this->motivosPuntos !== null) {
                    $m = $this->motivosPuntos->resolver($motivoId, $teacherId);
                    if ($m) {
                        $motivo = $m['nombre'];
                    } else {
                        $motivo = '';
                        $motivoId = null;
                    }
                }

                if ($estudianteId <= 0) continue;

                $puntos = max(-100, min(100, $puntos));

                if ($puntos !== 0) {
                    $this->puntos->registrar($estudianteId, $curso_id, $semana_id, $puntos, $motivo !== '' ? $motivo : null, $teacherId, $motivoId);
                }
            }

            $this->db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Puntos guardados correctamente.']);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'message' => 'Error al guardar los puntos.'], 500);
        }
    }

    /** Página de administración de motivos de puntos */
    public function motivos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $cursoRegreso = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $motivos = $this->motivosPuntos->listarParaDocente($this->docenteId(), false);
        $categorias = $this->motivosPuntos->listarCategorias();
        $pageTitle = 'Motivos de Puntos';
        require __DIR__ . '/../Views/docente/motivos.php';
    }

    /** Formulario de motivo (crear/editar) para modal */
    public function motivoFormulario(): void
    {
        $this->requireDocente();
        $docenteId = $this->docenteId();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $motivo = null;
        if ($id > 0) {
            foreach ($this->motivosPuntos->listarParaDocente($docenteId, false) as $m) {
                if ((int) $m['id'] === $id && !$m['es_sistema']) {
                    $motivo = $m;
                    break;
                }
            }
            if (!$motivo) {
                setFlash('error', 'Motivo no encontrado o del sistema.');
                redirect(BASE_URL . 'docente/motivos');
                return;
            }
        }
        $categorias = $this->motivosPuntos->listarCategorias();
        require __DIR__ . '/../Views/docente/motivos_form.php';
    }

    /** Catálogo de motivos de puntos (sistema + propios) */
    public function motivosPuntos(): void
    {
        $this->requireDocente();
        $todos = !empty($_GET['todos']);
        $this->jsonResponse([
            'success' => true,
            'motivos' => $this->motivosPuntos->listarParaDocente($this->docenteId(), !$todos),
            'categorias' => $this->motivosPuntos->listarCategorias(),
        ]);
    }

    public function guardarMotivoPunto(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $categoriaId = isset($_POST['categoria_id']) && $_POST['categoria_id'] !== '' ? (int) $_POST['categoria_id'] : null;
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            $this->jsonResponse(['success' => false, 'message' => 'El nombre del motivo es obligatorio (máx. 100 caracteres).'], 400);
            return;
        }
        if (mb_strlen($descripcion) > 500) {
            $this->jsonResponse(['success' => false, 'message' => 'La descripción no puede exceder 500 caracteres.'], 400);
            return;
        }

        try {
            if ($id > 0) {
                $ok = $this->motivosPuntos->actualizar($id, $this->docenteId(), $nombre, $categoriaId, $descripcion);
                if (!$ok) {
                    $this->jsonResponse(['success' => false, 'message' => 'No se pudo actualizar el motivo.'], 400);
                    return;
                }
                $this->jsonResponse(['success' => true, 'message' => 'Motivo actualizado.', 'id' => $id]);
                return;
            }
            $nuevoId = $this->motivosPuntos->crear($this->docenteId(), $nombre, $categoriaId, $descripcion);
            $this->jsonResponse(['success' => true, 'message' => 'Motivo agregado.', 'id' => $nuevoId, 'nombre' => $nombre]);
        } catch (RuntimeException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            logError('Error guardando motivo: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'No se pudo guardar el motivo.'], 500);
        }
    }

    public function alternarMotivoPunto(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);
        try {
            $ok = $this->motivosPuntos->alternar($id, $this->docenteId());
            if ($ok) {
                $this->jsonResponse(['success' => true, 'motivos' => $this->motivosPuntos->listarParaDocente($this->docenteId(), false)]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Los motivos del sistema no se pueden modificar.'], 403);
            }
        } catch (Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => 'No se pudo cambiar el estado del motivo.'], 500);
        }
    }

    public function registrarPuntos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            $cursos = $this->cursos->getByDocenteActivos($teacherId);

            $curso = null;
            $estudiantes = [];
            $resumenPorEstudiante = [];
            $historialPuntos = [];
            $semanaAbierta = null;
            $semanaSelect = 0;

            if ($curso_id > 0) {
                $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
                if ($curso) {
                    $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);
                    foreach ($estudiantes as $est) {
                        $estId = (int) $est['id'];
                        $resumenPorEstudiante[$estId] = $this->puntos->getResumenEstudiante($curso_id, $estId);
                    }

                    $historialPuntos = $this->puntos->getHistorialCurso($curso_id);

                    // Semanas abiertas del curso (selector ascendente 1..N); activa = la que contiene hoy
                    $stmt = $this->db->prepare('SELECT id, numero_semana, nombre, fecha_inicio, fecha_fin FROM semanas WHERE curso_id = :curso_id AND docente_id = :docente_id AND cerrada = 0 ORDER BY numero_semana ASC');
                    $stmt->execute([':curso_id' => $curso_id, ':docente_id' => $teacherId]);
                    $semanasAbiertasCurso = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $hoy = date('Y-m-d');
                    $semanaAbierta = null;
                    foreach ($semanasAbiertasCurso as $s) {
                        if ($hoy >= $s['fecha_inicio'] && $hoy <= $s['fecha_fin']) {
                            $semanaAbierta = $s;
                            break;
                        }
                    }
                    // Si ninguna cubre hoy, la mas reciente abierta
                    if ($semanaAbierta === null && !empty($semanasAbiertasCurso)) {
                        $semanaAbierta = $semanasAbiertasCurso[count($semanasAbiertasCurso) - 1];
                    }

                    // Si el docente eligio una semana explicita en el selector, usarla
                    $semanaDesdeGet = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;
                    if ($semanaDesdeGet > 0) {
                        foreach ($semanasAbiertasCurso as $s) {
                            if ((int) $s['id'] === $semanaDesdeGet) {
                                $semanaAbierta = $s;
                                break;
                            }
                        }
                    }
                    // Pasar la semana efectiva a la vista (id) para el JS
                    $semanaSelect = $semanaAbierta !== null ? (int) $semanaAbierta['id'] : ($semanaDesdeGet > 0 ? $semanaDesdeGet : 0);
                }
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los datos.');
            redirect(BASE_URL . 'docente/registrarPuntos');
            return;
        }

        require __DIR__ . '/../Views/docente/registrar_puntos.php';
    }

    public function guardarPuntosDirecto(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        $teacherId = $this->docenteId();
        $curso_id = (int) ($_POST['curso_id'] ?? 0);
        $datos = $_POST['puntos'] ?? [];

        if ($curso_id <= 0 || empty($datos)) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
            return;
        }

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
            if (!$curso) {
                $this->jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }

            $semana = null;
            $semana_id = (int) ($_POST['semana_id'] ?? 0);

            if ($semana_id > 0) {
                // Semana elegida explícitamente por el docente
                $stmt = $this->db->prepare('SELECT id, numero_semana, nombre FROM semanas WHERE id = :id AND curso_id = :curso_id AND docente_id = :docente_id AND cerrada = 0');
                $stmt->execute([':id' => $semana_id, ':curso_id' => $curso_id, ':docente_id' => $teacherId]);
                $semana = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$semana) {
                    $this->jsonResponse(['success' => false, 'message' => 'La semana seleccionada no es válida o está cerrada.'], 400);
                    return;
                }
            } else {
                // Sin selección: semana activa = la que contiene hoy según su rango; si ninguna, la más reciente abierta
                $stmt = $this->db->prepare('SELECT id, numero_semana, nombre FROM semanas WHERE curso_id = :curso_id AND docente_id = :docente_id AND cerrada = 0 AND CURDATE() BETWEEN fecha_inicio AND fecha_fin ORDER BY numero_semana DESC LIMIT 1');
                $stmt->execute([':curso_id' => $curso_id, ':docente_id' => $teacherId]);
                $semana = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$semana) {
                    $stmt = $this->db->prepare('SELECT id, numero_semana, nombre FROM semanas WHERE curso_id = :curso_id AND docente_id = :docente_id AND cerrada = 0 ORDER BY numero_semana DESC LIMIT 1');
                    $stmt->execute([':curso_id' => $curso_id, ':docente_id' => $teacherId]);
                    $semana = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if (!$semana) {
                $this->jsonResponse(['success' => false, 'message' => 'No hay semanas abiertas en este curso. Abra una semana primero.'], 400);
                return;
            }

            $semana_id = (int) $semana['id'];

            $this->db->beginTransaction();

            foreach ($datos as $estudianteId => $item) {
                $estudianteId = (int) $estudianteId;
                $puntos = (int) ($item['puntos'] ?? 0);
                $motivo = trim((string) ($item['motivo'] ?? ''));
                $motivoId = isset($item['motivo_id']) && $item['motivo_id'] !== '' ? (int) $item['motivo_id'] : null;

                if ($motivoId !== null && $this->motivosPuntos !== null) {
                    $m = $this->motivosPuntos->resolver($motivoId, $teacherId);
                    if ($m) {
                        $motivo = $m['nombre'];
                    } else {
                        $motivo = '';
                        $motivoId = null;
                    }
                }

                if ($estudianteId <= 0) continue;

                $puntos = max(-100, min(100, $puntos));

                if ($puntos !== 0) {
                    $this->puntos->registrar($estudianteId, $curso_id, $semana_id, $puntos, $motivo !== '' ? $motivo : null, $teacherId, $motivoId);
                }
            }

            $this->db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Puntos guardados en semana ' . $semana['numero_semana'] . ': ' . $semana['nombre']]);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'message' => 'Error al guardar los puntos.'], 500);
        }
    }

    public function historialPuntos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $estudiante_id = isset($_GET['estudiante_id']) ? (int) $_GET['estudiante_id'] : 0;

        if ($curso_id <= 0 || $estudiante_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Parámetros inválidos.'], 400);
            return;
        }

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
            if (!$curso) {
                $this->jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }

            $historial = $this->puntos->getPorEstudiante($curso_id, $estudiante_id);
            $resumen = $this->puntos->getResumenEstudiante($curso_id, $estudiante_id);

            $this->jsonResponse([
                'success' => true,
                'historial' => $historial,
                'resumen' => $resumen,
            ]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al cargar historial.'], 500);
        }
    }

    public function asistenciaHoy(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        if ($curso_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Curso inválido.'], 400);
            return;
        }

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
            if (!$curso) {
                $this->jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }

            $hoy = date('Y-m-d');
            $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

            if ($semana_id > 0) {
                // Obtener el rango de la semana seleccionada
                $stmt = $this->db->prepare('SELECT fecha_inicio, fecha_fin FROM semanas WHERE id = :id AND curso_id = :curso_id AND docente_id = :docente_id');
                $stmt->execute([':id' => $semana_id, ':curso_id' => $curso_id, ':docente_id' => $teacherId]);
                $semana = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($semana) {
                    // Si hoy cae dentro de la semana, intentar asistencia de hoy; si no hay, usar el rango completo
                    if ($hoy >= $semana['fecha_inicio'] && $hoy <= $semana['fecha_fin']) {
                        $asistencia = $this->asistencias->getByCursoYFecha($curso_id, $hoy, null, $teacherId);
                        if (!empty($asistencia)) {
                            $resultado = [];
                            foreach ($asistencia as $estId => $info) { $resultado[(int)$estId] = $info['estado']; }
                            $this->jsonResponse(['success' => true, 'asistencia' => $resultado, 'fecha' => $hoy, 'semana_id' => $semana_id]);
                            return;
                        }
                    }
                    // Mostrar la asistencia de las fechas de esa semana (agregado por fecha mas reciente)
                    $asistencia = $this->asistencias->getByCursoYRangoFechas($curso_id, $semana['fecha_inicio'], $semana['fecha_fin'], $teacherId);
                    $resultado = [];
                    foreach ($asistencia as $estId => $info) { $resultado[(int)$estId] = $info['estado']; }
                    $this->jsonResponse(['success' => true, 'asistencia' => $resultado, 'fecha' => $hoy, 'semana_id' => $semana_id]);
                    return;
                }
            }

            // Sin semana elegida: hoy, si no hay, buscar la fecha mas reciente con asistencia del curso
            $asistencia = $this->asistencias->getByCursoYFecha($curso_id, $hoy, null, $teacherId);
            $fechaUsada = $hoy;

            if (empty($asistencia)) {
                $stmt = $this->db->prepare('SELECT fecha FROM asistencias WHERE curso_id = :curso_id AND registrado_por = :docente_id ORDER BY fecha DESC LIMIT 1');
                $stmt->execute([':curso_id' => $curso_id, ':docente_id' => $teacherId]);
                $ultima = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ultima) {
                    $fechaUsada = $ultima['fecha'];
                    $asistencia = $this->asistencias->getByCursoYFecha($curso_id, $fechaUsada, null, $teacherId);
                }
            }

            $resultado = [];
            foreach ($asistencia as $estId => $info) {
                $resultado[(int) $estId] = $info['estado'];
            }

            $this->jsonResponse(['success' => true, 'asistencia' => $resultado, 'fecha' => $fechaUsada]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al consultar asistencia.'], 500);
        }
    }

    private function participarValidarCursoSemana(int &$cursoId, int &$semanaId): bool
    {
        $teacherId = $this->docenteId();
        $cursoId = (int) $cursoId;
        $semanaId = (int) $semanaId;

        if ($cursoId <= 0 || $semanaId <= 0) {
            return false;
        }
        $curso = $this->cursos->getByIdForDocente($cursoId, $teacherId);
        if (!$curso) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT id FROM semanas WHERE id = :id AND curso_id = :curso_id AND docente_id = :docente_id AND cerrada = 0');
        $stmt->execute([':id' => $semanaId, ':curso_id' => $cursoId, ':docente_id' => $teacherId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Activa la participación (abre ronda) para el docente actual.
     */
    public function activarParticipacion(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }
        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        $cursoId = (int) ($_POST['curso_id'] ?? 0);
        $semanaId = (int) ($_POST['semana_id'] ?? 0);

        if (!$this->participarValidarCursoSemana($cursoId, $semanaId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Curso o semana no válida.'], 400);
            return;
        }

        try {
            $id = $this->participacionVivo->abrir($cursoId, $semanaId, $this->docenteId());
            if ($id === null) {
                $this->jsonResponse(['success' => false, 'message' => 'Ya hay una participación activa. Ciérrala antes de abrir otra.'], 409);
                return;
            }
            $this->jsonResponse(['success' => true, 'message' => 'Participación activada. Los estudiantes ya pueden registrarse.', 'id' => $id]);
        } catch (PDOException $e) {
            logError('Error activando participación: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al activar la participación.'], 500);
        }
    }

    /**
     * Devuelve el estado actual de la participación (ronda abierta + cola).
     */
    public function estadoParticipacion(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $cursoId = (int) ($_GET['curso_id'] ?? 0);
        $semanaId = (int) ($_GET['semana_id'] ?? 0);

        if (!$this->participarValidarCursoSemana($cursoId, $semanaId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Curso o semana no válida.'], 400);
            return;
        }

        try {
            $activa = $this->participacionVivo->getAbierta($cursoId, $semanaId, $this->docenteId());
            if (!$activa) {
                $this->jsonResponse(['success' => true, 'activa' => null, 'cola' => []]);
                return;
            }
            $cola = $this->participacionVivo->getCola((int) $activa['id']);
            // Mapear motivos para mostrar nombre
            $motivos = $this->motivosPuntos->listarParaDocente($this->docenteId(), false);
            $motivoMap = [];
            foreach ($motivos as $m) {
                $motivoMap[(int) $m['id']] = $m['nombre'];
            }
            foreach ($cola as &$fila) {
                $fila['motivo_nombre'] = isset($fila['motivo_id']) && isset($motivoMap[(int) $fila['motivo_id']]) ? $motivoMap[(int) $fila['motivo_id']] : null;
            }
            unset($fila);

            $this->jsonResponse(['success' => true, 'activa' => $activa, 'cola' => $cola]);
        } catch (PDOException $e) {
            logError('Error consultando participación: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al consultar la participación.'], 500);
        }
    }

    /**
     * Filtro 1: marca si el estudiante participó.
     */
    public function marcarParticipacion(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }
        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        $participacionId = (int) ($_POST['participacion_id'] ?? 0);
        $estudianteId = (int) ($_POST['estudiante_id'] ?? 0);
        $participa = !empty($_POST['participa']) ? 1 : 0;

        if ($participacionId <= 0 || $estudianteId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
            return;
        }

        try {
            $activa = $this->db->prepare('SELECT id FROM participaciones_activas WHERE id = :id AND docente_id = :docente_id AND estado = "abierta"');
            $activa->execute([':id' => $participacionId, ':docente_id' => $this->docenteId()]);
            if (!$activa->fetch()) {
                $this->jsonResponse(['success' => false, 'message' => 'Participación no válida o cerrada.'], 404);
                return;
            }
            $this->participacionVivo->marcarParticipacion($participacionId, $estudianteId, (bool) $participa);
            $this->jsonResponse(['success' => true, 'message' => 'Marcado actualizado.']);
        } catch (PDOException $e) {
            logError('Error marcando participación: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al marcar.'], 500);
        }
    }

    /**
     * Filtro 2: asigna motivo + puntos al participante marcado.
     */
    public function asignarMotivoParticipacion(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }
        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        $participacionId = (int) ($_POST['participacion_id'] ?? 0);
        $estudianteId = (int) ($_POST['estudiante_id'] ?? 0);
        $motivoId = isset($_POST['motivo_id']) && $_POST['motivo_id'] !== '' ? (int) $_POST['motivo_id'] : null;
        $puntos = (int) ($_POST['puntos'] ?? 0);

        if ($participacionId <= 0 || $estudianteId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
            return;
        }

        try {
            $activa = $this->db->prepare('SELECT id FROM participaciones_activas WHERE id = :id AND docente_id = :docente_id AND estado = "abierta"');
            $activa->execute([':id' => $participacionId, ':docente_id' => $this->docenteId()]);
            if (!$activa->fetch()) {
                $this->jsonResponse(['success' => false, 'message' => 'Participación no válida o cerrada.'], 404);
                return;
            }
            $puntos = max(-100, min(100, $puntos));
            $this->participacionVivo->asignarMotivo($participacionId, $estudianteId, $motivoId, $puntos);
            $this->jsonResponse(['success' => true, 'message' => 'Motivo asignado.']);
        } catch (PDOException $e) {
            logError('Error asignando motivo: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al asignar motivo.'], 500);
        }
    }

    /**
     * Cierra la participación: elimina no marcados y registra los confirmados como puntos reales.
     */
    public function cerrarParticipacion(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }
        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        $participacionId = (int) ($_POST['participacion_id'] ?? 0);

        if ($participacionId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
            return;
        }

        try {
            $activa = $this->db->prepare('SELECT id, semana_id, curso_id FROM participaciones_activas WHERE id = :id AND docente_id = :docente_id AND estado = "abierta"');
            $activa->execute([':id' => $participacionId, ':docente_id' => $this->docenteId()]);
            $info = $activa->fetch(PDO::FETCH_ASSOC);
            if (!$info) {
                $this->jsonResponse(['success' => false, 'message' => 'Participación no válida o ya cerrada.'], 404);
                return;
            }

            $this->db->beginTransaction();
            // Cerrar y marcar estados (confirmado/eliminado)
            $this->participacionVivo->cerrar($participacionId, $this->docenteId());
            // Insertar los confirmados como puntos reales
            $insertados = $this->participacionVivo->confirmarMarcados((int) $info['id'], (int) $info['curso_id'], (int) $info['semana_id'], $this->docenteId(), $this->puntos, $this->motivosPuntos);
            $this->db->commit();

            $this->jsonResponse(['success' => true, 'message' => 'Participación cerrada. Se registraron ' . count($insertados) . ' puntos.', 'registrados' => count($insertados)]);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logError('Error cerrando participación: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al cerrar la participación.'], 500);
        }
    }

    public function eliminarPunto(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $punto_id = isset($_POST['punto_id']) ? (int) $_POST['punto_id'] : 0;
        $password = $_POST['password'] ?? '';

        if ($punto_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
            return;
        }

        if ($password === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Debe ingresar su contraseña.'], 400);
            return;
        }

        try {
            $stmtUser = $this->db->prepare('SELECT password FROM usuarios WHERE id = :id');
            $stmtUser->execute([':id' => $teacherId]);
            $row = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($password, $row['password'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Contraseña incorrecta.'], 403);
                return;
            }

            $stmt = $this->db->prepare('SELECT pp.*, c.docente_id FROM puntos_participacion pp JOIN cursos c ON c.id = pp.curso_id WHERE pp.id = :id');
            $stmt->execute([':id' => $punto_id]);
            $punto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$punto) {
                $this->jsonResponse(['success' => false, 'message' => 'Registro no encontrado.'], 404);
                return;
            }
            if ((int) $punto['docente_id'] !== $teacherId) {
                $this->jsonResponse(['success' => false, 'message' => 'No tiene permiso para eliminar este registro.'], 403);
                return;
            }

            $del = $this->db->prepare('DELETE FROM puntos_participacion WHERE id = :id');
            $del->execute([':id' => $punto_id]);

            $this->jsonResponse(['success' => true, 'message' => 'Registro eliminado.']);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar.'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // SANCIONES
    // ═══════════════════════════════════════════════════════════════

    public function apreciaciones(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            $cursos = $this->cursos->getByDocenteActivos($teacherId);

            $curso = null;
            $estudiantes = [];
            $apreciacionesPorEstudiante = [];
            $semanas = [];
            $promediosPorEstudiante = [];
            $nivelesPorEstudiante = [];
            $niveles = [];

            if ($curso_id > 0) {
                $niveles = $this->nivelesMago->getAllActivos();
                $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
                if ($curso) {
                    $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);

                    foreach ($estudiantes as $est) {
                        $stmtProm = $this->db->prepare('
                            SELECT s.id, c.porcentaje, cal.nota
                            FROM semanas s
                            INNER JOIN criterios c ON c.semana_id = s.id
                            INNER JOIN calificaciones cal ON cal.criterio_id = c.id AND cal.estudiante_id = :eid
                            WHERE s.curso_id = :cid
                        ');
                        $stmtProm->execute([':eid' => $est['id'], ':cid' => $curso_id]);
                        $rows = $stmtProm->fetchAll(PDO::FETCH_ASSOC);
                        $semanasLocal = [];
                        foreach ($rows as $row) {
                            $sid = (int) $row['id'];
                            if (!isset($semanasLocal[$sid])) $semanasLocal[$sid] = ['sp' => 0.0, 'sw' => 0.0];
                            $semanasLocal[$sid]['sp'] += (float) $row['nota'] * (float) $row['porcentaje'] / 100;
                            $semanasLocal[$sid]['sw'] += (float) $row['porcentaje'];
                        }
                        $totalP = 0.0;
                        $totalW = 0.0;
                        foreach ($semanasLocal as $s) {
                            if ($s['sw'] > 0) { $totalP += $s['sp']; $totalW += $s['sw']; }
                        }
                        $promedio = $totalW > 0 ? round($totalP / ($totalW / 100), 2) : 0.0;
                        $nivelEst = studentPowerMode($promedio, $niveles);
                        $nivelesPorEstudiante[(int) $est['id']] = $nivelEst;
                    }

                    $semanasStmt = $this->db->prepare('SELECT id, numero_semana, nombre, cerrada FROM semanas WHERE curso_id = :curso_id AND docente_id = :docente_id ORDER BY numero_semana ASC');
                    $semanasStmt->execute([':curso_id' => $curso_id, ':docente_id' => $teacherId]);
                    $semanas = $semanasStmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($semana_id <= 0 && !empty($semanas)) {
                        foreach ($semanas as $s) {
                            if ((int) $s['cerrada'] === 0) {
                                $semana_id = (int) $s['id'];
                                break;
                            }
                        }
                        if ($semana_id <= 0) {
                            $semana_id = (int) end($semanas)['id'];
                        }
                    }

                    if ($semana_id > 0) {
                        $apreciacionesExistentes = $this->apreciaciones->getBySemanaCurso($semana_id, $curso_id);
                        foreach ($apreciacionesExistentes as $san) {
                            $apreciacionesPorEstudiante[(int) $san['estudiante_id']] = $san;
                        }

                        $promediosPorEstudiante = $this->calificaciones->getPromediosPorSemanaYCurso($semana_id, $curso_id);
                    }
                }
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los datos.');
            redirect(BASE_URL . 'docente/apreciaciones');
            return;
        }

        require __DIR__ . '/../Views/docente/apreciaciones.php';
    }

    public function apreciarEstudiante(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            return;
        }

        $teacherId = $this->docenteId();
        $curso_id = (int) ($_POST['curso_id'] ?? 0);
        $semana_id = (int) ($_POST['semana_id'] ?? 0);
        $datos = $_POST['apreciaciones'] ?? [];

        if ($curso_id <= 0 || $semana_id <= 0 || empty($datos)) {
            $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos.'], 400);
            return;
        }

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
            if (!$curso) {
                $this->jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            if (!$semana) {
                $this->jsonResponse(['success' => false, 'message' => 'Semana no válida.'], 404);
                return;
            }
            if ((int) $semana['cerrada'] === 1) {
                $this->jsonResponse(['success' => false, 'message' => 'No puede ajustar en una semana cerrada.'], 400);
                return;
            }

            $this->db->beginTransaction();

            $niveles = $this->nivelesMago->getAllActivos();
            $stmtNivel = $this->db->prepare('
                SELECT s.id, c.porcentaje, cal.nota
                FROM semanas s
                INNER JOIN criterios c ON c.semana_id = s.id
                INNER JOIN calificaciones cal ON cal.criterio_id = c.id AND cal.estudiante_id = :eid
                WHERE s.curso_id = :cid
            ');

            foreach ($datos as $estudianteId => $item) {
                $estudianteId = (int) $estudianteId;
                $puntos = (float) ($item['puntos'] ?? 0);
                $motivo = trim((string) ($item['motivo'] ?? ''));

                if ($estudianteId <= 0) continue;

                if ($puntos === 0) {
                    $del = $this->db->prepare('DELETE FROM apreciaciones WHERE estudiante_id = :eid AND semana_id = :sid AND curso_id = :cid');
                    $del->execute([':eid' => $estudianteId, ':sid' => $semana_id, ':cid' => $curso_id]);
                } else {
                    $stmtNivel->execute([':eid' => $estudianteId, ':cid' => $curso_id]);
                    $rows = $stmtNivel->fetchAll(PDO::FETCH_ASSOC);
                    $semanasLocal = [];
                    foreach ($rows as $row) {
                        $sid = (int) $row['id'];
                        if (!isset($semanasLocal[$sid])) $semanasLocal[$sid] = ['sp' => 0.0, 'sw' => 0.0];
                        $semanasLocal[$sid]['sp'] += (float) $row['nota'] * (float) $row['porcentaje'] / 100;
                        $semanasLocal[$sid]['sw'] += (float) $row['porcentaje'];
                    }
                    $totalP = 0.0;
                    $totalW = 0.0;
                    foreach ($semanasLocal as $s) {
                        if ($s['sw'] > 0) { $totalP += $s['sp']; $totalW += $s['sw']; }
                    }
                    $promedio = $totalW > 0 ? round($totalP / ($totalW / 100), 2) : 0.0;
                    $nivelEst = studentPowerMode($promedio, $niveles);
                    if ((int) ($nivelEst['rango'] ?? 1) >= 5 && $puntos < 0) continue;

                    $puntos = max(-3, min(3, $puntos));
                    $this->apreciaciones->upsert($estudianteId, $curso_id, $semana_id, $puntos, $motivo !== '' ? $motivo : null, $teacherId);
                }
            }

            $this->db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Ajustes guardados en Semana ' . $semana['numero_semana'] . ': ' . $semana['nombre']]);
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->jsonResponse(['success' => false, 'message' => 'Error al guardar las apreciaciones.'], 500);
        }
    }

    public function eliminarApreciacion(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $apreciacion_id = isset($_POST['apreciacion_id']) ? (int) $_POST['apreciacion_id'] : 0;
        $password = $_POST['password'] ?? '';

        if ($apreciacion_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID inválido.'], 400);
            return;
        }

        if ($password === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Debe ingresar su contraseña.'], 400);
            return;
        }

        try {
            $stmtUser = $this->db->prepare('SELECT password FROM usuarios WHERE id = :id');
            $stmtUser->execute([':id' => $teacherId]);
            $row = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($password, $row['password'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Contraseña incorrecta.'], 403);
                return;
            }

            $stmt = $this->db->prepare('SELECT san.*, c.docente_id FROM apreciaciones san JOIN cursos c ON c.id = san.curso_id WHERE san.id = :id');
            $stmt->execute([':id' => $apreciacion_id]);
            $apreciacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$apreciacion) {
                $this->jsonResponse(['success' => false, 'message' => 'Sanción no encontrada.'], 404);
                return;
            }
            if ((int) $apreciacion['docente_id'] !== $teacherId) {
                $this->jsonResponse(['success' => false, 'message' => 'No tiene permiso para eliminar esta sanción.'], 403);
                return;
            }

            $del = $this->db->prepare('DELETE FROM apreciaciones WHERE id = :id');
            $del->execute([':id' => $apreciacion_id]);

            $this->jsonResponse(['success' => true, 'message' => 'Sanción eliminada. Promedio restaurado.']);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al eliminar la sanción.'], 500);
        }
    }

    public function historialApreciaciones(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $estudiante_id = isset($_GET['estudiante_id']) ? (int) $_GET['estudiante_id'] : 0;

        if ($curso_id <= 0 || $estudiante_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Parámetros inválidos.'], 400);
            return;
        }

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
            if (!$curso) {
                $this->jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }

            $historial = $this->apreciaciones->getByEstudianteCurso($estudiante_id, $curso_id);

            $this->jsonResponse([
                'success' => true,
                'historial' => $historial,
            ]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al cargar historial.'], 500);
        }
    }

    public function perfil(): void
    {
        $this->requirePerfil();
        if (headers_sent()) return;

        try {
            $stmt = $this->db->prepare('SELECT apellidos, nombres, dni, email FROM usuarios WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $this->docenteId()]);
            $perfilData = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $perfilData = null;
        }

        require __DIR__ . '/../Views/docente/perfil.php';
    }

    public function guardarPerfil(): void
    {
        $this->requirePerfil();
        if (headers_sent()) return;

        $isAjax = $this->isAjax();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/perfil');
            return;
        }

        $section = $_POST['section'] ?? '';
        $teacherId = $this->docenteId();

        if ($section === 'photo') {
            $file = $_FILES['foto'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $msg = 'Seleccione una imagen válida.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/perfil');
                return;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowed)) {
                $msg = 'Formato no permitido. Use JPG, PNG, GIF o WEBP.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/perfil');
                return;
            }

            if ($file['size'] > 2 * 1024 * 1024) {
                $msg = 'La imagen no debe superar 2MB.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/perfil');
                return;
            }

            $foto = uploadProfilePhoto($file, $teacherId);
            if ($foto) {
                try {
                    (new User($this->db))->updateFoto($teacherId, $foto);
                    $_SESSION['user_foto'] = $foto;
                    $msg = 'Foto de perfil actualizada correctamente.';
                    if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
                    setFlash('success', $msg);
                } catch (PDOException $e) {
                    $msg = 'Error al guardar la foto en la base de datos.';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
                    setFlash('error', $msg);
                }
            } else {
                $msg = 'Error al subir la imagen.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
            }

            redirect(BASE_URL . 'docente/perfil');
            return;
        }

        if ($section === 'datos') {
            $apellidos = trim($_POST['apellidos'] ?? '');
            $nombres = trim($_POST['nombres'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($apellidos) || empty($nombres)) {
                $msg = 'Los apellidos y nombres son obligatorios.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/perfil');
                return;
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg = 'El correo electrónico no es válido.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/perfil');
                return;
            }

            try {
                if (!empty($email)) {
                    $stmt = $this->db->prepare('SELECT id FROM usuarios WHERE email = :email AND id != :id LIMIT 1');
                    $stmt->execute([':email' => $email, ':id' => $teacherId]);
                    if ($stmt->fetch()) {
                        $msg = 'El correo electrónico ya está en uso por otro usuario.';
                        if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                        setFlash('error', $msg);
                        redirect(BASE_URL . 'docente/perfil');
                        return;
                    }
                }

                $oldStmt = $this->db->prepare('SELECT apellidos, nombres, email FROM usuarios WHERE id = :id');
                $oldStmt->execute([':id' => $teacherId]);
                $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

                $newApellidos = formatApellidos($apellidos);
                $newNombres = formatNombres($nombres);
                $newEmail = $email;

                $this->db->prepare('UPDATE usuarios SET apellidos = :apellidos, nombres = :nombres, email = :email WHERE id = :id')
                    ->execute([':apellidos' => $newApellidos, ':nombres' => $newNombres, ':email' => $newEmail, ':id' => $teacherId]);

                $_SESSION['user_nombre'] = $newApellidos . ' ' . $newNombres;
                $_SESSION['user_nombres'] = $newNombres;
                $_SESSION['user_apellidos'] = $newApellidos;
                $_SESSION['user_email'] = $newEmail;

                if ($old) {
                    $cambios = [];
                    if (trim($old['apellidos']) !== $newApellidos) $cambios['Apellidos'] = $old['apellidos'] . ' → ' . $newApellidos;
                    if (trim($old['nombres']) !== $newNombres) $cambios['Nombres'] = $old['nombres'] . ' → ' . $newNombres;
                    if (trim($old['email']) !== $newEmail) $cambios['Email'] = $old['email'] . ' → ' . $newEmail;
                    if (!empty($cambios)) {
                        $mailer = new Mailer($this->db);
                        $mailer->sendProfileChangeNotification($newEmail, $newApellidos . ' ' . $newNombres, 'datos', $cambios, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');
                    }
                }

                $msg = 'Datos personales actualizados correctamente.';
                if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
                setFlash('success', $msg);
            } catch (PDOException $e) {
                $msg = 'Error al actualizar los datos.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
                setFlash('error', $msg);
            }

            redirect(BASE_URL . 'docente/perfil');
            return;
        }

        if ($section === 'password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $msg = 'Todos los campos son obligatorios.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/perfil');
                return;
            }

            if (!passwordComplejidadOk($newPassword)) {
                $msg = 'La nueva contraseña debe tener al menos 8 caracteres, con al menos una mayúscula, una minúscula y un número.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/perfil');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $msg = 'Las contraseñas no coinciden.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/perfil');
                return;
            }

            try {
                $user = (new User($this->db))->getPasswordById($teacherId);

                if (!$user || !password_verify($currentPassword, $user)) {
                    $msg = 'La contraseña actual es incorrecta.';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'docente/perfil');
                    return;
                }

                if (passwordYaUsada($this->db, $teacherId, $newPassword)) {
                    $msg = 'La nueva contraseña ya fue utilizada anteriormente. Elija una contraseña diferente.';
                    if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                    setFlash('error', $msg);
                    redirect(BASE_URL . 'docente/perfil');
                    return;
                }

                $this->db->beginTransaction();
                guardarPasswordHistorial($this->db, $teacherId, $user);
                (new User($this->db))->updatePassword($teacherId, $newPassword);
                $this->db->commit();

                $mailer = new Mailer($this->db);
                $mailer->sendProfileChangeNotification(
                    $_SESSION['user_email'] ?? '',
                    $_SESSION['user_nombre'] ?? '',
                    'password',
                    ['Contraseña' => 'Cambiada exitosamente'],
                    $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                );

                $msg = 'Contraseña actualizada correctamente.';
                if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg]); return; }
                setFlash('success', $msg);
            } catch (PDOException $e) {
                $msg = 'Error al actualizar la contraseña.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
                setFlash('error', $msg);
            }

            redirect(BASE_URL . 'docente/perfil');
            return;
        }

        if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Sección no válida.']); return; }
        redirect(BASE_URL . 'docente/perfil');
    }

    public function notas(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            $semana = null;
            if ($semana_id > 0) {
                $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            } elseif ($curso_id > 0) {
                $curso = $this->cursos->getByIdForDocente($curso_id, $teacherId);
                if ($curso) {
                    $semanasCurso = $this->semanas->getAllByCurso($curso_id, $teacherId);
                    if (!empty($semanasCurso)) {
                        $semana = $this->semanas->getBasicForDocente((int) $semanasCurso[0]['id'], $teacherId);
                        $semana_id = (int) $semanasCurso[0]['id'];
                    } else {
                        setFlash('info', 'Este curso aún no tiene semanas registradas.');
                        redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
                        return;
                    }
                }
            }

            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $semana_id = (int) $semana['id'];
            $criterios = $this->criterios->getAllBySemanaConBloqueo($semana_id);

            $curso_id = (int) $semana['curso_id'];
            if ($curso_id > 0) {
                $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);
            } else {
                $estudiantes = (new User($this->db))->getAllEstudiantesActivos();
            }

            $calificaciones = [];
            $criterioGrupal = null;
            $grupos = [];
            $gruposPorEstudiante = [];
            if (!empty($criterios) && !empty($estudiantes)) {
                $criterioIds = array_column($criterios, 'id');
                $gradeRows = $this->calificaciones->getNotasByCriterios($criterioIds);

                foreach ($gradeRows as $row) {
                    $calificaciones[$row['estudiante_id']][$row['criterio_id']] = $row['nota'];
                }
            }

            $criterioGrupal = $this->criterios->getGrupalBySemana($semana_id);
            if ($criterioGrupal) {
                $grupos = $this->grupos->getBySemanaConIntegrantes($semana_id);
                $gruposPorEstudiante = $this->grupos->getEstudiantesConGrupo($semana_id);
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los datos de calificaciones.');
            $semana = null;
            $criterios = [];
            $estudiantes = [];
            $calificaciones = [];
            $criterioGrupal = null;
            $grupos = [];
            $gruposPorEstudiante = [];
        }

        require __DIR__ . '/../Views/docente/calificaciones.php';
    }

    public function guardarNotas(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $teacherId = $this->docenteId();
        $semana_id = (int)($_POST['semana_id'] ?? 0);

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $teacherId);
            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($semana['cerrada']) {
                setFlash('error', 'No se pueden modificar notas de una semana cerrada.');
                redirect(BASE_URL . 'docente/notas?semana_id=' . $semana_id);
                return;
            }

            $criterioIds = $this->criterios->getIdsBySemana($semana_id);

            if (empty($criterioIds)) {
                setFlash('error', 'No hay criterios definidos para esta semana.');
                redirect(BASE_URL . 'docente/notas?semana_id=' . $semana_id);
                return;
            }

            $curso_id = (int) $semana['curso_id'];
            $allowedStudentIds = [];
            if ($curso_id > 0) {
                $allowedStudentIds = $this->asignaciones->getEstudianteIdsByCurso($curso_id);
            }

            $grades = $_POST['grades'] ?? [];
            $gruposNotas = $_POST['grupos'] ?? [];

            $this->db->beginTransaction();

            foreach ($criterioIds as $cid) {
                $this->calificaciones->deleteByCriterioAndStudents($cid, $allowedStudentIds);
            }

            $savedCount = 0;
            foreach ($grades as $studentId => $criterioGrades) {
                $sid = (int) $studentId;
                if ($curso_id > 0 && !empty($allowedStudentIds) && !in_array($sid, $allowedStudentIds, true)) continue;

                foreach ($criterioGrades as $criterioId => $nota) {
                    $nota = trim((string)($nota ?? ''));
                    if ($nota === '' || !is_numeric($nota)) continue;

                    $notaValue = (float) $nota;
                    if ($notaValue < 0 || $notaValue > 20) continue;

                    $cid = (int) $criterioId;
                    if (!in_array($cid, $criterioIds, true)) continue;

                    $this->calificaciones->insert($sid, $cid, $notaValue);
                    $savedCount++;
                }
            }

            foreach ($gruposNotas as $grupoId => $criterioNotas) {
                $gid = (int) $grupoId;
                if ($gid <= 0) continue;

                $miembros = $this->grupos->getIntegrantesIdsByGrupo($gid);
                if (empty($miembros)) continue;

                foreach ($criterioNotas as $criterioId => $nota) {
                    $nota = trim((string)($nota ?? ''));
                    if ($nota === '' || !is_numeric($nota)) continue;

                    $notaValue = (float) $nota;
                    if ($notaValue < 0 || $notaValue > 20) continue;

                    $cid = (int) $criterioId;
                    if (!in_array($cid, $criterioIds, true)) continue;

                    foreach ($miembros as $sid) {
                        if ($curso_id > 0 && !empty($allowedStudentIds) && !in_array($sid, $allowedStudentIds, true)) continue;
                        $this->calificaciones->createOrUpdate($sid, $cid, $notaValue);
                        $savedCount++;
                    }
                }
            }

            $this->db->commit();
            setFlash('success', "Calificaciones guardadas correctamente ({$savedCount} registros).");
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            setFlash('error', 'Error al guardar las calificaciones.');
        }

        $redirect = $_POST['redirect'] ?? '';
        if ($redirect !== '' && strpos($redirect, BASE_URL) === 0) {
            redirect($redirect);
        } else {
            redirect(BASE_URL . 'docente/notas?semana_id=' . $semana_id);
        }
    }

    public function alternarBloqueoCriterio(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $criterioId = (int) ($_POST['criterio_id'] ?? 0);
        if ($criterioId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        try {
            $row = $this->criterios->getConSemana($criterioId);

            if (!$row || (int) $row['docente_id'] !== $this->docenteId()) {
                $this->jsonResponse(['success' => false, 'message' => 'No autorizado.']);
                return;
            }
            if ($row['cerrada']) {
                $this->jsonResponse(['success' => false, 'message' => 'No se puede modificar una semana cerrada.']);
                return;
            }

            $this->criterios->toggleBlocked($criterioId);
            $nuevoEstado = $this->criterios->isBlocked($criterioId);

            $this->jsonResponse(['success' => true, 'bloqueado' => $nuevoEstado]);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al actualizar.']);
        }
    }

    public function alternarBloqueoSemana(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        if (!validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            $this->jsonResponse(['success' => false, 'message' => 'Token de seguridad inválido.']);
            return;
        }

        $semana_id = (int) ($_POST['semana_id'] ?? 0);
        if ($semana_id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        try {
            $cerrada = $this->semanas->isClosedForDocente($semana_id, $this->docenteId());

            if ($cerrada === null) {
                $this->jsonResponse(['success' => false, 'message' => 'Semana no encontrada.']);
                return;
            }

            // Teacher can only close (1), never reopen (0)
            if ($cerrada) {
                $this->jsonResponse(['success' => false, 'message' => 'La semana ya está cerrada. Solicite al administrador que la reabra.']);
                return;
            }

            $this->semanas->close($semana_id);
            $this->discusiones->closeAllBySemana($semana_id);

            $this->jsonResponse(['success' => true, 'message' => 'Semana cerrada correctamente. Ya no se puede modificar.']);
        } catch (PDOException $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al cerrar la semana.']);
        }
    }

    // ==================== REPORTES ====================

    public function reportes(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();

        try {
            $cursos = $this->cursos->getAllActivosByDocente($docenteId);
            $semanas = $this->semanas->getAllByDocenteSimple($docenteId);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los datos.');
            $cursos = [];
            $semanas = [];
        }

        require __DIR__ . '/../Views/docente/reportes.php';
    }

    public function reporteNotasPorSemana(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $docenteId);

            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/reportes');
                return;
            }

            $criterios = $this->criterios->getAllBySemanaSimple($semana_id);

            $curso_id = (int) ($semana['curso_id'] ?? 0);
            if ($curso_id > 0) {
                $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);
            } else {
                $estudiantes = (new User($this->db))->getAllEstudiantesActivos();
            }

            $calificaciones = [];
            $promedios = [];
            if (!empty($criterios) && !empty($estudiantes)) {
                $criterioIds = array_column($criterios, 'id');
                $gradeRows = $this->calificaciones->getNotasByCriterios($criterioIds);

                foreach ($gradeRows as $row) {
                    $calificaciones[$row['estudiante_id']][$row['criterio_id']] = $row['nota'];
                }

                $apreciacionesSemana = $curso_id > 0 ? $this->apreciaciones->getBySemanaCurso($semana_id, $curso_id) : [];
                $apreciacionesMap = [];
                foreach ($apreciacionesSemana as $san) {
                    $apreciacionesMap[(int) $san['estudiante_id']] = (float) $san['puntos'];
                }
                foreach ($estudiantes as $est) {
                    $sumaPonderada = 0;
                    $sumaPorcentajes = 0;
                    foreach ($criterios as $crit) {
                        $nota = $calificaciones[$est['id']][$crit['id']] ?? null;
                        if ($nota !== null) {
                            $sumaPonderada += (float) $nota * (float) $crit['porcentaje'] / 100;
                            $sumaPorcentajes += (float) $crit['porcentaje'];
                        }
                    }
                    $prom = $sumaPorcentajes > 0 ? round($sumaPonderada / ($sumaPorcentajes / 100), 2) : 0;
                    if (isset($apreciacionesMap[(int) $est['id']])) {
                        $prom = max(0, round($prom + $apreciacionesMap[(int) $est['id']], 2));
                    }
                    $promedios[$est['id']] = $prom;
                }
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al generar el reporte.');
            redirect(BASE_URL . 'docente/reportes');
            return;
        }

        require __DIR__ . '/../Views/docente/reporte_notas_semana.php';
    }

    public function reportePromediosEstudiantes(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            if ($curso_id > 0) {
                $estudiantes = $this->asignaciones->getEstudiantesByCursoConCarrera($curso_id);
            } else {
                $estudiantes = (new User($this->db))->getAllEstudiantesActivosConCarrera();
            }

            if ($curso_id > 0) {
                $semanas = $this->semanas->getByCursoParaReportes($curso_id, $docenteId);
            } else {
                $semanas = $this->semanas->getAllByDocenteSimple($docenteId);
            }

            $reportData = [];
            $allGrades = $this->calificaciones->getAllForDocente($docenteId);

            $gradesByStudent = [];
            foreach ($allGrades as $row) {
                $gradesByStudent[$row['estudiante_id']][] = $row;
            }

            $allApreciaciones = [];
            if ($curso_id > 0) {
                $allApreciacionesRaw = $this->apreciaciones->getByCurso($curso_id);
            } else {
                $allApreciacionesRaw = $this->apreciaciones->getByCurso(0);
            }
            foreach ($allApreciacionesRaw as $san) {
                $allApreciaciones[(int) $san['estudiante_id']][] = $san;
            }

            foreach ($estudiantes as $est) {
                $grades = $gradesByStudent[$est['id']] ?? [];
                $sumaPonderada = 0;
                $sumaPorcentajes = 0;

                foreach ($grades as $g) {
                    $sumaPonderada += (float) $g['nota'] * (float) $g['porcentaje'] / 100;
                    $sumaPorcentajes += (float) $g['porcentaje'];
                }

                $promedio = $sumaPorcentajes > 0 ? round($sumaPonderada / ($sumaPorcentajes / 100), 2) : 0;
                $reportData[$est['id']] = [
                    'apellidos' => $est['apellidos'],
                    'nombres' => $est['nombres'],
                    'username' => $est['username'],
                    'promedio' => $promedio,
                    'total_calificaciones' => count($grades),
                ];
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al generar el reporte.');
            redirect(BASE_URL . 'docente/reportes');
            return;
        }

        require __DIR__ . '/../Views/docente/promedios_estudiante.php';
    }

    public function reporteRendimientoGrupo(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $docenteId);

            if (!$semana) {
                setFlash('error', 'Semana no encontrada.');
                redirect(BASE_URL . 'docente/reportes');
                return;
            }

            $criterios = $this->criterios->getAllBySemanaSimple($semana_id);

            $curso_id = (int) ($semana['curso_id'] ?? 0);
            if ($curso_id > 0) {
                $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);
            } else {
                $estudiantes = (new User($this->db))->getAllEstudiantesActivos();
            }

            $calificaciones = [];
            $promedios = [];
            if (!empty($criterios) && !empty($estudiantes)) {
                $criterioIds = array_column($criterios, 'id');
                $gradeRows = $this->calificaciones->getNotasByCriterios($criterioIds);

                foreach ($gradeRows as $row) {
                    $calificaciones[$row['estudiante_id']][$row['criterio_id']] = $row['nota'];
                }

                $notas = [];
                $apreciacionesSemana = $curso_id > 0 ? $this->apreciaciones->getBySemanaCurso($semana_id, $curso_id) : [];
                $apreciacionesMap = [];
                foreach ($apreciacionesSemana as $san) {
                    $apreciacionesMap[(int) $san['estudiante_id']] = (float) $san['puntos'];
                }
                foreach ($estudiantes as $est) {
                    $sumaPonderada = 0;
                    $sumaPorcentajes = 0;
                    foreach ($criterios as $crit) {
                        $nota = $calificaciones[$est['id']][$crit['id']] ?? null;
                        if ($nota !== null) {
                            $sumaPonderada += (float) $nota * (float) $crit['porcentaje'] / 100;
                            $sumaPorcentajes += (float) $crit['porcentaje'];
                        }
                    }
                    $prom = $sumaPorcentajes > 0 ? round($sumaPonderada / ($sumaPorcentajes / 100), 2) : 0;
                    if (isset($apreciacionesMap[(int) $est['id']])) {
                        $prom = max(0, round($prom + $apreciacionesMap[(int) $est['id']], 2));
                    }
                    $promedios[$est['id']] = $prom;
                    if ($prom > 0) $notas[] = $prom;
                }

                $stats = [
                    'total_estudiantes' => count($estudiantes),
                    'con_calificaciones' => count($notas),
                    'sin_calificaciones' => count($estudiantes) - count($notas),
                    'promedio_general' => !empty($notas) ? round(array_sum($notas) / count($notas), 2) : 0,
                    'nota_maxima' => !empty($notas) ? max($notas) : 0,
                    'nota_minima' => !empty($notas) ? min($notas) : 0,
                    'aprobados' => count(array_filter($notas, fn($n) => $n >= 10)),
                    'reprobados' => count(array_filter($notas, fn($n) => $n < 10)),
                ];
            } else {
                $stats = [
                    'total_estudiantes' => count($estudiantes),
                    'con_calificaciones' => 0,
                    'sin_calificaciones' => count($estudiantes),
                    'promedio_general' => 0,
                    'nota_maxima' => 0,
                    'nota_minima' => 0,
                    'aprobados' => 0,
                    'reprobados' => 0,
                ];
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al generar el reporte.');
            redirect(BASE_URL . 'docente/reportes');
            return;
        }

        require __DIR__ . '/../Views/docente/rendimiento_grupo.php';
    }

    public function reporteResumenCurso(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        if ($curso_id <= 0) {
            setFlash('error', 'Seleccione un curso.');
            redirect(BASE_URL . 'docente/reportes');
            return;
        }

        try {
            $curso = $this->cursos->getByIdParaReporte($curso_id, $docenteId);

            if (!$curso) {
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'docente/reportes');
                return;
            }

            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);

            $semanas = $this->semanas->getByCursoParaReportes($curso_id, $docenteId);

            $promediosPorSemana = [];
            $promediosFinales = [];

            if (!empty($semanas) && !empty($estudiantes)) {
                foreach ($semanas as $semana) {
                    $criterios = $this->criterios->getAllBySemanaSimple($semana['id']);

                    if (empty($criterios)) {
                        foreach ($estudiantes as $est) {
                            $promediosPorSemana[$est['id']][$semana['id']] = null;
                        }
                        continue;
                    }

                    $criterioIds = array_column($criterios, 'id');
                    $gradeRows = $this->calificaciones->getNotasByCriterios($criterioIds);

                    $calificaciones = [];
                    foreach ($gradeRows as $row) {
                        $calificaciones[$row['estudiante_id']][$row['criterio_id']] = (float) $row['nota'];
                    }

                    foreach ($estudiantes as $est) {
                        $sumaPonderada = 0;
                        $sumaPorcentajes = 0;
                        foreach ($criterios as $crit) {
                            $nota = $calificaciones[$est['id']][$crit['id']] ?? null;
                            if ($nota !== null) {
                                $sumaPonderada += (float) $nota * (float) $crit['porcentaje'] / 100;
                                $sumaPorcentajes += (float) $crit['porcentaje'];
                            }
                        }
                        $promediosPorSemana[$est['id']][$semana['id']] = $sumaPorcentajes > 0 ? round($sumaPonderada / ($sumaPorcentajes / 100), 2) : null;
                    }
                }

                foreach ($estudiantes as $est) {
                    $sumNotas = 0;
                    $countSemanas = 0;
                    foreach ($semanas as $semana) {
                        $prom = $promediosPorSemana[$est['id']][$semana['id']] ?? null;
                        if ($prom !== null) {
                            $sumNotas += $prom;
                            $countSemanas++;
                        }
                    }
                    $promediosFinales[$est['id']] = $countSemanas > 0 ? round($sumNotas / $countSemanas, 2) : null;
                }
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al generar el reporte.');
            redirect(BASE_URL . 'docente/reportes');
            return;
        }

        require __DIR__ . '/../Views/docente/resumen_curso.php';
    }

    public function exportarNotasSemana(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = (int) ($_GET['curso_id'] ?? 0);

        if ($curso_id <= 0) { setFlash('error', 'Curso inválido.'); redirect(BASE_URL . 'docente/semanas'); return; }

        try {
            $curso = $this->cursos->getByIdParaReporte($curso_id, $docenteId);
            if (!$curso) { setFlash('error', 'Curso no encontrado.'); redirect(BASE_URL . 'docente/semanas'); return; }

            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);

            $semanas = $this->semanas->getCerradasByCurso($curso_id, $docenteId);

            $promedios = [];
            foreach ($semanas as $sem) {
                $criterios = $this->criterios->getAllBySemanaSimple($sem['id']);
                if (empty($criterios)) { foreach ($estudiantes as $e) $promedios[$e['id']][$sem['id']] = null; continue; }

                $cIds = array_column($criterios, 'id');
                $grades = $this->calificaciones->getNotasByCriterios($cIds);
                $cal = []; foreach ($grades as $g) $cal[$g['estudiante_id']][$g['criterio_id']] = (float)$g['nota'];

                foreach ($estudiantes as $est) {
                    $sw = 0; $sp = 0;
                    foreach ($criterios as $cr) {
                        $n = $cal[$est['id']][$cr['id']] ?? null;
                        if ($n !== null) { $sp += $n * (float)$cr['porcentaje'] / 100; $sw += (float)$cr['porcentaje']; }
                    }
                    $promedios[$est['id']][$sem['id']] = $sw > 0 ? round($sp / ($sw / 100), 2) : null;
                }
            }

            $finales = [];
            foreach ($estudiantes as $est) {
                $sum = 0; $cnt = 0;
                foreach ($semanas as $s) { $p = $promedios[$est['id']][$s['id']] ?? null; if ($p !== null) { $sum += $p; $cnt++; } }
                $finales[$est['id']] = $cnt > 0 ? round($sum / $cnt, 2) : null;
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al exportar.');
            redirect(BASE_URL . 'docente/semanasCurso?curso_id=' . $curso_id);
            return;
        }

        ob_clean();
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="notas_' . $curso['codigo'] . '_' . date('Ymd') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8">';
        echo '<style>td,th{border:1px solid #ccc;padding:5px 8px;font-family:Arial;font-size:11px;text-align:center}th{background:#4E37E6;color:#fff;font-weight:700}td.est{text-align:left;font-weight:600}.prom{background:#f0f0f0;font-weight:700}.bajo{color:#dc3545}.alto{color:#0d6efd}</style>';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Notas</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '</head><body>';
        echo '<table>';
        echo '<tr><th rowspan="2">#</th><th rowspan="2">Estudiante</th>';
        foreach ($semanas as $s) {
            echo '<th colspan="2">' . htmlspecialchars($s['nombre']) . ' (Sem ' . $s['numero_semana'] . ')</th>';
        }
        echo '<th rowspan="2">Promedio Final</th></tr>';
        echo '<tr>';
        foreach ($semanas as $s) {
            echo '<th>Prom</th><th style="font-weight:400;color:#ffc107;">Aprobó</th>';
        }
        echo '</tr>';

        $idx = 1;
        foreach ($estudiantes as $est) {
            $promFinal = $finales[$est['id']] ?? null;
            echo '<tr>';
            echo '<td>' . $idx++ . '</td>';
            echo '<td class="est">' . htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) . '</td>';
            foreach ($semanas as $s) {
                $p = $promedios[$est['id']][$s['id']] ?? null;
                if ($p !== null) {
                    $cls = $p < 13 ? 'bajo' : 'alto';
                    echo '<td class="' . $cls . ' prom">' . number_format($p, 2) . '</td>';
                    echo '<td>' . ($p >= 13 ? 'Sí' : 'No') . '</td>';
                } else {
                    echo '<td class="prom">--</td><td>--</td>';
                }
            }
            $fcls = $promFinal !== null ? ($promFinal < 13 ? 'bajo' : 'alto') : '';
            echo '<td class="prom ' . $fcls . '">' . ($promFinal !== null ? number_format($promFinal, 2) : '--') . '</td>';
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    public function exportarReporteCurso(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = (int) ($_GET['curso_id'] ?? 0);
        $format = sanitize($_GET['format'] ?? 'pdf');

        if ($curso_id <= 0) { setFlash('error', 'Seleccione un curso.'); redirect(BASE_URL . 'docente/reportes'); return; }

        try {
            $curso = $this->cursos->getByIdParaReporte($curso_id, $docenteId);
            if (!$curso) { setFlash('error', 'Curso no encontrado.'); redirect(BASE_URL . 'docente/reportes'); return; }

            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);

            $semanas = $this->semanas->getByCursoParaReportes($curso_id, $docenteId);

            $promediosPorSemana = [];
            $promediosFinales = [];

            foreach ($semanas as $semana) {
                $criterios = $this->criterios->getAllBySemanaSimple($semana['id']);
                if (empty($criterios)) { foreach ($estudiantes as $e) $promediosPorSemana[$e['id']][$semana['id']] = null; continue; }

                $cIds = array_column($criterios, 'id');
                $grades = $this->calificaciones->getNotasByCriterios($cIds);
                $cal = []; foreach ($grades as $g) $cal[$g['estudiante_id']][$g['criterio_id']] = (float)$g['nota'];

                foreach ($estudiantes as $est) {
                    $sw = 0; $sp = 0;
                    foreach ($criterios as $cr) {
                        $n = $cal[$est['id']][$cr['id']] ?? null;
                        if ($n !== null) { $sp += (float)$n * (float)$cr['porcentaje'] / 100; $sw += (float)$cr['porcentaje']; }
                    }
                    $promediosPorSemana[$est['id']][$semana['id']] = $sw > 0 ? round($sp / ($sw / 100), 2) : null;
                }
            }

            foreach ($estudiantes as $est) {
                $sum = 0; $cnt = 0;
                foreach ($semanas as $s) { $p = $promediosPorSemana[$est['id']][$s['id']] ?? null; if ($p !== null) { $sum += $p; $cnt++; } }
                $promediosFinales[$est['id']] = $cnt > 0 ? round($sum / $cnt, 2) : null;
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al exportar.'); redirect(BASE_URL . 'docente/reportes'); return;
        }

        switch ($format) {
            case 'word': $this->generateWordReport($curso, $estudiantes, $semanas, $promediosPorSemana, $promediosFinales); break;
            case 'excel': $this->generateExcelReport($curso, $estudiantes, $semanas, $promediosPorSemana, $promediosFinales); break;
            default: $this->generatePdfReport($curso, $estudiantes, $semanas, $promediosPorSemana, $promediosFinales); break;
        }
    }

    private function generateWordReport(array $curso, array $estudiantes, array $semanas, array $promediosPorSemana, array $promediosFinales): void
    {
        $nombreCurso = htmlspecialchars($curso['nombre']);
        $fecha = date('d/m/Y H:i');
        $aprobados = count(array_filter($promediosFinales, fn($p) => $p !== null && $p >= 10));
        $reprobados = count(array_filter($promediosFinales, fn($p) => $p !== null && $p < 10));
        $validos = array_filter($promediosFinales, fn($p) => $p !== null);
        $promGeneral = $validos ? round(array_sum($validos) / count($validos), 2) : 0;

        // Chart data as SVG
        $chartSvg = $this->buildChartSvg($estudiantes, $semanas, $promediosPorSemana);

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
        $html .= '<head><meta charset="utf-8">';
        $html .= '<!--[if gte mso 9]><xml><w:wordDocument><w:view>Print</w:view><w:zoom>100</w:zoom><w:orientation>landscape</w:orientation></w:wordDocument><o:DocumentProperties><o:Orientation>Landscape</o:Orientation></o:DocumentProperties></xml><style> @page {size:landscape; margin:1cm; }</style><![endif]-->';
        $html .= '<style>@page{size:landscape;margin:1cm;}body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #333;padding:6px 8px;text-align:center;font-size:11px;}th{background:#2c3e50;color:#fff;font-weight:bold;}.t-aprobado{color:#28a745;font-weight:bold;}.t-reprobado{color:#dc3545;font-weight:bold;}.aprobado{background:#28a745;color:#fff;font-weight:bold;}.reprobado{background:#dc3545;color:#fff;font-weight:bold;}.prom-header{background:#17a2b8;color:#fff;}.title{font-size:22px;font-weight:bold;color:#2c3e50;margin-bottom:5px;}.subtitle{font-size:13px;color:#666;margin-bottom:15px;}.page-break{page-break-before:always;}.summary{display:flex;gap:20px;margin:15px 0;}.summary-box{padding:10px 15px;border-radius:6px;color:#fff;font-size:13px;}</style>';
        $html .= '</head><body>';

        // Page 1: Grades matrix
        $html .= '<div class="title">Resumen del Curso: ' . $nombreCurso . '</div>';
        $html .= '<div class="subtitle">Fecha: ' . $fecha . ' | Codigo: ' . htmlspecialchars($curso['codigo']) . '</div>';

        $html .= '<div class="summary">';
        $html .= '<div class="summary-box" style="background:#28a745;">Aprobados: ' . $aprobados . '</div>';
        $html .= '<div class="summary-box" style="background:#dc3545;">Reprobados: ' . $reprobados . '</div>';
        $html .= '<div class="summary-box" style="background:#17a2b8;">Promedio General: ' . number_format($promGeneral, 2) . '</div>';
        $html .= '</div>';

        $html .= '<table><thead><tr><th>#</th><th>Estudiante</th>';
        foreach ($semanas as $s) { $html .= '<th>' . htmlspecialchars($s['nombre']) . '<br><small>Sem ' . $s['numero_semana'] . '</small></th>'; }
        $html .= '<th style="background:#17a2b8;">Promedio<br>Final</th></tr></thead><tbody>';

        $idx = 1;
        foreach ($estudiantes as $est) {
            $html .= '<tr><td>' . $idx++ . '</td><td style="text-align:left;font-weight:bold;">' . htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) . '</td>';
            foreach ($semanas as $s) {
                $p = $promediosPorSemana[$est['id']][$s['id']] ?? null;
                if ($p !== null) {
                    $cls = $p >= 10 ? 't-aprobado' : 't-reprobado';
                    $html .= '<td class="' . $cls . '">' . number_format($p, 2) . '</td>';
                } else { $html .= '<td>--</td>'; }
            }
            $f = $promediosFinales[$est['id']] ?? null;
            $fcls = ($f !== null && $f >= 10) ? 'aprobado' : 'reprobado';
            $html .= '<td class="' . $fcls . '"><strong>' . ($f !== null ? number_format($f, 2) : '--') . '</strong></td></tr>';
        }
        $html .= '</tbody></table>';

        // Page 2: Chart
        $html .= '<div class="page-break"></div>';
        $html .= '<div class="title">Evolucion de Calificaciones</div>';
        $html .= '<div class="subtitle">Grafico de evolucion por estudiante</div>';
        $html .= '<div style="text-align:center;margin-top:20px;">' . $chartSvg . '</div>';

        // Legend
        $html .= '<div style="margin-top:20px;font-size:12px;"><strong>Leyenda:</strong><br>';
        $colores = ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c','#e67e22','#34495e','#e91e63','#00bcd4','#8bc34a','#ff5722'];
        foreach ($estudiantes as $i => $est) {
            $c = $colores[$i % count($colores)];
            $html .= '<span style="display:inline-block;margin:2px 8px;"><span style="display:inline-block;width:12px;height:12px;background:' . $c . ';border-radius:2px;vertical-align:middle;"></span> ' . htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) . '</span> ';
        }
        $html .= '</div>';

        $html .= '</body></html>';

        header('Content-Type: application/msword');
        header('Content-Disposition: attachment; filename="Resumen_' . str_replace(' ', '_', $curso['nombre']) . '_' . date('Ymd') . '.doc"');
        echo $html;
        exit;
    }

    private function generatePdfReport(array $curso, array $estudiantes, array $semanas, array $promediosPorSemana, array $promediosFinales): void
    {
        $aprobados = count(array_filter($promediosFinales, fn($p) => $p !== null && $p >= 10));
        $reprobados = count(array_filter($promediosFinales, fn($p) => $p !== null && $p < 10));
        $validos = array_filter($promediosFinales, fn($p) => $p !== null);
        $promGen = $validos ? round(array_sum($validos) / count($validos), 2) : 0;

        $nombreCurso = htmlspecialchars($curso['nombre']);
        $fecha = date('d/m/Y H:i');
        $chartSvg = $this->buildChartSvg($estudiantes, $semanas, $promediosPorSemana);

        $html = '<html><head><meta charset="utf-8">';
        $html .= '<style>'
            . 'body{font-family:DejaVu Sans,Helvetica,Arial,sans-serif;margin:10px;}'
            . 'table{border-collapse:collapse;width:100%;}'
            . 'th,td{border:1px solid #333;padding:6px 8px;text-align:center;font-size:11px;}'
            . 'th{background:#2c3e50;color:#fff;font-weight:bold;}'
            . '.aprobado{background:#28a745;color:#fff;font-weight:bold;}'
            . '.reprobado{background:#dc3545;color:#fff;font-weight:bold;}'
            . '.t-aprobado{color:#28a745;font-weight:bold;}'
            . '.t-reprobado{color:#dc3545;font-weight:bold;}'
            . '.title{font-size:22px;font-weight:bold;color:#2c3e50;margin-bottom:5px;}'
            . '.subtitle{font-size:13px;color:#666;margin-bottom:15px;}'
            . '.page-break{page-break-before:always;}'
            . '.summary{width:100%;margin:15px 0;}'
            . '.summary-box{display:inline-block;padding:10px 15px;border-radius:6px;color:#fff;font-size:13px;}'
            . '</style>';
        $html .= '</head><body>';

        // Page 1: Resumen + matriz de notas
        $html .= '<div class="title">Resumen del Curso: ' . $nombreCurso . '</div>';
        $html .= '<div class="subtitle">Fecha: ' . $fecha . ' | Código: ' . htmlspecialchars($curso['codigo']) . '</div>';

        $html .= '<div class="summary">'
            . '<span class="summary-box" style="background:#28a745;margin-right:10px;">Aprobados: ' . $aprobados . '</span>'
            . '<span class="summary-box" style="background:#dc3545;margin-right:10px;">Reprobados: ' . $reprobados . '</span>'
            . '<span class="summary-box" style="background:#17a2b8;">Promedio General: ' . number_format($promGen, 2) . '</span>'
            . '</div>';

        $html .= '<table><thead><tr><th>#</th><th>Estudiante</th>';
        foreach ($semanas as $s) { $html .= '<th>' . htmlspecialchars($s['nombre']) . '<br><small>Sem ' . $s['numero_semana'] . '</small></th>'; }
        $html .= '<th style="background:#17a2b8;">Promedio<br>Final</th></tr></thead><tbody>';

        $idx = 1;
        foreach ($estudiantes as $est) {
            $html .= '<tr><td>' . $idx++ . '</td><td style="text-align:left;font-weight:bold;">' . htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) . '</td>';
            foreach ($semanas as $s) {
                $p = $promediosPorSemana[$est['id']][$s['id']] ?? null;
                if ($p !== null) {
                    $cls = $p >= 10 ? 't-aprobado' : 't-reprobado';
                    $html .= '<td class="' . $cls . '">' . number_format($p, 2) . '</td>';
                } else { $html .= '<td>--</td>'; }
            }
            $f = $promediosFinales[$est['id']] ?? null;
            $fcls = ($f !== null && $f >= 10) ? 'aprobado' : 'reprobado';
            $html .= '<td class="' . $fcls . '"><strong>' . ($f !== null ? number_format($f, 2) : '--') . '</strong></td></tr>';
        }
        $html .= '</tbody></table>';

        // Page 2: Gráfico de evolución
        $html .= '<div class="page-break"></div>';
        $html .= '<div class="title">Evolución de Calificaciones</div>';
        $html .= '<div class="subtitle">Gráfico de evolución por estudiante</div>';
        $html .= '<div style="text-align:center;margin-top:20px;">' . $chartSvg . '</div>';

        // Leyenda de colores
        $html .= '<div style="margin-top:14px;margin-bottom:8px;font-size:12px;"><strong>LEYENDA DE COLORES</strong>: Cada color identifica a un estudiante; su línea muestra la evolución de sus calificaciones por semana.</div>';
        $html .= '<div style="margin-top:10px;font-size:12px;">';
        $colores = ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c','#e67e22','#34495e','#e91e63','#00bcd4','#8bc34a','#ff5722'];
        foreach ($estudiantes as $i => $est) {
            $c = $colores[$i % count($colores)];
            $html .= '<span style="display:inline-block;margin:3px 10px;"><span style="display:inline-block;width:14px;height:14px;background:' . $c . ';border-radius:3px;vertical-align:middle;"></span> ' . htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) . '</span> ';
        }
        $html .= '</div>';

        $html .= '</body></html>';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->WriteHTML($html);

        $filename = 'Resumen_' . str_replace(' ', '_', $curso['nombre']) . '_' . date('Ymd') . '.pdf';
        $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    private function marginL() { return 10; }
    private function marginR() { return 10; }

    private function buildChartSvg(array $estudiantes, array $semanas, array $promediosPorSemana): string
    {
        $svgW = 750; $svgH = 350;
        $chartX = 50; $chartY = 20; $chartW = $svgW - 80; $chartH = $svgH - 80;
        $maxVal = 20;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $svgW . ' ' . $svgH . '" style="max-width:100%;height:auto;">';

        // Grid
        for ($v = 0; $v <= $maxVal; $v += 5) {
            $yy = $chartY + $chartH - ($v / $maxVal * $chartH);
            $svg .= '<line x1="' . $chartX . '" y1="' . $yy . '" x2="' . ($chartX + $chartW) . '" y2="' . $yy . '" stroke="#ddd" stroke-width="1"/>';
            $svg .= '<text x="' . ($chartX - 5) . '" y="' . ($yy + 4) . '" text-anchor="end" font-size="10" fill="#666">' . $v . '</text>';
        }

        // X labels
        $colores = ['#e74c3c','#3498db','#2ecc71','#f39c12','#9b59b6','#1abc9c','#e67e22','#34495e','#e91e63','#00bcd4','#8bc34a','#ff5722'];

        if (count($semanas) > 1) {
            $segW = $chartW / (count($semanas) - 1);
            foreach ($semanas as $i => $s) {
                $xx = $chartX + ($i * $segW);
                $fechaTxt = formatDate($s['fecha_inicio'] ?? null);
                $svg .= '<text x="' . $xx . '" y="' . ($chartY + $chartH + 16) . '" text-anchor="middle" font-size="9" fill="#333">Sem ' . $s['numero_semana'] . '</text>';
                $svg .= '<text x="' . $xx . '" y="' . ($chartY + $chartH + 27) . '" text-anchor="middle" font-size="8" fill="#666">' . ($fechaTxt !== '' ? $fechaTxt : '') . '</text>';
            }
        } else {
            foreach ($semanas as $s) {
                $fechaTxt = formatDate($s['fecha_inicio'] ?? null);
                $svg .= '<text x="' . ($chartX + $chartW / 2) . '" y="' . ($chartY + $chartH + 16) . '" text-anchor="middle" font-size="9" fill="#333">Sem ' . $s['numero_semana'] . '</text>';
                $svg .= '<text x="' . ($chartX + $chartW / 2) . '" y="' . ($chartY + $chartH + 27) . '" text-anchor="middle" font-size="8" fill="#666">' . ($fechaTxt !== '' ? $fechaTxt : '') . '</text>';
            }
        }

        // Lines
        foreach ($estudiantes as $ei => $est) {
            $color = $colores[$ei % count($colores)];
            $points = [];
            foreach ($semanas as $si => $s) {
                $p = $promediosPorSemana[$est['id']][$s['id']] ?? null;
                if ($p === null) continue;
                $xx = count($semanas) > 1 ? $chartX + ($si * $segW) : $chartX + $chartW / 2;
                $yy = $chartY + $chartH - ($p / $maxVal * $chartH);
                $points[] = "$xx,$yy";
            }
            if (count($points) > 1) {
                $svg .= '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="' . $color . '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>';
            }
            foreach ($points as $pt) {
                list($px, $py) = explode(',', $pt);
                $svg .= '<circle cx="' . $px . '" cy="' . $py . '" r="4" fill="' . $color . '" stroke="#fff" stroke-width="1.5"/>';
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    private function generateExcelReport(array $curso, array $estudiantes, array $semanas, array $prom, array $finales): void
    {
        $aprobados = count(array_filter($finales, fn($p) => $p !== null && $p >= 10));
        $reprobados = count(array_filter($finales, fn($p) => $p !== null && $p < 10));
        $validos = array_filter($finales, fn($p) => $p !== null);
        $promGen = $validos ? round(array_sum($validos) / count($validos), 2) : 0;

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="Resumen_' . str_replace(' ', '_', $curso['nombre']) . '_' . date('Ymd') . '.xls"');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="utf-8">';
        echo '<style>td,th{border:1px solid #000;padding:4px 8px;text-align:center;}th{font-weight:bold;background:#ccc;}</style>';
        echo '</head><body>';

        echo '<h3>RESUMEN: ' . htmlspecialchars($curso['nombre']) . ' (' . htmlspecialchars($curso['codigo']) . ')</h3>';
        echo '<p>Fecha: ' . date('d/m/Y') . '</p>';
        echo '<p>Aprobados: ' . $aprobados . ' | Reprobados: ' . $reprobados . ' | Promedio General: ' . number_format($promGen, 2) . '</p>';

        echo '<table><thead><tr><th>#</th><th>Estudiante</th>';
        foreach ($semanas as $s) { echo '<th>Sem ' . $s['numero_semana'] . '</th>'; }
        echo '<th>Prom. Final</th><th>Estado</th></tr></thead><tbody>';

        $idx = 1;
        foreach ($estudiantes as $est) {
            echo '<tr><td>' . $idx++ . '</td><td style="text-align:left;font-weight:bold;">' . htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) . '</td>';
            foreach ($semanas as $s) {
                $p = $prom[$est['id']][$s['id']] ?? null;
                echo '<td>' . ($p !== null ? number_format($p, 2) : '--') . '</td>';
            }
            $f = $finales[$est['id']] ?? null;
            $estado = $f !== null ? ($f >= 10 ? 'Aprobado' : 'Reprobado') : '--';
            echo '<td style="font-weight:bold;">' . ($f !== null ? number_format($f, 2) : '--') . '</td>';
            echo '<td>' . $estado . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<br><h3>TENDENCIA POR ESTUDIANTE</h3>';
        echo '<table><thead><tr><th>Estudiante</th>';
        foreach ($semanas as $s) { echo '<th>Sem ' . $s['numero_semana'] . '</th>'; }
        echo '<th>Prom. Final</th><th>Tendencia</th></tr></thead><tbody>';
        foreach ($estudiantes as $est) {
            echo '<tr><td style="text-align:left;font-weight:bold;">' . htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) . '</td>';
            $vals = [];
            foreach ($semanas as $s) {
                $p = $prom[$est['id']][$s['id']] ?? null;
                echo '<td>' . ($p !== null ? number_format($p, 2) : '--') . '</td>';
                if ($p !== null) $vals[] = $p;
            }
            $f = $finales[$est['id']] ?? null;
            echo '<td style="font-weight:bold;">' . ($f !== null ? number_format($f, 2) : '--') . '</td>';
            $trend = '--';
            if (count($vals) >= 2) {
                $diff = end($vals) - $vals[0];
                $trend = $diff > 0 ? "Mejora (+$diff)" : ($diff < 0 ? "Baja ($diff)" : 'Estable');
            }
            echo '<td>' . $trend . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '</body></html>';
        exit;
    }

    // ==================== MIS ESTUDIANTES (ASIGNACIÃ“N) ====================

    public function misCursos(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();

        try {
            $cursos = $this->cursos->getByDocenteConConteos($docenteId);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los cursos.');
            $cursos = [];
        }

        require __DIR__ . '/../Views/docente/mis_cursos.php';
    }

    public function subirSillabus(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        $teacherId = $this->docenteId();
        $curso_id = (int) ($_POST['curso_id'] ?? 0);

        try {
            $curso = $this->cursos->getByIdForDocenteSimple($curso_id, $teacherId);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar el curso.');
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        if (!$curso) {
            setFlash('error', 'Curso no encontrado o no asignado a usted.');
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        if (empty($_FILES['sillabus']) || ($_FILES['sillabus']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            setFlash('error', 'Seleccione un archivo PDF válido.');
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        $file = $_FILES['sillabus'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            setFlash('error', 'El sílabo debe ser un archivo PDF (.pdf).');
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mime !== 'application/pdf') {
            setFlash('error', 'El archivo seleccionado no es un PDF válido.');
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            setFlash('error', 'El archivo no puede superar los 5 MB.');
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        $dir = ROOT_PATH . 'public/uploads/syllabus/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = 'sillabus_curso_' . $curso_id . '.pdf';
        $destination = $dir . $filename;
        $relativePath = 'uploads/syllabus/' . $filename;

        try {
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $this->cursos->updateSillabus($curso_id, $relativePath);
                setFlash('success', 'Sílabo subido correctamente.');
            } else {
                setFlash('error', 'No se pudo guardar el archivo.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al guardar el sílabo.');
        }

        redirect(BASE_URL . 'docente/misCursos');
    }

    public function verSillabus(): void
    {
        if (!isLoggedIn()) {
            redirect(BASE_URL . 'auth/iniciarSesion');
        }

        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            $curso = $this->cursos->getById($curso_id);
            if (!$curso || empty($curso['sillabus_pdf'])) {
                setFlash('error', 'El curso no tiene sílabo disponible.');
                redirect(BASE_URL . 'auth/iniciarSesion');
                return;
            }

            $rol = $_SESSION['user_rol'] ?? '';
            $permitido = false;
            if ($rol === 'docente') {
                $permitido = (int) $curso['docente_id'] === (int) $_SESSION['user_id'];
            } elseif ($rol === 'estudiante') {
                $permitido = $this->asignaciones->exists((int) $_SESSION['user_id'], $curso_id);
            } elseif ($rol === 'admin') {
                $permitido = true;
            }

            if (!$permitido) {
                setFlash('error', 'No tiene permiso para ver este sílabo.');
                redirect(BASE_URL . 'auth/iniciarSesion');
                return;
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar el sílabo.');
            redirect(BASE_URL . 'auth/iniciarSesion');
            return;
        }

        $filePath = ROOT_PATH . 'public/' . $curso['sillabus_pdf'];
        if (!is_file($filePath)) {
            setFlash('error', 'El archivo del sílabo no existe.');
            redirect(BASE_URL . 'auth/iniciarSesion');
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Sillabo_' . $curso['codigo'] . '.pdf"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function eliminarSillabus(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $teacherId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            $curso = $this->cursos->getById($curso_id);
            if (!$curso || (int) $curso['docente_id'] !== $teacherId) {
                setFlash('error', 'Curso no encontrado o no asignado a usted.');
                redirect(BASE_URL . 'docente/misCursos');
                return;
            }

            if (!empty($curso['sillabus_pdf'])) {
                $filePath = ROOT_PATH . 'public/' . $curso['sillabus_pdf'];
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                $this->cursos->updateSillabus($curso_id, null);
                setFlash('success', 'Sílabo eliminado.');
            } else {
                setFlash('info', 'El curso no tenía sílabo.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al eliminar el sílabo.');
        }

        redirect(BASE_URL . 'docente/misCursos');
    }

    public function estudiantesCurso(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $docenteId);

            if (!$curso) {
                setFlash('error', 'Curso no encontrado o no asignado a usted.');
                redirect(BASE_URL . 'docente/misCursos');
                return;
            }

            $asignados = $this->asignaciones->getEstudiantesByCursoConCarrera($curso_id);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los datos.');
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        require __DIR__ . '/../Views/docente/estudiantes_curso.php';
    }

    // ==================== ASISTENCIA ====================

    private function diaNombreDeFecha(string $fecha): string
    {
        $dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
        $n = (int) date('N', strtotime($fecha)); // 1 = Lunes ... 7 = Domingo
        return $dias[$n - 1];
    }

    public function asistencias(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        try {
            $cursos = $this->asistencias->getResumenPorDocente($this->docenteId());
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los cursos.');
            $cursos = [];
        }

        require __DIR__ . '/../Views/docente/asistencias.php';
    }

    public function exportarAsistenciaCursoDetalle(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = (int) ($_GET['curso_id'] ?? 0);

        if ($curso_id <= 0) { setFlash('error', 'Curso inválido.'); redirect(BASE_URL . 'docente/asistencias'); return; }

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $docenteId);
            if (!$curso) {
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'docente/asistencias');
                return;
            }
            $detalle = $this->asistencias->getDetallePorCurso($curso_id);
        } catch (PDOException $e) {
            setFlash('error', 'Error al exportar la asistencia.');
            redirect(BASE_URL . 'docente/reporteAsistencia?curso_id=' . $curso_id);
            return;
        }

        if (empty($detalle)) {
            setFlash('info', 'No hay asistencia registrada para exportar.');
            redirect(BASE_URL . 'docente/asistencias');
            return;
        }

        $estadoLabel = [
            'P' => 'P',
            'T' => 'T',
            'A' => 'A',
            'J' => 'J',
        ];
        $estadoClass = [
            'P' => 'pres',
            'T' => 'tard',
            'A' => 'aus',
            'J' => 'just',
        ];

        $fechas = [];
        $estudiantes = [];
        foreach ($detalle as $d) {
            $fecha = $d['fecha'];
            if (!in_array($fecha, $fechas, true)) $fechas[] = $fecha;
            $sid = (int) $d['estudiante_id'];
            if (!isset($estudiantes[$sid])) {
                $estudiantes[$sid] = [
                    'apellidos' => $d['apellidos'],
                    'nombres'   => $d['nombres'],
                    'dni'       => $d['dni'] ?? '',
                    'estados'   => [],
                ];
            }
            $estudiantes[$sid]['estados'][$fecha] = $d['estado'];
        }

        // Generar .xlsx real con PhpSpreadsheet (evita la carpeta _files del .xls HTML)
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Asistencia');
        $headers = array_merge(['#', 'Estudiante', 'DNI'], array_map(fn($f) => date('d/m', strtotime($f)), $fechas), ['Presente', 'Tardanza', 'Ausente', 'Justificado', 'Total', '% Asistencia']);
        $colCount = count($headers);
        // Encabezado
        $row = 1;
        foreach ($headers as $c => $h) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1) . $row;
            $sheet->setCellValue($cell, $h);
        }
        $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount) . '1';
        $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('4E37E6');
        $sheet->getStyle($headerRange)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'))->setBold(true)->setSize(10);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(18);
        // Datos
        $idx = 1; $row = 2;
        foreach ($estudiantes as $sid => $est) {
            $total = 0; $pres = 0; $tard = 0; $aus = 0; $just = 0;
            $col = 1;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, $idx++);
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, $est['apellidos'] . ', ' . $est['nombres']);
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row, $est['dni']);
            foreach ($fechas as $f) {
                $estado = $est['estados'][$f] ?? '';
                $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
                if ($estado === '') {
                    $sheet->setCellValue($cell, '-');
                    $sheet->getStyle($cell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('999999'));
                } else {
                    $sheet->setCellValue($cell, $estado);
                    $color = $estado === 'P' ? '1E7E34' : ($estado === 'T' ? 'B8860B' : ($estado === 'A' ? 'C0392B' : '1F618D'));
                    $sheet->getStyle($cell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color))->setBold(true);
                    $total++;
                    if ($estado === 'P') $pres++;
                    if ($estado === 'T') $tard++;
                    if ($estado === 'A') $aus++;
                    if ($estado === 'J') $just++;
                }
                $sheet->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $col++;
            }
            $asistio = $pres + $tard + $just;
            $pct = $total > 0 ? round(($asistio / $total) * 100, 1) : 0;
            foreach ([$pres, $tard, $aus, $just, $total] as $v) { $cAddr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $row; $sheet->setCellValue($cAddr, $v); $sheet->getStyle($cAddr)->getFont()->setBold(true); $sheet->getStyle($cAddr)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); }
            $pctCell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
            $sheet->setCellValue($pctCell, $pct / 100);
            $sheet->getStyle($pctCell)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle($pctCell)->getFont()->setBold(true);
            $sheet->getStyle($pctCell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $col++;
            // Bordes y alineación estudiante
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            $row++;
        }
        // Bordes, autofiltro y anchos
        $lastRow = $row - 1;
        if ($lastRow >= 1) {
            $sheet->getStyle('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount) . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CCCCCC'));
            $sheet->setAutoFilter($headerRange);
            $sheet->freezePane('D2');
        }
        foreach (range(1, $colCount) as $c) { $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c))->setAutoSize(true); }
        $sheet->getColumnDimension('B')->setAutoSize(false)->setWidth(28);
        ob_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="asistencia_' . $curso['codigo'] . '_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function exportarAsistenciaCursoTxt(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;
        $docenteId = $this->docenteId();
        $curso_id = (int) ($_GET['curso_id'] ?? 0);
        if ($curso_id <= 0) { setFlash('error', 'Curso inválido.'); redirect(BASE_URL . 'docente/asistencias'); return; }
        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $docenteId);
            if (!$curso) { setFlash('error', 'Curso no encontrado.'); redirect(BASE_URL . 'docente/asistencias'); return; }
            $detalle = $this->asistencias->getDetallePorCurso($curso_id);
        } catch (PDOException $e) { setFlash('error', 'Error al exportar.'); redirect(BASE_URL . 'docente/reporteAsistencia?curso_id=' . $curso_id); return; }
        if (empty($detalle)) { setFlash('info', 'No hay asistencia registrada para exportar.'); redirect(BASE_URL . 'docente/asistencias'); return; }
        $fechas = [];
        $estudiantes = [];
        foreach ($detalle as $d) {
            $fecha = $d['fecha'];
            if (!in_array($fecha, $fechas, true)) $fechas[] = $fecha;
            $sid = (int) $d['estudiante_id'];
            if (!isset($estudiantes[$sid])) { $estudiantes[$sid] = ['apellidos' => $d['apellidos'], 'nombres' => $d['nombres'], 'dni' => $d['dni'] ?? '', 'estados' => []]; }
            $estudiantes[$sid]['estados'][$fecha] = $d['estado'];
        }
        ob_clean();
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="asistencia_' . $curso['codigo'] . '_' . date('Ymd') . '.txt"');
        header('Cache-Control: max-age=0');
        // BOM para que bloc de notas muestre tildes
        echo "\xEF\xBB\xBF";
        echo "Asistencia - " . $curso['codigo'] . " - " . $curso['nombre'] . " | Generado: " . date('d/m/Y H:i') . "\r\n";
        // Anchos fijos para alineación monoespaciada (monoespaciada: Consolas/Courier)
        $wNum = 3; $wEst = 50; $wDni = 10; $wFecha = 6; $wCnt = 7; $wTot = 7; $wPct = 7;
        $totalWidth = $wNum + 3 + $wEst + 3 + $wDni + 3 + count($fechas) * ($wFecha + 3) + 5 * ($wCnt + 3) + $wTot + 3 + $wPct;
        echo str_repeat('=', min($totalWidth, 200)) . "\r\n";
        $pad = fn($v, $w, $align = 'left') => $align === 'center' ? str_pad($v, $w, ' ', STR_PAD_BOTH) : ($align === 'right' ? str_pad($v, $w, ' ', STR_PAD_LEFT) : str_pad(mb_strimwidth($v, 0, $w, ''), $w, ' '));
        // Ordenar estudiantes por apellidos para que el TXT esté ordenado
        uasort($estudiantes, fn($a, $b) => strcmp($a['apellidos'] . $a['nombres'], $b['apellidos'] . $b['nombres']));
        $headerVals = array_merge(['#', 'Estudiante', 'DNI'], array_map(fn($f) => date('d/m', strtotime($f)), $fechas), ['Pres','Tard','Aus','Just','Total','%']);
        $headerWidths = array_merge([$wNum, $wEst, $wDni], array_fill(0, count($fechas), $wFecha), [$wCnt, $wCnt, $wCnt, $wCnt, $wTot, $wPct]);
        $line = '';
        foreach ($headerVals as $i => $h) {
            $isFecha = $i >= 3 && $i < 3 + count($fechas);
            $isCount = $i >= 3 + count($fechas);
            $align = $isFecha ? 'center' : ($isCount ? 'right' : ($i === 0 ? 'center' : 'left'));
            $line .= $pad($h, $headerWidths[$i], $align) . ($i < count($headerVals) - 1 ? ' | ' : '');
        }
        echo $line . "\r\n";
        echo str_repeat('-', mb_strlen($line)) . "\r\n";
        $idx = 1;
        foreach ($estudiantes as $est) {
            $pres = $tard = $aus = $just = $total = 0;
            $vals = [];
            $vals[] = $pad((string)$idx++, $wNum, 'center');
            $vals[] = $pad($est['apellidos'] . ', ' . $est['nombres'], $wEst, 'left');
            $vals[] = $pad($est['dni'], $wDni, 'center');
            foreach ($fechas as $f) {
                $e = $est['estados'][$f] ?? '-';
                $vals[] = $pad($e, $wFecha, 'center');
                if ($e !== '-' && $e !== '') { $total++; if ($e === 'P') $pres++; if ($e === 'T') $tard++; if ($e === 'A') $aus++; if ($e === 'J') $just++; }
            }
            $asistio = $pres + $tard + $just;
            $pct = $total > 0 ? round(($asistio / $total) * 100, 1) . '%' : '0%';
            foreach ([$pres, $tard, $aus, $just, $total] as $v) { $vals[] = $pad((string)$v, $v === $total ? $wTot : $wCnt, 'right'); }
            $vals[] = $pad($pct, $wPct, 'right');
            echo implode(' | ', $vals) . "\r\n";
        }
        echo "\r\nP=Presente | T=Tardanza | A=Ausente | J=Justificado | -=Sin clase\r\n";
        exit;
    }

    public function tomarAsistencia(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $fecha = trim((string) ($_GET['fecha'] ?? date('Y-m-d')));

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $docenteId);
            if (!$curso) {
                setFlash('error', 'Curso no encontrado o no asignado a usted.');
                redirect(BASE_URL . 'docente/asistencias');
                return;
            }

            $fechaValida = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha);
            if ($fechaValida) {
                [$y, $m, $d] = array_map('intval', explode('-', $fecha));
                $fechaValida = checkdate($m, $d, $y);
            }
            if (!$fechaValida) {
                setFlash('error', 'Fecha inválida.');
                redirect(BASE_URL . 'docente/asistencias');
                return;
            }

            $horarios = $this->horarios->getByCursoSimple($curso_id);
            $diasHorario = array_column($horarios, 'dia');
            $diaNombre = $this->diaNombreDeFecha($fecha);
            $coincideConHorario = in_array($diaNombre, $diasHorario, true);

            $bloquesDia = [];
            foreach ($horarios as $h) {
                if (($h['dia'] ?? '') === $diaNombre) {
                    $bloquesDia[] = $h;
                }
            }
            usort($bloquesDia, fn($a, $b) => strcmp($a['hora_inicio'], $b['hora_inicio']));
            $idsBloques = array_column($bloquesDia, 'id');
            $horario_id = (int) ($_GET['horario_id'] ?? 0);
            if (!in_array($horario_id, $idsBloques, true)) {
                $horario_id = !empty($bloquesDia)
                    ? (int) $bloquesDia[0]['id']
                    : (int) ($horarios[0]['id'] ?? 0);
            }
            $bloqueActivo = null;
            foreach ($bloquesDia as $b) {
                if ((int) $b['id'] === $horario_id) {
                    $bloqueActivo = $b;
                    break;
                }
            }

            $estudiantes = $this->asignaciones->getEstudiantesByCursoSimple($curso_id);
            $existentes = $this->asistencias->getByCursoYFecha($curso_id, $fecha, $horario_id);
            $rostrosCurso = $this->rostros->getDescriptorsByCurso($curso_id);
            $estudiantesConRostro = array_map(
                fn($r) => (int) $r['estudiante_id'],
                $rostrosCurso
            );
            $estudiantesConRostro = array_flip($estudiantesConRostro);
            $tolerancia = $this->cursos->getTolerancia($curso_id);
            $horaInicioConfigurada = $tolerancia['hora_inicio'] ?? null;
            $horaInicioHorario = $bloqueActivo['hora_inicio'] ?? null;
            $horaInicioEfectiva = !empty($horaInicioHorario) ? $horaInicioHorario : $horaInicioConfigurada;
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los datos.');
            redirect(BASE_URL . 'docente/asistencias');
            return;
        }

        require __DIR__ . '/../Views/docente/tomar_asistencia.php';
    }

    public function guardarAsistencia(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . 'docente/asistencias');
            return;
        }

        $docenteId = $this->docenteId();
        $curso_id = (int) ($_POST['curso_id'] ?? 0);
        $fecha = trim((string) ($_POST['fecha'] ?? ''));
        $estados = $_POST['estados'] ?? [];

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                setFlash('error', 'Curso no encontrado o no asignado a usted.');
                redirect(BASE_URL . 'docente/asistencias');
                return;
            }

            $fechaValida = (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha);
            if ($fechaValida) {
                [$y, $m, $d] = array_map('intval', explode('-', $fecha));
                $fechaValida = checkdate($m, $d, $y);
            }
            if (!$fechaValida) {
                setFlash('error', 'Fecha inválida.');
                redirect(BASE_URL . 'docente/asistencias');
                return;
            }

            $estadosValidos = Asistencia::ESTADOS;
            $allowedIds = $this->asignaciones->getEstudianteIdsByCurso($curso_id);
            $observaciones = $_POST['observaciones'] ?? [];
            $tolerancia = $this->cursos->getTolerancia($curso_id);

            $horario_id = (int) ($_POST['horario_id'] ?? 0);
            $horarioValido = false;
            $horaInicioLimite = null;
            foreach ($this->horarios->getByCursoSimple($curso_id) as $h) {
                if ((int) $h['id'] === $horario_id) {
                    $horarioValido = true;
                    $horaInicioLimite = $h['hora_inicio'] ?? null;
                    break;
                }
            }
            if (!$horarioValido) {
                $horario_id = null;
                $horaInicioLimite = $tolerancia['hora_inicio'] ?? null;
            }

            $diasHorario = array_column($this->horarios->getByCursoSimple($curso_id), 'dia');
            $coincideHorario = in_array($this->diaNombreDeFecha($fecha), $diasHorario, true);
            $esRecuperacion = isset($_POST['recuperacion']) && $_POST['recuperacion'] === '1';
            if (!$coincideHorario && !$esRecuperacion) {
                setFlash('error', 'Este día no hay clase programada para el curso. Para registrar asistencia excepcionalmente, marque la opción "Clase de recuperación".');
                redirect(BASE_URL . 'docente/tomarAsistencia?curso_id=' . $curso_id . '&fecha=' . urlencode($fecha) . '&horario_id=' . (int) ($horario_id ?? 0));
                return;
            }

            $limpio = [];
            foreach ($estados as $estudiante_id => $estado) {
                $sid = (int) $estudiante_id;
                if (!in_array($sid, $allowedIds, true)) continue;
                if (!in_array($estado, $estadosValidos, true)) continue;
                $limpio[$sid] = $estado;
            }

            if (empty($limpio)) {
                setFlash('warning', 'Ningún estudiante fue marcado. Marque la asistencia de al menos un estudiante y vuelva a guardar.');
                redirect(BASE_URL . 'docente/tomarAsistencia?curso_id=' . $curso_id . '&fecha=' . $fecha . '&horario_id=' . $horario_id);
                return;
            }

            $this->db->beginTransaction();
            $count = $this->asistencias->saveAll($curso_id, $fecha, $limpio, $docenteId, $observaciones, $horario_id);
            $this->db->commit();

            setFlash('success', 'Asistencia guardada correctamente (' . $count . ' estudiantes).');
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            setFlash('error', 'Error al guardar la asistencia.');
        }

        redirect(BASE_URL . 'docente/tomarAsistencia?curso_id=' . $curso_id . '&fecha=' . urlencode($fecha) . '&horario_id=' . (int) ($horario_id ?? 0));
    }

    public function guardarTolerancia(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $curso_id = (int) ($_POST['curso_id'] ?? 0);
        $hora = trim((string) ($_POST['hora_inicio'] ?? ''));
        $minutos = (int) ($_POST['tolerancia_minutos'] ?? 0);

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                jsonResponse(['success' => false, 'message' => 'Curso no encontrado o no asignado a usted.'], 404);
                return;
            }

            $horaValida = $hora === '' || (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $hora);
            if (!$horaValida) {
                jsonResponse(['success' => false, 'message' => 'Hora de inicio no válida.']);
                return;
            }
            if ($minutos < 0 || $minutos > 600) {
                jsonResponse(['success' => false, 'message' => 'Tolerancia no válida (0 a 600 minutos).']);
                return;
            }

            $ok = $this->cursos->updateTolerancia($curso_id, $hora !== '' ? $hora : null, $minutos);
            if (!$ok) {
                jsonResponse(['success' => false, 'message' => 'No se pudo guardar la tolerancia.']);
                return;
            }

            jsonResponse(['success' => true, 'message' => 'Tolerancia guardada correctamente.']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al guardar la tolerancia.'], 500);
        }
    }

    public function formularioRostro(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $estudiante_id = isset($_GET['estudiante_id']) ? (int) $_GET['estudiante_id'] : 0;

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                jsonResponse(['success' => false, 'message' => 'Curso no encontrado o no asignado a usted.'], 404);
                return;
            }

            $idsCurso = $this->asignaciones->getEstudianteIdsByCurso($curso_id);
            if (!in_array($estudiante_id, $idsCurso, true)) {
                jsonResponse(['success' => false, 'message' => 'Estudiante no pertenece a este curso.'], 404);
                return;
            }

            $stmt = $this->db->prepare('SELECT id, apellidos, nombres FROM usuarios WHERE id = :id AND rol = "estudiante" LIMIT 1');
            $stmt->execute([':id' => $estudiante_id]);
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$estudiante) {
                jsonResponse(['success' => false, 'message' => 'Estudiante no encontrado.'], 404);
                return;
            }

            $registro = $this->rostros->getByEstudiante($estudiante_id);

            $isAjax = true;
            $guardarRostroUrl = BASE_URL . 'docente/guardarRostro';
            $eliminarRostroUrl = BASE_URL . 'docente/eliminarRostro';
            $nombreEstudiante = $estudiante['apellidos'] . ', ' . $estudiante['nombres'];
            $estudianteId = $estudiante_id;
            $cursoId = $curso_id;
            $yaRegistrado = $registro !== null;
            $fotoPath = $registro['foto_path'] ?? null;
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al cargar los datos.'], 500);
            return;
        }

        require __DIR__ . '/../Views/docente/registro_caras.php';
    }

    public function guardarRostro(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $estudiante_id = (int) ($_POST['estudiante_id'] ?? 0);
        $descriptor = trim((string) ($_POST['descriptor'] ?? ''));
        $foto = trim((string) ($_POST['foto'] ?? ''));
        $curso_id = (int) ($_POST['curso_id'] ?? 0);

        $decoded = json_decode($descriptor, true);
        if (!is_array($decoded) || count($decoded) !== 128) {
            jsonResponse(['success' => false, 'message' => 'Descriptor facial no válido.']);
            return;
        }
        $descriptorLimpio = json_encode(array_map(fn($v) => (float) $v, $decoded), JSON_UNESCAPED_UNICODE);

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }
            $idsCurso = $this->asignaciones->getEstudianteIdsByCurso($curso_id);
            if (!in_array($estudiante_id, $idsCurso, true)) {
                jsonResponse(['success' => false, 'message' => 'Estudiante no pertenece a este curso.'], 404);
                return;
            }

            $fotoPath = saveFacePhoto($foto, $estudiante_id);
            if (!$fotoPath) {
                jsonResponse(['success' => false, 'message' => 'No se pudo guardar la foto.']);
                return;
            }

            $ok = $this->rostros->save($estudiante_id, $descriptorLimpio, $fotoPath, $docenteId);
            if (!$ok) {
                jsonResponse(['success' => false, 'message' => 'Error al guardar el rostro.'], 500);
                return;
            }

            jsonResponse(['success' => true, 'message' => 'Rostro registrado correctamente.', 'redirect' => BASE_URL . 'docente/tomarAsistencia?curso_id=' . $curso_id]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al guardar el rostro.'], 500);
        }
    }

    public function eliminarRostro(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $estudiante_id = (int) ($_POST['estudiante_id'] ?? 0);
        $curso_id = (int) ($_POST['curso_id'] ?? 0);

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }
            $idsCurso = $this->asignaciones->getEstudianteIdsByCurso($curso_id);
            if (!in_array($estudiante_id, $idsCurso, true)) {
                jsonResponse(['success' => false, 'message' => 'Estudiante no pertenece a este curso.'], 404);
                return;
            }

            $registro = $this->rostros->getByEstudiante($estudiante_id);
            if ($registro && !empty($registro['foto_path'])) {
                $file = ROOT_PATH . 'public/' . $registro['foto_path'];
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            $this->rostros->delete($estudiante_id);
            jsonResponse(['success' => true, 'message' => 'Rostro eliminado correctamente.']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al eliminar el rostro.'], 500);
        }
    }

    public function obtenerRostrosCurso(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                jsonResponse(['success' => false, 'message' => 'Curso no válido.'], 404);
                return;
            }

            $list = $this->rostros->getDescriptorsByCurso($curso_id);
            $salida = [];
            foreach ($list as $item) {
                $descriptor = json_decode($item['descriptor'], true);
                if (!is_array($descriptor) || count($descriptor) !== 128) continue;
                $salida[] = [
                    'estudiante_id' => $item['estudiante_id'],
                    'apellidos'     => $item['apellidos'],
                    'nombres'       => $item['nombres'],
                    'descriptor'    => $descriptor,
                    'foto_path'     => $item['foto_path'],
                ];
            }
            jsonResponse(['success' => true, 'data' => $salida]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al cargar los rostros.'], 500);
        }
    }

    public function reporteAsistencia(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $docenteId);
            if (!$curso) {
                setFlash('error', 'Curso no encontrado o no asignado a usted.');
                redirect(BASE_URL . 'docente/asistencias');
                return;
            }
            $resumen = $this->asistencias->getResumenPorCurso($curso_id);
            $justificaciones = $this->asistencias->getJustificacionesByCurso($curso_id);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar el reporte.');
            redirect(BASE_URL . 'docente/asistencias');
            return;
        }

        require __DIR__ . '/../Views/docente/reporte_asistencia.php';
    }

    public function exportarAsistenciaCurso(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = (int) ($_GET['curso_id'] ?? 0);

        if ($curso_id <= 0) { setFlash('error', 'Curso inválido.'); redirect(BASE_URL . 'docente/asistencias'); return; }

        try {
            $curso = $this->cursos->getByIdForDocente($curso_id, $docenteId);
            if (!$curso) {
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'docente/asistencias');
                return;
            }
            $resumen = $this->asistencias->getResumenPorCurso($curso_id);
            $justificaciones = $this->asistencias->getJustificacionesByCurso($curso_id);
        } catch (PDOException $e) {
            setFlash('error', 'Error al exportar.');
            redirect(BASE_URL . 'docente/reporteAsistencia?curso_id=' . $curso_id);
            return;
        }

        ob_clean();
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="asistencia_' . $curso['codigo'] . '_' . date('Ymd') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8">';
        echo '<style>td,th{border:1px solid #ccc;padding:5px 8px;font-family:Arial;font-size:11px;text-align:center}th{background:#4E37E6;color:#fff;font-weight:700}td.est{text-align:left;font-weight:600}.pct{font-weight:700}</style>';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Asistencia</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '</head><body>';
        echo '<table>';
        echo '<tr><th>#</th><th>Estudiante</th><th>DNI</th><th>Presente</th><th>Tardanza</th><th>Ausente</th><th>Justificado</th><th>Total</th><th>% Asistencia</th><th>Motivo de justificaci&oacute;n</th></tr>';

        $justMap = [];
        foreach ($justificaciones as $j) {
            $justMap[(int) $j['estudiante_id']][] = [
                'fecha' => $j['fecha'],
                'obs'   => $j['observacion'] ?? '',
            ];
        }

        $idx = 1;
        foreach ($resumen as $r) {
            $total = (int) $r['total'];
            $asistio = (int) $r['presentes'] + (int) $r['tardanzas'] + (int) $r['justificados'];
            $pct = $total > 0 ? round(($asistio / $total) * 100, 1) : 0;

            $justTexto = '';
            foreach ($justMap[(int) $r['estudiante_id']] ?? [] as $j) {
                $justTexto .= date('d/m/Y', strtotime($j['fecha'])) . ': ' . $j['obs'] . ' | ';
            }
            $justTexto = rtrim($justTexto, ' | ');

            echo '<tr>';
            echo '<td>' . $idx++ . '</td>';
            echo '<td class="est">' . htmlspecialchars($r['apellidos'] . ', ' . $r['nombres']) . '</td>';
            echo '<td>' . htmlspecialchars($r['dni'] ?? '') . '</td>';
            echo '<td>' . (int) $r['presentes'] . '</td>';
            echo '<td>' . (int) $r['tardanzas'] . '</td>';
            echo '<td>' . (int) $r['ausencias'] . '</td>';
            echo '<td>' . (int) $r['justificados'] . '</td>';
            echo '<td>' . $total . '</td>';
            echo '<td class="pct">' . number_format($pct, 1) . '%</td>';
            echo '<td class="est">' . htmlspecialchars($justTexto) . '</td>';
            echo '</tr>';
        }
        echo '</table></body></html>';
        exit;
    }

    public function formularioAgregarEstudiantes(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;

        try {
            $curso = $this->cursos->getByIdForDocenteSimple($curso_id, $docenteId);

            if (!$curso) {
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'docente/misCursos');
                return;
            }

            $disponibles = $this->asignaciones->getDisponiblesParaCurso($curso_id);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar los estudiantes.');
            redirect(BASE_URL . 'docente/estudiantesCurso?curso_id=' . $curso_id);
            return;
        }

        require __DIR__ . '/../Views/docente/formulario_agregar_estudiantes.php';
    }

    public function guardarEstudiantes(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . 'docente/misCursos');
            return;
        }

        $docenteId = $this->docenteId();
        $curso_id = (int)($_POST['curso_id'] ?? 0);
        $estudiantes = $_POST['estudiantes'] ?? [];

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                setFlash('error', 'Curso no encontrado o no asignado a usted.');
                redirect(BASE_URL . 'docente/misCursos');
                return;
            }

            $count = 0;
            foreach ($estudiantes as $estId) {
                if ($this->asignaciones->create((int)$estId, $curso_id)) {
                    $count++;
                }
            }

            setFlash('success', "{$count} estudiante(s) asignado(s) al curso correctamente.");
        } catch (PDOException $e) {
            setFlash('error', 'Error al asignar estudiantes.');
        }

        redirect(BASE_URL . 'docente/estudiantesCurso?curso_id=' . $curso_id);
    }

    public function eliminarEstudianteCurso(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $curso_id = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
        $estudiante_id = isset($_GET['estudiante_id']) ? (int) $_GET['estudiante_id'] : 0;

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                setFlash('error', 'Curso no encontrado.');
                redirect(BASE_URL . 'docente/misCursos');
                return;
            }

            $removed = $this->asignaciones->delete($estudiante_id, $curso_id);

            if ($removed) {
                setFlash('success', 'Estudiante removido del curso.');
            } else {
                setFlash('error', 'Estudiante no encontrado en este curso.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al remover estudiante.');
        }

        redirect(BASE_URL . 'docente/estudiantesCurso?curso_id=' . $curso_id);
    }

    // ==================== REPORTE DETALLE ESTUDIANTE ====================

    public function obtenerEstudiantesCurso(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $cursoId   = (int) ($_GET['curso_id'] ?? 0);

        if ($cursoId <= 0) { echo '<div class="text-muted text-center py-3">Seleccione un curso</div>'; return; }

        try {
            $estudiantes = $this->asignaciones->getEstudiantesByCursoConCarrera($cursoId);
        } catch (PDOException $e) {
            $estudiantes = [];
        }

        if (empty($estudiantes)):
        ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-user-slash" style="font-size:2rem;"></i><br>
                <p class="mt-2 mb-0">No hay estudiantes asignados a este curso.</p>
            </div>
        <?php else: ?>
            <div class="list-group" id="studentList">
                <?php foreach ($estudiantes as $e): ?>
                    <a href="<?= BASE_URL ?>docente/reporteDetalleEstudiante?curso_id=<?= $cursoId ?>&estudiante_id=<?= $e['id'] ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3"
                       style="border-left:3px solid #007bff;">
                        <div>
                            <div class="font-weight-bold" style="font-size:.9rem;"><?= htmlspecialchars($e['apellidos'] . ', ' . $e['nombres']) ?></div>
                            <small class="text-muted">DNI: <?= htmlspecialchars($e['dni']) ?></small>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif;
    }

    public function reporteDetalleEstudiante(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $cursoId   = (int) ($_GET['curso_id'] ?? 0);
        $estId     = (int) ($_GET['estudiante_id'] ?? 0);

        try {
            $cursos = $this->cursos->getAllActivosByDocente($docenteId);
        } catch (PDOException $e) {
            $cursos = [];
        }

        $estudiantes = [];
        $calificaciones = [];
        $semanas = [];
        $estudiante = null;
        $promedioFinal = 0;

        if ($cursoId > 0) {
            try {
                $estudiantes = $this->asignaciones->getEstudiantesByCursoConCarrera($cursoId);
            } catch (PDOException $e) {
                $estudiantes = [];
            }
        }

        if ($cursoId > 0 && $estId > 0) {
            try {
                $estudiante = (new User($this->db))->getByIdSimple($estId);

                $semanas = $this->semanas->getByCursoParaReportes($cursoId, $docenteId);

                $totalPromedio = 0;
                $countSem = 0;

                foreach ($semanas as &$sem) {
                    $criterios = $this->criterios->getAllBySemanaSimple($sem['id']);

                    $notas = [];
                    $sumaPond = 0;
                    $sumaPeso = 0;

                    foreach ($criterios as &$cr) {
                        $nota = $this->calificaciones->getByCriterioAndEstudiante($cr['id'], $estId);
                        $notas[] = ['criterio_nombre' => $cr['nombre'], 'porcentaje' => $cr['porcentaje'], 'nota' => $nota];
                        if ($nota !== null) {
                            $sumaPond += $nota * ($cr['porcentaje'] / 100);
                            $sumaPeso += $cr['porcentaje'];
                        }
                    }
                    unset($cr);

                    $promedioSem = $sumaPeso > 0 ? round($sumaPond * (100 / $sumaPeso), 2) : null;
                    $sem['notas'] = $notas;
                    $sem['promedio'] = $promedioSem;

                    if ($promedioSem !== null) {
                        $totalPromedio += $promedioSem;
                        $countSem++;
                    }
                }
                unset($sem);

                $promedioFinal = $countSem > 0 ? round($totalPromedio / $countSem, 2) : 0;
            } catch (PDOException $e) {
                $semanas = [];
            }
        }

        require __DIR__ . '/../Views/docente/detalle_estudiante.php';
    }

    // ==================== HORARIO ====================

    public function horariosJson(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $rangeStart = $_GET['start'] ?? date('Y-m-d');
        $rangeEnd   = $_GET['end'] ?? date('Y-m-d', strtotime('+7 days'));

        try {
            $horarios = $this->horarios->getByDocente($docenteId);
        } catch (PDOException $e) {
            jsonResponse([]);
            return;
        }

        $dowMap = ['Lunes'=>1, 'Martes'=>2, 'Miercoles'=>3, 'Jueves'=>4, 'Viernes'=>5, 'Sabado'=>6, 'Domingo'=>7];
        $palette = ['#4E37E6','#E63777','#37E6A6','#0EA5E9','#CFE637','#8B5CF6','#F97316'];

        $startTs = strtotime(substr($rangeStart, 0, 10)) ?: strtotime('monday this week');
        $endTs   = strtotime(substr($rangeEnd, 0, 10))   ?: strtotime('next monday');
        $events  = [];

        foreach ($horarios as $i => $h) {
            $targetDow = $dowMap[$h['dia']];
            $color     = $palette[$i % count($palette)];
            $hStart    = substr($h['hora_inicio'], 0, 5);
            $hEnd      = substr($h['hora_fin'], 0, 5);

            for ($ts = $startTs; $ts < $endTs; $ts = strtotime('+1 day', $ts)) {
                if ((int)date('N', $ts) !== $targetDow) continue;
                $dateStr = date('Y-m-d', $ts);
                $events[] = [
                    'id'              => (int)$h['id'] . '-' . $dateStr,
                    'groupId'         => (int)$h['id'],
                    'title'           => $h['curso_codigo'] . ' - ' . $h['curso_nombre'],
                    'start'           => $dateStr . 'T' . $hStart,
                    'end'             => $dateStr . 'T' . $hEnd,
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'textColor'       => '#fff',
                    'extendedProps'   => [
                        'horario_id'      => (int)$h['id'],
                        'curso_id'        => (int)$h['curso_id'],
                        'curso_nombre'    => $h['curso_nombre'],
                        'curso_codigo'    => $h['curso_codigo'],
                        'ambiente_nombre' => $h['ambiente_nombre'],
                        'dia'             => $h['dia'],
                        'hora_inicio'     => $hStart,
                        'hora_fin'        => $hEnd,
                    ],
                ];
            }
        }

        jsonResponse($events);
    }

    public function horarios(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();

        try {
            $cursos = $this->cursos->getAllActivosByDocente($docenteId);

            $horarios = $this->horarios->getByDocente($docenteId);

            $ambientes = $this->ambientes->getAllActivos();
        } catch (PDOException $e) {
            $cursos = [];
            $horarios = [];
            $ambientes = [];
        }

        require __DIR__ . '/../Views/docente/horario.php';
    }

    public function exportarHorarios(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();

        $horarios = $this->horarios->getByDocente($docenteId);

        $dias = ['Lunes','Martes','Miercoles','Jueves','Viernes'];
        $horas = range(7, 14);

        ob_clean();

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="horario_' . date('Ymd') . '.xls"');
        header('Cache-Control: max-age=0');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta charset="UTF-8">';
        echo '<style>td,th{border:1px solid #ccc;padding:6px 10px;font-family:Arial;font-size:12px;text-align:center}th{background:#f0f0f0;font-weight:700}td.hora{background:#fafafa;font-weight:600}</style>';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Horario</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '</head><body>';
        echo '<table>';
        echo '<tr><th>Hora</th>';
        foreach ($dias as $d) echo '<th>' . $d . '</th>';
        echo '</tr>';

        foreach ($horas as $hr) {
            $hLabel = str_pad((string)$hr, 2, '0', STR_PAD_LEFT) . ':00';
            echo '<tr><td class="hora">' . $hLabel . '</td>';
            $slotStart = str_pad((string)$hr, 2, '0', STR_PAD_LEFT) . ':00:00';
            $slotEnd = str_pad((string)($hr + 1), 2, '0', STR_PAD_LEFT) . ':00:00';
            foreach ($dias as $d) {
                $blocks = array_filter($horarios, function($h) use ($d, $slotStart, $slotEnd) {
                    return $h['dia'] === $d && $h['hora_inicio'] < $slotEnd && $h['hora_fin'] > $slotStart;
                });
                $cell = '';
                foreach ($blocks as $b) {
                    $cell .= htmlspecialchars($b['curso_codigo'] . ' - ' . $b['curso_nombre']);
                    $cell .= ' (' . substr($b['hora_inicio'],0,5) . '-' . substr($b['hora_fin'],0,5) . ')';
                    if ($b['ambiente_nombre']) $cell .= ' [' . htmlspecialchars($b['ambiente_nombre']) . ']';
                    $cell .= "\n";
                }
                echo '<td>' . nl2br(trim($cell)) . '</td>';
            }
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    public function formularioHorario(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $id = (int) ($_GET['id'] ?? 0);

        $cursos = $this->cursos->getAllActivosByDocente($docenteId);

        $ambientes = $this->ambientes->getAllActivos();

        if ($id > 0) {
            $horario = $this->horarios->getByIdForDocente($id, $docenteId);
            if (!$horario) { http_response_code(404); echo json_encode(['error' => 'Horario no encontrado']); return; }
        } else {
            $horaInicio = $_GET['hora'] ?? '08:00';
            $horaFin    = $_GET['hora_fin'] ?? null;
            if (!$horaFin) {
                $parts = explode(':', $horaInicio);
                $horaFin = str_pad((string)min((int)$parts[0] + 1, 13), 2, '0', STR_PAD_LEFT) . ':' . ($parts[1] ?? '00');
            }
            $horario = [
                'id' => 0, 'curso_id' => 0, 'ambiente_id' => null,
                'dia' => $_GET['dia'] ?? 'Lunes',
                'hora_inicio' => $horaInicio . ':00',
                'hora_fin' => $horaFin . ':00',
            ];
        }

        require __DIR__ . '/../Views/docente/formulario_horario.php';
    }

    public function guardarHorario(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->scheduleAjaxResponse(false, 'Método no permitido.');
            return;
        }

        $docenteId  = $this->docenteId();
        $id         = (int) ($_POST['id'] ?? 0);
        $curso_id   = (int) ($_POST['curso_id'] ?? 0);
        $ambiente_id = (int) ($_POST['ambiente_id'] ?? 0);
        $dia        = sanitize($_POST['dia'] ?? '');
        $hora_inicio = sanitize($_POST['hora_inicio'] ?? '');
        $hora_fin    = sanitize($_POST['hora_fin'] ?? '');

        $diasValidos = ['Lunes','Martes','Miercoles','Jueves','Viernes'];

        if ($curso_id <= 0 || !in_array($dia, $diasValidos) || $hora_inicio === '' || $hora_fin === '') {
            $this->scheduleAjaxResponse(false, 'Complete todos los campos obligatorios.');
            return;
        }

        try {
            if (!$this->cursos->existsByIdForDocente($curso_id, $docenteId)) {
                $this->scheduleAjaxResponse(false, 'Curso no válido.');
                return;
            }

            if ($this->horarios->hasConflict($docenteId, $dia, $hora_inicio, $hora_fin, $id)) {
                $this->scheduleAjaxResponse(false, 'Ya tiene un horario en ese día y horario.');
                return;
            }

            $ambiente = $ambiente_id > 0 ? $ambiente_id : null;

            if ($id > 0) {
                $this->horarios->update($id, $docenteId, $curso_id, $ambiente, $dia, $hora_inicio, $hora_fin);
                $this->scheduleAjaxResponse(true, 'Horario actualizado correctamente.');
            } else {
                $this->horarios->create($curso_id, $docenteId, $ambiente, $dia, $hora_inicio, $hora_fin);
                $this->scheduleAjaxResponse(true, 'Horario registrado correctamente.');
            }
        } catch (PDOException $e) {
            $this->scheduleAjaxResponse(false, 'Error al guardar el horario.');
        }
    }

    public function eliminarHorario(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($id <= 0) {
            $this->scheduleAjaxResponse(false, 'ID inválido.');
            return;
        }

        try {
            $deleted = $this->horarios->delete($id, $docenteId);

            if ($deleted) {
                $this->scheduleAjaxResponse(true, 'Horario eliminado.');
            } else {
                $this->scheduleAjaxResponse(false, 'Horario no encontrado.');
            }
        } catch (PDOException $e) {
            $this->scheduleAjaxResponse(false, 'Error al eliminar el horario.');
        }
    }

    private function scheduleAjaxResponse(bool $ok, string $message): void
    {
        if (isAjaxRequest() || isset($_GET['ajax']) || isset($_POST['ajax'])) {
            jsonResponse(['ok' => $ok, 'message' => $message]);
        }
        setFlash($ok ? 'success' : 'error', $message);
        redirect(BASE_URL . 'docente/horarios');
    }

    // ==================== DISCUSIONES / DEBATES ====================

    public function discusiones(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();
        $semana_id = isset($_GET['semana_id']) ? (int) $_GET['semana_id'] : 0;

        try {
            if ($semana_id > 0) {
                $semana = $this->semanas->getBasicForDocente($semana_id, $docenteId);
                if (!$semana) {
                    setFlash('error', 'Semana no encontrada.');
                    redirect(BASE_URL . 'docente/semanas');
                    return;
                }
                $curso = $this->cursos->getByIdForDocenteSimple((int) $semana['curso_id'], $docenteId);
                $discusiones = $this->discusiones->getBySemana($semana_id);
                require __DIR__ . '/../Views/docente/discusiones.php';
            } else {
                $discusiones = $this->discusiones->getByDocente($docenteId);
                $semanasAbiertas = $this->semanas->getAbiertasConCurso($docenteId);
                require __DIR__ . '/../Views/docente/misDiscusiones.php';
            }
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar las discusiones.');
            redirect(BASE_URL . 'docente/semanas');
            return;
        }
    }

    public function crearDiscusion(): void
    {
        $this->requireDocente();

        $isAjax = $this->isAjax();
        $docenteId = $this->docenteId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405); return; }
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        $semana_id = (int) ($_POST['semana_id'] ?? 0);
        $titulo = sanitize($_POST['titulo'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $fechaInicio = !empty($_POST['fecha_inicio']) ? date('Y-m-d H:i:s', strtotime((string) $_POST['fecha_inicio'])) : null;
        $fechaFin = !empty($_POST['fecha_fin']) ? date('Y-m-d H:i:s', strtotime((string) $_POST['fecha_fin'])) : null;

        if ($semana_id <= 0 || $titulo === '') {
            $msg = 'El título es obligatorio.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/discusiones?semana_id=' . $semana_id);
            return;
        }

        if ($fechaInicio === null || $fechaFin === null) {
            $msg = 'Debe definir la fecha de inicio y fin del debate.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/discusiones?semana_id=' . $semana_id);
            return;
        }
        if ($fechaFin <= $fechaInicio) {
            $msg = 'La fecha de fin debe ser posterior a la fecha de inicio.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
            setFlash('error', $msg);
            redirect(BASE_URL . 'docente/discusiones?semana_id=' . $semana_id);
            return;
        }

        try {
            $semana = $this->semanas->getBasicForDocente($semana_id, $docenteId);
            if (!$semana) {
                $msg = 'Semana no encontrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 404); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/semanas');
                return;
            }
            if ($semana['cerrada']) {
                $msg = 'No se pueden crear debates en una semana cerrada.';
                if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg]); return; }
                setFlash('error', $msg);
                redirect(BASE_URL . 'docente/discusiones?semana_id=' . $semana_id);
                return;
            }

            $id = $this->discusiones->create([
                'semana_id' => $semana_id,
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'creado_por' => $docenteId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ]);

            // Notificar por correo a los matriculados del curso
            try {
            $mailer = new Mailer($this->db);
            if ($mailer->isConfigured()) {
            $stmtEst = $this->db->prepare(
            'SELECT u.id, u.nombres, u.apellidos, u.email
            FROM asignaciones a
            INNER JOIN usuarios u ON u.id = a.estudiante_id
            WHERE a.curso_id = :curso_id AND u.estado = 1 AND u.email IS NOT NULL'
            );
            $stmtEst->execute([':curso_id' => (int) ($semana['curso_id'] ?? $semana_id)]);
            $cursoNombre = htmlspecialchars($curso['nombre'] ?? '');
            $hoy = date('Y-m-d');
            $asunto = 'Nuevo debate: ' . $titulo;
            foreach ($stmtEst->fetchAll(PDO::FETCH_ASSOC) as $est) {
            if (empty($est['email'])) continue;
            $stLog = $this->db->prepare('SELECT 1 FROM debate_notificaciones_log WHERE discusion_id = :d AND usuario_id = :u AND fecha = :f AND tipo = \'creacion\'');
            $stLog->execute([':d' => $id, ':u' => (int) $est['id'], ':f' => $hoy]);
            if ($stLog->fetchColumn()) continue;
            $cuerpo = '<p>Hola <strong>' . htmlspecialchars(trim($est['nombres'] . ' ' . $est['apellidos'])) . '</strong>,</p>'
            . '<p>Se ha habilitado un nuevo debate en el curso <strong>' . $cursoNombre . '</strong>, semana ' . (int) ($semana['numero_semana'] ?? 0) . '.</p>'
            . '<p><strong>Tema:</strong> ' . htmlspecialchars($titulo) . '</p>'
            . (!empty($fechaInicio) && !empty($fechaFin)
            ? '<p><strong>Vigencia:</strong> del ' . date('d/m/Y H:i', strtotime($fechaInicio)) . ' al ' . date('d/m/Y H:i', strtotime($fechaFin)) . '</p>'
            : '')
            . '<p>Ingresa al sistema para participar antes del cierre.</p>';
            if ($mailer->send($est['email'], trim($est['nombres'] . ' ' . $est['apellidos']), $asunto, $cuerpo)) {
            $stIns = $this->db->prepare('INSERT INTO debate_notificaciones_log (discusion_id, usuario_id, fecha, tipo) VALUES (:d, :u, :f, \'creacion\')');
            $stIns->execute([':d' => $id, ':u' => (int) $est['id'], ':f' => $hoy]);
            }
            }
            }
            } catch (Throwable $eNotif) {
            logError('Error notificando nuevo debate', ['message' => $eNotif->getMessage(), 'id' => $id]);
            }

            $msg = 'Debate creado correctamente.';
            if ($isAjax) { $this->jsonResponse(['success' => true, 'message' => $msg, 'id' => $id]); return; }
            setFlash('success', $msg);
        } catch (PDOException $e) {
            logError('Error creando discusión', ['message' => $e->getMessage(), 'semana_id' => $semana_id]);
            $msg = 'Error al crear el debate.';
            if ($isAjax) { $this->jsonResponse(['success' => false, 'message' => $msg], 500); return; }
            setFlash('error', $msg);
        }

        redirect(BASE_URL . 'docente/discusiones?semana_id=' . $semana_id);
    }

    public function chatDiscusion(): void
    {
        $this->requireDocente();

        $docenteId = $this->docenteId();
        $discusion_id = isset($_GET['discusion_id']) ? (int) $_GET['discusion_id'] : 0;

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                setFlash('error', 'Debate no encontrado.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                setFlash('error', 'No tiene acceso a este debate.');
                redirect(BASE_URL . 'docente/semanas');
                return;
            }

            $curso = $this->cursos->getByIdForDocenteSimple((int) $semana['curso_id'], $docenteId);
            $mensajes = $this->mensajesDiscusion->getByDiscusion($discusion_id, 200);
            $aprobaciones = $this->aprobaciones->getPorDiscusion($discusion_id);

            $this->discusionLeida->marcarLeida($discusion_id, $docenteId, $mensajes ? (int) end($mensajes)['id'] : null);
        } catch (PDOException $e) {
            setFlash('error', 'Error al cargar el debate.');
            redirect(BASE_URL . 'docente/semanas');
            return;
        }

        require __DIR__ . '/../Views/docente/chat_discusion.php';
    }

    public function enviarMensaje(): void
    {
        $this->requireDocente();

        $docenteId = $this->docenteId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $discusion_id = (int) ($_POST['discusion_id'] ?? 0);
        $mensaje = trim((string) ($_POST['mensaje'] ?? ''));

        if ($discusion_id <= 0 || $mensaje === '') {
            jsonResponse(['success' => false, 'message' => 'Mensaje vacío.'], 400);
            return;
        }

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            if ($discusion['estado'] === 'cerrada') {
                jsonResponse(['success' => false, 'message' => 'Este debate está cerrado.']);
                return;
            }

            if ($semana['cerrada']) {
                jsonResponse(['success' => false, 'message' => 'La semana está cerrada.']);
                return;
            }

            $id = $this->mensajesDiscusion->create($discusion_id, $docenteId, $mensaje);
            $this->discusionLeida->marcarLeida($discusion_id, $docenteId, $id);

            jsonResponse(['success' => true, 'message' => 'Mensaje enviado.', 'id' => $id]);
        } catch (PDOException $e) {
            logError('Error enviando mensaje', ['message' => $e->getMessage()]);
            jsonResponse(['success' => false, 'message' => 'Error al enviar el mensaje.'], 500);
        }
    }

    public function editarMensaje(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $mensaje_id = (int) ($_POST['mensaje_id'] ?? 0);
        $mensaje = trim((string) ($_POST['mensaje'] ?? ''));

        if ($mensaje_id <= 0 || $mensaje === '') {
            jsonResponse(['success' => false, 'message' => 'Mensaje no válido.'], 400);
            return;
        }

        try {
            $msg = $this->mensajesDiscusion->getById($mensaje_id);
            if (!$msg) {
                jsonResponse(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
                return;
            }

            if ((int) $msg['usuario_id'] !== $docenteId) {
                jsonResponse(['success' => false, 'message' => 'Solo puedes editar tus propios mensajes.'], 403);
                return;
            }

            $this->mensajesDiscusion->update($mensaje_id, $docenteId, $mensaje);
            jsonResponse(['success' => true, 'message' => 'Mensaje editado.']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al editar el mensaje.'], 500);
        }
    }

    public function obtenerMensajes(): void
    {
        $this->requireDocente();

        $docenteId = $this->docenteId();
        $discusion_id = (int) ($_GET['discusion_id'] ?? 0);
        $after_id = (int) ($_GET['after_id'] ?? 0);

        if ($discusion_id <= 0) {
            jsonResponse(['success' => false, 'message' => 'Parámetros inválidos.'], 400);
            return;
        }

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            $mensajes = $this->mensajesDiscusion->getByDiscusion($discusion_id, 100, $after_id);
            $since = $_GET['since'] ?? '';
            $editados = $since !== '' ? $this->mensajesDiscusion->getEditadosDesde($discusion_id, $since) : [];
            $likesMap = $this->likes->countForDiscusion($discusion_id, $docenteId);
            $anuncios = $since !== '' ? $this->likes->getAnunciosDesde($discusion_id, $since) : [];
            $aprobaciones = $this->aprobaciones->getPorDiscusion($discusion_id);

            if (!empty($mensajes)) {
                $ultimoId = end($mensajes)['id'];
                $this->discusionLeida->marcarLeida($discusion_id, $docenteId, $ultimoId);
            }

            jsonResponse(['success' => true, 'mensajes' => $mensajes, 'editados' => $editados, 'estado' => $discusion['estado'], 'typing' => $this->typing->getTypingUsers($discusion_id, $docenteId), 'likes' => $likesMap, 'anuncios' => $anuncios, 'aprobaciones' => (object) $aprobaciones]);

            if (!empty($mensajes)) {
                $ultimoId = end($mensajes)['id'];
                $this->discusionLeida->marcarLeida($discusion_id, $docenteId, $ultimoId);
            }

            jsonResponse(['success' => true, 'mensajes' => $mensajes, 'editados' => $editados, 'estado' => $discusion['estado'], 'typing' => $this->typing->getTypingUsers($discusion_id, $docenteId)]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al obtener mensajes.'], 500);
        }
    }

    public function cerrarDiscusion(): void
    {
        $this->requireDocente();
        if (headers_sent()) return;

        $docenteId = $this->docenteId();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $discusion_id = (int) ($_POST['discusion_id'] ?? 0);

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            if ($discusion['estado'] === 'cerrada') {
                jsonResponse(['success' => false, 'message' => 'El debate está finalizado. Para abrirlo use "Reabrir" indicando un nuevo rango de fechas.']);
                return;
            }

            $this->discusiones->close($discusion_id);

            // El aviso a los matriculados se encola: responde de inmediato y
            // el frontend luego consume el trabajo con barra de progreso.
            $jobId = $this->encolarAvisoEstadoDebate($discusion_id, 'finalizado');

            jsonResponse([
                'success' => true,
                'message' => 'Debate finalizado.',
                'estado' => 'cerrada',
                'job_id' => $jobId,
            ]);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al cambiar estado del debate.'], 500);
        }
    }

    /**
     * Prepara el trabajo de aviso por correo tras cerrar o reabrir un debate.
     * Solo encola: el envio real lo hace el frontend llamando a
     * procesarNotificacionDebate, de modo que la respuesta es inmediata.
     */
    private function encolarAvisoEstadoDebate(int $discusionId, string $tipo, array $extra = []): ?int
    {
        try {
            if (!isset($this->notifDebate)) {
                $this->notifDebate = new NotificacionDebate($this->db);
            }
            $mailer = new Mailer($this->db);
            if (!$mailer->isConfigured()) {
                return null;
            }
            $destinatarios = $this->notifDebate->destinatariosPendientes($discusionId, $tipo);
            if (!$destinatarios) {
                return null;
            }
            return $this->notifDebate->crearJob($discusionId, $tipo, $destinatarios);
        } catch (Throwable $e) {
            logError('Error encolando aviso de debate', [
                'message' => $e->getMessage(),
                'discusion' => $discusionId,
                'tipo' => $tipo,
            ]);
            return null;
        }
    }

    /**
     * Consume el trabajo de notificacion: envia los correos pendientes de a uno
     * y devuelve el avance para que el frontend muestre el progreso.
     */
    public function procesarNotificacionDebate(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        if (!isset($this->notifDebate)) {
            $this->notifDebate = new NotificacionDebate($this->db);
        }

        $jobId = (int) ($_POST['job_id'] ?? 0);
        if ($jobId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Trabajo no válido.'], 400);
            return;
        }

        $job = $this->notifDebate->getJob($jobId);
        if (!$job) {
            jsonResponse(['success' => false, 'message' => 'El trabajo ya no existe.'], 404);
            return;
        }

        $discusionId = (int) $job['discusion_id'];
        $discusion = $this->discusiones->getById($discusionId);
        if (!$discusion) {
            jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
            return;
        }
        $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $this->docenteId());
        if (!$semana) {
            jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
            return;
        }

        $tipo = (string) $job['tipo'];
        $esReapertura = $tipo === 'reabierto';
        $rango = '';
        if ($esReapertura && !empty($discusion['fecha_inicio']) && !empty($discusion['fecha_fin'])) {
            $rango = date('d/m/Y H:i', strtotime((string) $discusion['fecha_inicio']))
                . ' a ' . date('d/m/Y H:i', strtotime((string) $discusion['fecha_fin']));
        }
        $asunto = ($esReapertura ? 'Debate reabierto — ' : 'Debate cerrado — ') . $discusion['titulo'];

        $stCurso = $this->db->prepare('SELECT nombre FROM cursos WHERE id = :id');
        $stCurso->execute([':id' => (int) ($semana['curso_id'] ?? 0)]);
        $cursoNombre = (string) ($stCurso->fetchColumn() ?: '');

        $mailer = new Mailer($this->db);
        $procesados = [];

        // Un destinatario por peticion: el frontend va llamando en bucle y
        // cada respuesta le devuelve el nombre del alumno que acaba de avisarse.
        foreach ($this->notifDebate->pendientes($jobId, 1) as $dest) {
            $ok = false;
            $error = null;
            try {
                $cuerpo = '<p>Hola <strong>' . htmlspecialchars($dest['nombre']) . '</strong>,</p>'
                    . '<p>El docente ha <strong>' . ($esReapertura ? 'reabierto' : 'cerrado') . '</strong> el debate <strong>'
                    . htmlspecialchars($discusion['titulo']) . '</strong>'
                    . (!empty($cursoNombre) ? ' del curso <strong>' . htmlspecialchars($cursoNombre) . '</strong>' : '')
                    . '.</p>'
                    . ($esReapertura
                        ? '<p>Vigente del <strong>' . $rango . '</strong>. Ya puedes volver a participar y dar likes.</p>'
                        : '<p>Ya no acepta nuevas participaciones.</p>');
                $ok = $mailer->send($dest['email'], $dest['nombre'], $asunto, $cuerpo);
                if (!$ok) {
                    $error = 'SMTP no confirmó el envío';
                }
            } catch (Throwable $e) {
                $ok = false;
                $error = $e->getMessage();
                logError('Error enviando aviso de debate', [
                    'message' => $e->getMessage(),
                    'job' => $jobId,
                    'destinatario' => $dest['email'],
                ]);
            }

            $this->notifDebate->marcar((int) $dest['id'], $ok ? 'enviado' : 'fallido', $ok ? null : substr((string) $error, 0, 250));

            if ($ok) {
                try {
                    $this->db->prepare(
                        "INSERT INTO debate_notificaciones_log (discusion_id, usuario_id, fecha, tipo)
                         VALUES (:d, :u, :f, :t)
                         ON DUPLICATE KEY UPDATE fecha = VALUES(fecha)"
                    )->execute([
                        ':d' => $discusionId,
                        ':u' => (int) $dest['usuario_id'],
                        ':f' => date('Y-m-d'),
                        ':t' => $tipo,
                    ]);
                } catch (Throwable $eLog) {
                    logError('Error registrando aviso de debate', ['message' => $eLog->getMessage()]);
                }
            }

            $procesados[] = [
                'nombre' => $dest['nombre'],
                'estado' => $ok ? 'enviado' : 'fallido',
                'error' => $ok ? null : $error,
            ];
        }

        $estado = $this->notifDebate->refrescarJob($jobId);
        $total = (int) $estado['total'];
        $hechos = (int) $estado['enviados'] + (int) $estado['fallidos'];

        jsonResponse([
            'success' => true,
            'job_id' => $jobId,
            'total' => $total,
            'procesados' => $hechos,
            'enviados' => (int) $estado['enviados'],
            'fallidos' => (int) $estado['fallidos'],
            'completado' => $estado['estado'] === 'completado',
            'ultimos' => $procesados,
        ]);
    }

    /** Reabre un debate finalizado indicando un nuevo rango de vigencia */
    public function reabrirDiscusion(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $discusion_id = (int) ($_POST['discusion_id'] ?? 0);
        $tsInicio = !empty($_POST['fecha_inicio']) ? strtotime(trim((string) $_POST['fecha_inicio'])) : false;
        $tsFin = !empty($_POST['fecha_fin']) ? strtotime(trim((string) $_POST['fecha_fin'])) : false;

        if ($discusion_id <= 0 || $tsInicio === false || $tsFin === false) {
            jsonResponse(['success' => false, 'message' => 'Debe indicar el nuevo rango de fechas.'], 400);
            return;
        }
        $fechaInicio = date('Y-m-d H:i:s', $tsInicio);
        $fechaFin = date('Y-m-d H:i:s', $tsFin);
        if ($fechaFin <= $fechaInicio) {
            jsonResponse(['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio.'], 400);
            return;
        }

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            if ($discusion['estado'] !== 'cerrada') {
                jsonResponse(['success' => false, 'message' => 'El debate ya está abierto.'], 400);
                return;
            }

            $ok = $this->discusiones->reabrir($discusion_id, $fechaInicio, $fechaFin);
            if (!$ok) {
                jsonResponse(['success' => false, 'message' => 'No se pudo reabrir el debate.'], 400);
                return;
            }

            // El aviso a los matriculados se encola: responde de inmediato y
            // el frontend luego consume el trabajo con barra de progreso.
            $jobId = $this->encolarAvisoEstadoDebate($discusion_id, 'reabierto', [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ]);

            jsonResponse([
                'success' => true,
                'message' => 'Debate reabierto hasta el ' . date('d/m/Y H:i', strtotime($fechaFin)) . '.',
                'estado' => 'abierta',
                'job_id' => $jobId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ]);
        } catch (PDOException $e) {
            logError('Error reabriendo debate', ['message' => $e->getMessage(), 'id' => $discusion_id]);
            jsonResponse(['success' => false, 'message' => 'Error al reabrir el debate.'], 500);
        }
    }

    public function anularDiscusion(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $discusion_id = (int) ($_POST['discusion_id'] ?? 0);

        if ($discusion_id <= 0) {
            jsonResponse(['success' => false, 'message' => 'Debate no válido.'], 400);
            return;
        }

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            $this->discusiones->delete($discusion_id);
            jsonResponse(['success' => true, 'message' => 'Debate eliminado.']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al eliminar el debate.'], 500);
        }
    }

    public function editarDiscusion(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $discusion_id = (int) ($_POST['discusion_id'] ?? 0);
        $titulo = sanitize($_POST['titulo'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');

        if ($discusion_id <= 0 || $titulo === '') {
            jsonResponse(['success' => false, 'message' => 'El título es obligatorio.'], 400);
            return;
        }

        try {
            $discusion = $this->discusiones->getById($discusion_id);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            $this->discusiones->update($discusion_id, $titulo, $descripcion ?: null);
            jsonResponse(['success' => true, 'message' => 'Debate actualizado.']);
        } catch (PDOException $e) {
            jsonResponse(['success' => false, 'message' => 'Error al actualizar el debate.'], 500);
        }
    }

    public function indicarEscritura(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }
        $docenteId = $this->docenteId();
        $discusion_id = (int) ($_POST['discusion_id'] ?? 0);
        if ($discusion_id <= 0) {
            jsonResponse(['success' => false, 'message' => 'Inválido.'], 400);
            return;
        }
        $action = $_POST['action'] ?? 'typing';
        if ($action === 'stop') {
            $this->typing->clearTyping($docenteId, $discusion_id);
        } else {
            $this->typing->setTyping($docenteId, $discusion_id);
        }
        jsonResponse(['success' => true]);
    }

    /**
     * Check docente sobre un mensaje. Solo los comentarios con check
     * habilitan los likes de los estudiantes (y con ello los puntos).
     */
    public function aprobarMensaje(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $mensaje_id = (int) ($_POST['mensaje_id'] ?? 0);
        if ($mensaje_id <= 0) {
            jsonResponse(['success' => false, 'message' => 'Inválido.'], 400);
            return;
        }

        try {
            $msg = $this->mensajesDiscusion->getById($mensaje_id);
            if (!$msg) {
                jsonResponse(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
                return;
            }

            $discusion = $this->discusiones->getById((int) $msg['discusion_id']);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            if ($discusion['estado'] !== 'abierta') {
                jsonResponse(['success' => false, 'message' => 'El debate está cerrado.']);
                return;
            }

            $resultado = $this->aprobaciones->toggle($mensaje_id, $docenteId);
            $aprobado = $resultado === 'aprobado';

            $stmt = $this->db->prepare('SELECT created_at FROM mensaje_aprobaciones WHERE mensaje_id = :m');
            $stmt->execute([':m' => $mensaje_id]);
            $fecha = (string) ($stmt->fetchColumn() ?: '');

            $nombreDocente = trim((string) ($_SESSION['user_nombres'] ?? '') . ' ' . (string) ($_SESSION['user_nombre'] ?? ''));

            jsonResponse([
                'success' => true,
                'aprobado' => $aprobado,
                'docente' => trim($nombreDocente),
                'fecha' => $fecha,
                'mensaje' => $aprobado
                    ? 'Likes habilitados para los estudiantes en este comentario.'
                    : 'Likes deshabilitados en este comentario.',
            ]);
        } catch (PDOException $e) {
            logError('Error al aprobar mensaje', ['message' => $e->getMessage()]);
            jsonResponse(['success' => false, 'message' => 'Error al procesar el check.'], 500);
        }
    }

    public function darLikeMensaje(): void
    {
        $this->requireDocente();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
            return;
        }

        $docenteId = $this->docenteId();
        $mensaje_id = (int) ($_POST['mensaje_id'] ?? 0);
        if ($mensaje_id <= 0) {
            jsonResponse(['success' => false, 'message' => 'Inválido.'], 400);
            return;
        }

        try {
            $msg = $this->mensajesDiscusion->getById($mensaje_id);
            if (!$msg) {
                jsonResponse(['success' => false, 'message' => 'Mensaje no encontrado.'], 404);
                return;
            }
            if ((int) $msg['usuario_id'] === $docenteId) {
                jsonResponse(['success' => false, 'message' => 'No puedes dar like a tu propio mensaje.']);
                return;
            }

            $discusion = $this->discusiones->getById((int) $msg['discusion_id']);
            if (!$discusion) {
                jsonResponse(['success' => false, 'message' => 'Debate no encontrado.'], 404);
                return;
            }

            $semana = $this->semanas->getBasicForDocente((int) $discusion['semana_id'], $docenteId);
            if (!$semana) {
                jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403);
                return;
            }

            $resultado = $this->likes->toggle($mensaje_id, $docenteId);

            $stmt = $this->db->prepare('SELECT COUNT(*) FROM mensaje_likes WHERE mensaje_id = :m');
            $stmt->execute([':m' => $mensaje_id]);
            $totalLikes = (int) $stmt->fetchColumn();

            jsonResponse([
                'success' => true,
                'liked' => $resultado === 'liked',
                'total_likes' => $totalLikes,
                'otorgado' => false,
            ]);
        } catch (PDOException $e) {
            logError('Error al dar like docente', ['message' => $e->getMessage()]);
            jsonResponse(['success' => false, 'message' => 'Error al procesar el like.'], 500);
        }
    }

    /** Lista de usuarios que dieron like a un mensaje del debate */
    public function likesDeMensaje(): void
    {
        $this->requireDocente();
        $docenteId = $this->docenteId();
        $mensajeId = (int) ($_GET['mensaje_id'] ?? 0);

        if ($mensajeId <= 0) {
            jsonResponse(['success' => false, 'message' => 'Mensaje no valido.'], 400);
            return;
        }

        try {
            $st = $this->db->prepare(
                'SELECT d.id, d.semana_id FROM mensajes_discusion md
                 INNER JOIN discusiones d ON d.id = md.discusion_id
                 WHERE md.id = :mid'
            );
            $st->execute([':mid' => $mensajeId]);
            $disc = $st->fetch(PDO::FETCH_ASSOC);
            if (!$disc) { jsonResponse(['success' => false, 'message' => 'Mensaje no encontrado.'], 404); return; }

            $semana = $this->semanas->getBasicForDocente((int) $disc['semana_id'], $docenteId);
            if (!$semana) { jsonResponse(['success' => false, 'message' => 'Sin acceso.'], 403); return; }

            $stL = $this->db->prepare(
                'SELECT u.nombres, u.apellidos, u.username, l.created_at
                 FROM mensaje_likes l
                 INNER JOIN usuarios u ON u.id = l.usuario_id
                 WHERE l.mensaje_id = :mid
                 ORDER BY l.created_at ASC'
            );
            $stL->execute([':mid' => $mensajeId]);
            jsonResponse(['success' => true, 'likes' => $stL->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            logError('Error obteniendo likes', ['message' => $e->getMessage()]);
            jsonResponse(['success' => false, 'message' => 'Error al obtener los likes.'], 500);
        }
    }

}
