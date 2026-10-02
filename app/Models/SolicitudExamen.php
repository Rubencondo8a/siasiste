<?php

declare(strict_types=1);

class SolicitudExamen
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function crear(int $estudiante_id, int $curso_id, int $semana_id, float $objetivo, float $obtenidas): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO solicitud_examen (estudiante_id, curso_id, semana_id, mana_objetivo, mana_obtenida, estado)
            VALUES (:estudiante_id, :curso_id, :semana_id, :objetivo, :obtenidas, "pendiente")
        ');
        $stmt->execute([
            ':estudiante_id' => $estudiante_id,
            ':curso_id' => $curso_id,
            ':semana_id' => $semana_id,
            ':objetivo' => $objetivo,
            ':obtenidas' => $obtenidas,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function tienePendiente(int $estudiante_id, int $curso_id, int $semana_id): bool
    {
        $stmt = $this->db->prepare('
            SELECT id FROM solicitud_examen
            WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id AND semana_id = :semana_id AND estado = "pendiente"
            LIMIT 1
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id, ':semana_id' => $semana_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function getPendientesPorEstudiante(int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT s.id, s.estudiante_id, s.mana_objetivo, s.mana_obtenida, s.created_at,
                   c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   se.numero_semana, se.nombre AS semana_nombre
            FROM solicitud_examen s
            INNER JOIN cursos c ON c.id = s.curso_id
            LEFT JOIN semanas se ON se.id = s.semana_id
            WHERE s.estudiante_id = :estudiante_id
            ORDER BY s.created_at DESC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendientesPorDocente(int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT s.id, s.estudiante_id, s.mana_objetivo, s.mana_obtenida, s.created_at,
                   u.apellidos, u.nombres, u.username,
                   c.id AS curso_id, c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   se.id AS semana_id, se.numero_semana, se.nombre AS semana_nombre
            FROM solicitud_examen s
            INNER JOIN cursos c ON c.id = s.curso_id AND c.docente_id = :docente_id
            INNER JOIN usuarios u ON u.id = s.estudiante_id
            LEFT JOIN semanas se ON se.id = s.semana_id
            WHERE s.estado = "pendiente"
            ORDER BY s.created_at DESC
        ');
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdParaDocente(int $id, int $docente_id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT s.*, c.id AS curso_id, se.id AS semana_id, se.numero_semana, se.nombre AS semana_nombre
            FROM solicitud_examen s
            INNER JOIN cursos c ON c.id = s.curso_id AND c.docente_id = :docente_id
            LEFT JOIN semanas se ON se.id = s.semana_id
            WHERE s.id = :id AND s.estado = "pendiente"
            LIMIT 1
        ');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Semanas (id) con solicitud pendiente por estudiante, en un curso.
     */
    public function getPendientesPorCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT estudiante_id, semana_id FROM solicitud_examen
            WHERE curso_id = :curso_id AND estado = "pendiente" AND semana_id IS NOT NULL
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['estudiante_id']][] = (int) $row['semana_id'];
        }
        return $map;
    }

    public function atender(int $id, int $docente_id): bool
    {
        $stmt = $this->db->prepare('
            UPDATE solicitud_examen
            SET estado = "atendida", atendida_por = :docente_id, atendida_en = NOW()
            WHERE id = :id
        ');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        return $stmt->rowCount() > 0;
    }

    public function rechazar(int $id, int $docente_id, string $motivo): bool
    {
        $stmt = $this->db->prepare('
            UPDATE solicitud_examen
            SET estado = "rechazada", atendida_por = :docente_id, atendida_en = NOW(),
                motivo_rechazo = :motivo
            WHERE id = :id AND estado = "pendiente"
        ');
        $stmt->execute([
            ':id' => $id,
            ':docente_id' => $docente_id,
            ':motivo' => $motivo,
        ]);
        return $stmt->rowCount() > 0;
    }
}
