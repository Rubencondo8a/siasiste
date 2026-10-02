<?php

declare(strict_types=1);

class Beneficio
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM beneficios ORDER BY nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActivos(): array
    {
        $stmt = $this->db->query('SELECT * FROM beneficios WHERE estado = 1 ORDER BY nombre ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM beneficios WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO beneficios (nombre, descripcion, tipo, costo_gemas, puntos_bonus, estado)
            VALUES (:nombre, :descripcion, :tipo, :costo_gemas, :puntos_bonus, :estado)
        ');
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':tipo' => $data['tipo'],
            ':costo_gemas' => $data['costo_gemas'],
            ':puntos_bonus' => $data['puntos_bonus'] ?? 1,
            ':estado' => $data['estado'] ?? 1,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        $sql = 'UPDATE beneficios SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function toggleEstado(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE beneficios SET estado = NOT estado WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM beneficios')->fetchColumn();
    }

    public function countCanjes(int $beneficioId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM canjes WHERE beneficio_id = :id');
        $stmt->execute([':id' => $beneficioId]);
        return (int) $stmt->fetchColumn();
    }
}
