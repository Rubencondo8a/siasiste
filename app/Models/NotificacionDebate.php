<?php

declare(strict_types=1);

class NotificacionDebate
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Crea un trabajo de notificacion con la lista de destinatarios pendientes.
     * Los ya notificados antes (mismo discusion + usuario + tipo) se excluyen.
     */
    public function crearJob(int $discusionId, string $tipo, array $destinatarios): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO debate_notif_jobs (discusion_id, tipo, total, estado)
                 VALUES (:d, :t, :total, 'pendiente')"
            )->execute([':d' => $discusionId, ':t' => $tipo, ':total' => count($destinatarios)]);

            $jobId = (int) $this->db->lastInsertId();
            if ($destinatarios) {
                $ins = $this->db->prepare(
                    "INSERT INTO debate_notif_destinatarios (job_id, usuario_id, nombre, email)
                     VALUES (:j, :u, :n, :e)"
                );
                foreach ($destinatarios as $d) {
                    $ins->execute([
                        ':j' => $jobId,
                        ':u' => (int) $d['usuario_id'],
                        ':n' => $d['nombre'],
                        ':e' => $d['email'],
                    ]);
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $jobId;
    }

    /** Estudiantes del curso del debate que aun no recibieron este tipo de aviso. */
    public function destinatariosPendientes(int $discusionId, string $tipo): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id AS usuario_id,
                    TRIM(CONCAT(u.nombres, ' ', u.apellidos)) AS nombre,
                    u.email
             FROM asignaciones a
             INNER JOIN usuarios u ON u.id = a.estudiante_id
             INNER JOIN semanas s ON s.curso_id = a.curso_id
             WHERE s.id = :sid
               AND u.estado = 1
               AND u.email IS NOT NULL AND u.email <> ''
               AND NOT EXISTS (
                   SELECT 1 FROM debate_notificaciones_log l
                   WHERE l.discusion_id = :d2 AND l.usuario_id = u.id AND l.tipo = :t2
               )
             ORDER BY u.nombres, u.apellidos"
        );
        $stmt->execute([':sid' => $this->semanaIdDeDiscusion($discusionId), ':d2' => $discusionId, ':t2' => $tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function semanaIdDeDiscusion(int $discusionId): int
    {
        $stmt = $this->db->prepare('SELECT semana_id FROM discusiones WHERE id = :id');
        $stmt->execute([':id' => $discusionId]);
        return (int) $stmt->fetchColumn();
    }

    public function getJob(int $jobId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM debate_notif_jobs WHERE id = :id');
        $stmt->execute([':id' => $jobId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /** Marca un destinatario como enviado o fallido y actualiza los contadores. */
    public function marcar(int $destinatarioId, string $estado, ?string $error = null): void
    {
        $this->db->prepare(
            "UPDATE debate_notif_destinatarios
             SET estado = :e, error = :err, enviado_en = NOW()
             WHERE id = :id"
        )->execute([':e' => $estado, ':err' => $error, ':id' => $destinatarioId]);
    }

    /** Actualiza el progreso del job a partir del conteo real de destinatarios. */
    public function refrescarJob(int $jobId): array
    {
        $this->db->prepare(
            "UPDATE debate_notif_jobs j
             SET j.enviados = (SELECT COUNT(*) FROM debate_notif_destinatarios WHERE job_id = j.id AND estado = 'enviado'),
                 j.fallidos  = (SELECT COUNT(*) FROM debate_notif_destinatarios WHERE job_id = j.id AND estado = 'fallido'),
                 j.estado    = CASE
                                WHEN (SELECT COUNT(*) FROM debate_notif_destinatarios WHERE job_id = j.id AND estado = 'pendiente') = 0
                                THEN 'completado' ELSE 'en_proceso' END
             WHERE j.id = :id"
        )->execute([':id' => $jobId]);

        return $this->getJob($jobId) ?? [];
    }

    public function pendientes(int $jobId, int $limite = 1): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM debate_notif_destinatarios
             WHERE job_id = :j AND estado = 'pendiente'
             ORDER BY id ASC LIMIT $limite"
        );
        $stmt->execute([':j' => $jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ultimosProcesados(int $jobId, int $limite = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT nombre, email, estado, error FROM debate_notif_destinatarios
             WHERE job_id = :j AND estado <> 'pendiente'
             ORDER BY id DESC LIMIT $limite"
        );
        $stmt->execute([':j' => $jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ultimo job abierto de un debate para no duplicar envios concurrentes. */
    public function jobAbierto(int $discusionId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM debate_notif_jobs
             WHERE discusion_id = :d AND estado <> 'completado'
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':d' => $discusionId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
}
