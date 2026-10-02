<?php

declare(strict_types=1);

class Carrera
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM carreras ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActivas(): array
    {
        return $this->db->query('SELECT id, nombre, codigo, total_ciclos FROM carreras WHERE estado = 1 ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM carreras WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existsByCodigo(string $codigo): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM carreras WHERE codigo = :codigo LIMIT 1');
        $stmt->execute([':codigo' => $codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO carreras (nombre, codigo, total_ciclos, descripcion, estado) VALUES (:nombre, :codigo, :total_ciclos, :descripcion, :estado)'
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':codigo' => $data['codigo'],
            ':total_ciclos' => $data['total_ciclos'] ?? 6,
            ':descripcion' => $data['descripcion'] ?? null,
            ':estado' => $data['estado'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE carreras SET nombre = :nombre, codigo = :codigo, total_ciclos = :total_ciclos, descripcion = :descripcion, estado = :estado WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':codigo' => $data['codigo'],
            ':total_ciclos' => $data['total_ciclos'] ?? 6,
            ':descripcion' => $data['descripcion'] ?? null,
            ':estado' => $data['estado'] ?? 1,
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM carreras WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countCursos(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM cursos WHERE carrera_id = :carrera_id');
        $stmt->execute([':carrera_id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    public function countEstudiantes(int $id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM usuarios WHERE carrera_id = :carrera_id AND rol = "estudiante"');
        $stmt->execute([':carrera_id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
