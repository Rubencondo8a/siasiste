<?php

declare(strict_types=1);

class Asistencia
{
    private PDO $db;

    public const ESTADOS = ['P', 'T', 'A', 'J'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getByCursoYFecha(int $curso_id, string $fecha, ?int $horario_id = null, ?int $registrado_por = null): array
    {
        $sql = '
            SELECT estudiante_id, estado, observacion
            FROM asistencias
            WHERE curso_id = :curso_id AND fecha = :fecha
        ';
        $params = [':curso_id' => $curso_id, ':fecha' => $fecha];
        if ($horario_id !== null) {
            $sql .= ' AND horario_id = :horario_id';
            $params[':horario_id'] = $horario_id;
        }
        if ($registrado_por !== null) {
            $sql .= ' AND registrado_por = :registrado_por';
            $params[':registrado_por'] = $registrado_por;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['estudiante_id']] = [
                'estado'      => $row['estado'],
                'observacion' => $row['observacion'] ?? '',
            ];
        }
        return $map;
    }

    public function getByCursoYRangoFechas(int $curso_id, string $fecha_inicio, string $fecha_fin, int $docente_id): array
    {
        $sql = '
            SELECT estudiante_id, fecha, horario_id, estado, observacion
            FROM asistencias
            WHERE curso_id = :curso_id AND registrado_por = :registrado_por
              AND fecha BETWEEN :fecha_inicio AND :fecha_fin
            ORDER BY fecha DESC, id DESC
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':curso_id' => $curso_id,
            ':registrado_por' => $docente_id,
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin,
        ]);
        $ordered = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ordered[] = $row;
        }
        $map = [];
        foreach ($ordered as $row) {
            $sid = (int) $row['estudiante_id'];
            if (!isset($map[$sid])) {
                $map[$sid] = [
                    'estado'      => $row['estado'],
                    'observacion' => $row['observacion'] ?? '',
                    'fecha'       => $row['fecha'],
                ];
            }
        }
        return $map;
    }

    public function saveAll(int $curso_id, string $fecha, array $estados, int $docente_id, array $observaciones = [], ?int $horario_id = null): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO asistencias (curso_id, estudiante_id, fecha, horario_id, estado, observacion, registrado_por)
            VALUES (:curso_id, :estudiante_id, :fecha, :horario_id, :estado, :observacion, :registrado_por)
            ON DUPLICATE KEY UPDATE estado = VALUES(estado), observacion = VALUES(observacion), registrado_por = VALUES(registrado_por)
        ');

        $count = 0;
        foreach ($estados as $estudiante_id => $estado) {
            $obs = trim((string) ($observaciones[$estudiante_id] ?? ''));
            if ($estado !== 'J') {
                $obs = '';
            }
            $stmt->execute([
                ':curso_id' => $curso_id,
                ':estudiante_id' => (int) $estudiante_id,
                ':fecha' => $fecha,
                ':horario_id' => $horario_id,
                ':estado' => $estado,
                ':observacion' => $obs !== '' ? $obs : null,
                ':registrado_por' => $docente_id,
            ]);
            $count++;
        }
        return $count;
    }

    public function getJustificacionesByCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT estudiante_id, fecha, observacion
            FROM asistencias
            WHERE curso_id = :curso_id AND estado = \'J\'
            ORDER BY fecha ASC
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetallePorCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id AS curso_id, c.nombre AS curso_nombre, c.codigo, c.ciclo,
                   a.fecha, h.hora_inicio, h.hora_fin,
                   u.id AS estudiante_id, u.apellidos, u.nombres, u.dni,
                   a.estado, a.observacion, u2.username AS registrado_por
            FROM asistencias a
            INNER JOIN cursos c ON c.id = a.curso_id AND c.id = :curso_id
            LEFT JOIN horarios h ON h.id = a.horario_id
            INNER JOIN usuarios u ON u.id = a.estudiante_id
            LEFT JOIN usuarios u2 ON u2.id = a.registrado_por
            ORDER BY a.fecha ASC, h.hora_inicio ASC, u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResumenPorDocente(int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id, c.nombre, c.codigo, c.ciclo, p.nombre AS periodo_nombre,
                   (SELECT COUNT(*) FROM asignaciones a WHERE a.curso_id = c.id) AS total_estudiantes,
                   (SELECT COUNT(DISTINCT a2.fecha) FROM asistencias a2 WHERE a2.curso_id = c.id) AS dias_registrados,
                   (SELECT COUNT(DISTINCT a3.estudiante_id) FROM asistencias a3 WHERE a3.curso_id = c.id AND a3.fecha = CURDATE()) AS registrados_hoy
            FROM cursos c
            INNER JOIN periodos p ON p.id = c.periodo_id
            WHERE c.docente_id = :docente_id AND c.estado = 1 AND p.estado = 1
            ORDER BY p.nombre ASC, c.nombre ASC
        ');
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResumenPorEstudiante(int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id AS curso_id, c.nombre AS curso_nombre, c.codigo, c.ciclo,
                   COALESCE(SUM(a.estado = \'P\'), 0) AS presentes,
                   COALESCE(SUM(a.estado = \'T\'), 0) AS tardanzas,
                   COALESCE(SUM(a.estado = \'A\'), 0) AS ausencias,
                   COALESCE(SUM(a.estado = \'J\'), 0) AS justificados,
                   COUNT(a.id) AS total
            FROM cursos c
            INNER JOIN asignaciones ec ON ec.curso_id = c.id AND ec.estudiante_id = :estudiante_id_a
            LEFT JOIN asistencias a ON a.curso_id = c.id AND a.estudiante_id = :estudiante_id_b
            WHERE c.estado = 1
            GROUP BY c.id, c.nombre, c.codigo, c.ciclo
            ORDER BY c.nombre ASC
        ');
        $stmt->execute([':estudiante_id_a' => $estudiante_id, ':estudiante_id_b' => $estudiante_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistorialPorEstudianteYCurso(int $estudiante_id, int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT a.fecha, a.estado, a.observacion
            FROM asistencias a
            WHERE a.estudiante_id = :estudiante_id AND a.curso_id = :curso_id
            ORDER BY a.fecha ASC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Estudiantes cuya inasistencia supera el umbral, por unidad didactica
     * (curso) y alumno. La inasistencia es solo la falta de asistencia (A)
     * sobre el total de registros del alumno en ese curso.
     */
    public function getAlertaInasistencia(int $docente_id, float $umbral = 30.0): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id AS curso_id,
                   c.nombre AS curso_nombre,
                   c.codigo AS curso_codigo,
                   u.id AS estudiante_id,
                   u.apellidos, u.nombres, u.dni,
                   COALESCE(SUM(a.estado = \'P\'), 0) AS presentes,
                   COALESCE(SUM(a.estado = \'T\'), 0) AS tardanzas,
                   COALESCE(SUM(a.estado = \'A\'), 0) AS ausencias,
                   COALESCE(SUM(a.estado = \'J\'), 0) AS justificados,
                   COUNT(a.id) AS total
            FROM cursos c
            INNER JOIN asignaciones ec ON ec.curso_id = c.id
            INNER JOIN usuarios u ON u.id = ec.estudiante_id AND u.rol = \'estudiante\' AND u.estado = 1
            INNER JOIN asistencias a ON a.estudiante_id = u.id AND a.curso_id = c.id
            WHERE c.docente_id = :docente_id
            GROUP BY c.id, c.nombre, c.codigo, u.id, u.apellidos, u.nombres, u.dni
            HAVING total > 0 AND (SUM(a.estado = \'A\') * 100.0 / COUNT(a.id)) > :umbral
            ORDER BY (SUM(a.estado = \'A\') / COUNT(a.id)) DESC, u.apellidos ASC, u.nombres ASC
        ');
        $stmt->bindValue(':docente_id', $docente_id, PDO::PARAM_INT);
        $stmt->bindValue(':umbral', $umbral, PDO::PARAM_STR);
        $stmt->execute();

        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($filas as &$f) {
            $f['porcentaje'] = (float) ($f['ausencias'] / $f['total'] * 100);
        }
        unset($f);

        return $filas;
    }

    public function getResumenPorCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id AS estudiante_id, u.apellidos, u.nombres, u.dni,
                   COALESCE(SUM(a.estado = \'P\'), 0) AS presentes,
                   COALESCE(SUM(a.estado = \'T\'), 0) AS tardanzas,
                   COALESCE(SUM(a.estado = \'A\'), 0) AS ausencias,
                   COALESCE(SUM(a.estado = \'J\'), 0) AS justificados,
                   COUNT(a.id) AS total
            FROM usuarios u
            INNER JOIN asignaciones ec ON ec.estudiante_id = u.id AND ec.curso_id = :curso_id_a
            LEFT JOIN asistencias a ON a.estudiante_id = u.id AND a.curso_id = :curso_id_b
            WHERE u.rol = \'estudiante\' AND u.estado = 1
            GROUP BY u.id, u.apellidos, u.nombres, u.dni
            ORDER BY u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':curso_id_a' => $curso_id, ':curso_id_b' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Detalle dia a dia de un alumno en una unidad, solo si ese curso
     * pertenece al docente indicado. Devuelve cabecera con el resumen
     * y el listado de sesiones para ver donde se concentran las faltas.
     */
    public function getDetalleInasistencia(int $docente_id, int $estudiante_id, int $curso_id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT c.id AS curso_id, c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   u.id AS estudiante_id, u.apellidos, u.nombres, u.dni,
                   u.email
            FROM cursos c
            INNER JOIN usuarios u ON u.id = :estudiante_id
                 AND u.rol = \'estudiante\' AND u.estado = 1
            WHERE c.id = :curso_id AND c.docente_id = :docente_id
        ');
        $stmt->execute([
            ':docente_id'    => $docente_id,
            ':estudiante_id' => $estudiante_id,
            ':curso_id'      => $curso_id,
        ]);
        $cabecera = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cabecera) {
            return null;
        }

        $stmt = $this->db->prepare('
            SELECT a.id, a.fecha, a.estado, a.observacion, a.created_at,
                   h.hora_inicio, h.hora_fin
            FROM asistencias a
            LEFT JOIN horarios h ON h.id = a.horario_id
            WHERE a.estudiante_id = :estudiante_id AND a.curso_id = :curso_id
            ORDER BY a.fecha DESC, h.hora_inicio DESC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
        $sesiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $conteo = ['P' => 0, 'T' => 0, 'A' => 0, 'J' => 0];
        foreach ($sesiones as $s) {
            if (isset($conteo[$s['estado']])) {
                $conteo[$s['estado']]++;
            }
        }
        $total = count($sesiones);
        $cabecera['presentes']   = $conteo['P'];
        $cabecera['tardanzas']   = $conteo['T'];
        $cabecera['ausencias']   = $conteo['A'];
        $cabecera['justificados'] = $conteo['J'];
        $cabecera['total']       = $total;
        $cabecera['porcentaje']  = $total > 0 ? (float) ($conteo['A'] / $total * 100) : 0.0;
        $cabecera['sesiones']    = $sesiones;

        // Rachas consecutivas de inasistencia, para ver si el problema
        // es puntual o una ausencia sostenida en el tiempo.
        $rachas = [];
        $racha  = 0;
        foreach ($sesiones as $s) {
            if ($s['estado'] === 'A') {
                $racha++;
            } else {
                if ($racha > 0) {
                    $rachas[] = $racha;
                }
                $racha = 0;
            }
        }
        if ($racha > 0) {
            $rachas[] = $racha;
        }
        $cabecera['racha_maxima'] = $rachas ? max($rachas) : 0;

        return $cabecera;
    }
}
