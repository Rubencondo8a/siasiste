<?php

declare(strict_types=1);

class Ambiente
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM ambientes ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActivos(): array
    {
        return $this->db->query('SELECT id, nombre FROM ambientes WHERE estado = 1 ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM ambientes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ambientes (nombre, ubicacion, capacidad, estado) VALUES (:nombre, :ubicacion, :capacidad, :estado)'
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':ubicacion' => $data['ubicacion'] ?? '',
            ':capacidad' => $data['capacidad'] ?? 0,
            ':estado' => $data['estado'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE ambientes SET nombre = :nombre, ubicacion = :ubicacion, capacidad = :capacidad, estado = :estado WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':ubicacion' => $data['ubicacion'] ?? '',
            ':capacidad' => $data['capacidad'] ?? 0,
            ':estado' => $data['estado'] ?? 1,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM ambientes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countHorarios(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM horarios WHERE ambiente_id = :ambiente_id');
        $stmt->execute([':ambiente_id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
