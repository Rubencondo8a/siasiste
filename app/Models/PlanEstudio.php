<?php

declare(strict_types=1);

class PlanEstudio
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->query('
            SELECT pe.*, c.nombre AS carrera_nombre, c.codigo AS carrera_codigo
            FROM planes_estudio pe
            INNER JOIN carreras c ON c.id = pe.carrera_id
            ORDER BY c.nombre ASC, pe.nombre DESC
        ')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCarrera(int $carrera_id): array
    {
        $stmt = $this->db->prepare('
            SELECT id, nombre, descripcion, estado
            FROM planes_estudio
            WHERE carrera_id = :carrera_id
            ORDER BY nombre DESC
        ');
        $stmt->execute([':carrera_id' => $carrera_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCarreraActivos(int $carrera_id): array
    {
        $stmt = $this->db->prepare('
            SELECT id, nombre, descripcion
            FROM planes_estudio
            WHERE carrera_id = :carrera_id AND estado = 1
            ORDER BY nombre DESC
        ');
        $stmt->execute([':carrera_id' => $carrera_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM planes_estudio WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByIdConCarrera(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT pe.*, c.nombre AS carrera_nombre, c.codigo AS carrera_codigo
            FROM planes_estudio pe
            INNER JOIN carreras c ON c.id = pe.carrera_id
            WHERE pe.id = :id LIMIT 1
        ');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function exists(int $carrera_id, string $nombre, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM planes_estudio WHERE carrera_id = :carrera_id AND nombre = :nombre';
        $params = [':carrera_id' => $carrera_id, ':nombre' => $nombre];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO planes_estudio (carrera_id, nombre, descripcion, estado) VALUES (:carrera_id, :nombre, :descripcion, :estado)'
        );
        $stmt->execute([
            ':carrera_id' => $data['carrera_id'],
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':estado' => $data['estado'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE planes_estudio SET carrera_id = :carrera_id, nombre = :nombre, descripcion = :descripcion, estado = :estado WHERE id = :id'
        );
        $stmt->execute([
            ':carrera_id' => $data['carrera_id'],
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':estado' => $data['estado'] ?? 1,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM planes_estudio WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countCursos(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM cursos WHERE plan_id = :plan_id');
        $stmt->execute([':plan_id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
