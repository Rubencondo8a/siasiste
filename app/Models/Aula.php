<?php

namespace App\Models;

class Aula extends BaseModel
{
    protected $table = 'aulas';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO aulas (codigo, nombre, capacidad, edificio, tipo, activo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['codigo'],
            $data['nombre'],
            $data['capacidad'] ?? 40,
            $data['edificio'] ?? null,
            $data['tipo'] ?? null,
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE aulas SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }
}
