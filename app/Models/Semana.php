<?php

declare(strict_types=1);

class Semana
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT s.*, CONCAT(u.apellidos, ' ', u.nombres) AS docente_nombre
             FROM semanas s
             JOIN usuarios u ON u.id = s.docente_id
             ORDER BY s.numero_semana ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllByDocente(int $docente_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, CONCAT(u.apellidos, ' ', u.nombres) AS docente_nombre
             FROM semanas s
             JOIN usuarios u ON u.id = s.docente_id
             WHERE s.docente_id = :docente_id
             ORDER BY s.numero_semana ASC"
        );
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, CONCAT(u.apellidos, ' ', u.nombres) AS docente_nombre
             FROM semanas s
             JOIN usuarios u ON u.id = s.docente_id
             WHERE s.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO semanas (numero_semana, nombre, descripcion, fecha_inicio, fecha_fin, docente_id, curso_id)
             VALUES (:numero_semana, :nombre, :descripcion, :fecha_inicio, :fecha_fin, :docente_id, :curso_id)"
        );
        $stmt->execute([
            ':numero_semana' => $data['numero_semana'],
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':fecha_inicio' => $data['fecha_inicio'] ?? null,
            ':fecha_fin' => $data['fecha_fin'] ?? null,
            ':docente_id' => $data['docente_id'],
            ':curso_id' => $data['curso_id'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE semanas SET numero_semana = :numero_semana, nombre = :nombre, descripcion = :descripcion,
                    fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin WHERE id = :id"
        );
        $stmt->execute([
            ':numero_semana' => $data['numero_semana'],
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':fecha_inicio' => $data['fecha_inicio'] ?? null,
            ':fecha_fin' => $data['fecha_fin'] ?? null,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM semanas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteForDocente(int $id, int $docente_id, int $curso_id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM semanas WHERE id = :id AND docente_id = :docente_id AND curso_id = :curso_id");
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id, ':curso_id' => $curso_id]);
        return $stmt->rowCount() > 0;
    }

    public function getWithCriteria(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, CONCAT(u.apellidos, ' ', u.nombres) AS docente_nombre
             FROM semanas s
             JOIN usuarios u ON u.id = s.docente_id
             WHERE s.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $semana = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$semana) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM criterios WHERE semana_id = :semana_id ORDER BY nombre ASC"
        );
        $stmt->execute([':semana_id' => $id]);
        $semana['criterios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $semana;
    }

    public function getTotalPercentage(int $semana_id): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(porcentaje), 0) FROM criterios WHERE semana_id = :semana_id"
        );
        $stmt->execute([':semana_id' => $semana_id]);
        return (float) $stmt->fetchColumn();
    }

    public function countByDocente(int $docente_id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM semanas WHERE docente_id = :docente_id");
        $stmt->execute([':docente_id' => $docente_id]);
        return (int) $stmt->fetchColumn();
    }

    // ==================== DOCENTE ====================

    public function getBasicForDocente(int $id, int $docente_id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, descripcion, cerrada, numero_semana, fecha_inicio, fecha_fin, curso_id FROM semanas WHERE id = :id AND docente_id = :docente_id LIMIT 1');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByIdForDocenteAndCurso(int $id, int $docente_id, int $curso_id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, descripcion, cerrada, numero_semana, fecha_inicio, fecha_fin FROM semanas WHERE id = :id AND docente_id = :docente_id AND curso_id = :curso_id LIMIT 1');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id, ':curso_id' => $curso_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAllByCurso(int $curso_id, int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT s.id, s.nombre, s.descripcion, s.cerrada, s.numero_semana, s.fecha_inicio, s.fecha_fin, s.created_at,
                   COUNT(cr.id) AS total_criterios,
                   COALESCE(SUM(cr.porcentaje), 0) AS suma_porcentajes,
                   COALESCE(pp.total_puntos, 0) AS total_puntos,
                   COALESCE(gr.total_grupos, 0) AS total_grupos
            FROM semanas s
            LEFT JOIN criterios cr ON cr.semana_id = s.id
            LEFT JOIN (
                SELECT semana_id, curso_id, COALESCE(SUM(puntos), 0) AS total_puntos
                FROM puntos_participacion
                GROUP BY semana_id, curso_id
            ) pp ON pp.semana_id = s.id AND pp.curso_id = s.curso_id
            LEFT JOIN (
                SELECT semana_id, COUNT(*) AS total_grupos
                FROM equipos
                GROUP BY semana_id
            ) gr ON gr.semana_id = s.id
            WHERE s.curso_id = :curso_id AND s.docente_id = :docente_id
            GROUP BY s.id, pp.total_puntos, gr.total_grupos
            ORDER BY s.numero_semana ASC
        ');
        $stmt->execute([':curso_id' => $curso_id, ':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isClosedForDocente(int $id, int $docente_id): ?bool
    {
        $stmt = $this->db->prepare('SELECT cerrada FROM semanas WHERE id = :id AND docente_id = :docente_id LIMIT 1');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        $cerrada = $stmt->fetchColumn();
        return $cerrada === false ? null : (bool) $cerrada;
    }

    public function existsOverlap(int $docente_id, int $curso_id, string $inicio, string $fin, ?int $exclude_id = null): bool
    {
        $sql = 'SELECT id FROM semanas WHERE docente_id = :docente_id AND curso_id = :curso_id AND fecha_inicio < :fin AND fecha_fin > :inicio';
        if ($exclude_id !== null) $sql .= ' AND id != :id';
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $params = [':docente_id' => $docente_id, ':curso_id' => $curso_id, ':inicio' => $inicio, ':fin' => $fin];
        if ($exclude_id !== null) $params[':id'] = $exclude_id;
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function getByNumeroForDocenteCurso(int $docente_id, int $curso_id, int $numero_semana): ?array
    {
        $stmt = $this->db->prepare('SELECT id, numero_semana, fecha_inicio, fecha_fin FROM semanas WHERE docente_id = :docente_id AND curso_id = :curso_id AND numero_semana = :numero_semana LIMIT 1');
        $stmt->execute([':docente_id' => $docente_id, ':curso_id' => $curso_id, ':numero_semana' => $numero_semana]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function close(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE semanas SET cerrada = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function reopen(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE semanas SET cerrada = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getByCursoParaReportes(int $curso_id, int $docente_id): array
    {
        $stmt = $this->db->prepare('SELECT s.id, s.nombre, s.numero_semana, s.fecha_inicio, s.fecha_fin FROM semanas s WHERE s.docente_id = :docente_id AND s.curso_id = :curso_id ORDER BY s.numero_semana ASC');
        $stmt->execute([':docente_id' => $docente_id, ':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCerradasByCurso(int $curso_id, int $docente_id): array
    {
        $stmt = $this->db->prepare('SELECT s.id, s.nombre, s.numero_semana FROM semanas s WHERE s.docente_id = :docente_id AND s.curso_id = :curso_id AND s.cerrada = 1 ORDER BY s.numero_semana');
        $stmt->execute([':docente_id' => $docente_id, ':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAllByDocenteSimple(int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT s.id, s.nombre, s.numero_semana, s.fecha_inicio, s.fecha_fin,
                   s.curso_id, c.nombre AS curso_nombre
            FROM semanas s
            INNER JOIN cursos c ON c.id = s.curso_id
            WHERE s.docente_id = :docente_id
            ORDER BY c.nombre ASC, s.numero_semana ASC
        ');
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countByDocenteAndCursos(int $docente_id, array $cursoIds): int
    {
        if (empty($cursoIds)) return 0;
        $ph = implode(',', array_fill(0, count($cursoIds), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM semanas WHERE docente_id = ? AND curso_id IN ($ph)");
        $stmt->execute(array_merge([$docente_id], $cursoIds));
        return (int) $stmt->fetchColumn();
    }

    public function countByCursoForDocente(int $curso_id, int $docente_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM semanas WHERE curso_id = ? AND docente_id = ?');
        $stmt->execute([$curso_id, $docente_id]);
        return (int) $stmt->fetchColumn();
    }

    public function countEvaluadasByDocenteAndCursos(int $docente_id, array $cursoIds): int
    {
        if (empty($cursoIds)) return 0;
        $ph = implode(',', array_fill(0, count($cursoIds), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT s.id) FROM semanas s INNER JOIN criterios cr ON cr.semana_id = s.id INNER JOIN calificaciones cal ON cal.criterio_id = cr.id WHERE s.docente_id = ? AND s.curso_id IN ($ph)");
        $stmt->execute(array_merge([$docente_id], $cursoIds));
        return (int) $stmt->fetchColumn();
    }

    public function countEvaluadasByCursoForDocente(int $curso_id, int $docente_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(DISTINCT s.id) FROM semanas s INNER JOIN criterios cr ON cr.semana_id = s.id INNER JOIN calificaciones cal ON cal.criterio_id = cr.id WHERE s.curso_id = ? AND s.docente_id = ?');
        $stmt->execute([$curso_id, $docente_id]);
        return (int) $stmt->fetchColumn();
    }

    // ==================== ESTUDIANTE ====================

    public function getAbiertasConCurso(int $docente_id): array
    {
        $stmt = $this->db->prepare(
            'SELECT s.id, s.numero_semana, s.nombre AS semana_nombre, c.id AS curso_id, c.nombre AS curso_nombre
             FROM semanas s
             INNER JOIN cursos c ON c.id = s.curso_id
             WHERE s.docente_id = :docente_id AND s.cerrada = 0
             ORDER BY c.nombre ASC, s.numero_semana ASC'
        );
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdSimple(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, numero_semana, curso_id, cerrada, fecha_inicio, fecha_fin FROM semanas WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAllByCursoSimple(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT s.id, s.nombre, s.numero_semana, s.cerrada, s.fecha_inicio, s.fecha_fin
            FROM semanas s
            WHERE s.curso_id = :curso_id
            ORDER BY s.numero_semana ASC
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCalificadasPorEstudianteYCurso(int $curso_id, int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT DISTINCT s.id, s.nombre, s.numero_semana, s.fecha_inicio, s.fecha_fin
            FROM semanas s
            LEFT JOIN criterios c ON c.semana_id = s.id
            LEFT JOIN calificaciones cal ON cal.criterio_id = c.id AND cal.estudiante_id = :estudiante_id
            WHERE s.curso_id = :curso_id
            ORDER BY s.numero_semana ASC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countEvaluadasPorEstudianteYCurso(int $curso_id, int $estudiante_id): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(DISTINCT s.id)
            FROM semanas s
            INNER JOIN criterios cr ON cr.semana_id = s.id
            INNER JOIN calificaciones cal ON cal.criterio_id = cr.id AND cal.estudiante_id = :estudiante_id
            WHERE s.curso_id = :curso_id
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
        return (int) $stmt->fetchColumn();
    }

    public function getSemanaActualPorCurso(int $curso_id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id, nombre, numero_semana, fecha_inicio, fecha_fin
            FROM semanas
            WHERE curso_id = :curso_id AND CURDATE() BETWEEN fecha_inicio AND fecha_fin
            LIMIT 1
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
