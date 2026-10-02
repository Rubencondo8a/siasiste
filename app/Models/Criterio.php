<?php

declare(strict_types=1);

class Criterio
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, s.nombre AS semana_nombre, s.numero_semana AS semana_numero
             FROM criterios c
             JOIN semanas s ON s.id = c.semana_id
             ORDER BY s.numero_semana ASC, c.nombre ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllBySemana(int $semana_id): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, s.nombre AS semana_nombre, s.numero_semana AS semana_numero
             FROM criterios c
             JOIN semanas s ON s.id = c.semana_id
             WHERE c.semana_id = :semana_id
             ORDER BY c.nombre ASC"
        );
        $stmt->execute([':semana_id' => $semana_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, s.nombre AS semana_nombre, s.numero_semana AS semana_numero
             FROM criterios c
             JOIN semanas s ON s.id = c.semana_id
             WHERE c.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO criterios (nombre, porcentaje, semana_id, es_grupal, tipo) VALUES (:nombre, :porcentaje, :semana_id, :es_grupal, :tipo)"
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':porcentaje' => $data['porcentaje'],
            ':semana_id' => $data['semana_id'],
            ':es_grupal' => $data['es_grupal'] ?? 0,
            ':tipo' => $data['tipo'] ?? 'conceptual',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function createMany(int $semana_id, array $items): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO criterios (nombre, porcentaje, semana_id, es_grupal, tipo) VALUES (:nombre, :porcentaje, :semana_id, :es_grupal, :tipo)"
        );
        foreach ($items as $item) {
            $stmt->execute([
                ':nombre' => $item['nombre'],
                ':porcentaje' => $item['porcentaje'],
                ':semana_id' => $semana_id,
                ':es_grupal' => $item['es_grupal'] ?? 0,
                ':tipo' => $item['tipo'] ?? 'conceptual',
            ]);
        }
    }

    public function update(int $id, array $data): bool
    {
        $current = $this->getById($id);
        if (!$current) return false;

        $stmt = $this->db->prepare(
            "UPDATE criterios SET nombre = :nombre, porcentaje = :porcentaje, tipo = :tipo WHERE id = :id"
        );
        $stmt->execute([
            ':nombre' => $data['nombre'] ?? $current['nombre'],
            ':porcentaje' => $data['porcentaje'] ?? $current['porcentaje'],
            ':tipo' => $data['tipo'] ?? $current['tipo'],
            ':id' => $id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM criterios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteForSemana(int $id, int $semana_id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM criterios WHERE id = :id AND semana_id = :semana_id");
        $stmt->execute([':id' => $id, ':semana_id' => $semana_id]);
        return $stmt->rowCount() > 0;
    }

    public function getWithGrades(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, s.nombre AS semana_nombre, s.numero_semana AS semana_numero
             FROM criterios c
             JOIN semanas s ON s.id = c.semana_id
             WHERE c.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $criterio = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$criterio) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT cal.id, cal.nota, cal.estudiante_id, CONCAT(u.apellidos, ' ', u.nombres) AS estudiante_nombre
             FROM calificaciones cal
             JOIN usuarios u ON u.id = cal.estudiante_id
             WHERE cal.criterio_id = :criterio_id
             ORDER BY CONCAT(u.apellidos, ' ', u.nombres) ASC"
        );
        $stmt->execute([':criterio_id' => $id]);
        $criterio['calificaciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $criterio;
    }

    // ==================== DOCENTE ====================

    public function getAllBySemanaSimple(int $semana_id): array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, porcentaje, es_grupal, tipo FROM criterios WHERE semana_id = :semana_id ORDER BY nombre ASC');
        $stmt->execute([':semana_id' => $semana_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGrupalBySemana(int $semana_id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, porcentaje, bloqueado, es_grupal FROM criterios WHERE semana_id = :semana_id AND es_grupal = 1 LIMIT 1');
        $stmt->execute([':semana_id' => $semana_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function existsByNombreForSemana(int $semana_id, string $nombre, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM criterios WHERE semana_id = :semana_id AND nombre = :nombre';
        $params = [':semana_id' => $semana_id, ':nombre' => $nombre];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude';
            $params[':exclude'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function getAllBySemanaConBloqueo(int $semana_id): array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, porcentaje, bloqueado, es_grupal FROM criterios WHERE semana_id = :semana_id ORDER BY nombre ASC');
        $stmt->execute([':semana_id' => $semana_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByIdForSemana(int $id, int $semana_id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, porcentaje, es_grupal, tipo FROM criterios WHERE id = :id AND semana_id = :semana_id LIMIT 1');
        $stmt->execute([':id' => $id, ':semana_id' => $semana_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getPorcentajeByIdForSemana(int $id, int $semana_id): ?float
    {
        $stmt = $this->db->prepare('SELECT porcentaje FROM criterios WHERE id = :id AND semana_id = :semana_id LIMIT 1');
        $stmt->execute([':id' => $id, ':semana_id' => $semana_id]);
        $valor = $stmt->fetchColumn();
        return $valor === false ? null : (float) $valor;
    }

    public function getIdsBySemana(int $semana_id): array
    {
        $stmt = $this->db->prepare('SELECT id FROM criterios WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semana_id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getConSemana(int $criterio_id): ?array
    {
        $stmt = $this->db->prepare('SELECT c.id, c.semana_id, s.docente_id, s.cerrada FROM criterios c INNER JOIN semanas s ON s.id = c.semana_id WHERE c.id = :id LIMIT 1');
        $stmt->execute([':id' => $criterio_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function toggleBlocked(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE criterios SET bloqueado = NOT bloqueado WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function isBlocked(int $id): int
    {
        $stmt = $this->db->prepare('SELECT bloqueado FROM criterios WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
