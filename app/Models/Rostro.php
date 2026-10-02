<?php

declare(strict_types=1);

class Rostro
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getByEstudiante(int $estudiante_id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id, estudiante_id, descriptor, foto_path, created_at, updated_at
            FROM rostros
            WHERE estudiante_id = :estudiante_id
            LIMIT 1
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getDescriptorsByCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT r.estudiante_id, u.apellidos, u.nombres, r.descriptor, r.foto_path
            FROM rostros r
            INNER JOIN usuarios u ON u.id = r.estudiante_id
            INNER JOIN asignaciones a ON a.estudiante_id = r.estudiante_id
            WHERE a.curso_id = :curso_id AND u.estado = 1
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        $list = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $list[] = [
                'estudiante_id' => (int) $row['estudiante_id'],
                'apellidos'     => $row['apellidos'],
                'nombres'       => $row['nombres'],
                'descriptor'    => $row['descriptor'],
                'foto_path'     => $row['foto_path'],
            ];
        }
        return $list;
    }

    public function getEstudiantesSinRostroByCurso(int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id, u.apellidos, u.nombres
            FROM asignaciones a
            INNER JOIN usuarios u ON u.id = a.estudiante_id
            LEFT JOIN rostros r ON r.estudiante_id = u.id
            WHERE a.curso_id = :curso_id AND u.estado = 1 AND r.id IS NULL
            ORDER BY u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':curso_id' => $curso_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(int $estudiante_id, string $descriptor, string $foto_path, ?int $creado_por = null): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO rostros (estudiante_id, descriptor, foto_path, creado_por)
            VALUES (:estudiante_id, :descriptor, :foto_path, :creado_por)
            ON DUPLICATE KEY UPDATE descriptor = VALUES(descriptor), foto_path = VALUES(foto_path), creado_por = VALUES(creado_por)
        ');
        return $stmt->execute([
            ':estudiante_id' => $estudiante_id,
            ':descriptor'    => $descriptor,
            ':foto_path'     => $foto_path,
            ':creado_por'    => $creado_por,
        ]);
    }

    public function delete(int $estudiante_id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM rostros WHERE estudiante_id = :estudiante_id');
        return $stmt->execute([':estudiante_id' => $estudiante_id]);
    }
}
