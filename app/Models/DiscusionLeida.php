<?php

declare(strict_types=1);

class DiscusionLeida
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function marcarLeida(int $discusionId, int $usuarioId, ?int $ultimoMensajeId = null): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO discusion_leida (discusion_id, usuario_id, ultimo_mensaje_id)
            VALUES (:discusion_id, :usuario_id, :ultimo_mensaje_id)
            ON DUPLICATE KEY UPDATE ultimo_mensaje_id = VALUES(ultimo_mensaje_id), updated_at = NOW()
        ');
        $stmt->execute([
            ':discusion_id' => $discusionId,
            ':usuario_id' => $usuarioId,
            ':ultimo_mensaje_id' => $ultimoMensajeId,
        ]);
    }

    public function getUltimoLeido(int $discusionId, int $usuarioId): ?int
    {
        $stmt = $this->db->prepare('
            SELECT ultimo_mensaje_id FROM discusion_leida
            WHERE discusion_id = :discusion_id AND usuario_id = :usuario_id
        ');
        $stmt->execute([':discusion_id' => $discusionId, ':usuario_id' => $usuarioId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? (int) $result : null;
    }

    public function getMensajesNuevos(int $discusionId, int $usuarioId): int
    {
        $ultimoLeido = $this->getUltimoLeido($discusionId, $usuarioId);
        $sql = 'SELECT COUNT(*) FROM mensajes_discusion WHERE discusion_id = :discusion_id';
        $params = [':discusion_id' => $discusionId];

        if ($ultimoLeido !== null) {
            $sql .= ' AND id > :ultimo_id';
            $params[':ultimo_id'] = $ultimoLeido;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
