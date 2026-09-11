<?php

namespace App\Models;

class Alerta extends BaseModel
{
    protected $table = 'alertas';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO alertas (estudiante_id, porcentaje_inasistencia, nivel, fecha_deteccion, observaciones, derivado_a, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['estudiante_id'],
            $data['porcentaje_inasistencia'] ?? 0,
            $data['nivel'] ?? 'verde',
            $data['fecha_deteccion'] ?? date('Y-m-d H:i:s'),
            $data['observaciones'] ?? null,
            $data['derivado_a'] ?? null,
            $data['estado'] ?? 'activa'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE alertas SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findActivas()
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido, e.codigo, e.grado, e.seccion
             FROM alertas a
             INNER JOIN estudiantes e ON a.estudiante_id = e.id
             WHERE a.estado = 'activa'
             ORDER BY FIELD(a.nivel, 'rojo','amarillo','verde'), a.fecha_deteccion DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function calcularAlerta($estudianteId)
    {
        $stmt = $this->db->prepare(
            "SELECT
                e.id,
                CONCAT(e.nombre, ' ', e.apellido) AS nombre_completo,
                COUNT(a.id) AS total_clases,
                SUM(CASE WHEN a.estado = 'ausente' THEN 1 ELSE 0 END) AS total_ausencias,
                ROUND(SUM(CASE WHEN a.estado = 'ausente' THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id), 2) AS porcentaje_inasistencia
             FROM estudiantes e
             INNER JOIN asistencias a ON e.id = a.estudiante_id
             WHERE e.id = ?
             GROUP BY e.id"
        );
        $stmt->execute([$estudianteId]);
        $result = $stmt->fetch();

        if (!$result || $result['total_clases'] == 0) {
            return null;
        }

        $pct = $result['porcentaje_inasistencia'];
        if ($pct >= 30) {
            $nivel = 'rojo';
        } elseif ($pct >= 15) {
            $nivel = 'amarillo';
        } else {
            $nivel = 'verde';
        }

        $result['nivel'] = $nivel;
        return $result;
    }

    public function getAlertasByNivel($nivel)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido, e.codigo
             FROM alertas a
             INNER JOIN estudiantes e ON a.estudiante_id = e.id
             WHERE a.nivel = ?
             ORDER BY a.fecha_deteccion DESC"
        );
        $stmt->execute([$nivel]);
        return $stmt->fetchAll();
    }
}
