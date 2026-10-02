<?php

declare(strict_types=1);

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT id, CONCAT(apellidos, ' ', nombres) AS nombre, username, rol, created_at FROM usuarios ORDER BY apellidos ASC, nombres ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllByRole(string $role): array
    {
        $stmt = $this->db->prepare("SELECT id, CONCAT(apellidos, ' ', nombres) AS nombre, username, rol, created_at FROM usuarios WHERE rol = :rol ORDER BY apellidos ASC, nombres ASC");
        $stmt->execute([':rol' => $role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDocentesActivos(): array
    {
        return $this->db->query('SELECT id, apellidos, nombres FROM usuarios WHERE rol = "docente" AND estado = 1 ORDER BY apellidos ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllEstudiantesActivos(): array
    {
        return $this->db->query('SELECT id, apellidos, nombres, username FROM usuarios WHERE rol = "estudiante" AND estado = 1 ORDER BY apellidos ASC, nombres ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllEstudiantesActivosConCarrera(): array
    {
        return $this->db->query('SELECT id, apellidos, nombres, username, carrera_id FROM usuarios WHERE rol = "estudiante" AND estado = 1 ORDER BY apellidos ASC, nombres ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, CONCAT(apellidos, ' ', nombres) AS nombre, username, rol, created_at FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByIdSimple(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, nombres, apellidos, dni FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (apellidos, nombres, username, password, rol) VALUES (:apellidos, :nombres, :username, :password, :rol)"
        );
        $stmt->execute([
            ':apellidos' => $data['apellidos'],
            ':nombres' => $data['nombres'],
            ':username' => $data['username'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':rol' => $data['rol'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data['password']) && $data['password'] !== '') {
            $stmt = $this->db->prepare(
                "UPDATE usuarios SET apellidos = :apellidos, nombres = :nombres, username = :username, password = :password, rol = :rol WHERE id = :id"
            );
            $stmt->execute([
                ':apellidos' => $data['apellidos'],
                ':nombres' => $data['nombres'],
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':rol' => $data['rol'],
                ':id' => $id,
            ]);
        } else {
            $stmt = $this->db->prepare(
                "UPDATE usuarios SET apellidos = :apellidos, nombres = :nombres, username = :username, rol = :rol WHERE id = :id"
            );
            $stmt->execute([
                ':apellidos' => $data['apellidos'],
                ':nombres' => $data['nombres'],
                ':username' => $data['username'],
                ':rol' => $data['rol'],
                ':id' => $id,
            ]);
        }
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = :rol");
        $stmt->execute([':rol' => $role]);
        return (int) $stmt->fetchColumn();
    }

    public function getPasswordById(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT password FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $password = $stmt->fetchColumn();
        return $password === false ? null : (string) $password;
    }

    public function updatePassword(int $id, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE usuarios SET password = :password, debe_cambiar_password = 0, password_expira = NULL WHERE id = :id');
        $stmt->execute([':password' => $hash, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function updateFoto(int $id, string $foto): bool
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET foto = :foto WHERE id = :id');
        $stmt->execute([':foto' => $foto, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getStudentById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, CONCAT(u.apellidos, ' ', u.nombres) AS nombre, u.username, u.rol,
                    c.id AS criterio_id, c.nombre AS criterio_nombre, c.porcentaje,
                    s.id AS semana_id, s.nombre AS semana_nombre,
                    cal.nota
             FROM usuarios u
             LEFT JOIN calificaciones cal ON cal.estudiante_id = u.id
             LEFT JOIN criterios c ON c.id = cal.criterio_id
             LEFT JOIN semanas s ON s.id = c.semana_id
             WHERE u.id = :id AND u.rol = 'estudiante'
             ORDER BY s.numero_semana, c.nombre"
        );
        $stmt->execute([':id' => $id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        $student = [
            'id' => $rows[0]['id'],
            'nombre' => $rows[0]['nombre'],
            'username' => $rows[0]['username'],
            'rol' => $rows[0]['rol'],
            'calificaciones' => [],
        ];

        foreach ($rows as $row) {
            if ($row['criterio_id'] !== null) {
                $student['calificaciones'][] = [
                    'criterio_id' => $row['criterio_id'],
                    'criterio_nombre' => $row['criterio_nombre'],
                    'porcentaje' => $row['porcentaje'],
                    'semana_id' => $row['semana_id'],
                    'semana_nombre' => $row['semana_nombre'],
                    'nota' => $row['nota'],
                ];
            }
        }

        return $student;
    }

    public function getTotalStudents(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante'");
        return (int) $stmt->fetchColumn();
    }
}
