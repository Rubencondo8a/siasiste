<?php

namespace App\Models;

class Usuario extends BaseModel
{
    protected $table = 'usuarios';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (usuario, contrasena, role_id, nombre, email, activo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['usuario'],
            $data['contrasena'],
            $data['role_id'],
            $data['nombre'],
            $data['email'] ?? null,
            $data['activo'] ?? 1
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE usuarios SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findByUsuario($usuario)
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre AS rol, r.nombre_mostrar AS rol_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.usuario = ?"
        );
        $stmt->execute([$usuario]);
        return $stmt->fetch();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre AS rol, r.nombre_mostrar AS rol_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findAll($orderBy = 'u.id ASC')
    {
        $this->validateOrderBy($orderBy);
        $stmt = $this->db->query(
            "SELECT u.*, r.nombre AS rol, r.nombre_mostrar AS rol_nombre
             FROM usuarios u
             LEFT JOIN roles r ON r.id = u.role_id
             ORDER BY {$orderBy}"
        );
        return $stmt->fetchAll();
    }

    protected function validateOrderBy($orderBy)
    {
        if (!preg_match('/^[a-zA-Z0-9_\s,\.]+$/', $orderBy)) {
            throw new \InvalidArgumentException("Invalid ORDER BY clause");
        }
    }
}
