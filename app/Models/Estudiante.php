<?php

namespace App\Models;

class Estudiante extends BaseModel
{
    protected $table = 'estudiantes';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO estudiantes (codigo, dni, nombre, apellido, grado, seccion, programa_id, email, telefono, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['codigo'],
            $data['dni'] ?? null,
            $data['nombre'],
            $data['apellido'],
            $data['grado'] ?? null,
            $data['seccion'] ?? null,
            $data['programa_id'] ?? null,
            $data['email'] ?? null,
            $data['telefono'] ?? null,
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE estudiantes SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findByCodigo($codigo)
    {
        return $this->findOneWhere('codigo', $codigo);
    }

    public function searchByQuery($q)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM estudiantes
             WHERE nombre LIKE ? OR apellido LIKE ? OR codigo LIKE ? OR email LIKE ?
             ORDER BY nombre ASC"
        );
        $term = "%{$q}%";
        $stmt->execute([$term, $term, $term, $term]);
        return $stmt->fetchAll();
    }
}
