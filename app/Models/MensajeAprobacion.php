<?php

declare(strict_types=1);

class MensajeAprobacion
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Marca o desmarca la aprobación docente sobre un mensaje.
     * Un mensaje aprobado es el único que puede recibir likes de los estudiantes.
     */
    public function toggle(int $mensajeId, int $docenteId): string
    {
        $stmt = $this->db->prepare('SELECT id FROM mensaje_aprobaciones WHERE mensaje_id = :m');
        $stmt->execute([':m' => $mensajeId]);
        if ($stmt->fetch()) {
            $del = $this->db->prepare('DELETE FROM mensaje_aprobaciones WHERE mensaje_id = :m');
            $del->execute([':m' => $mensajeId]);
            return 'desaprobado';
        }
        $ins = $this->db->prepare('INSERT INTO mensaje_aprobaciones (mensaje_id, docente_id) VALUES (:m, :d)');
        $ins->execute([':m' => $mensajeId, ':d' => $docenteId]);
        return 'aprobado';
    }

    public function isAprobado(int $mensajeId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM mensaje_aprobaciones WHERE mensaje_id = :m');
        $stmt->execute([':m' => $mensajeId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Mapa de mensajes aprobados dentro de una discusión.
     * @return array<int, array{aprobado:int, docente:string, fecha:string}>
     */
    public function getPorDiscusion(int $discusionId): array
    {
        $stmt = $this->db->prepare('
            SELECT ma.mensaje_id, ma.docente_id, ma.created_at,
                   COALESCE(CONCAT(ud.nombres, \' \', ud.apellidos), \'Docente\') AS docente
            FROM mensaje_aprobaciones ma
            INNER JOIN mensajes_discusion md ON md.id = ma.mensaje_id
            LEFT JOIN usuarios ud ON ud.id = ma.docente_id
            WHERE md.discusion_id = :d
        ');
        $stmt->execute([':d' => $discusionId]);
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(int) $row['mensaje_id']] = [
                'aprobado' => 1,
                'docente'  => (string) $row['docente'],
                'fecha'   => (string) $row['created_at'],
            ];
        }
        return $map;
    }
}
