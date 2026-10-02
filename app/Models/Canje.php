<?php

declare(strict_types=1);

class Canje
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $estudianteId, int $beneficioId, ?int $cursoId, ?int $semanaId, int $gemasGastadas, ?array $detalles): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO canjes (estudiante_id, beneficio_id, curso_id, semana_id, gemas_gastadas, detalles)
            VALUES (:estudiante_id, :beneficio_id, :curso_id, :semana_id, :gemas_gastadas, :detalles)
        ');
        $stmt->execute([
            ':estudiante_id' => $estudianteId,
            ':beneficio_id' => $beneficioId,
            ':curso_id' => $cursoId,
            ':semana_id' => $semanaId,
            ':gemas_gastadas' => $gemasGastadas,
            ':detalles' => $detalles ? json_encode($detalles, JSON_UNESCAPED_UNICODE) : null,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function getPorEstudiante(int $estudianteId): array
    {
        $stmt = $this->db->prepare('
            SELECT c.*, b.nombre AS beneficio_nombre, b.tipo AS beneficio_tipo
            FROM canjes c
            INNER JOIN beneficios b ON b.id = c.beneficio_id
            WHERE c.estudiante_id = :estudiante_id
            ORDER BY c.created_at DESC
        ');
        $stmt->execute([':estudiante_id' => $estudianteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll(): array
    {
        $stmt = $this->db->query('
            SELECT c.*, b.nombre AS beneficio_nombre, b.tipo AS beneficio_tipo,
                   u.apellidos, u.nombres
            FROM canjes c
            INNER JOIN beneficios b ON b.id = c.beneficio_id
            INNER JOIN usuarios u ON u.id = c.estudiante_id
            ORDER BY c.created_at DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function yaCanjeo(int $estudianteId, int $beneficioId, int $cursoId, int $semanaId): bool
    {
        $stmt = $this->db->prepare('
            SELECT id FROM canjes
            WHERE estudiante_id = :estudiante_id AND beneficio_id = :beneficio_id
                  AND curso_id = :curso_id AND semana_id = :semana_id
            LIMIT 1
        ');
        $stmt->execute([
            ':estudiante_id' => $estudianteId,
            ':beneficio_id' => $beneficioId,
            ':curso_id' => $cursoId,
            ':semana_id' => $semanaId,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM canjes')->fetchColumn();
    }
}
