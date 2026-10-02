<?php

declare(strict_types=1);

class ParticipacionVivo
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Devuelve la ronda ABERTA para un curso/semana/docente (aislada por docente).
     */
    public function getAbierta(int $cursoId, int $semanaId, int $docenteId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM participaciones_activas
            WHERE curso_id = :curso_id AND semana_id = :semana_id AND docente_id = :docente_id AND estado = "abierta"
            ORDER BY id DESC LIMIT 1
        ');
        $stmt->execute([':curso_id' => $cursoId, ':semana_id' => $semanaId, ':docente_id' => $docenteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Abre una nueva ronda. Devuelve el id o null si ya hay una abierta.
     */
    public function abrir(int $cursoId, int $semanaId, int $docenteId): ?int
    {
        if ($this->getAbierta($cursoId, $semanaId, $docenteId)) {
            return null;
        }
        $stmt = $this->db->prepare('
            INSERT INTO participaciones_activas (curso_id, semana_id, docente_id, estado)
            VALUES (:curso_id, :semana_id, :docente_id, "abierta")
        ');
        $stmt->execute([':curso_id' => $cursoId, ':semana_id' => $semanaId, ':docente_id' => $docenteId]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Cierra la ronda. Devuelve los resúmenes y marca confirmado/eliminado.
     */
    public function cerrar(int $participacionId, int $docenteId): void
    {
        // Verificar propiedad
        $stmt = $this->db->prepare('SELECT id FROM participaciones_activas WHERE id = :id AND docente_id = :docente_id AND estado = "abierta"');
        $stmt->execute([':id' => $participacionId, ':docente_id' => $docenteId]);
        if (!$stmt->fetch()) {
            return;
        }
        $stmt = $this->db->prepare('UPDATE participaciones_activas SET estado = "cerrada", cerrada_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $participacionId]);

        // Los que participaron (participa=1) y tienen motivo+puntos se confirman
        // Confirmar los marcados
        $this->db->prepare('UPDATE cola_participacion SET estado = "confirmado", confirmado_at = NOW() WHERE participacion_id = :id AND participo = 1 AND motivo_id IS NOT NULL')->execute([':id' => $participacionId]);
        // Eliminar los que no participaron (no marcados con participo=1) o no tienen motivo
        $this->db->prepare('UPDATE cola_participacion SET estado = "eliminado" WHERE participacion_id = :id AND NOT (participo = 1 AND motivo_id IS NOT NULL)')->execute([':id' => $participacionId]);
    }

    /**
     * Registra/marca a un estudiante en la cola (1 sola vez). Devuelve el orden asignado.
     */
    public function autoRegistrar(int $participacionId, int $estudianteId): array
    {
        $stmt = $this->db->prepare('SELECT id FROM cola_participacion WHERE participacion_id = :pid AND estudiante_id = :eid');
        $stmt->execute([':pid' => $participacionId, ':eid' => $estudianteId]);
        if ($stmt->fetch()) {
            return ['ok' => false, 'message' => 'Ya te registraste en esta participación.', 'orden' => null];
        }

        // Siguiente orden
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(orden), 0) + 1 AS siguiente FROM cola_participacion WHERE participacion_id = :pid');
        $stmt->execute([':pid' => $participacionId]);
        $orden = (int) $stmt->fetchColumn();

        $this->db->prepare('
            INSERT INTO cola_participacion (participacion_id, estudiante_id, orden, estado)
            VALUES (:pid, :eid, :orden, "pendiente")
        ')->execute([':pid' => $participacionId, ':eid' => $estudianteId, ':orden' => $orden]);

        return ['ok' => true, 'message' => '', 'orden' => $orden];
    }

    /**
     * Devuelve la cola con nombres de estudiantes.
     */
    public function getCola(int $participacionId): array
    {
        $stmt = $this->db->prepare('
            SELECT c.*, u.apellidos, u.nombres, u.username
            FROM cola_participacion c
            INNER JOIN usuarios u ON u.id = c.estudiante_id
            WHERE c.participacion_id = :pid
            ORDER BY c.orden ASC
        ');
        $stmt->execute([':pid' => $participacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marca si el estudiante participó (filtro 1).
     */
    public function marcarParticipacion(int $participacionId, int $estudianteId, bool $participa): void
    {
        $this->db->prepare('
            UPDATE cola_participacion SET participo = :p
            WHERE participacion_id = :pid AND estudiante_id = :eid AND estado = "pendiente"
        ')->execute([':p' => $participa ? 1 : 0, ':pid' => $participacionId, ':eid' => $estudianteId]);
    }

    /**
     * Asigna motivo + puntos a un participante marcado (filtro 2).
     */
    public function asignarMotivo(int $participacionId, int $estudianteId, ?int $motivoId, int $puntos): void
    {
        $this->db->prepare('
            UPDATE cola_participacion SET motivo_id = :mid, puntos = :puntos
            WHERE participacion_id = :pid AND estudiante_id = :eid AND estado = "pendiente"
        ')->execute([':mid' => $motivoId, ':puntos' => $puntos, ':pid' => $participacionId, ':eid' => $estudianteId]);
    }

    /**
     * Confirma (inserta en puntos_participacion) y registra el cierre final.
     * Devuelve array de puntos confirmados listos para insertar.
     */
    public function confirmarMarcados(int $participacionId, int $cursoId, int $semanaId, int $docenteId, PuntoParticipacion $puntosModel, MotivoPunto $motivosPuntos): array
    {
        $cola = $this->getCola($participacionId);
        $insertados = [];
        foreach ($cola as $c) {
            if ($c['estado'] !== 'confirmado') continue;
            $puntos = (int) $c['puntos'];
            $motivoId = $c['motivo_id'] !== null ? (int) $c['motivo_id'] : null;
            $motivo = null;
            if ($motivoId !== null) {
                $m = $motivosPuntos->resolver($motivoId, $docenteId);
                $motivo = $m ? $m['nombre'] : null;
            }
            if ($puntos !== 0) {
                $id = $puntosModel->registrar((int) $c['estudiante_id'], $cursoId, $semanaId, $puntos, $motivo, $docenteId, $motivoId);
                $insertados[] = ['id' => $id, 'estudiante_id' => (int) $c['estudiante_id'], 'puntos' => $puntos];
            }
        }
        return $insertados;
    }
}
