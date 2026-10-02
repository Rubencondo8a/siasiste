<?php

declare(strict_types=1);

class Discusion
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Cierra automáticamente los debates abiertos cuyo fecha_fin ya venció. */
    public function cerrarVencidas(): void
    {
        try {
            $this->db->prepare(
                "UPDATE discusiones SET estado = 'cerrada', cerrada_en = fecha_fin
                 WHERE estado = 'abierta' AND fecha_fin IS NOT NULL AND fecha_fin <= NOW()"
            )->execute();
        } catch (PDOException $e) {
            // no interrumpe el flujo
        }
    }

    public function getBySemana(int $semanaId): array
    {
        $this->cerrarVencidas();
        $stmt = $this->db->prepare('
            SELECT d.*, CONCAT(u.apellidos, \' \', u.nombres) AS creador_nombre, u.foto AS creador_foto,
                   (SELECT COUNT(*) FROM mensajes_discusion md WHERE md.discusion_id = d.id) AS total_mensajes,
                   (SELECT md2.id FROM mensajes_discusion md2 WHERE md2.discusion_id = d.id ORDER BY md2.id DESC LIMIT 1) AS ultimo_mensaje_id
            FROM discusiones d
            INNER JOIN usuarios u ON u.id = d.creado_por
            WHERE d.semana_id = :semana_id
            ORDER BY d.estado ASC, d.created_at DESC
        ');
        $stmt->execute([':semana_id' => $semanaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $this->cerrarVencidas();
        $stmt = $this->db->prepare('
            SELECT d.*, CONCAT(u.apellidos, \' \', u.nombres) AS creador_nombre, u.foto AS creador_foto
            FROM discusiones d
            INNER JOIN usuarios u ON u.id = d.creado_por
            WHERE d.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByIdAndSemana(int $id, int $semanaId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT d.*, CONCAT(u.apellidos, \' \', u.nombres) AS creador_nombre, u.foto AS creador_foto
            FROM discusiones d
            INNER JOIN usuarios u ON u.id = d.creado_por
            WHERE d.id = :id AND d.semana_id = :semana_id
        ');
        $stmt->execute([':id' => $id, ':semana_id' => $semanaId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO discusiones (semana_id, titulo, descripcion, creado_por, estado, fecha_inicio, fecha_fin)
            VALUES (:semana_id, :titulo, :descripcion, :creado_por, \'abierta\', :fecha_inicio, :fecha_fin)
        ');
        $stmt->execute([
            ':semana_id' => $data['semana_id'],
            ':titulo' => $data['titulo'],
            ':descripcion' => $data['descripcion'] ?? null,
            ':creado_por' => $data['creado_por'],
            ':fecha_inicio' => $data['fecha_inicio'] ?? null,
            ':fecha_fin' => $data['fecha_fin'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Reabre un debate cerrado asignando un nuevo rango de vigencia. */
    public function reabrir(int $id, string $fecha_inicio, string $fecha_fin): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE discusiones
             SET estado = 'abierta', fecha_inicio = :inicio, fecha_fin = :fin, cerrada_en = NULL
             WHERE id = :id AND estado = 'cerrada'"
        );
        $stmt->execute([':inicio' => $fecha_inicio, ':fin' => $fecha_fin, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function update(int $id, string $titulo, ?string $descripcion): bool
    {
        $stmt = $this->db->prepare('UPDATE discusiones SET titulo = :titulo, descripcion = :descripcion WHERE id = :id');
        $stmt->execute([':titulo' => $titulo, ':descripcion' => $descripcion, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function close(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE discusiones SET estado = \'cerrada\', cerrada_en = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function reopen(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE discusiones SET estado = \'abierta\', cerrada_en = NULL WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function closeAllBySemana(int $semanaId): bool
    {
        $stmt = $this->db->prepare('UPDATE discusiones SET estado = \'cerrada\' WHERE semana_id = :semana_id AND estado = \'abierta\'');
        $stmt->execute([':semana_id' => $semanaId]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $this->db->prepare('DELETE FROM discusion_leida WHERE discusion_id = :id')->execute([':id' => $id]);
        $this->db->prepare('DELETE FROM mensajes_discusion WHERE discusion_id = :id')->execute([':id' => $id]);
        $stmt = $this->db->prepare('DELETE FROM discusiones WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countBySemana(int $semanaId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM discusiones WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semanaId]);
        return (int) $stmt->fetchColumn();
    }

    public function countAbiertasBySemana(int $semanaId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM discusiones WHERE semana_id = :semana_id AND estado = \'abierta\'');
        $stmt->execute([':semana_id' => $semanaId]);
        return (int) $stmt->fetchColumn();
    }

    public function getConMensajesNuevos(int $semanaId, int $usuarioId): array
    {
        $this->cerrarVencidas();
        $stmt = $this->db->prepare('
            SELECT d.*,
                   (SELECT COUNT(*) FROM mensajes_discusion md WHERE md.discusion_id = d.id) AS total_mensajes,
                   dl.ultimo_mensaje_id,
                   (SELECT COUNT(*) FROM mensajes_discusion md2
                    WHERE md2.discusion_id = d.id
                    AND md2.id > COALESCE(dl.ultimo_mensaje_id, 0)) AS mensajes_nuevos
            FROM discusiones d
            LEFT JOIN discusion_leida dl ON dl.discusion_id = d.id AND dl.usuario_id = :usuario_id
            WHERE d.semana_id = :semana_id
            ORDER BY d.estado ASC, d.created_at DESC
        ');
        $stmt->execute([':semana_id' => $semanaId, ':usuario_id' => $usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDocente(int $docenteId): array
    {
        $this->cerrarVencidas();
        $stmt = $this->db->prepare('
            SELECT d.*,
                   s.numero_semana, s.nombre AS semana_nombre,
                   c.nombre AS curso_nombre, c.id AS curso_id,
                   (SELECT COUNT(*) FROM mensajes_discusion md WHERE md.discusion_id = d.id) AS total_mensajes,
                   (SELECT md2.id FROM mensajes_discusion md2 WHERE md2.discusion_id = d.id ORDER BY md2.id DESC LIMIT 1) AS ultimo_mensaje_id
            FROM discusiones d
            INNER JOIN semanas s ON s.id = d.semana_id
            INNER JOIN cursos c ON c.id = s.curso_id
            WHERE s.docente_id = :docente_id
            ORDER BY d.created_at DESC
        ');
        $stmt->execute([':docente_id' => $docenteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCursoForEstudiante(int $cursoId, int $estudianteId): array
    {
        $this->cerrarVencidas();
        $stmt = $this->db->prepare('
            SELECT d.*,
                   s.numero_semana, s.nombre AS semana_nombre,
                   (SELECT COUNT(*) FROM mensajes_discusion md WHERE md.discusion_id = d.id) AS total_mensajes,
                   dl.ultimo_mensaje_id,
                   (SELECT COUNT(*) FROM mensajes_discusion md2
                    WHERE md2.discusion_id = d.id
                    AND md2.id > COALESCE(dl.ultimo_mensaje_id, 0)) AS mensajes_nuevos
            FROM discusiones d
            INNER JOIN semanas s ON s.id = d.semana_id
            LEFT JOIN discusion_leida dl ON dl.discusion_id = d.id AND dl.usuario_id = :estudiante_id
            WHERE s.curso_id = :curso_id
            ORDER BY s.numero_semana ASC, d.created_at DESC
        ');
        $stmt->execute([':estudiante_id' => $estudianteId, ':curso_id' => $cursoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getParaDashboardEstudiante(int $estudianteId): array
    {
        $this->cerrarVencidas();
        $stmt = $this->db->prepare('
            SELECT d.id, d.titulo, d.estado, d.created_at, d.fecha_inicio, d.fecha_fin,
                   s.nombre AS semana_nombre, s.numero_semana,
                   c.nombre AS curso_nombre, c.id AS curso_id,
                   (SELECT COUNT(*) FROM mensajes_discusion md WHERE md.discusion_id = d.id) AS total_mensajes,
                   dl.ultimo_mensaje_id,
                   (SELECT COUNT(*) FROM mensajes_discusion md2
                    WHERE md2.discusion_id = d.id
                    AND md2.id > COALESCE(dl.ultimo_mensaje_id, 0)) AS mensajes_nuevos
            FROM discusiones d
            INNER JOIN semanas s ON s.id = d.semana_id
            INNER JOIN cursos c ON c.id = s.curso_id
            INNER JOIN asignaciones a ON a.curso_id = c.id AND a.estudiante_id = :estudiante_id
            LEFT JOIN discusion_leida dl ON dl.discusion_id = d.id AND dl.usuario_id = :estudiante_id2
            WHERE d.estado = \'abierta\'
            ORDER BY d.created_at DESC
            LIMIT 10
        ');
        $stmt->execute([':estudiante_id' => $estudianteId, ':estudiante_id2' => $estudianteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
