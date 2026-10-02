<?php

class TypingIndicador
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function setTyping(int $userId, int $discusionId): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO typing_indicadores (user_id, discusion_id, updated_at)
            VALUES (:user_id, :discusion_id, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ');
        $stmt->execute([':user_id' => $userId, ':discusion_id' => $discusionId]);
    }

    public function clearTyping(int $userId, int $discusionId): void
    {
        $stmt = $this->db->prepare('DELETE FROM typing_indicadores WHERE user_id = :user_id AND discusion_id = :discusion_id');
        $stmt->execute([':user_id' => $userId, ':discusion_id' => $discusionId]);
    }

    public function getTypingUsers(int $discusionId, int $excludeUserId): array
    {
        $stmt = $this->db->prepare('
            SELECT ti.user_id, CONCAT(u.apellidos, \' \', u.nombres) AS nombre
            FROM typing_indicadores ti
            INNER JOIN usuarios u ON u.id = ti.user_id
            WHERE ti.discusion_id = :discusion_id
              AND ti.user_id != :exclude_user_id
              AND ti.updated_at >= DATE_SUB(NOW(), INTERVAL 5 SECOND)
        ');
        $stmt->execute([':discusion_id' => $discusionId, ':exclude_user_id' => $excludeUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cleanExpired(): void
    {
        $stmt = $this->db->prepare('DELETE FROM typing_indicadores WHERE updated_at < DATE_SUB(NOW(), INTERVAL 10 SECOND)');
        $stmt->execute();
    }
}
