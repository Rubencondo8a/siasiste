<?php

namespace App\Models;

class Auditoria
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function log($usuarioId, $accion, $entidad, $entidadId, $detalle = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $usuarioId ?: null,
            $accion,
            $entidad,
            $entidadId ? (int)$entidadId : null,
            $detalle,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
        return $this->db->lastInsertId();
    }

    public function findAll($limit = 100, $offset = 0)
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.usuario, u.nombre
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([(int)$limit, (int)$offset]);
        return $stmt->fetchAll();
    }

    public function count()
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM auditoria")->fetchColumn();
    }
}
