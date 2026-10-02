<?php

declare(strict_types=1);

class MensajeDiscusion
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getByDiscusion(int $discusionId, int $limit = 100, int $afterId = 0): array
    {
        $sql = '
            SELECT md.*, CONCAT(u.apellidos, \' \', u.nombres) AS autor_nombre,
                   u.foto AS autor_foto, u.rol AS autor_rol
            FROM mensajes_discusion md
            INNER JOIN usuarios u ON u.id = md.usuario_id
            WHERE md.discusion_id = :discusion_id
        ';
        $params = [':discusion_id' => $discusionId];

        if ($afterId > 0) {
            $sql .= ' AND md.id > :after_id';
            $params[':after_id'] = $afterId;
        }

        $sql .= ' ORDER BY md.created_at ASC';
        $sql .= ' LIMIT ' . $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(int $discusionId, int $usuarioId, string $mensaje): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO mensajes_discusion (discusion_id, usuario_id, mensaje)
            VALUES (:discusion_id, :usuario_id, :mensaje)
        ');
        $stmt->execute([
            ':discusion_id' => $discusionId,
            ':usuario_id' => $usuarioId,
            ':mensaje' => $mensaje,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, int $usuarioId, string $mensaje): bool
    {
        $stmt = $this->db->prepare('
            UPDATE mensajes_discusion SET mensaje = :mensaje, editado = 1
            WHERE id = :id AND usuario_id = :usuario_id
        ');
        $stmt->execute([':mensaje' => $mensaje, ':id' => $id, ':usuario_id' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT md.*, CONCAT(u.apellidos, \' \', u.nombres) AS autor_nombre,
                   u.foto AS autor_foto, u.rol AS autor_rol
            FROM mensajes_discusion md
            INNER JOIN usuarios u ON u.id = md.usuario_id
            WHERE md.id = :id
        ');
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function countByDiscusion(int $discusionId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM mensajes_discusion WHERE discusion_id = :discusion_id');
        $stmt->execute([':discusion_id' => $discusionId]);
        return (int) $stmt->fetchColumn();
    }

    public function getEditadosDesde(int $discusionId, string $since): array
    {
        $stmt = $this->db->prepare('
            SELECT md.id, md.mensaje, md.editado, md.updated_at
            FROM mensajes_discusion md
            WHERE md.discusion_id = :discusion_id
            AND md.editado = 1
            AND md.updated_at > :since
        ');
        $stmt->execute([':discusion_id' => $discusionId, ':since' => $since]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
