<?php

declare(strict_types=1);

class MotivoPunto
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Catálogo visible por el docente: motivos del sistema + los suyos */
    public function listarParaDocente(int $docenteId, bool $soloActivos = true): array
    {
        $sql = "SELECT m.id, m.docente_id, m.nombre, m.descripcion, m.clave, m.activo,
                       (m.docente_id = 0) AS es_sistema,
                       c.id AS categoria_id, c.nombre AS categoria, c.icono AS categoria_icono
                FROM motivos_puntos m
                LEFT JOIN categorias_puntos c ON c.id = m.categoria_id
                WHERE (m.docente_id = 0 OR m.docente_id = :docente_id)";
        if ($soloActivos) {
            $sql .= " AND m.activo = 1";
        }
        $sql .= " ORDER BY c.nombre ASC, m.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':docente_id' => $docenteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarCategorias(): array
    {
        return $this->db->query(
            "SELECT id, nombre, icono FROM categorias_puntos WHERE activo = 1 ORDER BY nombre ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(int $docenteId, string $nombre, ?int $categoriaId = null, ?string $descripcion = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO motivos_puntos (docente_id, nombre, categoria_id, descripcion)
             VALUES (:docente_id, :nombre, :categoria_id, :descripcion)"
        );
        try {
            $stmt->execute([
                ':docente_id' => $docenteId,
                ':nombre' => $nombre,
                ':categoria_id' => $categoriaId,
                ':descripcion' => $descripcion !== '' && $descripcion !== null ? $descripcion : null,
            ]);
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new RuntimeException('Ya existe un motivo con ese nombre.');
            }
            throw $e;
        }
        return (int) $this->db->lastInsertId();
    }

    /** Actualiza un motivo propio (los del sistema no se pueden editar). */
    public function actualizar(int $id, int $docenteId, string $nombre, ?int $categoriaId, ?string $descripcion): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE motivos_puntos
                 SET nombre = :nombre, categoria_id = :categoria_id, descripcion = :descripcion
                 WHERE id = :id AND docente_id = :docente_id AND docente_id <> 0"
            );
            $stmt->execute([
                ':nombre' => $nombre,
                ':categoria_id' => $categoriaId,
                ':descripcion' => $descripcion !== '' && $descripcion !== null ? $descripcion : null,
                ':id' => $id,
                ':docente_id' => $docenteId,
            ]);
            return true;
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw new RuntimeException('Ya existe un motivo con ese nombre.');
            }
            throw $e;
        }
    }

    /** Activa/desactiva un motivo propio (los del sistema no se pueden modificar). */
    public function alternar(int $id, int $docenteId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE motivos_puntos SET activo = 1 - activo
             WHERE id = :id AND docente_id = :docente_id AND docente_id <> 0"
        );
        $stmt->execute([':id' => $id, ':docente_id' => $docenteId]);
        return $stmt->rowCount() > 0;
    }

    /** Motivo de sistema por clave (ej. 'debate_likes'). */
    public function getIdPorClave(string $clave): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM motivos_puntos WHERE docente_id = 0 AND clave = :clave LIMIT 1"
        );
        $stmt->execute([':clave' => $clave]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    /** Resuelve un motivo para el registro de puntos. */
    public function resolver(int $id, int $docenteId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre FROM motivos_puntos
             WHERE id = :id AND activo = 1 AND (docente_id = 0 OR docente_id = :docente_id)"
        );
        $stmt->execute([':id' => $id, ':docente_id' => $docenteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
