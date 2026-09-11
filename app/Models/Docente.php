<?php

namespace App\Models;

class Docente extends BaseModel
{
    protected $table = 'docentes';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO docentes (usuario_id, nombre, apellido, email, telefono, especialidad_id, dni, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['usuario_id'] ?? null,
            $data['nombre'],
            $data['apellido'],
            $data['email'] ?? null,
            $data['telefono'] ?? null,
            $data['especialidad_id'] ?? null,
            $data['dni'] ?? null,
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE docentes SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findByUsuarioId($usuarioId)
    {
        return $this->findOneWhere('usuario_id', $usuarioId);
    }

    public function searchByQuery($q)
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, e.nombre AS especialidad_nombre
             FROM docentes d
             LEFT JOIN especialidades e ON e.id = d.especialidad_id
             WHERE d.nombre LIKE ? OR d.apellido LIKE ? OR d.email LIKE ? OR d.dni LIKE ?
             ORDER BY d.nombre ASC"
        );
        $term = "%{$q}%";
        $stmt->execute([$term, $term, $term, $term]);
        return $stmt->fetchAll();
    }
}
