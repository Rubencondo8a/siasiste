<?php

declare(strict_types=1);

class PuntoParticipacion
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getPorSemana(int $semanaId, int $cursoId): array
    {
        $stmt = $this->db->prepare('
            SELECT pp.*, u.apellidos, u.nombres, u.username
            FROM puntos_participacion pp
            INNER JOIN usuarios u ON u.id = pp.estudiante_id
            WHERE pp.semana_id = :semana_id AND pp.curso_id = :curso_id
            ORDER BY u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':semana_id' => $semanaId, ':curso_id' => $cursoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPorEstudiante(int $cursoId, int $estudianteId): array
    {
        $stmt = $this->db->prepare('
            SELECT pp.*, s.numero_semana, s.nombre AS semana_nombre
            FROM puntos_participacion pp
            INNER JOIN semanas s ON s.id = pp.semana_id
            WHERE pp.curso_id = :curso_id AND pp.estudiante_id = :estudiante_id
            ORDER BY pp.created_at DESC
            LIMIT 100
        ');
        $stmt->execute([':curso_id' => $cursoId, ':estudiante_id' => $estudianteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPorEstudiantePaginado(int $cursoId, int $estudianteId, int $page = 1, int $perPage = 10, ?int $semanaId = null): array
    {
        $offset = max(0, ($page - 1) * $perPage);

        $whereExtra = '';
        $paramsCount = [':curso_id' => $cursoId, ':estudiante_id' => $estudianteId];
        if ($semanaId !== null) {
            $whereExtra = ' AND pp.semana_id = :semana_id';
            $paramsCount[':semana_id'] = $semanaId;
        }

        $countStmt = $this->db->prepare('
            SELECT COUNT(*) FROM puntos_participacion pp
            WHERE pp.curso_id = :curso_id AND pp.estudiante_id = :estudiante_id' . $whereExtra
        );
        $countStmt->execute($paramsCount);
        $total = (int) $countStmt->fetchColumn();

        $paramsData = [':curso_id' => $cursoId, ':estudiante_id' => $estudianteId];
        if ($semanaId !== null) {
            $paramsData[':semana_id'] = $semanaId;
        }

        $stmt = $this->db->prepare('
            SELECT pp.*, s.numero_semana, s.nombre AS semana_nombre
            FROM puntos_participacion pp
            INNER JOIN semanas s ON s.id = pp.semana_id
            WHERE pp.curso_id = :curso_id AND pp.estudiante_id = :estudiante_id' . $whereExtra . '
            ORDER BY pp.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':curso_id', $cursoId, PDO::PARAM_INT);
        $stmt->bindValue(':estudiante_id', $estudianteId, PDO::PARAM_INT);
        if ($semanaId !== null) {
            $stmt->bindValue(':semana_id', $semanaId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
        ];
    }

    public function getTotalEstudiante(int $cursoId, int $estudianteId): int
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(puntos), 0) FROM puntos_participacion
            WHERE curso_id = :curso_id AND estudiante_id = :estudiante_id
        ');
        $stmt->execute([':curso_id' => $cursoId, ':estudiante_id' => $estudianteId]);
        return (int) $stmt->fetchColumn();
    }

    public function getResumenEstudiante(int $cursoId, int $estudianteId): array
    {
        $total = $this->getTotalEstudiante($cursoId, $estudianteId);
        $cristales = (int) floor($total / 16);
        $sobrante = $total % 16;
        return [
            'total_puntos' => $total,
            'cristales' => $cristales,
            'sobrante' => $sobrante,
        ];
    }

    public function getTotalEnSemana(int $semanaId, int $cursoId): int
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(puntos), 0) FROM puntos_participacion
            WHERE semana_id = :semana_id AND curso_id = :curso_id
        ');
        $stmt->execute([':semana_id' => $semanaId, ':curso_id' => $cursoId]);
        return (int) $stmt->fetchColumn();
    }

    public function registrar(int $estudianteId, int $cursoId, int $semanaId, int $puntos, ?string $motivo, int $docenteId, ?int $motivoId = null): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO puntos_participacion (estudiante_id, curso_id, semana_id, puntos, motivo, motivo_id, docente_id)
            VALUES (:estudiante_id, :curso_id, :semana_id, :puntos, :motivo, :motivo_id, :docente_id)
        ');
        $stmt->execute([
            ':estudiante_id' => $estudianteId,
            ':curso_id' => $cursoId,
            ':semana_id' => $semanaId,
            ':puntos' => $puntos,
            ':motivo' => $motivo !== null && $motivo !== '' ? $motivo : null,
            ':motivo_id' => $motivoId,
            ':docente_id' => $docenteId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function eliminar(int $puntoId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM puntos_participacion WHERE id = :id');
        $stmt->execute([':id' => $puntoId]);
        return $stmt->rowCount() > 0;
    }

    public function getPorId(int $puntoId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT pp.*, s.numero_semana, s.nombre AS semana_nombre
            FROM puntos_participacion pp
            INNER JOIN semanas s ON s.id = pp.semana_id
            WHERE pp.id = :id
        ');
        $stmt->execute([':id' => $puntoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getPuntosPorCurso(int $cursoId): array
    {
        $stmt = $this->db->prepare('
            SELECT pp.estudiante_id, u.apellidos, u.nombres,
                   COALESCE(SUM(pp.puntos), 0) AS total_puntos,
                   FLOOR(COALESCE(SUM(pp.puntos), 0) / 16) AS cristales,
                   COALESCE(SUM(pp.puntos), 0) % 16 AS sobrante
            FROM puntos_participacion pp
            INNER JOIN usuarios u ON u.id = pp.estudiante_id
            WHERE pp.curso_id = :curso_id
            GROUP BY pp.estudiante_id, u.apellidos, u.nombres
            ORDER BY total_puntos DESC
        ');
        $stmt->execute([':curso_id' => $cursoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistorialCurso(int $cursoId): array
    {
        $stmt = $this->db->prepare('
            SELECT pp.estudiante_id, pp.puntos, pp.motivo, pp.created_at,
                   s.numero_semana, s.nombre AS semana_nombre
            FROM puntos_participacion pp
            INNER JOIN semanas s ON s.id = pp.semana_id
            WHERE pp.curso_id = :curso_id
            ORDER BY pp.created_at DESC
            LIMIT 200
        ');
        $stmt->execute([':curso_id' => $cursoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
