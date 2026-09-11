<?php

namespace App\Models;

class Horario extends BaseModel
{
    protected $table = 'horarios';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO horarios (dia, hora_inicio, hora_fin, aula_id, unidad_id, docente_id, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['dia'],
            $data['hora_inicio'],
            $data['hora_fin'],
            $data['aula_id'] ?? null,
            $data['unidad_id'] ?? null,
            $data['docente_id'] ?? null,
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE horarios SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findByDia($dia)
    {
        $stmt = $this->db->prepare("SELECT * FROM horarios WHERE dia = ? ORDER BY hora_inicio ASC");
        $stmt->execute([$dia]);
        return $stmt->fetchAll();
    }

    public function tieneConflictoDocente($docenteId, $dia, $horaInicio, $horaFin, $excluirId = null)
    {
        $sql = "SELECT id FROM horarios WHERE docente_id = ? AND dia = ? AND hora_inicio < ? AND hora_fin > ?";
        $params = [$docenteId, $dia, $horaFin, $horaInicio];
        if ($excluirId) { $sql .= " AND id != ?"; $params[] = $excluirId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function tieneConflictoAula($aulaId, $dia, $horaInicio, $horaFin, $excluirId = null)
    {
        $sql = "SELECT id FROM horarios WHERE aula_id = ? AND dia = ? AND hora_inicio < ? AND hora_fin > ?";
        $params = [$aulaId, $dia, $horaFin, $horaInicio];
        if ($excluirId) { $sql .= " AND id != ?"; $params[] = $excluirId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
}
