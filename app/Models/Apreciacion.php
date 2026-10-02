<?php

declare(strict_types=1);

class Apreciacion
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getApreciacion(int $semanaId, int $estudianteId, int $cursoId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT a.*, CONCAT(u.apellidos, " ", u.nombres) AS docente_nombre
            FROM apreciaciones a
            INNER JOIN usuarios u ON u.id = a.docente_id
            WHERE a.semana_id = :semana_id AND a.estudiante_id = :estudiante_id AND a.curso_id = :curso_id
        ');
        $stmt->execute([':semana_id' => $semanaId, ':estudiante_id' => $estudianteId, ':curso_id' => $cursoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getApreciacionSimple(int $semanaId, int $estudianteId, int $cursoId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(puntos, 0) FROM apreciaciones WHERE semana_id = :semana_id AND estudiante_id = :estudiante_id AND curso_id = :curso_id');
        $stmt->execute([':semana_id' => $semanaId, ':estudiante_id' => $estudianteId, ':curso_id' => $cursoId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (float) $result : 0.0;
    }

    public function upsert(
        int $estudianteId,
        int $cursoId,
        int $semanaId,
        float $puntos,
        ?string $motivo,
        int $docenteId
    ): bool {
        $stmt = $this->db->prepare('
            INSERT INTO apreciaciones (estudiante_id, semana_id, curso_id, puntos, motivo, docente_id)
            VALUES (:estudiante_id, :semana_id, :curso_id, :puntos, :motivo, :docente_id)
            ON DUPLICATE KEY UPDATE
                puntos = VALUES(puntos),
                motivo = VALUES(motivo),
                docente_id = VALUES(docente_id)
        ');
        $stmt->execute([
            ':estudiante_id' => $estudianteId,
            ':semana_id'     => $semanaId,
            ':curso_id'      => $cursoId,
            ':puntos'        => $puntos,
            ':motivo'        => $motivo !== null && $motivo !== '' ? $motivo : null,
            ':docente_id'    => $docenteId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM apreciaciones WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getBySemanaCurso(int $semanaId, int $cursoId): array
    {
        $stmt = $this->db->prepare('
            SELECT a.*, CONCAT(u.apellidos, " ", u.nombres) AS estudiante_nombre
            FROM apreciaciones a
            INNER JOIN usuarios u ON u.id = a.estudiante_id
            WHERE a.semana_id = :semana_id AND a.curso_id = :curso_id
            ORDER BY u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':semana_id' => $semanaId, ':curso_id' => $cursoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCurso(int $cursoId): array
    {
        $stmt = $this->db->prepare('
            SELECT a.*, CONCAT(u.apellidos, " ", u.nombres) AS estudiante_nombre,
                   sem.numero_semana, sem.nombre AS semana_nombre
            FROM apreciaciones a
            INNER JOIN usuarios u ON u.id = a.estudiante_id
            INNER JOIN semanas sem ON sem.id = a.semana_id
            WHERE a.curso_id = :curso_id
            ORDER BY sem.numero_semana ASC, u.apellidos ASC
        ');
        $stmt->execute([':curso_id' => $cursoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByEstudianteCurso(int $estudianteId, int $cursoId): array
    {
        $stmt = $this->db->prepare('
            SELECT a.*, sem.numero_semana, sem.nombre AS semana_nombre
            FROM apreciaciones a
            INNER JOIN semanas sem ON sem.id = a.semana_id
            WHERE a.estudiante_id = :estudiante_id AND a.curso_id = :curso_id
            ORDER BY sem.numero_semana ASC
        ');
        $stmt->execute([':estudiante_id' => $estudianteId, ':curso_id' => $cursoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalApreciacionesCurso(int $cursoId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(puntos), 0) FROM apreciaciones WHERE curso_id = :curso_id');
        $stmt->execute([':curso_id' => $cursoId]);
        return (float) $stmt->fetchColumn();
    }

    public function getApreciacionesPorEstudiante(int $estudianteId): array
    {
        $stmt = $this->db->prepare('SELECT semana_id, curso_id, puntos FROM apreciaciones WHERE estudiante_id = :estudiante_id');
        $stmt->execute([':estudiante_id' => $estudianteId]);
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int) $row['semana_id']][(int) $row['curso_id']] = (float) $row['puntos'];
        }
        return $result;
    }
}
