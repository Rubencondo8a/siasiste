<?php

declare(strict_types=1);

class Curso
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAllWithCarrera(): array
    {
        return $this->db->query('
            SELECT c.*, car.nombre AS carrera_nombre,
                   pe.id AS plan_id, pe.nombre AS plan_nombre
            FROM cursos c
            LEFT JOIN carreras car ON car.id = c.carrera_id
            LEFT JOIN planes_estudio pe ON pe.id = c.plan_id
            ORDER BY c.created_at DESC
        ')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActivos(): array
    {
        return $this->db->query('SELECT id, nombre, codigo FROM cursos WHERE estado = 1 ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cursos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByIdWithCarrera(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT c.*, car.nombre AS carrera_nombre,
                   pe.id AS plan_id, pe.nombre AS plan_nombre
            FROM cursos c
            LEFT JOIN carreras car ON car.id = c.carrera_id
            LEFT JOIN planes_estudio pe ON pe.id = c.plan_id
            WHERE c.id = :id LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existsByCodigo(string $codigo): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM cursos WHERE codigo = :codigo LIMIT 1');
        $stmt->execute([':codigo' => $codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cursos (nombre, codigo, ciclo, carrera_id, plan_id, descripcion, periodo_id, docente_id, estado) VALUES (:nombre, :codigo, :ciclo, :carrera_id, :plan_id, :descripcion, :periodo_id, :docente_id, :estado)'
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':codigo' => $data['codigo'],
            ':ciclo' => $data['ciclo'],
            ':carrera_id' => $data['carrera_id'],
            ':plan_id' => $data['plan_id'] ?? null,
            ':descripcion' => $data['descripcion'] ?? null,
            ':periodo_id' => $data['periodo_id'],
            ':docente_id' => $data['docente_id'] ?? null,
            ':estado' => $data['estado'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE cursos SET nombre = :nombre, codigo = :codigo, ciclo = :ciclo, carrera_id = :carrera_id, plan_id = :plan_id, descripcion = :descripcion, estado = :estado WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':codigo' => $data['codigo'],
            ':ciclo' => $data['ciclo'],
            ':carrera_id' => $data['carrera_id'],
            ':plan_id' => $data['plan_id'] ?? null,
            ':descripcion' => $data['descripcion'] ?? null,
            ':estado' => $data['estado'] ?? 1,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function getTolerancia(int $id): array
    {
        $stmt = $this->db->prepare('SELECT hora_inicio, tolerancia_minutos FROM cursos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: ['hora_inicio' => null, 'tolerancia_minutos' => 0];
    }

    public function updateTolerancia(int $id, ?string $hora_inicio, int $tolerancia_minutos): bool
    {
        $stmt = $this->db->prepare('UPDATE cursos SET hora_inicio = :hora_inicio, tolerancia_minutos = :tolerancia_minutos WHERE id = :id');
        return $stmt->execute([
            ':hora_inicio' => $hora_inicio ?: null,
            ':tolerancia_minutos' => max(0, $tolerancia_minutos),
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cursos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countSemanas(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM semanas WHERE curso_id = :curso_id');
        $stmt->execute([':curso_id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public function countEstudiantes(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM asignaciones WHERE curso_id = :curso_id');
        $stmt->execute([':curso_id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    // ==================== ASIGNACIONES (curso -> docente/periodo) ====================

    public function getAsignaciones(): array
    {
        return $this->db->query('
            SELECT c.id, c.nombre AS curso_nombre, c.codigo,
                   p.nombre AS periodo_nombre, p.estado AS periodo_estado,
                   u.apellidos AS docente_apellidos, u.nombres AS docente_nombres,
                   c.estado AS curso_estado
            FROM cursos c
            LEFT JOIN periodos p ON p.id = c.periodo_id
            LEFT JOIN usuarios u ON u.id = c.docente_id
            ORDER BY p.nombre ASC, c.nombre ASC
        ')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdWithAsignacion(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT c.*, p.nombre AS periodo_nombre, p.fecha_inicio AS periodo_inicio, p.fecha_fin AS periodo_fin, p.estado AS periodo_estado,
                   u.apellidos AS docente_apellidos, u.nombres AS docente_nombres, u.email AS docente_email
            FROM cursos c
            LEFT JOIN periodos p ON p.id = c.periodo_id
            LEFT JOIN usuarios u ON u.id = c.docente_id
            WHERE c.id = :id LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function assignDocente(int $id, ?int $docente_id, int $periodo_id): bool
    {
        $stmt = $this->db->prepare('UPDATE cursos SET docente_id = :docente_id, periodo_id = :periodo_id WHERE id = :id');
        $stmt->execute([':docente_id' => $docente_id, ':periodo_id' => $periodo_id, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function unassignDocente(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE cursos SET docente_id = NULL WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ==================== DOCENTE ====================

    public function getByDocenteActivos(int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id, c.nombre, c.codigo, p.nombre AS periodo_nombre, p.fecha_inicio, p.fecha_fin
            FROM cursos c
            INNER JOIN periodos p ON p.id = c.periodo_id
            WHERE c.docente_id = :docente_id AND c.estado = 1 AND p.estado = 1
            ORDER BY p.nombre ASC, c.nombre ASC
        ');
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDocenteConConteos(int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id, c.nombre, c.codigo, c.ciclo, c.sillabus_pdf, p.nombre AS periodo_nombre,
                   (SELECT COUNT(*) FROM semanas s WHERE s.curso_id = c.id) AS total_semanas,
                   (SELECT COUNT(*) FROM asignaciones ec WHERE ec.curso_id = c.id) AS total_estudiantes
            FROM cursos c
            INNER JOIN periodos p ON p.id = c.periodo_id
            WHERE c.docente_id = :docente_id AND c.estado = 1 AND p.estado = 1
            ORDER BY p.nombre ASC, c.nombre ASC
        ');
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateSillabus(int $id, ?string $sillabus_pdf): bool
    {
        $stmt = $this->db->prepare('UPDATE cursos SET sillabus_pdf = :sillabus_pdf WHERE id = :id');
        return $stmt->execute([':sillabus_pdf' => $sillabus_pdf, ':id' => $id]);
    }

    public function getByIdForDocente(int $id, int $docente_id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT c.id, c.nombre, c.codigo, c.ciclo, p.nombre AS periodo_nombre
            FROM cursos c
            INNER JOIN periodos p ON p.id = c.periodo_id
            WHERE c.id = :id AND c.docente_id = :docente_id AND c.estado = 1
            LIMIT 1
        ');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByIdForDocenteSimple(int $id, int $docente_id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, codigo FROM cursos WHERE id = :id AND docente_id = :docente_id AND estado = 1 LIMIT 1');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getPeriodoForDocente(int $curso_id, int $docente_id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id AS periodo_id, p.nombre AS periodo_nombre, p.fecha_inicio AS periodo_inicio, p.fecha_fin AS periodo_fin, p.estado AS periodo_estado,
                    c.nombre AS curso_nombre
             FROM cursos c
             INNER JOIN periodos p ON p.id = c.periodo_id
             WHERE c.id = :curso_id AND c.docente_id = :docente_id AND c.estado = 1
             LIMIT 1'
        );
        $stmt->execute([':curso_id' => $curso_id, ':docente_id' => $docente_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existsByIdForDocente(int $id, int $docente_id): bool
    {
        return $this->getByIdForDocenteSimple($id, $docente_id) !== null;
    }

    public function getAllActivosByDocente(int $docente_id): array
    {
        $stmt = $this->db->prepare('SELECT c.id, c.nombre, c.codigo FROM cursos c WHERE c.docente_id = :docente_id AND c.estado = 1 ORDER BY c.nombre ASC');
        $stmt->execute([':docente_id' => $docente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdParaReporte(int $id, int $docente_id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, codigo FROM cursos WHERE id = :id AND docente_id = :docente_id LIMIT 1');
        $stmt->execute([':id' => $id, ':docente_id' => $docente_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    // ==================== ESTUDIANTE ====================

    public function getByEstudiante(int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT c.id, c.nombre, c.codigo, c.ciclo, c.sillabus_pdf,
                   p.nombre AS periodo_nombre,
                   u.nombres AS docente_nombres, u.apellidos AS docente_apellidos
            FROM asignaciones a
            INNER JOIN cursos c ON c.id = a.curso_id
            INNER JOIN periodos p ON p.id = c.periodo_id
            LEFT JOIN usuarios u ON u.id = c.docente_id
            WHERE a.estudiante_id = :estudiante_id AND c.estado = 1
            ORDER BY p.nombre ASC, c.nombre ASC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdParaEstudiante(int $id, bool $soloActivos = false): ?array
    {
        $sql = '
            SELECT c.id, c.nombre, c.codigo, c.ciclo,
                   p.nombre AS periodo_nombre,
                   u.nombres AS docente_nombres, u.apellidos AS docente_apellidos
            FROM cursos c
            INNER JOIN periodos p ON p.id = c.periodo_id
            LEFT JOIN usuarios u ON u.id = c.docente_id
            WHERE c.id = :id';
        if ($soloActivos) $sql .= ' AND c.estado = 1';
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByIdInformacion(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT c.nombre, c.codigo, c.ciclo,
                   u.nombres AS docente_nombres, u.apellidos AS docente_apellidos,
                   p.nombre AS periodo_nombre
            FROM cursos c
            LEFT JOIN usuarios u ON u.id = c.docente_id
            INNER JOIN periodos p ON p.id = c.periodo_id
            WHERE c.id = :id LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
