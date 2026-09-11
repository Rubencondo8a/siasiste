<?php

namespace App\Models;

class Unidad extends BaseModel
{
    protected $table = 'unidades_didacticas';

    public function findAll($orderBy = 'u.nombre ASC')
    {
        $this->validateOrderBy($orderBy);
        $stmt = $this->db->query("SELECT u.* FROM unidades_didacticas u ORDER BY {$orderBy}");
        return $stmt->fetchAll();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO unidades_didacticas (nombre, descripcion, semestre, seccion, horas_semanales, activo)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['semestre'] ?? 1,
            $data['seccion'] ?? null,
            $data['horas_semanales'] ?? null,
            $data['activo'] ?? 1
        ]);
        $id = $this->db->lastInsertId();
        if (!empty($data['docentes'])) {
            $this->syncDocentes($id, $data['docentes']);
        }
        if (!empty($data['programas'])) {
            $this->syncProgramas($id, $data['programas']);
        }
        return $id;
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            if ($key === 'docentes' || $key === 'programas') continue;
            $fields[] = "{$key} = ?";
            $params[] = $value;
        }
        if (!empty($fields)) {
            $params[] = $id;
            $stmt = $this->db->prepare("UPDATE unidades_didacticas SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($params);
        }
        if (array_key_exists('docentes', $data)) {
            $this->syncDocentes($id, $data['docentes']);
        }
        if (array_key_exists('programas', $data)) {
            $this->syncProgramas($id, $data['programas']);
        }
    }

    public function findByDocente($docenteId)
    {
        $stmt = $this->db->prepare(
            "SELECT u.* FROM unidades_didacticas u
             INNER JOIN unidad_docente ud ON ud.unidad_id = u.id
             WHERE ud.docente_id = ? ORDER BY u.nombre ASC"
        );
        $stmt->execute([$docenteId]);
        return $stmt->fetchAll();
    }

    public function findByPrograma($programaId)
    {
        $stmt = $this->db->prepare(
            "SELECT u.* FROM unidades_didacticas u
             INNER JOIN unidad_programa up ON up.unidad_id = u.id
             WHERE up.programa_id = ? ORDER BY u.nombre ASC"
        );
        $stmt->execute([$programaId]);
        return $stmt->fetchAll();
    }

    public function getDocentes($unidadId)
    {
        $stmt = $this->db->prepare(
            "SELECT d.* FROM docentes d
             INNER JOIN unidad_docente ud ON ud.docente_id = d.id
             WHERE ud.unidad_id = ?"
        );
        $stmt->execute([$unidadId]);
        return $stmt->fetchAll();
    }

    public function syncDocentes($unidadId, $docenteIds)
    {
        $this->db->prepare("DELETE FROM unidad_docente WHERE unidad_id = ?")->execute([$unidadId]);
        $stmt = $this->db->prepare("INSERT INTO unidad_docente (unidad_id, docente_id) VALUES (?, ?)");
        foreach ((array) $docenteIds as $did) {
            if ($did) $stmt->execute([$unidadId, $did]);
        }
    }

    public function getProgramas($unidadId)
    {
        $stmt = $this->db->prepare(
            "SELECT p.* FROM programas_estudio p
             INNER JOIN unidad_programa up ON up.programa_id = p.id
             WHERE up.unidad_id = ?"
        );
        $stmt->execute([$unidadId]);
        return $stmt->fetchAll();
    }

    public function syncProgramas($unidadId, $programaIds)
    {
        $this->db->prepare("DELETE FROM unidad_programa WHERE unidad_id = ?")->execute([$unidadId]);
        $stmt = $this->db->prepare("INSERT INTO unidad_programa (unidad_id, programa_id) VALUES (?, ?)");
        foreach ((array) $programaIds as $pid) {
            if ($pid) $stmt->execute([$unidadId, $pid]);
        }
    }

    protected function validateOrderBy($orderBy)
    {
        if (!preg_match('/^[a-zA-Z0-9_\s,\.]+$/', $orderBy)) {
            throw new \InvalidArgumentException("Invalid ORDER BY clause");
        }
    }
}
