<?php

declare(strict_types=1);

class Examen
{
    public const ESTADO_BORRADOR  = 0;
    public const ESTADO_ACTIVO    = 1;
    public const ESTADO_ELIMINADO = 2;

    /** Disponible desde que se activa, sin fechas (solo aplica a tipo personal) */
    public const MODO_INMEDIATO = 'inmediato';
    /** Disponible solo dentro de la ventana disponible_desde / disponible_hasta */
    public const MODO_PROGRAMADO = 'programado';

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ===================== EXÁMENES =====================

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, c.nombre AS criterio_nombre, c.porcentaje,
                    s.id AS semana_id, s.nombre AS semana_nombre, s.numero_semana,
                    cu.id AS curso_id, cu.nombre AS curso_nombre, cu.codigo AS curso_codigo
             FROM examenes e
             JOIN criterios c ON c.id = e.criterio_id
             JOIN semanas s ON s.id = c.semana_id
             JOIN cursos cu ON cu.id = s.curso_id
             WHERE e.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByIdForDocente(int $id, int $docente_id): ?array
    {
        $examen = $this->getById($id);
        if (!$examen || (int) $examen['creado_por'] !== $docente_id) {
            return null;
        }
        return $examen;
    }

    public function listByDocente(int $docente_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, c.nombre AS criterio_nombre, s.nombre AS semana_nombre, s.numero_semana,
                    cu.id AS curso_id, cu.nombre AS curso_nombre, cu.codigo AS curso_codigo,
                    (SELECT COUNT(*) FROM examen_preguntas p WHERE p.examen_id = e.id) AS total_preguntas,
                    (SELECT COUNT(*) FROM examen_intentos i WHERE i.examen_id = e.id AND i.estado IN ('finalizado','expirado')) AS intentos_finalizados,
                    (SELECT MAX(fin_en) FROM examen_sesiones es WHERE es.examen_id = e.id AND es.estado='activa') AS sesion_activa_fin
             FROM examenes e
             JOIN criterios c ON c.id = e.criterio_id
             JOIN semanas s ON s.id = c.semana_id
             JOIN cursos cu ON cu.id = s.curso_id
             WHERE e.estado IN (0, 1) AND e.creado_por = :docente_id
             ORDER BY e.estado DESC, cu.nombre ASC, s.numero_semana ASC, e.created_at DESC"
        );
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $modo = self::normalizarModo($data['modo_disponibilidad'] ?? null, $data['disponible_desde'] ?? null);
        $stmt = $this->db->prepare(
            "INSERT INTO examenes (criterio_id, titulo, descripcion, tipo, duracion_min,
                                   disponible_desde, disponible_hasta, intentos_permitidos,
                                   modo_disponibilidad, estado, creado_por)
             VALUES (:criterio_id, :titulo, :descripcion, :tipo, :duracion_min,
                     :disponible_desde, :disponible_hasta, :intentos_permitidos,
                     :modo, :estado, :creado_por)"
        );
        $stmt->execute([
            ':criterio_id' => $data['criterio_id'],
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':tipo' => $data['tipo'],
            ':duracion_min' => $data['duracion_min'],
            ':disponible_desde' => $data['disponible_desde'] ?? null,
            ':disponible_hasta' => $data['disponible_hasta'] ?? null,
            ':intentos_permitidos' => $data['intentos_permitidos'],
            ':modo' => $modo,
            ':estado' => $data['estado'] ?? self::ESTADO_BORRADOR,
            ':creado_por' => $data['creado_por'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    private static function normalizarModo(?string $modo, ?string $desde): string
    {
        if ($modo === self::MODO_PROGRAMADO || $modo === self::MODO_INMEDIATO) {
            return $modo;
        }
        // Sin indicación explícita se deduce de las fechas informadas
        return $desde !== null ? self::MODO_PROGRAMADO : self::MODO_INMEDIATO;
    }

    public function update(int $id, array $data): bool
    {
        $modo = self::normalizarModo($data['modo_disponibilidad'] ?? null, $data['disponible_desde'] ?? null);
        $stmt = $this->db->prepare(
            "UPDATE examenes SET criterio_id = :criterio_id, titulo = :titulo, descripcion = :descripcion,
                    tipo = :tipo, duracion_min = :duracion_min, disponible_desde = :disponible_desde,
                    disponible_hasta = :disponible_hasta, intentos_permitidos = :intentos_permitidos,
                    modo_disponibilidad = :modo
             WHERE id = :id"
        );
        return $stmt->execute([
            ':criterio_id' => $data['criterio_id'],
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':tipo' => $data['tipo'],
            ':duracion_min' => $data['duracion_min'],
            ':disponible_desde' => $data['disponible_desde'],
            ':disponible_hasta' => $data['disponible_hasta'],
            ':intentos_permitidos' => $data['intentos_permitidos'],
            ':modo' => $modo,
            ':id' => $id,
        ]);
    }

    public function delete(int $id, int $docente_id): bool
    {
        // Finalizar cualquier intento en curso sin generar nota (el examen se elimina)
        $stmt = $this->db->prepare(
            "UPDATE examen_intentos
             SET estado = 'expirado', finalizado_at = NOW()
             WHERE examen_id = :id AND estado = 'en_curso'"
        );
        $stmt->execute([':id' => $id]);

        $stmt = $this->db->prepare(
            "UPDATE examenes SET estado = :eliminado WHERE id = :id AND creado_por = :docente_id AND estado <> :eliminado2"
        );
        return $stmt->execute([
            ':eliminado' => self::ESTADO_ELIMINADO,
            ':id' => $id,
            ':docente_id' => $docente_id,
            ':eliminado2' => self::ESTADO_ELIMINADO,
        ]);
    }

    /**
     * Ajusta la disponibilidad de un examen personal.
     * MODO_INMEDIATO: sin fechas, el alumno lo toma cuando quiera desde que se activa.
     * MODO_PROGRAMADO: dentro de la ventana disponible_desde / disponible_hasta.
     */
    public function actualizarDisponibilidad(int $id, int $docente_id, string $modo, ?string $desde, ?string $hasta): void
    {
        $modo = self::normalizarModo($modo, $desde);
        $examen = $this->getByIdForDocente($id, $docente_id);
        if (!$examen) {
            throw new RuntimeException('Examen no encontrado.');
        }
        if ($examen['tipo'] !== 'personal') {
            throw new RuntimeException('En un examen en vivo la disponibilidad la define la sesión, no una fecha.');
        }
        if ($this->getSesionActiva($id)) {
            throw new RuntimeException('No se puede cambiar la disponibilidad durante una sesión en vivo activa.');
        }
        $this->validarVentana($modo, $desde, $hasta);

        if ($modo === self::MODO_INMEDIATO) {
            $desde = null;
            $hasta = null;
        }

        $stmt = $this->db->prepare(
            "UPDATE examenes SET modo_disponibilidad = :modo, disponible_desde = :desde, disponible_hasta = :hasta
             WHERE id = :id AND creado_por = :docente_id"
        );
        $stmt->execute([
            ':modo' => $modo,
            ':desde' => $desde,
            ':hasta' => $hasta,
            ':id' => $id,
            ':docente_id' => $docente_id,
        ]);
    }

    /** Intentos que un alumno tiene actualmente en curso (para advertir al cambiar la ventana) */
    public function countIntentosEnCurso(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM examen_intentos WHERE examen_id = :id AND estado = 'en_curso'"
        );
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    private function validarVentana(string $modo, ?string $desde, ?string $hasta): void
    {
        if ($modo !== self::MODO_PROGRAMADO) {
            return;
        }
        if ($desde === null) {
            throw new RuntimeException('Indique la fecha y hora de inicio de disponibilidad.');
        }
        if ($hasta !== null && $hasta <= $desde) {
            throw new RuntimeException('"Disponible hasta" debe ser posterior a "disponible desde".');
        }
    }

    /**
     * Publica el examen: recién desde este momento el estudiante puede verlo.
     * Solo el modo programado exige tener una ventana definida.
     */
    public function activar(int $id, int $docente_id): void
    {
        $examen = $this->getByIdForDocente($id, $docente_id);
        if (!$examen) {
            throw new RuntimeException('Examen no encontrado.');
        }
        if ((int) $examen['estado'] === self::ESTADO_ELIMINADO) {
            throw new RuntimeException('El examen fue eliminado.');
        }
        if ((int) $examen['estado'] === self::ESTADO_ACTIVO) {
            return;
        }
        if ($this->countPreguntas($id) === 0) {
            throw new RuntimeException('Agregue al menos una pregunta antes de activar el examen.');
        }
        if ($examen['tipo'] === 'personal' && $examen['modo_disponibilidad'] === self::MODO_PROGRAMADO) {
            $this->validarVentana(self::MODO_PROGRAMADO, $examen['disponible_desde'] ?: null, $examen['disponible_hasta'] ?: null);
            if (!empty($examen['disponible_hasta']) && $examen['disponible_hasta'] <= date('Y-m-d H:i:s')) {
                throw new RuntimeException('La fecha límite ya pasó. Ajústela antes de activar el examen.');
            }
        }

        $stmt = $this->db->prepare(
            "UPDATE examenes SET estado = :activo WHERE id = :id AND creado_por = :docente_id"
        );
        $stmt->execute([':activo' => self::ESTADO_ACTIVO, ':id' => $id, ':docente_id' => $docente_id]);
    }

    /**
     * Devuelve el examen a borrador: deja de ser visible para el estudiante.
     * No se permite si hay una sesión en vivo activa o intentos en curso.
     */
    public function desactivar(int $id, int $docente_id): void
    {
        $examen = $this->getByIdForDocente($id, $docente_id);
        if (!$examen) {
            throw new RuntimeException('Examen no encontrado.');
        }
        if ($this->getSesionActiva($id)) {
            throw new RuntimeException('No se puede desactivar mientras hay una sesión en vivo activa. Finalícela primero.');
        }
        if ($this->countIntentosEnCurso($id) > 0) {
            throw new RuntimeException('No se puede desactivar: hay estudiantes rendiendo el examen ahora.');
        }

        $stmt = $this->db->prepare(
            "UPDATE examenes SET estado = :borrador WHERE id = :id AND creado_por = :docente_id"
        );
        $stmt->execute([':borrador' => self::ESTADO_BORRADOR, ':id' => $id, ':docente_id' => $docente_id]);
    }

    /** Exámenes disponibles para un estudiante según sus cursos matriculados */
    public function listByEstudiante(int $estudiante_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, c.nombre AS criterio_nombre, s.nombre AS semana_nombre, s.numero_semana,
                    cu.id AS curso_id, cu.nombre AS curso_nombre, cu.codigo AS curso_codigo,
                    (SELECT COUNT(*) FROM examen_preguntas p WHERE p.examen_id = e.id) AS total_preguntas,
                    (SELECT COUNT(*) FROM examen_intentos i
                        WHERE i.examen_id = e.id AND i.estudiante_id = :estudiante_id2
                          AND i.estado IN ('finalizado','expirado')) AS mis_intentos,
                    (SELECT COALESCE(MAX(x.intentos_extra), 0) FROM examen_intentos_extra x
                        WHERE x.examen_id = e.id AND x.estudiante_id = :estudiante_id5) AS intentos_extra,
                    (SELECT MAX(i.nota) FROM examen_intentos i
                        WHERE i.examen_id = e.id AND i.estudiante_id = :estudiante_id3
                          AND i.estado IN ('finalizado','expirado')) AS mi_nota,
                    (SELECT MAX(es.fin_en) FROM examen_sesiones es
                        WHERE es.examen_id = e.id AND es.estado = 'activa') AS sesion_activa_fin,
                    (SELECT EXISTS(SELECT 1 FROM examen_intentos i2
                        WHERE i2.examen_id = e.id AND i2.estudiante_id = :estudiante_id4
                          AND i2.estado = 'en_curso')) AS tiene_intento_en_curso
             FROM examenes e
             JOIN criterios c ON c.id = e.criterio_id
             JOIN semanas s ON s.id = c.semana_id
             JOIN cursos cu ON cu.id = s.curso_id
             JOIN asignaciones a ON a.curso_id = cu.id AND a.estudiante_id = :estudiante_id
             WHERE e.estado = :estado
             ORDER BY cu.nombre ASC, s.numero_semana ASC, e.created_at DESC"
        );
        $stmt->execute([
            ':estado' => self::ESTADO_ACTIVO,
            ':estudiante_id' => $estudiante_id,
            ':estudiante_id2' => $estudiante_id,
            ':estudiante_id3' => $estudiante_id,
            ':estudiante_id4' => $estudiante_id,
            ':estudiante_id5' => $estudiante_id,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPreguntas(int $examen_id): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(puntos),0), COUNT(*) FROM examen_preguntas WHERE examen_id = :id");
        $stmt->execute([':id' => $examen_id]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return (int) $row[1];
    }

    public function totalesPreguntas(int $examen_id): array
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(puntos),0) AS puntos_total FROM examen_preguntas WHERE examen_id = :id");
        $stmt->execute([':id' => $examen_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['total' => (int) $row['total'], 'puntos_total' => (float) $row['puntos_total']];
    }

    // ===================== SESIONES EN VIVO =====================

    public function iniciarSesionEnVivo(int $examen_id, int $docente_id, int $duracion_min): int
    {
        $this->finalizarSesionesVencidas($examen_id);
        $stmt = $this->db->prepare(
            "INSERT INTO examen_sesiones (examen_id, iniciada_por, fin_en)
             SELECT :examen_id, :docente_id, DATE_ADD(NOW(), INTERVAL :dur MINUTE)
             FROM DUAL
             WHERE NOT EXISTS (SELECT 1 FROM examen_sesiones WHERE examen_id = :examen_id2 AND estado = 'activa')"
        );
        $stmt->execute([
            ':examen_id' => $examen_id,
            ':docente_id' => $docente_id,
            ':dur' => $duracion_min,
            ':examen_id2' => $examen_id,
        ]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Ya existe una sesión en vivo activa para este examen.');
        }
        return (int) $this->db->lastInsertId();
    }

    public function getSesionActiva(int $examen_id): ?array
    {
        $this->finalizarSesionesVencidas($examen_id);
        $stmt = $this->db->prepare(
            "SELECT * FROM examen_sesiones WHERE examen_id = :id AND estado = 'activa' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':id' => $examen_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function finalizarSesion(int $sesion_id, int $docente_id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE examen_sesiones SET estado = 'finalizada'
             WHERE id = :id AND iniciada_por = :docente_id AND estado = 'activa'"
        );
        $ok = $stmt->execute([':id' => $sesion_id, ':docente_id' => $docente_id]);
        if ($ok && $stmt->rowCount() > 0) {
            $this->expirarIntentosDeSesion($sesion_id);
        }
        return $ok;
    }

    private function finalizarSesionesVencidas(int $examen_id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE examen_sesiones SET estado = 'finalizada'
             WHERE examen_id = :id AND estado = 'activa' AND fin_en <= NOW()"
        );
        $stmt->execute([':id' => $examen_id]);
        if ($stmt->rowCount() > 0) {
            $st = $this->db->prepare(
                "SELECT id FROM examen_sesiones WHERE examen_id = :id AND estado = 'finalizada'"
            );
            $st->execute([':id' => $examen_id]);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $sid) {
                $this->expirarIntentosDeSesion((int) $sid);
            }
        }
    }

    private function expirarIntentosDeSesion(int $sesion_id): void
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM examen_intentos
             WHERE sesion_id = :sesion_id AND estado = 'en_curso'"
        );
        $stmt->execute([':sesion_id' => $sesion_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $intentoId) {
            $this->calificarIntento((int) $intentoId, 'expirado');
        }
    }

    // ===================== PREGUNTAS Y OPCIONES =====================

    public function getPreguntas(int $examen_id, bool $conCorrectas = false): array
    {
        $sql = "SELECT * FROM examen_preguntas WHERE examen_id = :id ORDER BY orden ASC, id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $examen_id]);
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($preguntas) {
            $stOp = $this->db->prepare(
                "SELECT o.* FROM examen_opciones o
                 JOIN examen_preguntas p ON p.id = o.pregunta_id
                 WHERE p.examen_id = :id ORDER BY o.id ASC"
            );
            $stOp->execute([':id' => $examen_id]);
            $opcionesPorPregunta = [];
            foreach ($stOp->fetchAll(PDO::FETCH_ASSOC) as $op) {
                if (!$conCorrectas) {
                    unset($op['es_correcta']);
                }
                $opcionesPorPregunta[(int) $op['pregunta_id']][] = $op;
            }
            foreach ($preguntas as &$p) {
                $p['opciones'] = $opcionesPorPregunta[(int) $p['id']] ?? [];
            }
            unset($p);
        }
        return $preguntas;
    }

    public function crearPregunta(int $examen_id, string $texto, float $puntos, array $opciones, string $tipo = 'opcion'): int
    {
        $tipo = $tipo === 'abierta' ? 'abierta' : 'opcion';
        if ($tipo === 'abierta') {
            $opciones = [];
        }

        $this->db->beginTransaction();
        try {
            $stOrden = $this->db->prepare("SELECT COALESCE(MAX(orden),0)+1 FROM examen_preguntas WHERE examen_id = :id");
            $stOrden->execute([':id' => $examen_id]);
            $orden = (int) $stOrden->fetchColumn();

            $st = $this->db->prepare(
                "INSERT INTO examen_preguntas (examen_id, texto, tipo, puntos, orden) VALUES (:examen_id, :texto, :tipo, :puntos, :orden)"
            );
            $st->execute([':examen_id' => $examen_id, ':texto' => $texto, ':tipo' => $tipo, ':puntos' => $puntos, ':orden' => $orden]);
            $preguntaId = (int) $this->db->lastInsertId();

            if ($tipo === 'opcion') {
                $stOp = $this->db->prepare(
                    "INSERT INTO examen_opciones (pregunta_id, texto, es_correcta) VALUES (:pregunta_id, :texto, :correcta)"
                );
                $hayCorrecta = false;
                foreach ($opciones as $op) {
                    $correcta = !empty($op['es_correcta']) ? 1 : 0;
                    if ($correcta) $hayCorrecta = true;
                    $stOp->execute([
                        ':pregunta_id' => $preguntaId,
                        ':texto' => trim((string) $op['texto']),
                        ':correcta' => $correcta,
                    ]);
                }
                if (!$hayCorrecta) {
                    throw new RuntimeException('Debe marcar al menos una opción como correcta.');
                }
            }
            $this->db->commit();
            return $preguntaId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizarPregunta(int $pregunta_id, int $examen_id, string $texto, float $puntos, array $opciones, string $tipo = 'opcion'): bool
    {
        $tipo = $tipo === 'abierta' ? 'abierta' : 'opcion';
        if ($tipo === 'abierta') {
            $opciones = [];
        }

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT id FROM examen_preguntas WHERE id = :id AND examen_id = :examen_id");
            $st->execute([':id' => $pregunta_id, ':examen_id' => $examen_id]);
            if (!$st->fetchColumn()) {
                throw new RuntimeException('La pregunta no pertenece a ese examen.');
            }

            $stUp = $this->db->prepare(
                "UPDATE examen_preguntas SET texto = :texto, tipo = :tipo, puntos = :puntos
                 WHERE id = :id AND examen_id = :examen_id"
            );
            $stUp->execute([
                ':texto'      => $texto,
                ':tipo'       => $tipo,
                ':puntos'     => $puntos,
                ':id'         => $pregunta_id,
                ':examen_id'  => $examen_id,
            ]);

            $stDel = $this->db->prepare("DELETE FROM examen_opciones WHERE pregunta_id = :pid");
            $stDel->execute([':pid' => $pregunta_id]);

            if ($tipo === 'opcion') {
                $stOp = $this->db->prepare(
                    "INSERT INTO examen_opciones (pregunta_id, texto, es_correcta) VALUES (:pid, :texto, :correcta)"
                );
                $hayCorrecta = false;
                foreach ($opciones as $op) {
                    $txtOp = trim((string) ($op['texto'] ?? ''));
                    if ($txtOp === '') {
                        continue;
                    }
                    $correcta = !empty($op['es_correcta']) ? 1 : 0;
                    if ($correcta) {
                        $hayCorrecta = true;
                    }
                    $stOp->execute([':pid' => $pregunta_id, ':texto' => $txtOp, ':correcta' => $correcta]);
                }
                if (!$hayCorrecta) {
                    throw new RuntimeException('Debe marcar al menos una opción como correcta.');
                }
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function eliminarPregunta(int $pregunta_id, int $examen_id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM examen_preguntas WHERE id = :id AND examen_id = :examen_id");
        return $stmt->execute([':id' => $pregunta_id, ':examen_id' => $examen_id]);
    }

    // ===================== INTENTOS =====================

    /**
     * Inicia (o retoma) el intento de un estudiante.
     * Devuelve ['intento'=>fila, 'deadline'=>Y-m-d H:i:s] o lanza excepción con mensaje.
     */
    public function iniciarIntento(int $examen_id, int $estudiante_id): array
    {
        $examen = $this->getById($examen_id);
        if (!$examen || (int) $examen['estado'] !== self::ESTADO_ACTIVO) {
            throw new RuntimeException('El examen no existe o no está disponible.');
        }

        $stAsig = $this->db->prepare("SELECT 1 FROM asignaciones WHERE curso_id = :curso_id AND estudiante_id = :estudiante_id");
        $stAsig->execute([':curso_id' => $examen['curso_id'], ':estudiante_id' => $estudiante_id]);
        if (!$stAsig->fetchColumn()) {
            throw new RuntimeException('No está matriculado en el curso de este examen.');
        }

        $stCount = $this->db->prepare("SELECT COUNT(*) FROM examen_preguntas WHERE examen_id = :id");
        $stCount->execute([':id' => $examen_id]);
        if ((int) $stCount->fetchColumn() === 0) {
            throw new RuntimeException('El examen aún no tiene preguntas.');
        }

        $ahora = date('Y-m-d H:i:s');

        if ($examen['tipo'] === 'en_vivo') {
            $sesion = $this->getSesionActiva($examen_id);
            if (!$sesion || $sesion['fin_en'] <= $ahora) {
                throw new RuntimeException('No hay una sesión en vivo activa para este examen.');
            }
            $deadline = $sesion['fin_en'];
        } else {
            if (!empty($examen['disponible_desde']) && $ahora < $examen['disponible_desde']) {
                throw new RuntimeException('El examen aún no está disponible.');
            }
            if (!empty($examen['disponible_hasta']) && $ahora > $examen['disponible_hasta']) {
                throw new RuntimeException('El examen ya no está disponible.');
            }
            $deadline = null;
        }

        // ¿Tiene intento en curso? Retomarlo.
        $stCurso = $this->db->prepare(
            "SELECT * FROM examen_intentos
             WHERE examen_id = :examen_id AND estudiante_id = :estudiante_id AND estado = 'en_curso'
             ORDER BY id DESC LIMIT 1"
        );
        $stCurso->execute([':examen_id' => $examen_id, ':estudiante_id' => $estudiante_id]);
        $intento = $stCurso->fetch(PDO::FETCH_ASSOC);

        if ($intento) {
            if ($examen['tipo'] === 'personal') {
                $limite = date('Y-m-d H:i:s', strtotime($intento['iniciado_at'] . " +{$examen['duracion_min']} minutes"));
                if (!empty($examen['disponible_hasta'])) {
                    $limite = min($limite, $examen['disponible_hasta']);
                }
                if ($limite <= $ahora) {
                    $this->calificarIntento((int) $intento['id'], 'expirado');
                    $intento = null;
                } else {
                    return ['intento' => $intento, 'deadline' => $limite];
                }
            } else {
                if ($deadline <= $ahora) {
                    $this->calificarIntento((int) $intento['id'], 'expirado');
                    $intento = null;
                } else {
                    return ['intento' => $intento, 'deadline' => $deadline];
                }
            }
        }

        // Límite de intentos
        $stUsados = $this->db->prepare(
            "SELECT COUNT(*) FROM examen_intentos
             WHERE examen_id = :examen_id AND estudiante_id = :estudiante_id AND estado IN ('finalizado','expirado')"
        );
        $stUsados->execute([':examen_id' => $examen_id, ':estudiante_id' => $estudiante_id]);
        // fetchColumn() solo se puede leer una vez: se guarda el conteo para reutilizarlo
        $intentosUsados = (int) $stUsados->fetchColumn();
        $limiteIntentos = $this->getLimiteIntentos($examen, $estudiante_id);
        if ($intentosUsados >= $limiteIntentos) {
            throw new RuntimeException('Ya utilizó todos sus intentos para este examen.');
        }

        $numero = $intentosUsados + 1;

        $stIns = $this->db->prepare(
            "INSERT INTO examen_intentos (examen_id, estudiante_id, numero_intento, sesion_id, iniciado_at)
             VALUES (:examen_id, :estudiante_id, :numero, :sesion_id, NOW())"
        );
        $stIns->execute([
            ':examen_id' => $examen_id,
            ':estudiante_id' => $estudiante_id,
            ':numero' => $numero,
            ':sesion_id' => $sesion['id'] ?? null,
        ]);
        $intentoId = (int) $this->db->lastInsertId();

        if ($examen['tipo'] === 'personal') {
            $limite = date('Y-m-d H:i:s', strtotime("$ahora +{$examen['duracion_min']} minutes"));
            if (!empty($examen['disponible_hasta'])) {
                $limite = min($limite, $examen['disponible_hasta']);
            }
        } else {
            $limite = $deadline;
        }

        $intento = [
            'id' => $intentoId,
            'examen_id' => $examen_id,
            'estudiante_id' => $estudiante_id,
            'estado' => 'en_curso',
            'iniciado_at' => $ahora,
        ];
        return ['intento' => $intento, 'deadline' => $limite];
    }

    public function getIntentoParaEstudiante(int $intento_id, int $estudiante_id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM examen_intentos WHERE id = :id AND estudiante_id = :estudiante_id"
        );
        $stmt->execute([':id' => $intento_id, ':estudiante_id' => $estudiante_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Deadline real de un intento según su tipo. Null si ya no aplica. */
    public function deadlineDeIntento(array $intento): ?string
    {
        $examen = $this->getById((int) $intento['examen_id']);
        if (!$examen) return null;

        if ($examen['tipo'] === 'en_vivo') {
            if (!empty($intento['sesion_id'])) {
                $st = $this->db->prepare("SELECT fin_en FROM examen_sesiones WHERE id = :id");
                $st->execute([':id' => $intento['sesion_id']]);
                $fin = $st->fetchColumn();
                return $fin ?: null;
            }
            $sesion = $this->getSesionActiva($examen['id']);
            return $sesion['fin_en'] ?? null;
        }

        $limite = date('Y-m-d H:i:s', strtotime($intento['iniciado_at'] . " +{$examen['duracion_min']} minutes"));
        if (!empty($examen['disponible_hasta']) && $examen['disponible_hasta'] < $limite) {
            $limite = $examen['disponible_hasta'];
        }
        return $limite;
    }

    /**
     * Guarda la respuesta de una pregunta dentro del intento.
     * Valida que el intento siga vigente (control por servidor).
     * Soporta opción única ($opcion_id), selección múltiple ($marcadas) y respuesta escrita ($texto).
     * En selección múltiple, la respuesta es correcta solo si marca exactamente las correctas.
     */
    public function guardarRespuesta(int $intento_id, int $pregunta_id, ?int $opcion_id, ?string $texto = null, array $marcadas = []): bool
    {
        $stInt = $this->db->prepare("SELECT * FROM examen_intentos WHERE id = :id");
        $stInt->execute([':id' => $intento_id]);
        $intento = $stInt->fetch(PDO::FETCH_ASSOC);
        if (!$intento || $intento['estado'] !== 'en_curso') {
            throw new RuntimeException('El intento no está activo.');
        }

        $deadline = $this->deadlineDeIntento($intento);
        if ($deadline !== null && date('Y-m-d H:i:s') > $deadline) {
            $this->calificarIntento($intento_id, 'expirado');
            throw new RuntimeException('El tiempo del examen finalizó.');
        }

        // Validar que la pregunta pertenezca al examen
        $stQ = $this->db->prepare(
            "SELECT id, tipo FROM examen_preguntas WHERE id = :pregunta_id AND examen_id = :examen_id"
        );
        $stQ->execute([':pregunta_id' => $pregunta_id, ':examen_id' => $intento['examen_id']]);
        $pregunta = $stQ->fetch(PDO::FETCH_ASSOC);
        if (!$pregunta) {
            throw new RuntimeException('La pregunta no pertenece a este examen.');
        }

        if ($pregunta['tipo'] === 'abierta') {
            $correcta = 0;
            $opcionId = null;
            $respuestaTexto = trim((string) $texto);
            if ($respuestaTexto === '') {
                $respuestaTexto = null;
            } elseif (mb_strlen($respuestaTexto) > 150) {
                throw new RuntimeException('La respuesta no puede exceder los 150 caracteres.');
            }
        } else {
            // Opciones correctas de la pregunta
            $stCorrectas = $this->db->prepare(
                "SELECT id FROM examen_opciones WHERE pregunta_id = :pregunta_id AND es_correcta = 1"
            );
            $stCorrectas->execute([':pregunta_id' => $pregunta_id]);
            $idsCorrectos = array_map('intval', $stCorrectas->fetchAll(PDO::FETCH_COLUMN));

            if ($marcadas !== []) {
                $seleccionadas = array_map('intval', $marcadas);
            } elseif ($opcion_id !== null) {
                $seleccionadas = [$opcion_id];
            } else {
                $seleccionadas = [];
            }

            // Validar que todas las seleccionadas pertenezcan a la pregunta
            if ($seleccionadas !== []) {
                $placeholders = implode(',', array_fill(0, count($seleccionadas), '?'));
                $stVal = $this->db->prepare(
                    "SELECT COUNT(*) FROM examen_opciones
                     WHERE pregunta_id = :pid AND id IN ($placeholders)"
                );
                $stVal->execute(array_merge([':pid' => $pregunta_id], $seleccionadas));
                if ((int) $stVal->fetchColumn() !== count($seleccionadas)) {
                    throw new RuntimeException('Una de las opciones no pertenece a esta pregunta.');
                }
            }

            sort($seleccionadas);
            sort($idsCorrectos);
            $correcta = ($seleccionadas === $idsCorrectos) ? 1 : 0;
            $respuestaTexto = null;

            $stClear = $this->db->prepare(
                "UPDATE examen_respuestas SET correcta = :correcta, opcion_id = :opcion_id,
                        opciones_marcadas = :marcadas, respuesta_texto = NULL, puntos_otorgados = NULL,
                        respondida_at = NOW()
                 WHERE intento_id = :intento_id AND pregunta_id = :pregunta_id"
            );
            $stClear->execute([
                ':correcta' => $correcta,
                ':opcion_id' => $seleccionadas[0] ?? null,
                ':marcadas' => $seleccionadas !== [] ? json_encode($seleccionadas) : null,
                ':intento_id' => $intento_id,
                ':pregunta_id' => $pregunta_id,
            ]);
            return true;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO examen_respuestas (intento_id, pregunta_id, opcion_id, respuesta_texto, correcta, respondida_at)
             VALUES (:intento_id, :pregunta_id, :opcion_id, :respuesta_texto, :correcta, NOW())
             ON DUPLICATE KEY UPDATE opcion_id = VALUES(opcion_id), respuesta_texto = VALUES(respuesta_texto),
                                     correcta = VALUES(correcta), respondida_at = NOW()"
        );
        return $stmt->execute([
            ':intento_id' => $intento_id,
            ':pregunta_id' => $pregunta_id,
            ':opcion_id' => $opcionId,
            ':respuesta_texto' => $respuestaTexto,
            ':correcta' => $correcta,
        ]);
    }

    /** Recalcula puntaje/nota del intento y actualiza el registro y la calificación del criterio. */
    private function actualizarNotaIntento(int $intento_id): array
    {
        $stInt = $this->db->prepare("SELECT * FROM examen_intentos WHERE id = :id");
        $stInt->execute([':id' => $intento_id]);
        $intento = $stInt->fetch(PDO::FETCH_ASSOC);
        if (!$intento) return [];

        $totales = $this->totalesPreguntas((int) $intento['examen_id']);
        // Puntaje = puntos otorgados por el docente si existen;
        // si no, los puntos de la pregunta cuando la opción fue correcta.
        $stPts = $this->db->prepare(
            "SELECT COALESCE(SUM(
                        COALESCE(r.puntos_otorgados,
                                 CASE WHEN r.correcta = 1 THEN p.puntos ELSE 0 END)
                     ), 0)
             FROM examen_respuestas r
             JOIN examen_preguntas p ON p.id = r.pregunta_id
             WHERE r.intento_id = :intento_id"
        );
        $stPts->execute([':intento_id' => $intento_id]);
        $puntaje = (float) $stPts->fetchColumn();

        $nota = $totales['puntos_total'] > 0
            ? round(($puntaje / $totales['puntos_total']) * 20, 2)
            : 0.0;

        $stUp = $this->db->prepare(
            "UPDATE examen_intentos SET puntaje = :puntaje, nota = :nota WHERE id = :id"
        );
        $stUp->execute([':puntaje' => $puntaje, ':nota' => $nota, ':id' => $intento_id]);

        $stCrit = $this->db->prepare("SELECT criterio_id FROM examenes WHERE id = :id");
        $stCrit->execute([':id' => $intento['examen_id']]);
        $criterioId = $stCrit->fetchColumn();

        if ($criterioId) {
            $stCal = $this->db->prepare(
                "INSERT INTO calificaciones (estudiante_id, criterio_id, nota)
                 VALUES (:estudiante_id, :criterio_id, :nota)
                 ON DUPLICATE KEY UPDATE nota = GREATEST(nota, VALUES(nota))"
            );
            $stCal->execute([
                ':estudiante_id' => $intento['estudiante_id'],
                ':criterio_id' => $criterioId,
                ':nota' => $nota,
            ]);
        }
        return ['puntaje' => $puntaje, 'nota' => $nota];
    }

    /**
     * Docente asigna puntos a una respuesta escrita y se recalcula la nota.
     */
    public function calificarRespuestaAbierta(int $intento_id, int $pregunta_id, float $puntosOtorgados): array
    {
        $stmt = $this->db->prepare(
            "UPDATE examen_respuestas r
             JOIN examen_preguntas p ON p.id = r.pregunta_id
             SET r.puntos_otorgados = :puntos
             WHERE r.intento_id = :intento_id AND r.pregunta_id = :pregunta_id
               AND p.tipo = 'abierta'"
        );
        $stmt->execute([
            ':puntos' => max(0, $puntosOtorgados),
            ':intento_id' => $intento_id,
            ':pregunta_id' => $pregunta_id,
        ]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('No hay respuesta escrita que calificar para esa pregunta.');
        }
        return $this->actualizarNotaIntento($intento_id);
    }

    /** Califica el intento, calcula nota sobre 20 y registra la calificación del criterio. */
    public function calificarIntento(int $intento_id, string $estadoFinal = 'finalizado'): array
    {
        $stInt = $this->db->prepare("SELECT * FROM examen_intentos WHERE id = :id FOR UPDATE");
        $this->db->beginTransaction();
        try {
            $stInt->execute([':id' => $intento_id]);
            $intento = $stInt->fetch(PDO::FETCH_ASSOC);
            if (!$intento || $intento['estado'] !== 'en_curso') {
                $this->db->rollBack();
                $st2 = $this->db->prepare("SELECT * FROM examen_intentos WHERE id = :id");
                $st2->execute([':id' => $intento_id]);
                return $st2->fetch(PDO::FETCH_ASSOC) ?: [];
            }

            $resultado = $this->actualizarNotaIntento($intento_id);

            $stUp = $this->db->prepare(
                "UPDATE examen_intentos SET estado = :estado, finalizado_at = NOW() WHERE id = :id"
            );
            $stUp->execute([':estado' => $estadoFinal, ':id' => $intento_id]);

            $this->db->commit();
            return ['puntaje' => $resultado['puntaje'] ?? 0, 'nota' => $resultado['nota'] ?? 0, 'estado' => $estadoFinal];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function finalizarIntento(int $intento_id, int $estudiante_id): array
    {
        $intento = $this->getIntentoParaEstudiante($intento_id, $estudiante_id);
        if (!$intento) {
            throw new RuntimeException('Intento no encontrado.');
        }
        if ($intento['estado'] !== 'en_curso') {
            return $intento;
        }
        $deadline = $this->deadlineDeIntento($intento);
        $estado = ($deadline !== null && date('Y-m-d H:i:s') > $deadline) ? 'expirado' : 'finalizado';
        $this->calificarIntento($intento_id, $estado);
        return $this->getIntentoParaEstudiante($intento_id, $estudiante_id) ?? $intento;
    }

    /** Asegura estado correcto del intento antes de mostrarlo; devuelve [intento, deadline] o null si cerró. */
    public function sincronizarIntento(array $intento): ?array
    {
        if ($intento['estado'] !== 'en_curso') {
            return ['intento' => $intento, 'deadline' => null];
        }
        $deadline = $this->deadlineDeIntento($intento);
        if ($deadline !== null && date('Y-m-d H:i:s') > $deadline) {
            $this->calificarIntento((int) $intento['id'], 'expirado');
            return null;
        }
        return ['intento' => $intento, 'deadline' => $deadline];
    }

    // ===================== RESULTADOS / VIGILANCIA =====================

    public function getRespuestasIntento(int $intento_id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM examen_respuestas WHERE intento_id = :id");
        $stmt->execute([':id' => $intento_id]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $map[(int) $r['pregunta_id']] = $r;
        }
        return $map;
    }

    public function getIntentoById(int $intento_id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, CONCAT(u.apellidos, ' ', u.nombres) AS estudiante_nombre
             FROM examen_intentos i
             JOIN usuarios u ON u.id = i.estudiante_id
             WHERE i.id = :id"
        );
        $stmt->execute([':id' => $intento_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getProgresoExamen(int $examen_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, CONCAT(u.apellidos, ' ', u.nombres) AS estudiante_nombre
             FROM examen_intentos i
             JOIN usuarios u ON u.id = i.estudiante_id
             WHERE i.examen_id = :id
             ORDER BY FIELD(i.estado,'en_curso','finalizado','expirado'), i.nota DESC, u.apellidos ASC"
        );
        $stmt->execute([':id' => $examen_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ===================== INTENTOS EXTRA POR ALUMNO =====================
    // El docente puede otorgar intentos adicionales a un estudiante concreto
    // sin tocar examenes.intentos_permitidos (que aplica a todo el curso) y
    // SIN borrar el historial: el intento anterior se conserva y la nota que
    // se muestra sigue siendo la mejor (MAX(nota)).

    /** Intentos extra que le otorgó el docente a este alumno en este examen */
    public function getIntentosExtra(int $examen_id, int $estudiante_id): int
    {
        $st = $this->db->prepare(
            "SELECT intentos_extra FROM examen_intentos_extra
             WHERE examen_id = :examen_id AND estudiante_id = :estudiante_id LIMIT 1"
        );
        $st->execute([':examen_id' => $examen_id, ':estudiante_id' => $estudiante_id]);
        $valor = $st->fetchColumn();
        return $valor === false ? 0 : max(0, (int) $valor);
    }

    /**
     * El alumno debe estar asignado al curso al que pertenece el examen.
     * Sin esto un docente podria otorgar intentos a estudiantes de otros
     * cursos, que jamas verian ese examen.
     */
    public function estaAsignadoAlCurso(int $estudiante_id, int $curso_id): bool
    {
        if ($estudiante_id <= 0 || $curso_id <= 0) {
            return false;
        }
        $st = $this->db->prepare(
            "SELECT 1 FROM asignaciones
             WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id
             LIMIT 1"
        );
        $st->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
        return $st->fetchColumn() !== false;
    }

    /**
     * Limite efectivo de intentos de un alumno en un examen:
     * intentos_permitidos del examen + los extras que le otorgó el docente.
     *
     * @param array $examen Fila de examenes (de getById o similar)
     */
    public function getLimiteIntentos(array $examen, int $estudiante_id): int
    {
        return (int) $examen['intentos_permitidos']
            + $this->getIntentosExtra((int) $examen['id'], $estudiante_id);
    }

    /**
     * Otorga UN intento adicional a un alumno. Es la ultima instancia: como
     * el tope es 1, un segundo otorgamiento se rechaza en vez de acumular.
     * Gracias a eso la fila es la bitacora completa (quien, cuando, por que)
     * y nunca se pisa un motivo anterior.
     *
     * @throws RuntimeException si el alumno ya tiene el intento adicional
     */
    public function otorgarIntentoExtra(int $examen_id, int $estudiante_id, int $docente_id, string $motivo): void
    {
        if ($this->getIntentosExtra($examen_id, $estudiante_id) > 0) {
            throw new RuntimeException('Este estudiante ya tiene un intento adicional en este examen.');
        }

        // Un doble clic nunca debe dejar 2: el duplicado se queda en 1 y
        // conserva el motivo del primer otorgamiento.
        $st = $this->db->prepare(
            "INSERT INTO examen_intentos_extra (examen_id, estudiante_id, intentos_extra, motivo, otorgado_por)
             VALUES (:examen_id, :estudiante_id, 1, :motivo, :docente_id)
             ON DUPLICATE KEY UPDATE intentos_extra = 1"
        );
        $st->execute([
            ':examen_id'     => $examen_id,
            ':estudiante_id' => $estudiante_id,
            ':motivo'        => $motivo !== '' ? $motivo : null,
            ':docente_id'    => $docente_id,
        ]);
    }

    /**
     * Retira el intento adicional de un alumno. Nunca toca examen_intentos:
     * el historial del alumno se conserva intacto.
     *
     * @return bool true si habia un intento adicional que retirar
     */
    public function revocarIntentosExtra(int $examen_id, int $estudiante_id): bool
    {
        $del = $this->db->prepare(
            "DELETE FROM examen_intentos_extra
             WHERE examen_id = :examen_id AND estudiante_id = :estudiante_id
                AND intentos_extra > 0"
        );
        $del->execute([':examen_id' => $examen_id, ':estudiante_id' => $estudiante_id]);
        return $del->rowCount() > 0;
    }

    /**
     * Resumen por alumno (una fila por estudiante, no por intento) para la
     * pantalla de resultados del docente: cuantos intentos uso, cual es su
     * limite efectivo y si ya se le otorgaron extras.
     */
    public function getResumenPorEstudiante(int $examen_id): array
    {
        // ultimo_iniciado_at es correcto (MAX = intento mas reciente). No se
        // debe agregar MAX(finalizado_at): al ser agregados independientes
        // podrian venir de intentos distintos.
        $stmt = $this->db->prepare(
            "SELECT i.estudiante_id,
                    CONCAT(u.apellidos, ', ', u.nombres) AS estudiante_nombre,
                    u.username,
                    COUNT(*) AS intentos_totales,
                    SUM(CASE WHEN i.estado IN ('finalizado','expirado') THEN 1 ELSE 0 END) AS intentos_usados,
                    SUM(CASE WHEN i.estado = 'en_curso' THEN 1 ELSE 0 END) AS intentos_en_curso,
                    MAX(CASE WHEN i.estado IN ('finalizado','expirado') THEN i.nota END) AS mejor_nota,
                    MAX(i.iniciado_at)   AS ultimo_iniciado_at,
                    COALESCE(MAX(x.intentos_extra), 0) AS intentos_extra,
                    MAX(x.motivo)   AS motivo_extra,
                    MAX(x.creado_en) AS extra_creado_en
             FROM examen_intentos i
             JOIN usuarios u ON u.id = i.estudiante_id
             LEFT JOIN examen_intentos_extra x
                    ON x.examen_id = i.examen_id AND x.estudiante_id = i.estudiante_id
             WHERE i.examen_id = :id
             GROUP BY i.estudiante_id, u.apellidos, u.nombres, u.username
             ORDER BY u.apellidos ASC, u.nombres ASC"
        );
        $stmt->execute([':id' => $examen_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSesionActivaForDocente(int $examen_id, int $docente_id): ?array
    {
        $sesion = $this->getSesionActiva($examen_id);
        if (!$sesion || (int) $sesion['iniciada_por'] !== $docente_id) {
            return null;
        }
        return $sesion;
    }
}
