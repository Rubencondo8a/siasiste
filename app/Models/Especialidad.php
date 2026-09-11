<?php

namespace App\Models;

class Especialidad extends BaseModel
{
    protected $table = 'especialidades';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO especialidades (nombre, activo) VALUES (?, ?)"
        );
        $stmt->execute([
            $data['nombre'],
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE especialidades SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findActivas()
    {
        $stmt = $this->db->query("SELECT * FROM especialidades WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }
}
