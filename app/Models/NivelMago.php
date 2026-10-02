<?php

declare(strict_types=1);

class NivelMago
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->query('SELECT * FROM niveles_mago ORDER BY rango ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllActivos(): array
    {
        return $this->db->query('SELECT * FROM niveles_mago WHERE estado = 1 ORDER BY rango ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM niveles_mago WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM niveles_mago')->fetchColumn();
    }

    public function existeClave(string $clave, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM niveles_mago WHERE clave = :clave';
        $params = [':clave' => $clave];
        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptoId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function existeRango(int $rango, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM niveles_mago WHERE rango = :rango';
        $params = [':rango' => $rango];
        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptoId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function existeSolapamiento(float $min, float $max, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM niveles_mago WHERE (min_nota < :max AND :min < max_nota)';
        $params = [':min' => $min, ':max' => $max];
        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $exceptoId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO niveles_mago (clave, nombre, rango, min_nota, max_nota, icono, clase, avatar, mana, color_mana, mensaje, poderes, costo_donacion, estado)
             VALUES (:clave, :nombre, :rango, :min_nota, :max_nota, :icono, :clase, :avatar, :mana, :color_mana, :mensaje, :poderes, :costo_donacion, :estado)'
        );
        $stmt->execute([
            ':clave'          => $data['clave'],
            ':nombre'         => $data['nombre'],
            ':rango'          => $data['rango'],
            ':min_nota'       => $data['min_nota'],
            ':max_nota'       => $data['max_nota'],
            ':icono'          => $data['icono'],
            ':clase'          => $data['clase'],
            ':avatar'         => $data['avatar'],
            ':mana'           => $data['mana'],
            ':color_mana'     => $data['color_mana'] ?? '#ffc107',
            ':mensaje'        => $data['mensaje'] ?? null,
            ':poderes'        => $data['poderes'] ?? null,
            ':costo_donacion' => $data['costo_donacion'] ?? null,
            ':estado'         => $data['estado'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE niveles_mago SET clave = :clave, nombre = :nombre, rango = :rango, min_nota = :min_nota,
                    max_nota = :max_nota, icono = :icono, clase = :clase, avatar = :avatar, mana = :mana,
                    color_mana = :color_mana, mensaje = :mensaje, poderes = :poderes,
                    costo_donacion = :costo_donacion, estado = :estado
             WHERE id = :id'
        );
        $stmt->execute([
            ':clave'          => $data['clave'],
            ':nombre'         => $data['nombre'],
            ':rango'          => $data['rango'],
            ':min_nota'       => $data['min_nota'],
            ':max_nota'       => $data['max_nota'],
            ':icono'          => $data['icono'],
            ':clase'          => $data['clase'],
            ':avatar'         => $data['avatar'],
            ':mana'           => $data['mana'],
            ':color_mana'     => $data['color_mana'] ?? '#ffc107',
            ':mensaje'        => $data['mensaje'] ?? null,
            ':poderes'        => $data['poderes'] ?? null,
            ':costo_donacion' => $data['costo_donacion'] ?? null,
            ':estado'         => $data['estado'] ?? 1,
            ':id'             => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM niveles_mago WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
