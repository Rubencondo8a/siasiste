<?php

declare(strict_types=1);

class Calificacion
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT cal.*, CONCAT(u.apellidos, ' ', u.nombres) AS estudiante_nombre, c.nombre AS criterio_nombre,
                    c.porcentaje, s.nombre AS semana_nombre, s.numero_semana AS semana_numero
             FROM calificaciones cal
             JOIN usuarios u ON u.id = cal.estudiante_id
             JOIN criterios c ON c.id = cal.criterio_id
             JOIN semanas s ON s.id = c.semana_id
             ORDER BY CONCAT(u.apellidos, ' ', u.nombres) ASC, s.numero_semana ASC, c.nombre ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllByStudent(int $estudiante_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT cal.*, c.nombre AS criterio_nombre, c.porcentaje,
                    s.nombre AS semana_nombre, s.numero_semana AS semana_numero
             FROM calificaciones cal
             JOIN criterios c ON c.id = cal.criterio_id
             JOIN semanas s ON s.id = c.semana_id
             WHERE cal.estudiante_id = :estudiante_id
             ORDER BY s.numero_semana ASC, c.nombre ASC"
        );
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllByCriterio(int $criterio_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT cal.*, CONCAT(u.apellidos, ' ', u.nombres) AS estudiante_nombre
             FROM calificaciones cal
             JOIN usuarios u ON u.id = cal.estudiante_id
             WHERE cal.criterio_id = :criterio_id
             ORDER BY CONCAT(u.apellidos, ' ', u.nombres) ASC"
        );
        $stmt->execute([':criterio_id' => $criterio_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT cal.*, CONCAT(u.apellidos, ' ', u.nombres) AS estudiante_nombre, c.nombre AS criterio_nombre,
                    c.porcentaje, s.nombre AS semana_nombre
             FROM calificaciones cal
             JOIN usuarios u ON u.id = cal.estudiante_id
             JOIN criterios c ON c.id = cal.criterio_id
             JOIN semanas s ON s.id = c.semana_id
             WHERE cal.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function createOrUpdate(int $estudiante_id, int $criterio_id, float $nota): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO calificaciones (estudiante_id, criterio_id, nota)
             VALUES (:estudiante_id, :criterio_id, :nota)
             ON DUPLICATE KEY UPDATE nota = VALUES(nota)"
        );
        $stmt->execute([
            ':estudiante_id' => $estudiante_id,
            ':criterio_id' => $criterio_id,
            ':nota' => $nota,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function getByWeekAndStudent(int $semana_id, int $estudiante_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT cal.*, c.nombre AS criterio_nombre, c.porcentaje
             FROM calificaciones cal
             JOIN criterios c ON c.id = cal.criterio_id
             WHERE c.semana_id = :semana_id AND cal.estudiante_id = :estudiante_id
             ORDER BY c.nombre ASC"
        );
        $stmt->execute([
            ':semana_id' => $semana_id,
            ':estudiante_id' => $estudiante_id,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentAverage(int $estudiante_id): float
    {
        $stmt = $this->db->prepare(
            "SELECT SUM(cal.nota * c.porcentaje / 100) / SUM(c.porcentaje / 100) AS promedio
             FROM calificaciones cal
             JOIN criterios c ON c.id = cal.criterio_id
             WHERE cal.estudiante_id = :estudiante_id"
        );
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['promedio'] !== null ? round((float) $result['promedio'], 2) : 0.0;
    }

    public function getWeekAverage(int $semana_id, int $estudiante_id): float
    {
        $stmt = $this->db->prepare(
            "SELECT SUM(cal.nota * c.porcentaje / 100) / SUM(c.porcentaje / 100) AS promedio
             FROM calificaciones cal
             JOIN criterios c ON c.id = cal.criterio_id
             WHERE c.semana_id = :semana_id AND cal.estudiante_id = :estudiante_id"
        );
        $stmt->execute([
            ':semana_id' => $semana_id,
            ':estudiante_id' => $estudiante_id,
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['promedio'] !== null ? round((float) $result['promedio'], 2) : 0.0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM calificaciones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteByCriterio(int $criterio_id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM calificaciones WHERE criterio_id = :criterio_id");
        $stmt->execute([':criterio_id' => $criterio_id]);
        return $stmt->rowCount() > 0;
    }

    // ==================== DOCENTE ====================

    public function getNotasByCriterios(array $criterioIds): array
    {
        if (empty($criterioIds)) return [];
        $placeholders = implode(',', array_fill(0, count($criterioIds), '?'));
        $stmt = $this->db->prepare("SELECT estudiante_id, criterio_id, nota FROM calificaciones WHERE criterio_id IN ({$placeholders})");
        $stmt->execute($criterioIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCriterioAndEstudiante(int $criterio_id, int $estudiante_id): ?float
    {
        $stmt = $this->db->prepare('SELECT nota FROM calificaciones WHERE criterio_id = :criterio_id AND estudiante_id = :estudiante_id');
        $stmt->execute([':criterio_id' => $criterio_id, ':estudiante_id' => $estudiante_id]);
        $nota = $stmt->fetchColumn();
        return $nota === false ? null : (float) $nota;
    }

    public function deleteByCriterioAndStudents(int $criterio_id, array $studentIds): void
    {
        if (empty($studentIds)) {
            $this->deleteByCriterio($criterio_id);
            return;
        }
        $ph = implode(',', array_fill(0, count($studentIds), '?'));
        $stmt = $this->db->prepare("DELETE FROM calificaciones WHERE criterio_id = ? AND estudiante_id IN ($ph)");
        $stmt->execute(array_merge([$criterio_id], $studentIds));
    }

    public function insert(int $estudiante_id, int $criterio_id, float $nota): void
    {
        $stmt = $this->db->prepare('INSERT INTO calificaciones (estudiante_id, criterio_id, nota) VALUES (:estudiante_id, :criterio_id, :nota)');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':criterio_id' => $criterio_id, ':nota' => $nota]);
    }

    public function getAllForDocente(int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT cal.estudiante_id, cal.criterio_id, cal.nota, cr.porcentaje, cr.semana_id
            FROM calificaciones cal
            INNER JOIN criterios cr ON cr.id = cal.criterio_id
            INNER JOIN semanas s ON s.id = cr.semana_id
            WHERE s.docente_id = :docente_id
        ');
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByDocenteAndCursos(int $docente_id, array $cursoIds): int
    {
        if (empty($cursoIds)) return 0;
        $ph = implode(',', array_fill(0, count($cursoIds), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM calificaciones cal INNER JOIN criterios cr ON cr.id = cal.criterio_id INNER JOIN semanas s ON s.id = cr.semana_id WHERE s.docente_id = ? AND s.curso_id IN ($ph)");
        $stmt->execute(array_merge([$docente_id], $cursoIds));
        return (int) $stmt->fetchColumn();
    }

    // ==================== ESTUDIANTE ====================

    public function getResumenPorSemanas(int $estudiante_id): array
    {
        $stmt = $this->db->prepare("
            SELECT s.id, s.nombre, s.numero_semana, s.fecha_inicio, s.fecha_fin, s.curso_id,
                   c.porcentaje, cal.nota
            FROM semanas s
            INNER JOIN criterios c ON c.semana_id = s.id
            INNER JOIN calificaciones cal ON cal.criterio_id = c.id AND cal.estudiante_id = :estudiante_id
            ORDER BY s.numero_semana ASC, c.nombre ASC
        ");
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPorSemanaConNota(int $semana_id, int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.porcentaje, cal.nota
            FROM criterios c
            INNER JOIN calificaciones cal ON cal.criterio_id = c.id AND cal.estudiante_id = :estudiante_id
            WHERE c.semana_id = :semana_id
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':semana_id' => $semana_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPorSemanaCompleto(int $semana_id, int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.nombre, c.porcentaje, cal.nota
            FROM criterios c
            LEFT JOIN calificaciones cal ON cal.criterio_id = c.id AND cal.estudiante_id = :estudiante_id
            WHERE c.semana_id = :semana_id
            ORDER BY c.nombre ASC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':semana_id' => $semana_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Promedio por semana de todos los estudiantes de un curso (id => promedio).
     */
    public function getPromediosPorSemanaYCurso(int $semana_id, int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT cal.estudiante_id,
                   SUM(cal.nota * c.porcentaje / 100) / SUM(c.porcentaje / 100) AS promedio
            FROM calificaciones cal
            INNER JOIN criterios c ON c.id = cal.criterio_id
            INNER JOIN semanas s ON s.id = c.semana_id
            WHERE s.id = :semana_id AND s.curso_id = :curso_id
            GROUP BY cal.estudiante_id
        ');
        $stmt->execute([':semana_id' => $semana_id, ':curso_id' => $curso_id]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['estudiante_id']] = round((float) $row['promedio'], 2);
        }
        return $map;
    }
}
