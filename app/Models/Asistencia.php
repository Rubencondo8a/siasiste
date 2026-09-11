<?php

namespace App\Models;

class Asistencia extends BaseModel
{
    protected $table = 'asistencias';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO asistencias (estudiante_id, unidad_id, fecha, estado, registrado_por, synced, registro_tardio)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['estudiante_id'],
            $data['unidad_id'] ?? null,
            $data['fecha'],
            $data['estado'] ?? 'presente',
            $data['registrado_por'] ?? null,
            $data['synced'] ?? 1,
            $data['registro_tardio'] ?? 0
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE asistencias SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findByFechaFiltered($fecha, $programaId = null, $unidadId = null, $docenteId = null)
    {
        $sql = "SELECT a.*, e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido,
                       u.nombre AS unidad_nombre,
                       (SELECT GROUP_CONCAT(p2.nombre SEPARATOR ', ') FROM unidad_programa up2
                        INNER JOIN programas_estudio p2 ON p2.id = up2.programa_id
                        WHERE up2.unidad_id = u.id) AS programa_nombre,
                       ad.id AS detalle_id, ad.estado AS detalle_estado, ad.motivo_id,
                       ad.otro_motivo, ad.observaciones AS detalle_observaciones,
                       m.nombre AS motivo_nombre
                FROM asistencias a
                INNER JOIN estudiantes e ON a.estudiante_id = e.id
                LEFT JOIN unidades_didacticas u ON a.unidad_id = u.id
                LEFT JOIN asistencia_detalles ad ON ad.asistencia_id = a.id
                LEFT JOIN motivos m ON ad.motivo_id = m.id
                WHERE a.deleted_at IS NULL AND a.fecha = ?";
        $params = [$fecha];

        if ($unidadId) {
            $sql .= " AND a.unidad_id = ?";
            $params[] = $unidadId;
        } elseif ($programaId) {
            $sql .= " AND EXISTS (SELECT 1 FROM unidad_programa up WHERE up.unidad_id = u.id AND up.programa_id = ?)";
            $params[] = $programaId;
        }

        if ($docenteId) {
            $sql .= " AND EXISTS (SELECT 1 FROM unidad_docente ud WHERE ud.unidad_id = u.id AND ud.docente_id = ?)";
            $params[] = $docenteId;
        }

        $sql .= " ORDER BY e.apellido, e.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByFecha($fecha)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido,
                    u.nombre AS unidad_nombre
             FROM asistencias a
             INNER JOIN estudiantes e ON a.estudiante_id = e.id
             LEFT JOIN unidades_didacticas u ON a.unidad_id = u.id
             WHERE a.fecha = ?
             ORDER BY e.apellido, e.nombre"
        );
        $stmt->execute([$fecha]);
        return $stmt->fetchAll();
    }

    public function findByEstudiante($estudianteId)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.nombre AS unidad_nombre
             FROM asistencias a
             LEFT JOIN unidades_didacticas u ON a.unidad_id = u.id
             WHERE a.estudiante_id = ?
             ORDER BY a.fecha DESC"
        );
        $stmt->execute([$estudianteId]);
        return $stmt->fetchAll();
    }

    public function findByUnidadAndFecha($unidadId, $fecha)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido
             FROM asistencias a
             INNER JOIN estudiantes e ON a.estudiante_id = e.id
             WHERE a.unidad_id = ? AND a.fecha = ?
             ORDER BY e.apellido, e.nombre"
        );
        $stmt->execute([$unidadId, $fecha]);
        return $stmt->fetchAll();
    }

    public function countByMonth($month, $year)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM asistencias
             WHERE MONTH(fecha) = ? AND YEAR(fecha) = ?"
        );
        $stmt->execute([$month, $year]);
        return $stmt->fetch()['total'];
    }

    public function getEstadisticas()
    {
        $stmt = $this->db->query(
            "SELECT
                estado,
                COUNT(*) AS total,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM asistencias), 2) AS porcentaje
             FROM asistencias
             GROUP BY estado
             ORDER BY total DESC"
        );
        return $stmt->fetchAll();
    }
}
