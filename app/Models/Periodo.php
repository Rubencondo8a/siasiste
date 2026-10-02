<?php

declare(strict_types=1);

class Periodo
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM periodos ORDER BY fecha_inicio DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActivos(): array
    {
        return $this->db->query('SELECT id, nombre, estado FROM periodos ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActivo(): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM periodos WHERE estado = 1 ORDER BY fecha_inicio DESC LIMIT 1');
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM periodos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, estado) VALUES (:nombre, :fecha_inicio, :fecha_fin, :estado)'
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin' => $data['fecha_fin'],
            ':estado' => $data['estado'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE periodos SET nombre = :nombre, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, estado = :estado WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin' => $data['fecha_fin'],
            ':estado' => $data['estado'] ?? 1,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM periodos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countCursos(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM cursos WHERE periodo_id = :periodo_id');
        $stmt->execute([':periodo_id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
