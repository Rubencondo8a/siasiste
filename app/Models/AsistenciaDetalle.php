<?php

namespace App\Models;

class AsistenciaDetalle extends BaseModel
{
    protected $table = 'asistencia_detalles';

    public function findByAsistenciaId($asistenciaId)
    {
        $stmt = $this->db->prepare(
            "SELECT ad.*, m.nombre AS motivo_nombre
             FROM asistencia_detalles ad
             LEFT JOIN motivos m ON ad.motivo_id = m.id
             WHERE ad.asistencia_id = ?
             LIMIT 1"
        );
        $stmt->execute([$asistenciaId]);
        return $stmt->fetch();
    }

    public function upsert($asistenciaId, $data)
    {
        $existing = $this->findByAsistenciaId($asistenciaId);
        if ($existing) {
            $fields = [];
            $params = [];
            foreach (['estado', 'motivo_id', 'otro_motivo', 'observaciones'] as $key) {
                if (array_key_exists($key, $data)) {
                    $fields[] = "{$key} = ?";
                    $params[] = $data[$key];
                }
            }
            if (empty($fields)) return $existing['id'];
            $params[] = $asistenciaId;
            $stmt = $this->db->prepare(
                "UPDATE asistencia_detalles SET " . implode(', ', $fields) . " WHERE asistencia_id = ?"
            );
            $stmt->execute($params);
            return $existing['id'];
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO asistencia_detalles (asistencia_id, estado, motivo_id, otro_motivo, observaciones)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $asistenciaId,
                $data['estado'],
                $data['motivo_id'] ?? null,
                $data['otro_motivo'] ?? null,
                $data['observaciones'] ?? null
            ]);
            return $this->db->lastInsertId();
        }
    }

    public function deleteByAsistenciaId($asistenciaId)
    {
        return $this->deleteWhere('asistencia_id', $asistenciaId);
    }
}
