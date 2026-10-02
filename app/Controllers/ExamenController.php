<?php

declare(strict_types=1);

class ExamenController
{
    private PDO $db;
    private Examen $examenes;
    private Curso $cursos;
    private Semana $semanas;
    private Criterio $criterios;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->examenes = new Examen($db);
        $this->cursos = new Curso($db);
        $this->semanas = new Semana($db);
        $this->criterios = new Criterio($db);
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

    private function requireDocente(): int
    {
        if (!isLoggedIn() || !isDocente()) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'No autorizado.'], 403);
            }
            setFlash('error', 'No tiene permiso para acceder a esta sección.');
            redirect(BASE_URL . 'auth/iniciarSesion');
        }
        return (int) $_SESSION['user_id'];
    }

    private function requireEstudiante(): int
    {
        if (!isLoggedIn() || !isEstudiante()) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'No autorizado.'], 403);
            }
            setFlash('error', 'No tiene permiso para acceder a esta sección.');
            redirect(BASE_URL . 'auth/iniciarSesion');
        }
        return (int) $_SESSION['user_id'];
    }

    private function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    // ==========================================================
    //                         DOCENTE
    // ==========================================================

    public function lista(): void
    {
        $docenteId = $this->requireDocente();
        $examenes = $this->examenes->listByDocente($docenteId);
        $pageTitle = 'Mis Exámenes';
        require __DIR__ . '/../Views/examenes/lista_docente.php';
    }

    public function formulario(): void
    {
        $docenteId = $this->requireDocente();

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $examen = null;
        if ($id > 0) {
            $examen = $this->examenes->getByIdForDocente($id, $docenteId);
            if (!$examen) {
                setFlash('error', 'Examen no encontrado.');
                redirect(BASE_URL . 'examen/lista');
                return;
            }
        }

        // Cursos del docente con semanas y criterios para selects dependientes
        $cursos = $this->cursos->getByDocenteActivos($docenteId);
        $arbol = [];
        foreach ($cursos as $curso) {
            $semanas = $this->semanas->getAllByCurso((int) $curso['id'], $docenteId);
            foreach ($semanas as $semana) {
                $criteriosSemana = $this->criterios->getAllBySemanaSimple((int) $semana['id']);
                $arbol[$curso['id']][] = [
                    'semana' => ['id' => $semana['id'], 'nombre' => $semana['nombre'], 'numero_semana' => $semana['numero_semana']],
                    'criterios' => array_map(fn($c) => ['id' => $c['id'], 'nombre' => $c['nombre']], $criteriosSemana),
                ];
            }
        }

        require __DIR__ . '/../Views/examenes/formulario_docente.php';
    }

    public function guardar(): void
    {
        $docenteId = $this->requireDocente();

        $id = (int) ($this->post('id') ?? 0);
        $criterioId = (int) ($this->post('criterio_id') ?? 0);
        $titulo = trim((string) $this->post('titulo', ''));
        $descripcion = trim((string) $this->post('descripcion', '')) ?: null;
        $tipo = $this->post('tipo') === 'en_vivo' ? 'en_vivo' : 'personal';
        $duracion = max(1, (int) ($this->post('duracion_min') ?? 30));
        $intentos = max(1, (int) ($this->post('intentos_permitidos') ?? 1));
        $desde = !empty($_POST['disponible_desde']) ? date('Y-m-d H:i:s', strtotime((string) $_POST['disponible_desde'])) : null;
        $hasta = !empty($_POST['disponible_hasta']) ? date('Y-m-d H:i:s', strtotime((string) $_POST['disponible_hasta'])) : null;
        $esPersonal = $tipo === 'personal';
        $modo = $esPersonal
            ? (($_POST['modo_disponibilidad'] ?? '') === Examen::MODO_PROGRAMADO ? Examen::MODO_PROGRAMADO : Examen::MODO_INMEDIATO)
            : Examen::MODO_INMEDIATO;

        // En modo inmediato no hay ventana: se ignoran las fechasInformadas
        if ($modo === Examen::MODO_INMEDIATO) {
            $desde = null;
            $hasta = null;
        }

        if ($titulo === '' || $criterioId <= 0) {
            setFlash('error', 'Complete los campos obligatorios.');
            redirect(BASE_URL . 'examen/formulario' . ($id ? "?id=$id" : ''));
            return;
        }

        if ($modo === Examen::MODO_PROGRAMADO && $desde === null) {
            setFlash('error', 'Indique la fecha y hora de inicio de disponibilidad.');
            redirect(BASE_URL . 'examen/formulario' . ($id ? "?id=$id" : ''));
            return;
        }

        if ($desde !== null && $hasta !== null && $hasta <= $desde) {
            setFlash('error', 'La fecha "disponible hasta" debe ser posterior a "disponible desde".');
            redirect(BASE_URL . 'examen/formulario' . ($id ? "?id=$id" : ''));
            return;
        }

        // Validar que el criterio pertenezca a un curso del docente
        $criterio = $this->criterios->getById($criterioId);
        if (!$criterio) {
            setFlash('error', 'Criterio no válido.');
            redirect(BASE_URL . 'examen/formulario' . ($id ? "?id=$id" : ''));
            return;
        }

        $data = [
            'criterio_id' => $criterioId,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'duracion_min' => $duracion,
            'disponible_desde' => $desde,
            'disponible_hasta' => $hasta,
            'modo_disponibilidad' => $modo,
            'intentos_permitidos' => $intentos,
            'creado_por' => $docenteId,
        ];

        try {
            if ($id > 0 && $this->examenes->getByIdForDocente($id, $docenteId)) {
                $data['creado_por'] = $docenteId;
                $this->examenes->update($id, $data);
                setFlash('success', 'Examen actualizado correctamente.');
                redirect(BASE_URL . "examen/preguntas?examen_id=$id");
            } else {
                $nuevoId = $this->examenes->create($data);
                setFlash('success', 'Examen creado como borrador. Agregue las preguntas y actívelo cuando esté listo.');
                redirect(BASE_URL . "examen/preguntas?examen_id=$nuevoId");
            }
        } catch (Throwable $e) {
            logError('Error al guardar examen: ' . $e->getMessage());
            setFlash('error', 'Ocurrió un error al guardar el examen.');
            redirect(BASE_URL . 'examen/lista');
        }
    }

    /** Activa (publica) o desactiva (vuelve a borrador) un examen */
    public function cambiarEstado(): void
    {
        $docenteId = $this->requireDocente();
        $id = (int) ($this->post('id') ?? 0);
        $accion = (string) ($this->post('accion') ?? '');

        try {
            if ($accion === 'activar') {
                $this->examenes->activar($id, $docenteId);
                $mensaje = 'Examen activado. Los estudiantes ya pueden verlo.';
            } elseif ($accion === 'desactivar') {
                $this->examenes->desactivar($id, $docenteId);
                $mensaje = 'Examen desactivado. Volvió a borrador y los estudiantes ya no lo ven.';
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Acción no válida.'], 400);
                return;
            }
            $this->jsonResponse(['success' => true, 'message' => $mensaje]);
        } catch (RuntimeException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            logError('Error cambiarEstado: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al cambiar el estado del examen.'], 500);
        }
    }

    /** Ajusta la ventana de disponibilidad (fecha/hora) de un examen personal */
    public function guardarDisponibilidad(): void
    {
        $docenteId = $this->requireDocente();
        $examenId = (int) ($this->post('examen_id') ?? 0);
        $volver = BASE_URL . "examen/preguntas?examen_id=$examenId";

        $examen = $this->examenes->getByIdForDocente($examenId, $docenteId);
        if (!$examen) {
            setFlash('error', 'Examen no encontrado.');
            redirect($volver);
            return;
        }

        $desde = !empty($_POST['disponible_desde'])
            ? date('Y-m-d H:i:s', strtotime((string) $_POST['disponible_desde']))
            : null;
        $hasta = !empty($_POST['disponible_hasta'])
            ? date('Y-m-d H:i:s', strtotime((string) $_POST['disponible_hasta']))
            : null;
        $modo = ($this->post('modo_disponibilidad') ?? '') === Examen::MODO_PROGRAMADO
            ? Examen::MODO_PROGRAMADO
            : Examen::MODO_INMEDIATO;

        try {
            $this->examenes->actualizarDisponibilidad($examenId, $docenteId, $modo, $desde, $hasta);
            setFlash('success', $modo === Examen::MODO_PROGRAMADO
                ? 'Ventana de disponibilidad actualizada.'
                : 'Modo inmediato: el examen quedará disponible sin fechas desde que se active.');
        } catch (RuntimeException $e) {
            setFlash('error', $e->getMessage());
        } catch (Throwable $e) {
            logError('Error guardarDisponibilidad: ' . $e->getMessage());
            setFlash('error', 'Error al guardar la disponibilidad.');
        }
        redirect($volver);
    }

    public function eliminar(): void
    {
        $docenteId = $this->requireDocente();
        $id = (int) ($this->post('id') ?? 0);
        try {
            $this->examenes->delete($id, $docenteId);
            setFlash('success', 'Examen eliminado.');
        } catch (Throwable $e) {
            setFlash('error', 'No se pudo eliminar el examen.');
        }
        redirect(BASE_URL . 'examen/lista');
    }

    public function preguntas(): void
    {
        $docenteId = $this->requireDocente();
        $examenId = (int) ($_GET['examen_id'] ?? 0);
        $examen = $this->examenes->getByIdForDocente($examenId, $docenteId);

        if (!$examen) {
            setFlash('error', 'Examen no encontrado.');
            redirect(BASE_URL . 'examen/lista');
            return;
        }

        $preguntas = $this->examenes->getPreguntas($examenId, true);
        $totales = $this->examenes->totalesPreguntas($examenId);
        $sesionActiva = $this->examenes->getSesionActiva($examenId);
        $intentosEnCurso = $this->examenes->countIntentosEnCurso($examenId);
        $pageTitle = 'Preguntas - ' . $examen['titulo'];
        require __DIR__ . '/../Views/examenes/preguntas_docente.php';
    }

    public function guardarPregunta(): void
    {
        $docenteId = $this->requireDocente();
        $examenId = (int) ($this->post('examen_id') ?? 0);
        $examen = $this->examenes->getByIdForDocente($examenId, $docenteId);

        if (!$examen) {
            $this->jsonResponse(['success' => false, 'message' => 'Examen no encontrado.'], 404);
        }
        if ($this->examenes->getSesionActiva($examenId)) {
            $this->jsonResponse(['success' => false, 'message' => 'No puede modificar preguntas durante una sesión activa.'], 400);
        }

        $texto = trim((string) $this->post('texto', ''));
        $puntos = max(0.5, (float) ($this->post('puntos') ?? 1));
        $tipo = $this->post('tipo') === 'abierta' ? 'abierta' : 'opcion';
        $opciones = [];

        if ($tipo === 'opcion') {
            if (isset($_POST['opciones']) && is_array($_POST['opciones'])) {
                foreach ($_POST['opciones'] as $op) {
                    $txtOp = trim((string) ($op['texto'] ?? ''));
                    if ($txtOp !== '') {
                        $opciones[] = ['texto' => $txtOp, 'es_correcta' => !empty($op['es_correcta'])];
                    }
                }
            }
            if (isset($_POST['opcion_texto']) && is_array($_POST['opcion_texto'])) {
                $correctas = $_POST['opcion_correcta'] ?? [];
                foreach ($_POST['opcion_texto'] as $idx => $txtOp) {
                    $txtOp = trim((string) $txtOp);
                    if ($txtOp !== '') {
                        $opciones[] = ['texto' => $txtOp, 'es_correcta' => !empty($correctas[$idx])];
                    }
                }
            }
            if (count($opciones) < 2) {
                $this->jsonResponse(['success' => false, 'message' => 'La pregunta necesita al menos 2 opciones.'], 400);
            }
        }

        if ($texto === '') {
            $this->jsonResponse(['success' => false, 'message' => 'La pregunta necesita texto.'], 400);
        }

        try {
            $preguntaId = $this->examenes->crearPregunta($examenId, $texto, $puntos, $opciones, $tipo);
            $totales = $this->examenes->totalesPreguntas($examenId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Pregunta agregada.',
                'pregunta_id' => $preguntaId,
                'total_preguntas' => $totales['total'],
                'puntos_total' => $totales['puntos_total'],
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function editarPregunta(): void
    {
        $docenteId = $this->requireDocente();
        $examenId = (int) ($this->post('examen_id') ?? 0);
        $preguntaId = (int) ($this->post('pregunta_id') ?? 0);
        $examen = $this->examenes->getByIdForDocente($examenId, $docenteId);

        if (!$examen) {
            $this->jsonResponse(['success' => false, 'message' => 'Examen no encontrado.'], 404);
        }
        if ($preguntaId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'No se indicó qué pregunta editar.'], 400);
        }
        if ($this->examenes->getSesionActiva($examenId)) {
            $this->jsonResponse(['success' => false, 'message' => 'No puede modificar preguntas durante una sesión activa.'], 400);
        }
        // Con intentos en curso la respuesta ya está comprometida: editar la
        // pregunta cambiaría lo que se califica, así que se bloquea.
        if ($this->examenes->countIntentosEnCurso($examenId) > 0) {
            $this->jsonResponse(['success' => false, 'message' => 'No puede editar preguntas: hay estudiantes respondiendo este examen ahora mismo.'], 400);
        }

        $texto = trim((string) $this->post('texto', ''));
        $puntos = max(0.5, (float) ($this->post('puntos') ?? 1));
        $tipo = $this->post('tipo') === 'abierta' ? 'abierta' : 'opcion';
        $opciones = [];

        if ($tipo === 'opcion') {
            if (isset($_POST['opciones']) && is_array($_POST['opciones'])) {
                foreach ($_POST['opciones'] as $op) {
                    $txtOp = trim((string) ($op['texto'] ?? ''));
                    if ($txtOp !== '') {
                        $opciones[] = ['texto' => $txtOp, 'es_correcta' => !empty($op['es_correcta'])];
                    }
                }
            }
            if (isset($_POST['opcion_texto']) && is_array($_POST['opcion_texto'])) {
                $correctas = $_POST['opcion_correcta'] ?? [];
                foreach ($_POST['opcion_texto'] as $idx => $txtOp) {
                    $txtOp = trim((string) $txtOp);
                    if ($txtOp !== '') {
                        $opciones[] = ['texto' => $txtOp, 'es_correcta' => !empty($correctas[$idx])];
                    }
                }
            }
            if (count($opciones) < 2) {
                $this->jsonResponse(['success' => false, 'message' => 'La pregunta necesita al menos 2 opciones.'], 400);
            }
        }

        if ($texto === '') {
            $this->jsonResponse(['success' => false, 'message' => 'La pregunta necesita texto.'], 400);
        }

        try {
            $this->examenes->actualizarPregunta($preguntaId, $examenId, $texto, $puntos, $opciones, $tipo);
            $totales = $this->examenes->totalesPreguntas($examenId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Pregunta actualizada.',
                'total_preguntas' => $totales['total'],
                'puntos_total' => $totales['puntos_total'],
            ]);
        } catch (Throwable $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function eliminarPregunta(): void
    {
        $docenteId = $this->requireDocente();
        $examenId = (int) ($this->post('examen_id') ?? 0);
        $preguntaId = (int) ($this->post('pregunta_id') ?? 0);

        if (!$this->examenes->getByIdForDocente($examenId, $docenteId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Examen no encontrado.'], 404);
        }
        $ok = $this->examenes->eliminarPregunta($preguntaId, $examenId);
        $this->jsonResponse(['success' => $ok, 'message' => $ok ? 'Pregunta eliminada.' : 'No se pudo eliminar.']);
    }

    /** Lanza la sesión en vivo del examen */
    public function iniciarEnVivo(): void
    {
        $docenteId = $this->requireDocente();
        $examenId = (int) ($this->post('examen_id') ?? 0);
        $duracion = max(1, (int) ($this->post('duracion_min') ?? 0));

        $examen = $this->examenes->getByIdForDocente($examenId, $docenteId);
        if (!$examen) {
            $this->jsonResponse(['success' => false, 'message' => 'Examen no encontrado.'], 404);
        }
        if ((int) $examen['estado'] !== Examen::ESTADO_ACTIVO) {
            $this->jsonResponse(['success' => false, 'message' => 'Active el examen antes de lanzarlo en vivo.'], 400);
        }

        $totales = $this->examenes->totalesPreguntas($examenId);
        if ($totales['total'] === 0) {
            $this->jsonResponse(['success' => false, 'message' => 'El examen no tiene preguntas.'], 400);
        }

        try {
            $minutos = $duracion > 0 ? $duracion : (int) $examen['duracion_min'];
            $this->examenes->iniciarSesionEnVivo($examenId, $docenteId, $minutos);
            $sesion = $this->examenes->getSesionActiva($examenId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Examen en vivo iniciado.',
                'sesion_id' => (int) $sesion['id'],
                'fin_en' => $sesion['fin_en'],
                'redirect' => BASE_URL . "examen/vigilar?sesion_id={$sesion['id']}",
            ]);
        } catch (RuntimeException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            logError('Error iniciarEnVivo: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al iniciar la sesión.'], 500);
        }
    }

    public function vigilar(): void
    {
        $docenteId = $this->requireDocente();
        $sesionId = (int) ($_GET['sesion_id'] ?? 0);

        $st = $this->db->prepare(
            "SELECT es.*, e.titulo AS examen_titulo FROM examen_sesiones es
             JOIN examenes e ON e.id = es.examen_id
             WHERE es.id = :id AND es.iniciada_por = :docente_id"
        );
        $st->execute([':id' => $sesionId, ':docente_id' => $docenteId]);
        $sesion = $st->fetch(PDO::FETCH_ASSOC);

        if (!$sesion) {
            setFlash('error', 'Sesión no encontrada.');
            redirect(BASE_URL . 'examen/lista');
            return;
        }

        $examen = $this->examenes->getById((int) $sesion['examen_id']);
        $pageTitle = 'En vivo - ' . $examen['titulo'];
        require __DIR__ . '/../Views/examenes/vigilar_docente.php';
    }

    /** AJAX: progreso de la sesión en vivo */
    public function progreso(): void
    {
        $docenteId = $this->requireDocente();
        $sesionId = (int) ($_GET['sesion_id'] ?? 0);

        $st = $this->db->prepare(
            "SELECT * FROM examen_sesiones WHERE id = :id AND iniciada_por = :docente_id"
        );
        $st->execute([':id' => $sesionId, ':docente_id' => $docenteId]);
        $sesion = $st->fetch(PDO::FETCH_ASSOC);

        if (!$sesion) {
            $this->jsonResponse(['success' => false, 'message' => 'Sesión no encontrada.'], 404);
        }

        $intentos = $this->db->prepare(
            "SELECT i.id, i.estado, i.nota, i.puntaje, i.iniciado_at,
                    CONCAT(u.apellidos, ' ', u.nombres) AS estudiante_nombre
             FROM examen_intentos i
             JOIN usuarios u ON u.id = i.estudiante_id
             WHERE i.sesion_id = :sid OR (i.examen_id = :eid AND i.estado = 'en_curso')
             GROUP BY i.id
             ORDER BY FIELD(i.estado,'en_curso','finalizado','expirado'), i.nota DESC"
        );
        $intentos->execute([':sid' => $sesionId, ':eid' => $sesion['examen_id']]);
        $filas = $intentos->fetchAll(PDO::FETCH_ASSOC);

        $ahora = time();
        $restanteSeg = max(0, strtotime($sesion['fin_en']) - $ahora);
        $finalizada = $sesion['estado'] !== 'activa' || $restanteSeg === 0;

        $this->jsonResponse([
            'success' => true,
            'estado' => $finalizada ? 'finalizada' : 'activa',
            'restante_segundos' => $restanteSeg,
            'fin_en' => $sesion['fin_en'],
            'total_estudiantes' => count($filas),
            'en_curso' => count(array_filter($filas, fn($f) => $f['estado'] === 'en_curso')),
            'finalizados' => count(array_filter($filas, fn($f) => in_array($f['estado'], ['finalizado', 'expirado'], true))),
            'intentos' => $filas,
        ]);
    }

    public function finalizarEnVivo(): void
    {
        $docenteId = $this->requireDocente();
        $sesionId = (int) ($this->post('sesion_id') ?? 0);

        try {
            $ok = $this->examenes->finalizarSesion($sesionId, $docenteId);
            $this->jsonResponse([
                'success' => $ok,
                'message' => $ok ? 'Examen finalizado y notas registradas.' : 'No se pudo finalizar la sesión.',
            ]);
        } catch (Throwable $e) {
            logError('Error finalizarEnVivo: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al finalizar.'], 500);
        }
    }

    /** Revisión y calificación manual de respuestas escritas (docente) */
    public function revision(): void
    {
        $docenteId = $this->requireDocente();
        $intentoId = (int) ($_GET['intento_id'] ?? 0);

        $intento = $this->examenes->getIntentoById($intentoId);
        if (!$intento) {
            setFlash('error', 'Intento no encontrado.');
            redirect(BASE_URL . 'examen/lista');
            return;
        }
        $examen = $this->examenes->getByIdForDocente((int) $intento['examen_id'], $docenteId);
        if (!$examen) {
            setFlash('error', 'No tiene acceso a este intento.');
            redirect(BASE_URL . 'examen/lista');
            return;
        }

        // Sincronizar si sigue en curso pero ya venció
        if ($intento['estado'] === 'en_curso') {
            $sync = $this->examenes->sincronizarIntento($intento);
            if ($sync === null) {
                $intento = $this->examenes->getIntentoById($intentoId);
            }
        }

        $preguntas = $this->examenes->getPreguntas((int) $intento['examen_id'], true);
        $respuestas = $this->examenes->getRespuestasIntento($intentoId);
        $totales = $this->examenes->totalesPreguntas((int) $intento['examen_id']);
        $hayAbiertas = count(array_filter($preguntas, fn($p) => $p['tipo'] === 'abierta')) > 0;
        $pageTitle = 'Revisión - ' . $examen['titulo'];
        require __DIR__ . '/../Views/examenes/revision_docente.php';
    }

    /** AJAX: asignar puntos a una respuesta escrita */
    public function calificarAbierta(): void
    {
        $docenteId = $this->requireDocente();
        $intentoId = (int) ($this->post('intento_id') ?? 0);
        $preguntaId = (int) ($this->post('pregunta_id') ?? 0);
        $puntos = (float) ($this->post('puntos') ?? 0);

        $intento = $this->examenes->getIntentoById($intentoId);
        if (!$intento || !$this->examenes->getByIdForDocente((int) $intento['examen_id'], $docenteId)) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        try {
            $resultado = $this->examenes->calificarRespuestaAbierta($intentoId, $preguntaId, $puntos);
            $totales = $this->examenes->totalesPreguntas((int) $intento['examen_id']);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Puntos asignados.',
                'puntaje' => number_format($resultado['puntaje'], 1),
                'nota' => number_format($resultado['nota'], 2),
                'puntos_total' => number_format($totales['puntos_total'], 1),
            ]);
        } catch (RuntimeException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            logError('Error calificarAbierta: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al calificar.'], 500);
        }
    }

    /** Resultados de un examen (vista docente) */
    public function resultados(): void
    {
        $docenteId = $this->requireDocente();
        $examenId = (int) ($_GET['examen_id'] ?? 0);
        $examen = $this->examenes->getByIdForDocente($examenId, $docenteId);

        if (!$examen) {
            setFlash('error', 'Examen no encontrado.');
            redirect(BASE_URL . 'examen/lista');
            return;
        }

        $intentos = $this->examenes->getProgresoExamen($examenId);
        $totales = $this->examenes->totalesPreguntas($examenId);

        // Resumen por alumno (una fila por estudiante) con su limite efectivo
        $resumen = $this->examenes->getResumenPorEstudiante($examenId);
        $permitidos = (int) $examen['intentos_permitidos'];
        foreach ($resumen as &$r) {
            $usados = (int) $r['intentos_usados'];
            $r['limite'] = $permitidos + (int) $r['intentos_extra'];
            $r['restantes'] = max(0, $r['limite'] - $usados);
            $r['agotados'] = $usados >= $r['limite'];
        }
        unset($r);

        $pageTitle = 'Resultados - ' . $examen['titulo'];
        require __DIR__ . '/../Views/examenes/resultados_docente.php';
    }

    /**
     * AJAX: otorga N intentos extra a un estudiante de este examen.
     * NO borra el historial: el intento anterior se conserva y la nota que
     * se muestra sigue siendo la mejor (MAX(nota)).
     */
    public function otorgarIntento(): void
    {
        $docenteId = $this->requireDocente();

        $examenId      = (int) ($this->post('examen_id') ?? 0);
        $estudianteId  = (int) ($this->post('estudiante_id') ?? 0);
        $motivo        = trim((string) ($this->post('motivo') ?? ''));

        if ($examenId <= 0 || $estudianteId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Solicitud inválida.'], 400);
        }
        if ($motivo === '') {
            $this->jsonResponse(['success' => false, 'message' => 'Indique el motivo (queda registrado para reclamos).'], 400);
        }

        // El docente solo puede tocar examenes de los que es dueño
        $examen = $this->examenes->getByIdForDocente($examenId, $docenteId);
        if (!$examen) {
            $this->jsonResponse(['success' => false, 'message' => 'Examen no encontrado o sin acceso.'], 404);
        }

        try {
            $stEst = $this->db->prepare(
                "SELECT id, username, nombres, apellidos, email
                 FROM usuarios WHERE id = :id AND rol = 'estudiante' LIMIT 1"
            );
            $stEst->execute([':id' => $estudianteId]);
            $estudiante = $stEst->fetch(PDO::FETCH_ASSOC);

            if (!$estudiante) {
                $this->jsonResponse(['success' => false, 'message' => 'Estudiante no encontrado.'], 404);
            }

            // Solo alumnos matriculados en el curso del examen pueden recibir extras.
            if (!$this->examenes->estaAsignadoAlCurso($estudianteId, (int) $examen['curso_id'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'El estudiante no está asignado al curso de este examen.',
                ], 400);
            }

            $this->examenes->otorgarIntentoExtra($examenId, $estudianteId, $docenteId, $motivo);

            $nuevoLimite = $this->examenes->getLimiteIntentos($examen, $estudianteId);
            $nombre = trim(($estudiante['apellidos'] ?? '') . ' ' . ($estudiante['nombres'] ?? ''));
            $nombre = $nombre !== '' ? $nombre : (string) $estudiante['username'];

            logError('Intento adicional otorgado', [
                'examen_id'      => $examenId,
                'examen'         => $examen['titulo'],
                'estudiante_id'  => $estudianteId,
                'estudiante'     => $nombre,
                'motivo'         => $motivo,
                'nuevo_limite'   => $nuevoLimite,
                'docente_id'     => $docenteId,
            ]);
        } catch (PDOException $e) {
            logError('Error otorgarIntento: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al registrar el intento adicional.'], 500);
            return;
        } catch (RuntimeException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            return;
        }

        // Notificacion al alumno. El intento ya quedo registrado aunque el
        // correo falle: se informa el resultado en lugar de ocultarlo.
        $correoEnviado = false;
        try {
            if (!empty($estudiante['email'])) {
                $mailer = new Mailer($this->db);
                if ($mailer->isConfigured()) {
                    $correoEnviado = $mailer->sendIntentoExtra(
                        (string) $estudiante['email'],
                        $nombre,
                        (string) $examen['titulo'],
                        (string) ($examen['curso_nombre'] ?? ''),
                        $nuevoLimite,
                        $motivo
                    );
                } else {
                    logError('SMTP no configurado, no se notificó el intento extra', [
                        'examen_id' => $examenId,
                        'estudiante_id' => $estudianteId,
                    ]);
                }
            } else {
                logError('Estudiante sin correo, no se notificó el intento extra', [
                    'examen_id' => $examenId,
                    'estudiante_id' => $estudianteId,
                ]);
            }
        } catch (Throwable $e) {
            logError('Error notificando intento extra: ' . $e->getMessage());
        }

        $this->jsonResponse([
            'success'        => true,
            'message'        => 'Intento adicional otorgado a ' . $nombre . '.',
            'correo_enviado' => $correoEnviado,
            'nuevo_limite'   => $nuevoLimite,
        ]);
    }

    /**
     * AJAX: retracta los intentos extra de un estudiante de este examen.
     * Nunca borra examen_intentos: el historial se conserva.
     */
    public function revocarIntento(): void
    {
        $docenteId = $this->requireDocente();

        $examenId     = (int) ($this->post('examen_id') ?? 0);
        $estudianteId = (int) ($this->post('estudiante_id') ?? 0);

        if ($examenId <= 0 || $estudianteId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Solicitud inválida.'], 400);
        }

        $examen = $this->examenes->getByIdForDocente($examenId, $docenteId);
        if (!$examen) {
            $this->jsonResponse(['success' => false, 'message' => 'Examen no encontrado o sin acceso.'], 404);
        }

        try {
            $retirado = $this->examenes->revocarIntentosExtra($examenId, $estudianteId);

            if (!$retirado) {
                $this->jsonResponse(['success' => false, 'message' => 'Este estudiante no tiene un intento adicional.'], 400);
            }

            logError('Intento adicional retirado', [
                'examen_id'     => $examenId,
                'examen'        => $examen['titulo'],
                'estudiante_id' => $estudianteId,
                'docente_id'    => $docenteId,
            ]);
        } catch (PDOException $e) {
            logError('Error revocarIntento: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al retirar el intento adicional.'], 500);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Se retiró el intento adicional. El historial no se modificó.',
        ]);
    }

    // ==========================================================
    //                        ESTUDIANTE
    // ==========================================================

    public function misExamenes(): void
    {
        $estudianteId = $this->requireEstudiante();
        $examenes = $this->examenes->listByEstudiante($estudianteId);

        $ahora = date('Y-m-d H:i:s');
        foreach ($examenes as &$e) {
            $e['sesion_activa_fin'] = $e['sesion_activa_fin'] ?: null;
            if ($e['tipo'] === 'en_vivo') {
                $e['disponible_ahora'] = !empty($e['sesion_activa_fin']) && $e['sesion_activa_fin'] > $ahora;
            } else {
                $dentroVentana = true;
                if (!empty($e['disponible_desde'])) {
                    $dentroVentana = $dentroVentana && $ahora >= $e['disponible_desde'];
                }
                if (!empty($e['disponible_hasta'])) {
                    $dentroVentana = $dentroVentana && $ahora <= $e['disponible_hasta'];
                }
                $e['disponible_ahora'] = $dentroVentana;
            }
            $e['agoto_intentos'] = (int) $e['mis_intentos']
                >= (int) $e['intentos_permitidos'] + (int) ($e['intentos_extra'] ?? 0);
            $e['limite_intentos'] = (int) $e['intentos_permitidos'] + (int) ($e['intentos_extra'] ?? 0);
        }
        unset($e);

        $pageTitle = 'Mis Exámenes';
        require __DIR__ . '/../Views/examenes/mis_examenes.php';
    }

    /** AJAX: segundos restantes del intento según el reloj del servidor */
    public function restante(): void
    {
        $estudianteId = $this->requireEstudiante();
        $intentoId = (int) ($_GET['intento_id'] ?? 0);

        $intento = $this->examenes->getIntentoParaEstudiante($intentoId, $estudianteId);
        if (!$intento) {
            $this->jsonResponse(['success' => false, 'message' => 'Intento no válido.'], 403);
        }

        if ($intento['estado'] !== 'en_curso') {
            $this->jsonResponse([
                'success' => true,
                'estado' => $intento['estado'],
                'restante_segundos' => 0,
            ]);
        }

        $sync = $this->examenes->sincronizarIntento($intento);
        if ($sync === null) {
            $this->jsonResponse([
                'success' => true,
                'estado' => 'expirado',
                'restante_segundos' => 0,
            ]);
        }

        $restante = max(0, strtotime($sync['deadline']) - time());
        $this->jsonResponse([
            'success' => true,
            'estado' => 'en_curso',
            'restante_segundos' => $restante,
            'servidor' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Inicia o retoma un intento y muestra el runner del examen */
    public function tomar(): void
    {
        $estudianteId = $this->requireEstudiante();
        $examenId = (int) ($_GET['examen_id'] ?? 0);

        try {
            $r = $this->examenes->iniciarIntento($examenId, $estudianteId);
        } catch (RuntimeException $e) {
            setFlash('error', $e->getMessage());
            redirect(BASE_URL . 'examen/misExamenes');
            return;
        }

        $intento = $r['intento'];
        $deadline = $r['deadline'];
        $sync = $this->examenes->sincronizarIntento($intento);

        if ($sync === null) {
            setFlash('warning', 'El tiempo del examen finalizó. Se registró lo respondido.');
            redirect(BASE_URL . 'examen/resultado?id=' . $intento['id']);
            return;
        }

        $examen = $this->examenes->getById($examenId);
        $preguntas = $this->examenes->getPreguntas($examenId); // sin marcar correctas
        $respuestas = $this->examenes->getRespuestasIntento((int) $intento['id']);
        $deadlineIso = date('Y-m-d\TH:i:s', strtotime($sync['deadline']));
        $pageTitle = $examen['titulo'];
        require __DIR__ . '/../Views/examenes/tomar.php';
    }

    /** AJAX: autoguardado de una respuesta */
    public function responder(): void
    {
        $estudianteId = $this->requireEstudiante();
        $intentoId = (int) ($this->post('intento_id') ?? 0);
        $preguntaId = (int) ($this->post('pregunta_id') ?? 0);
        $opcionId = isset($_POST['opcion_id']) && $_POST['opcion_id'] !== '' ? (int) $_POST['opcion_id'] : null;
        $respuestaTexto = isset($_POST['respuesta_texto']) ? (string) $_POST['respuesta_texto'] : null;
        $marcadas = isset($_POST['opcion_ids']) && is_array($_POST['opcion_ids'])
            ? array_map('intval', $_POST['opcion_ids'])
            : [];

        $intento = $this->examenes->getIntentoParaEstudiante($intentoId, $estudianteId);
        if (!$intento) {
            $this->jsonResponse(['success' => false, 'message' => 'Intento no válido.'], 403);
        }

        try {
            $this->examenes->guardarRespuesta($intentoId, $preguntaId, $opcionId, $respuestaTexto, $marcadas);
            $this->jsonResponse(['success' => true, 'message' => 'Respuesta guardada.']);
        } catch (RuntimeException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage(), 'expirado' => true], 400);
        } catch (Throwable $e) {
            logError('Error responder: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al guardar la respuesta.'], 500);
        }
    }

    /** Finaliza el intento voluntariamente */
    public function finalizarIntento(): void
    {
        $estudianteId = $this->requireEstudiante();
        $intentoId = (int) ($this->post('intento_id') ?? 0);

        try {
            $intento = $this->examenes->finalizarIntento($intentoId, $estudianteId);
            $this->jsonResponse([
                'success' => true,
                'message' => 'Examen enviado correctamente.',
                'redirect' => BASE_URL . 'examen/resultado?id=' . $intentoId,
            ]);
        } catch (RuntimeException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            logError('Error finalizarIntento: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error al finalizar el intento.'], 500);
        }
    }

    /** Resultado de un intento (estudiante dueño) */
    public function resultado(): void
    {
        $estudianteId = $this->requireEstudiante();
        $intentoId = (int) ($_GET['id'] ?? 0);

        $intento = $this->examenes->getIntentoParaEstudiante($intentoId, $estudianteId);
        if (!$intento) {
            setFlash('error', 'Intento no encontrado.');
            redirect(BASE_URL . 'examen/misExamenes');
            return;
        }

        // Si sigue en curso pero ya expiró, calificarlo ahora
        $sync = $this->examenes->sincronizarIntento($intento);
        if ($sync === null) {
            $intento = $this->examenes->getIntentoParaEstudiante($intentoId, $estudianteId);
        }

        $examen = $this->examenes->getById((int) $intento['examen_id']);
        $preguntas = $this->examenes->getPreguntas((int) $intento['examen_id'], true); // con correctas para revisión
        $respuestas = $this->examenes->getRespuestasIntento($intentoId);
        $totales = $this->examenes->totalesPreguntas((int) $intento['examen_id']);
        $pageTitle = 'Resultado - ' . $examen['titulo'];
        require __DIR__ . '/../Views/examenes/resultado.php';
    }
}
