<?php

namespace App\Models;

class Role extends BaseModel
{
    protected $table = 'roles';

    public function findAll($orderBy = 'nombre ASC')
    {
        $this->validateOrderBy($orderBy);
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY {$orderBy}");
        return $stmt->fetchAll();
    }

    public function findActivos()
    {
        $stmt = $this->db->query("SELECT * FROM roles WHERE activo = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO roles (nombre, nombre_mostrar, descripcion, activo) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['nombre'],
            $data['nombre_mostrar'],
            $data['descripcion'] ?? null,
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE roles SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }
}
