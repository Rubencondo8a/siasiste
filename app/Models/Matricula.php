<?php

namespace App\Models;

class Matricula extends BaseModel
{
    protected $table = 'matriculas';

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO matriculas (estudiante_id, programa_id, periodo_id, semestre, estado)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['estudiante_id'],
            $data['programa_id'],
            $data['periodo_id'],
            $data['semestre'] ?? 1,
            $data['estado'] ?? 'activo'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        [$fields, $params] = $this->buildUpdate($data);
        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE matriculas SET {$fields} WHERE id = ?");
        return $stmt->execute($params);
    }

    public function findByEstudiante($estudianteId)
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, pa.nombre AS periodo_nombre, pe.nombre AS programa_nombre
             FROM matriculas m
             LEFT JOIN periodos_academicos pa ON pa.id = m.periodo_id
             LEFT JOIN programas_estudio pe ON pe.id = m.programa_id
             WHERE m.estudiante_id = ?
             ORDER BY m.semestre ASC, pa.fecha_inicio ASC"
        );
        $stmt->execute([$estudianteId]);
        return $stmt->fetchAll();
    }

    public function findActiva($estudianteId)
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, pa.nombre AS periodo_nombre, pe.nombre AS programa_nombre
             FROM matriculas m
             LEFT JOIN periodos_academicos pa ON pa.id = m.periodo_id
             LEFT JOIN programas_estudio pe ON pe.id = m.programa_id
             WHERE m.estudiante_id = ? AND m.estado = 'activo'
             ORDER BY m.semestre DESC LIMIT 1"
        );
        $stmt->execute([$estudianteId]);
        return $stmt->fetch();
    }

    public function avanzarSemestre($estudianteId, $nuevoPeriodoId = null)
    {
        $this->db->beginTransaction();
        try {
            $activa = $this->findActiva($estudianteId);
            if (!$activa) {
                $this->db->rollBack();
                return ['error' => 'No hay matrícula activa para este estudiante'];
            }

            $programa = $this->db->prepare("SELECT duracion_semestres FROM programas_estudio WHERE id = ?");
            $programa->execute([$activa['programa_id']]);
            $prog = $programa->fetch();

            $nuevoSemestre = $activa['semestre'] + 1;
            if ($prog && $nuevoSemestre > $prog['duracion_semestres']) {
                $stmt = $this->db->prepare("UPDATE matriculas SET estado = 'egresado' WHERE id = ?");
                $stmt->execute([$activa['id']]);
                $this->db->commit();
                return ['success' => true, 'message' => 'Estudiante egresado — completó todos los semestres', 'egresado' => true];
            }

            $stmt = $this->db->prepare("UPDATE matriculas SET estado = 'egresado' WHERE id = ?");
            $stmt->execute([$activa['id']]);

            if (!$nuevoPeriodoId) {
                $periodo = $this->db->query("SELECT id FROM periodos_academicos WHERE activo = 1 ORDER BY fecha_inicio DESC LIMIT 1")->fetch();
                $nuevoPeriodoId = $periodo ? $periodo['id'] : $activa['periodo_id'];
            }

            $nuevaId = $this->create([
                'estudiante_id' => $estudianteId,
                'programa_id' => $activa['programa_id'],
                'periodo_id' => $nuevoPeriodoId,
                'semestre' => $nuevoSemestre,
                'estado' => 'activo'
            ]);

            $this->db->prepare("UPDATE estudiantes SET grado = ? WHERE id = ?")
                ->execute(["{$nuevoSemestre}° Ciclo", $estudianteId]);

            $this->db->commit();
            return ['success' => true, 'message' => "Avanzado al semestre {$nuevoSemestre}", 'matricula_id' => $nuevaId];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    public function retirar($id)
    {
        return $this->update($id, ['estado' => 'retirado']);
    }

    public function getHistorial($estudianteId)
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, pa.nombre AS periodo_nombre, pa.fecha_inicio, pa.fecha_fin,
                    pe.nombre AS programa_nombre, pe.duracion_semestres,
                    e.codigo, e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido
             FROM matriculas m
             LEFT JOIN periodos_academicos pa ON pa.id = m.periodo_id
             LEFT JOIN programas_estudio pe ON pe.id = m.programa_id
             LEFT JOIN estudiantes e ON e.id = m.estudiante_id
             WHERE m.estudiante_id = ?
             ORDER BY m.semestre ASC"
        );
        $stmt->execute([$estudianteId]);
        return $stmt->fetchAll();
    }

    public function getEstudiantesPorSemestre($periodoId, $semestre)
    {
        $stmt = $this->db->prepare(
            "SELECT m.*, e.codigo, e.nombre AS estudiante_nombre, e.apellido AS estudiante_apellido,
                    pe.nombre AS programa_nombre
             FROM matriculas m
             LEFT JOIN estudiantes e ON e.id = m.estudiante_id
             LEFT JOIN programas_estudio pe ON pe.id = m.programa_id
             WHERE m.periodo_id = ? AND m.semestre = ? AND m.estado = 'activo'
             ORDER BY e.apellido ASC, e.nombre ASC"
        );
        $stmt->execute([$periodoId, $semestre]);
        return $stmt->fetchAll();
    }

    public function bulkCreate($rows)
    {
        $this->db->beginTransaction();
        $insertados = 0;
        $errores = [];
        try {
            foreach ($rows as $i => $row) {
                try {
                    $this->create($row);
                    $insertados++;
                } catch (\Exception $e) {
                    $errores[] = ['fila' => $i + 1, 'error' => $e->getMessage()];
                }
            }
            $this->db->commit();
            return ['insertados' => $insertados, 'errores' => $errores];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['insertados' => 0, 'errores' => [['fila' => 0, 'error' => $e->getMessage()]]];
        }
    }
}
