<?php

namespace App\Models;

class Periodo extends BaseModel
{
    protected $table = 'periodos_academicos';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO periodos_academicos (nombre, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['nombre'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE periodos_academicos SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findActivo()
    {
        $stmt = $this->db->prepare("SELECT * FROM periodos_academicos WHERE activo = 1 ORDER BY fecha_inicio DESC LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }
}
