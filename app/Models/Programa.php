<?php

namespace App\Models;

class Programa extends BaseModel
{
    protected $table = 'programas_estudio';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO programas_estudio (nombre, descripcion, duracion_semestres, activo) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['duracion_semestres'] ?? 6,
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE programas_estudio SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }
}
