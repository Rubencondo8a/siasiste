<?php

declare(strict_types=1);

class Equipo
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getBySemana(int $semana_id): array
    {
        $stmt = $this->db->prepare('
            SELECT g.id, g.semana_id, g.numero, g.nombre,
                   (SELECT COUNT(*) FROM equipo_estudiantes ge WHERE ge.grupo_id = g.id) AS total_integrantes
            FROM equipos g
            WHERE g.semana_id = :semana_id
            ORDER BY g.numero ASC
        ');
        $stmt->execute([':semana_id' => $semana_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getIntegrantesByGrupo(int $grupo_id): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id, u.apellidos, u.nombres, u.username
            FROM equipo_estudiantes ge
            INNER JOIN usuarios u ON u.id = ge.estudiante_id
            WHERE ge.grupo_id = :grupo_id
            ORDER BY u.apellidos ASC, u.nombres ASC
        ');
        $stmt->execute([':grupo_id' => $grupo_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBySemanaConIntegrantes(int $semana_id): array
    {
        $equipos = $this->getBySemana($semana_id);
        foreach ($equipos as &$g) {
            $g['integrantes'] = $this->getIntegrantesByGrupo((int) $g['id']);
        }
        unset($g);
        return $equipos;
    }

    public function getIntegrantesIdsByGrupo(int $grupo_id): array
    {
        $stmt = $this->db->prepare('SELECT estudiante_id FROM equipo_estudiantes WHERE grupo_id = :grupo_id');
        $stmt->execute([':grupo_id' => $grupo_id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Mapa estudiante_id => [grupo_id, equipo_numero, equipo_nombre] para una semana.
     */
    public function getEstudiantesConGrupo(int $semana_id): array
    {
        $stmt = $this->db->prepare('
            SELECT ge.estudiante_id, ge.grupo_id, g.numero AS equipo_numero, g.nombre AS equipo_nombre
            FROM equipo_estudiantes ge
            INNER JOIN equipos g ON g.id = ge.grupo_id
            WHERE ge.semana_id = :semana_id
        ');
        $stmt->execute([':semana_id' => $semana_id]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['estudiante_id']] = [
                'grupo_id' => (int) $row['grupo_id'],
                'equipo_numero' => (int) $row['equipo_numero'],
                'equipo_nombre' => $row['equipo_nombre'],
            ];
        }
        return $map;
    }

    public function getGrupoIdDeEstudiante(int $semana_id, int $estudiante_id): ?int
    {
        $stmt = $this->db->prepare('SELECT grupo_id FROM equipo_estudiantes WHERE semana_id = :semana_id AND estudiante_id = :estudiante_id LIMIT 1');
        $stmt->execute([':semana_id' => $semana_id, ':estudiante_id' => $estudiante_id]);
        $g = $stmt->fetchColumn();
        return $g === false ? null : (int) $g;
    }

    public function getSemanaIdByGrupo(int $grupo_id): ?int
    {
        $stmt = $this->db->prepare('SELECT semana_id FROM equipos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $grupo_id]);
        $s = $stmt->fetchColumn();
        return $s === false ? null : (int) $s;
    }

    public function countBySemana(int $semana_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM equipos WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semana_id]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Reemplaza la distribuciÃ³n de equipos de una semana, barajando a los estudiantes.
     * No toca las calificaciones (las notas grupales se conservan).
     */
    public function reemplazar(int $semana_id, int $num_equipos, array $estudianteIds): void
    {
        $stmt = $this->db->prepare('DELETE FROM equipo_estudiantes WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semana_id]);
        $stmt = $this->db->prepare('DELETE FROM equipos WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semana_id]);

        if ($num_equipos <= 0 || empty($estudianteIds)) {
            return;
        }

        shuffle($estudianteIds);
        $base = intdiv(count($estudianteIds), $num_equipos);
        $resto = count($estudianteIds) % $num_equipos;

        $insertGrupo = $this->db->prepare('INSERT INTO equipos (semana_id, numero, nombre) VALUES (:semana_id, :numero, :nombre)');
        $insertIntegrante = $this->db->prepare('INSERT INTO equipo_estudiantes (grupo_id, semana_id, estudiante_id) VALUES (:grupo_id, :semana_id, :estudiante_id)');

        $idx = 0;
        for ($i = 1; $i <= $num_equipos; $i++) {
            $tam = $base + ($i <= $resto ? 1 : 0);
            if ($tam <= 0) {
                continue;
            }
            $insertGrupo->execute([
                ':semana_id' => $semana_id,
                ':numero' => $i,
                ':nombre' => 'Equipo ' . $i,
            ]);
            $grupoId = (int) $this->db->lastInsertId();
            for ($j = 0; $j < $tam; $j++) {
                $insertIntegrante->execute([
                    ':grupo_id' => $grupoId,
                    ':semana_id' => $semana_id,
                    ':estudiante_id' => $estudianteIds[$idx++],
                ]);
            }
        }
    }

    /**
     * Mueve o asigna un estudiante a un grupo de la semana (upsert).
     */
    public function moverEstudiante(int $semana_id, int $estudiante_id, int $grupo_destino_id): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO equipo_estudiantes (grupo_id, semana_id, estudiante_id) VALUES (:grupo_id, :semana_id, :estudiante_id)
             ON DUPLICATE KEY UPDATE grupo_id = VALUES(grupo_id)'
        );
        $stmt->execute([
            ':grupo_id' => $grupo_destino_id,
            ':semana_id' => $semana_id,
            ':estudiante_id' => $estudiante_id,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function eliminar(int $semana_id): void
    {
        $stmt = $this->db->prepare('DELETE FROM equipo_estudiantes WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semana_id]);
        $stmt = $this->db->prepare('DELETE FROM equipos WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semana_id]);
    }

    /**
     * Copia la distribuciÃ³n de equipos de una semana origen a una semana destino.
     * Solo copia estudiantes que sigan asignados al curso.
     * Retorna resumen de la operaciÃ³n.
     */
    public function copiar(int $semanaOrigen, int $semanaDestino, array $estudiantesActivos): array
    {
        $equiposOrigen = $this->getBySemana($semanaOrigen);
        if (empty($equiposOrigen)) {
            return ['copiados' => 0, 'estudiantes' => 0, 'mensaje' => 'La semana origen no tiene equipos.'];
        }

        $activosIds = array_flip(array_map('intval', array_column($estudiantesActivos, 'id')));

        $stmt = $this->db->prepare('DELETE FROM equipo_estudiantes WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semanaDestino]);
        $stmt = $this->db->prepare('DELETE FROM equipos WHERE semana_id = :semana_id');
        $stmt->execute([':semana_id' => $semanaDestino]);

        $insertGrupo = $this->db->prepare('INSERT INTO equipos (semana_id, numero, nombre) VALUES (:semana_id, :numero, :nombre)');
        $insertIntegrante = $this->db->prepare('INSERT INTO equipo_estudiantes (grupo_id, semana_id, estudiante_id) VALUES (:grupo_id, :semana_id, :estudiante_id)');

        $totalequipos = 0;
        $totalEstudiantes = 0;

        foreach ($equiposOrigen as $g) {
            $insertGrupo->execute([
                ':semana_id' => $semanaDestino,
                ':numero' => (int) $g['numero'],
                ':nombre' => $g['nombre'],
            ]);
            $nuevoGrupoId = (int) $this->db->lastInsertId();
            $totalequipos++;

            $integrantesOrigen = $this->getIntegrantesIdsByGrupo((int) $g['id']);
            foreach ($integrantesOrigen as $estId) {
                if (!isset($activosIds[$estId])) {
                    continue;
                }
                $insertIntegrante->execute([
                    ':grupo_id' => $nuevoGrupoId,
                    ':semana_id' => $semanaDestino,
                    ':estudiante_id' => $estId,
                ]);
                $totalEstudiantes++;
            }
        }

        return [
            'copiados' => $totalequipos,
            'estudiantes' => $totalEstudiantes,
            'mensaje' => $totalequipos . ' grupo(s) copiados con ' . $totalEstudiantes . ' estudiante(s).',
        ];
    }
}

if (!class_exists('Grupo')) { class_alias('Equipo', 'Grupo'); }

