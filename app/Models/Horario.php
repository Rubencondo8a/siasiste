<?php

declare(strict_types=1);

class Horario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getByDocente(int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT h.*, c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   a.nombre AS ambiente_nombre
            FROM horarios h
            INNER JOIN cursos c ON c.id = h.curso_id
            LEFT JOIN ambientes a ON a.id = h.ambiente_id
            WHERE h.docente_id = :docente_id
            ORDER BY FIELD(h.dia, "Lunes","Martes","Miercoles","Jueves","Viernes"), h.hora_inicio ASC
        ');
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT h.*, c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   a.nombre AS ambiente_nombre
            FROM horarios h
            INNER JOIN cursos c ON c.id = h.curso_id
            LEFT JOIN ambientes a ON a.id = h.ambiente_id
            WHERE h.curso_id = :curso_id
            ORDER BY h.dia ASC, h.hora_inicio ASC
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdForDocente(int $id, int $docente_id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM horarios WHERE id = :id AND docente_id = :docente_id');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function hasConflict(int $docente_id, string $dia, string $hora_inicio, string $hora_fin, int $exclude = 0): bool
    {
        $stmt = $this->db->prepare('
            SELECT id FROM horarios
            WHERE docente_id = :docente_id AND dia = :dia
            AND hora_inicio < :hora_fin AND hora_fin > :hora_inicio
            AND id != :exclude
            LIMIT 1
        ');
        $stmt->execute([
            ':docente_id' => $docente_id,
            ':dia' => $dia,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin' => $hora_fin,
            ':exclude' => $exclude,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function create(int $curso_id, int $docente_id, ?int $ambiente_id, string $dia, string $hora_inicio, string $hora_fin): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO horarios (curso_id, docente_id, ambiente_id, dia, hora_inicio, hora_fin) VALUES (:curso_id, :docente_id, :ambiente_id, :dia, :hora_inicio, :hora_fin)'
        );
        $stmt->execute([
            ':curso_id' => $curso_id,
            ':docente_id' => $docente_id,
            ':ambiente_id' => $ambiente_id,
            ':dia' => $dia,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin' => $hora_fin,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, int $docente_id, int $curso_id, ?int $ambiente_id, string $dia, string $hora_inicio, string $hora_fin): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE horarios SET curso_id=:curso_id, ambiente_id=:ambiente_id, dia=:dia, hora_inicio=:hora_inicio, hora_fin=:hora_fin WHERE id=:id AND docente_id=:docente_id'
        );
        $stmt->execute([
            ':id' => $id,
            ':docente_id' => $docente_id,
            ':curso_id' => $curso_id,
            ':ambiente_id' => $ambiente_id,
            ':dia' => $dia,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin' => $hora_fin,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $docente_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM horarios WHERE id = :id AND docente_id = :docente_id');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        return $stmt->rowCount() > 0;
    }

    public function countByCurso(int $curso_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM horarios WHERE curso_id = :curso_id');
        $stmt->execute([':curso_id' => $curso_id]);
        return (int) $stmt->fetchColumn();
    }

    // ==================== ESTUDIANTE ====================

    public function getByEstudiante(int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT h.*, c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   a.nombre AS ambiente_nombre,
                   u.nombres AS docente_nombres, u.apellidos AS docente_apellidos
            FROM horarios h
            INNER JOIN cursos c ON c.id = h.curso_id
            LEFT JOIN ambientes a ON a.id = h.ambiente_id
            LEFT JOIN usuarios u ON u.id = c.docente_id
            INNER JOIN asignaciones ec ON ec.curso_id = h.curso_id AND ec.estudiante_id = :estudiante_id
            ORDER BY FIELD(h.dia, "Lunes","Martes","Miercoles","Jueves","Viernes"), h.hora_inicio ASC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCursoSimple(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT h.id, h.dia, h.hora_inicio, h.hora_fin, a.nombre AS ambiente_nombre
            FROM horarios h
            LEFT JOIN ambientes a ON a.id = h.ambiente_id
            WHERE h.curso_id = :curso_id
            ORDER BY FIELD(h.dia, "Lunes","Martes","Miercoles","Jueves","Viernes"), h.hora_inicio
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
