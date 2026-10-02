<?php

declare(strict_types=1);

class AyudaCompanero
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function countDonadas(int $emisor_id): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ayuda_companeros WHERE emisor_id = :emisor_id');
        $stmt->execute([':emisor_id' => $emisor_id]);
        return (int) $stmt->fetchColumn();
    }

    public function yaAyudo(int $emisor_id, int $receptor_id, int $curso_id, int $semana_id): bool
    {
        $stmt = $this->db->prepare('
            SELECT id FROM ayuda_companeros
            WHERE emisor_id = :emisor_id AND receptor_id = :receptor_id AND curso_id = :curso_id AND semana_id = :semana_id
            LIMIT 1
        ');
        $stmt->execute([
            ':emisor_id' => $emisor_id,
            ':receptor_id' => $receptor_id,
            ':curso_id' => $curso_id,
            ':semana_id' => $semana_id,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function registrar(int $emisor_id, int $receptor_id, int $curso_id, int $semana_id, float $cantidad = 1.0, float $costo = 0.0078125): bool
    {
        $stmt = $this->db->prepare('
            INSERT INTO ayuda_companeros (emisor_id, receptor_id, curso_id, semana_id, cantidad, costo, estado)
            VALUES (:emisor_id, :receptor_id, :curso_id, :semana_id, :cantidad, :costo, "activa")
        ');
        $stmt->execute([
            ':emisor_id' => $emisor_id,
            ':receptor_id' => $receptor_id,
            ':curso_id' => $curso_id,
            ':semana_id' => $semana_id,
            ':cantidad' => $cantidad,
            ':costo' => $costo,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function sumaCostoDonado(int $emisor_id): float
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(costo), 0) FROM ayuda_companeros
            WHERE emisor_id = :emisor_id
        ');
        $stmt->execute([':emisor_id' => $emisor_id]);
        return (float) $stmt->fetchColumn();
    }

    public function sumaActiva(int $receptor_id, int $curso_id, int $semana_id): float
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(cantidad), 0) FROM ayuda_companeros
            WHERE receptor_id = :receptor_id AND curso_id = :curso_id AND semana_id = :semana_id AND estado = "activa"
        ');
        $stmt->execute([':receptor_id' => $receptor_id, ':curso_id' => $curso_id, ':semana_id' => $semana_id]);
        return (float) $stmt->fetchColumn();
    }

    public function sumaTotalRecibida(int $receptor_id): float
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(cantidad), 0) FROM ayuda_companeros
            WHERE receptor_id = :receptor_id
        ');
        $stmt->execute([':receptor_id' => $receptor_id]);
        return (float) $stmt->fetchColumn();
    }

    public function marcarUsadas(int $receptor_id, int $curso_id, int $semana_id): void
    {
        $stmt = $this->db->prepare('
            UPDATE ayuda_companeros SET estado = "usada"
            WHERE receptor_id = :receptor_id AND curso_id = :curso_id AND semana_id = :semana_id AND estado = "activa"
        ');
        $stmt->execute([':receptor_id' => $receptor_id, ':curso_id' => $curso_id, ':semana_id' => $semana_id]);
    }

    /**
     * Maná disponible: base del nivel menos el costo acumulado de las ayudas.
     * El costo por unidad depende del nivel (1/64 Aprendiz, 1/128 Mago, 1/256 Super Mago, 1/512 Merlín).
     */
    public function balance(int $estudiante_id, float $manaNivel): float
    {
        $consumido = $this->sumaCostoDonado($estudiante_id);
        $balance = $manaNivel - $consumido;
        return max(0, (float) round($balance, 4));
    }

    /**
     * Maná necesaria para la oportunidad de examen según la población del curso.
     * Curso con 6 o más estudiantes requiere 2 mana; cursos más pequeños requieren 1.
     */
    public function objetivoMana(int $poblacionCurso): float
    {
        return $poblacionCurso >= 6 ? 2.0 : 1.0;
    }

    /**
     * Cursos del estudiante con sus compañeros (excluye a sí mismo) y las semanas del curso.
     */
    public function getCursosConCompaneros(int $estudiante_id): array
    {
        $cursos = $this->db->prepare('
            SELECT c.id AS curso_id, c.nombre AS curso_nombre, c.codigo AS curso_codigo
            FROM asignaciones a
            INNER JOIN cursos c ON c.id = a.curso_id
            WHERE a.estudiante_id = :estudiante_id AND c.estado = 1
            ORDER BY c.nombre ASC
        ');
        $cursos->execute([':estudiante_id' => $estudiante_id]);
        $result = $cursos->fetchAll(PDO::FETCH_ASSOC);

        $stmtSemanas = $this->db->prepare('
            SELECT id, nombre, numero_semana FROM semanas
            WHERE curso_id = :curso_id
            ORDER BY numero_semana ASC
        ');

        foreach ($result as &$curso) {
            $stmtSemanas->execute([':curso_id' => $curso['curso_id']]);
            $curso['semanas'] = $stmtSemanas->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare('
                SELECT u.id, u.apellidos, u.nombres
                FROM asignaciones ec
                INNER JOIN usuarios u ON u.id = ec.estudiante_id
                WHERE ec.curso_id = :curso_id AND u.id <> :yo_id AND u.estado = 1
                ORDER BY u.apellidos ASC, u.nombres ASC
            ');
            $stmt->execute([
                ':curso_id' => $curso['curso_id'],
                ':yo_id' => $estudiante_id,
            ]);
            $curso['companeros'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($curso);

        return $result;
    }

    /**
     * Semanas (id) del curso en las que el emisor ya ayudó a cada compañero.
     */
    public function getSemanasAyudadasPorCompanero(int $emisor_id, int $curso_id): array
    {
        $stmt = $this->db->prepare('
            SELECT receptor_id, semana_id FROM ayuda_companeros
            WHERE emisor_id = :emisor_id AND curso_id = :curso_id AND semana_id IS NOT NULL
        ');
        $stmt->execute([':emisor_id' => $emisor_id, ':curso_id' => $curso_id]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['receptor_id']][] = (int) $row['semana_id'];
        }
        return $map;
    }

    /**
     * Maná recibida por el estudiante agrupadas por curso y semana (activo + objetivo).
     */
    public function getRecibidasPorCurso(int $estudiante_id): array
    {
        $stmt = $this->db->prepare('
            SELECT ac.curso_id, c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   ac.semana_id, s.numero_semana,
                   COALESCE(SUM(ac.cantidad), 0) AS acumulado,
                   (SELECT COUNT(*) FROM asignaciones ec WHERE ec.curso_id = ac.curso_id) AS poblacion
            FROM ayuda_companeros ac
            INNER JOIN cursos c ON c.id = ac.curso_id
            LEFT JOIN semanas s ON s.id = ac.semana_id
            WHERE ac.receptor_id = :estudiante_id AND ac.estado = "activa"
            GROUP BY ac.curso_id, c.nombre, c.codigo, ac.semana_id, s.numero_semana
            ORDER BY c.nombre ASC, s.numero_semana ASC
        ');
        $stmt->execute([':estudiante_id' => $estudiante_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as &$fila) {
            $fila['objetivo'] = $this->objetivoMana((int) $fila['poblacion']);
        }
        unset($fila);

        return $result;
    }

    /**
     * Maná recibida por estudiante (curso y semana) visibles para el docente de sus cursos.
     */
    /** Donantes (emisores) de mana para un receptor/curso/semana */
    public function getDonantes(int $receptor_id, int $curso_id, ?int $semana_id): array
    {
        $sql = 'SELECT u.id, u.nombres, u.apellidos, u.username,
                       a.cantidad, a.created_at
                FROM ayuda_companeros a
                INNER JOIN usuarios u ON u.id = a.emisor_id
                WHERE a.receptor_id = :receptor_id AND a.curso_id = :curso_id';
        if ($semana_id !== null && $semana_id > 0) {
            $sql .= ' AND a.semana_id = :semana_id';
        } else {
            $sql .= ' AND a.semana_id IS NULL';
        }
        $sql .= ' ORDER BY a.created_at ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':receptor_id' => $receptor_id,
            ':curso_id' => $curso_id,
        ] + ($semana_id !== null && $semana_id > 0 ? [':semana_id' => $semana_id] : []));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getManaRecibidoPorDocente(int $docente_id): array
    {
        $stmt = $this->db->prepare('
            SELECT ac.curso_id, c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   ac.semana_id, s.numero_semana, s.nombre AS semana_nombre,
                   ac.receptor_id, u.apellidos, u.nombres, u.username,
                   COALESCE(SUM(ac.cantidad), 0) AS acumulado,
                   (SELECT COUNT(*) FROM asignaciones ec WHERE ec.curso_id = ac.curso_id) AS poblacion,
                   EXISTS(SELECT 1 FROM solicitud_examen se
                          WHERE se.estudiante_id = ac.receptor_id AND se.curso_id = ac.curso_id
                            AND se.semana_id = ac.semana_id AND se.estado = "pendiente") AS tiene_pendiente
            FROM ayuda_companeros ac
            INNER JOIN cursos c ON c.id = ac.curso_id AND c.docente_id = :docente_id
            INNER JOIN usuarios u ON u.id = ac.receptor_id
            LEFT JOIN semanas s ON s.id = ac.semana_id
            WHERE ac.estado = "activa"
            GROUP BY ac.curso_id, c.nombre, c.codigo, ac.semana_id, s.numero_semana, s.nombre,
                     ac.receptor_id, u.apellidos, u.nombres, u.username
            ORDER BY c.nombre ASC, s.numero_semana ASC, u.apellidos ASC
        ');
        $stmt->execute([':docente_id' => $docente_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($result as &$fila) {
            $fila['objetivo'] = $this->objetivoMana((int) $fila['poblacion']);
        }
        unset($fila);

        return $result;
    }
}
