<?php

declare(strict_types=1);

class MensajeLike
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function toggle(int $mensajeId, int $usuarioId): string
    {
        $stmt = $this->db->prepare('SELECT id FROM mensaje_likes WHERE mensaje_id = :m AND usuario_id = :u');
        $stmt->execute([':m' => $mensajeId, ':u' => $usuarioId]);
        if ($stmt->fetch()) {
            $del = $this->db->prepare('DELETE FROM mensaje_likes WHERE mensaje_id = :m AND usuario_id = :u');
            $del->execute([':m' => $mensajeId, ':u' => $usuarioId]);
            return 'unliked';
        }
        $ins = $this->db->prepare('INSERT INTO mensaje_likes (mensaje_id, usuario_id) VALUES (:m, :u)');
        $ins->execute([':m' => $mensajeId, ':u' => $usuarioId]);
        return 'liked';
    }

    public function hasLiked(int $mensajeId, int $usuarioId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM mensaje_likes WHERE mensaje_id = :m AND usuario_id = :u');
        $stmt->execute([':m' => $mensajeId, ':u' => $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    public function countForDiscusion(int $discusionId, int $viewerId): array
    {
        $stmt = $this->db->prepare('
            SELECT ml.mensaje_id,
                   COUNT(*) AS total_likes,
                   MAX(CASE WHEN ml.usuario_id = :viewer THEN 1 ELSE 0 END) AS yo_like
            FROM mensaje_likes ml
            INNER JOIN mensajes_discusion md ON md.id = ml.mensaje_id
            WHERE md.discusion_id = :d
            GROUP BY ml.mensaje_id
        ');
        $stmt->execute([':d' => $discusionId, ':viewer' => $viewerId]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['mensaje_id']] = [
                'total_likes' => (int) $row['total_likes'],
                'yo_like' => (int) $row['yo_like'],
            ];
        }
        return $map;
    }

    /**
     * Likes de estudiantes recibidos por un autor dentro de una semana.
     * Solo cuentan likes dados por estudiantes a mensajes de estudiantes.
     */
    public function countLikesEstudianteEnSemana(int $autorId, int $semanaId): int
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*)
            FROM mensaje_likes ml
            INNER JOIN mensajes_discusion md ON md.id = ml.mensaje_id
            INNER JOIN discusiones d ON d.id = md.discusion_id
            INNER JOIN usuarios ua ON ua.id = md.usuario_id AND ua.rol = \'estudiante\'
            INNER JOIN usuarios ul ON ul.id = ml.usuario_id AND ul.rol = \'estudiante\'
            WHERE d.semana_id = :semana_id
              AND md.usuario_id = :autor_id
              AND ml.usuario_id <> md.usuario_id
        ');
        $stmt->execute([':semana_id' => $semanaId, ':autor_id' => $autorId]);
        return (int) $stmt->fetchColumn();
    }

    public function puntosOtorgadosEnSemana(int $estudianteId, int $semanaId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM discusion_puntos_log WHERE estudiante_id = :e AND semana_id = :s');
        $stmt->execute([':e' => $estudianteId, ':s' => $semanaId]);
        return (int) $stmt->fetchColumn();
    }

    public function registrarPunto(int $estudianteId, int $semanaId, int $discusionId, int $mensajeId): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO discusion_puntos_log (estudiante_id, semana_id, discusion_id, mensaje_id)
            VALUES (:e, :s, :d, :m)
        ');
        $stmt->execute([':e' => $estudianteId, ':s' => $semanaId, ':d' => $discusionId, ':m' => $mensajeId]);
    }

    public function getAnunciosDesde(int $discusionId, string $since): array
    {
        $stmt = $this->db->prepare('
            SELECT l.id, l.estudiante_id, CONCAT(u.nombres, \' \', u.apellidos) AS nombre
            FROM discusion_puntos_log l
            INNER JOIN usuarios u ON u.id = l.estudiante_id
            WHERE l.discusion_id = :d AND l.created_at > :since
            ORDER BY l.created_at ASC
        ');
        $stmt->execute([':d' => $discusionId, ':since' => $since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
