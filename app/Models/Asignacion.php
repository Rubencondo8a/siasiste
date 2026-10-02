<?php

declare(strict_types=1);

class Asignacion
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getEstudiantesByCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id, u.apellidos, u.nombres, u.dni, u.email, u.username
            FROM asignaciones a
            INNER JOIN usuarios u ON u.id = a.estudiante_id
            WHERE a.curso_id = :curso_id
            ORDER BY u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCursosByEstudiante(int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id, c.nombre, c.codigo, c.ciclo
            FROM asignaciones a
            INNER JOIN cursos c ON c.id = a.curso_id
            WHERE a.estudiante_id = :estudiante_id
            ORDER BY c.nombre ASC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exists(int $estudiante_id, int $curso_id): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM asignaciones WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id LIMIT 1');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function create(int $estudiante_id, int $curso_id): bool
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO asignaciones (estudiante_id, curso_id) VALUES (:estudiante_id, :curso_id)');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $estudiante_id, int $curso_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM asignaciones WHERE estudiante_id = :estudiante_id AND curso_id = :curso_id');
        $stmt->execute([':estudiante_id' => $estudiante_id, ':curso_id' => $curso_id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteByCurso(int $curso_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM asignaciones WHERE curso_id = :curso_id');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->rowCount() > 0;
    }

    public function countByCurso(int $curso_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM asignaciones WHERE curso_id = :curso_id');
        $stmt->execute([':curso_id' => $curso_id]);
        return (int) $stmt->fetchColumn();
    }

    public function countByEstudiante(int $estudiante_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM asignaciones WHERE estudiante_id = :estudiante_id');
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        return (int) $stmt->fetchColumn();
    }

    public function countDistinctEstudiantesByCursos(array $cursoIds): int
    {
        if (empty($cursoIds)) return 0;
        $ph = implode(',', array_fill(0, count($cursoIds), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT a.estudiante_id) FROM asignaciones a WHERE a.curso_id IN ($ph)");
        $stmt->execute($cursoIds);
        return (int) $stmt->fetchColumn();
    }

    public function countDistinctEstudiantesByCurso(int $curso_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(DISTINCT a.estudiante_id) FROM asignaciones a WHERE a.curso_id = :curso_id');
        $stmt->execute([':curso_id' => $curso_id]);
        return (int) $stmt->fetchColumn();
    }

    public function getEstudianteIdsByCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('SELECT estudiante_id FROM asignaciones WHERE curso_id = :curso_id');
        $stmt->execute([':curso_id' => $curso_id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getEstudiantesByCursoSimple(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id, u.apellidos, u.nombres, u.username
            FROM asignaciones ec
            INNER JOIN usuarios u ON u.id = ec.estudiante_id
            WHERE ec.curso_id = :curso_id AND u.estado = 1
            ORDER BY u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEstudiantesByCursoConCarrera(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id, u.apellidos, u.nombres, u.dni, u.carrera_id, u.username, u.email,
                   c.nombre AS carrera_nombre, ec.fecha_asignacion
            FROM asignaciones ec
            INNER JOIN usuarios u ON u.id = ec.estudiante_id
            LEFT JOIN carreras c ON c.id = u.carrera_id
            WHERE ec.curso_id = :curso_id AND u.estado = 1
            ORDER BY u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDisponiblesParaCurso(int $curso_id): array
    {
        $yaAsignados = $this->getEstudianteIdsByCurso($curso_id);
        if (!empty($yaAsignados)) {
            $placeholders = implode(',', array_fill(0, count($yaAsignados), '?'));
            $stmt = $this->db->prepare("
                SELECT u.id, u.apellidos, u.nombres, u.username, u.email,
                       c.nombre AS carrera_nombre
                FROM usuarios u
                LEFT JOIN carreras c ON c.id = u.carrera_id
                WHERE u.rol = 'estudiante' AND u.estado = 1
                AND u.id NOT IN ({$placeholders})
                ORDER BY u.apellidos ASC, u.nombres ASC
            ");
            $stmt->execute($yaAsignados);
        } else {
            $stmt = $this->db->query("
                SELECT u.id, u.apellidos, u.nombres, u.username, u.email,
                       c.nombre AS carrera_nombre
                FROM usuarios u
                LEFT JOIN carreras c ON c.id = u.carrera_id
                WHERE u.rol = 'estudiante' AND u.estado = 1
                ORDER BY u.apellidos ASC, u.nombres ASC
            ");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
