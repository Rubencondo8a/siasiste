<?php

declare(strict_types=1);

/**
 * Recordatorios y avisos de debates (lógica compartida).
 * La ejecuta cron_debates.php (CLI/HTTP con clave) y el auto-disparo
 * diario integrado en index.php (sin necesidad de configurar cron).
 */

function enviarRecordatoriosDebates(PDO $db): array
{
    $resultado = ['debates' => 0, 'enviados' => 0, 'omitidos' => 0];
    require_once ROOT_PATH . 'app/Libraries/Mailer.php';

    $mailer = new Mailer($db);
    if (!$mailer->isConfigured()) {
        $resultado['error'] = 'SMTP no configurado';
        return $resultado;
    }

    $hoy = date('Y-m-d');

    // 1) Cerrar debates vencidos y avisar su finalizacion (una vez por usuario)
    $stmtVenc = $db->prepare(
        "SELECT d.id, d.titulo,
                s.curso_id, c.nombre AS curso_nombre
         FROM discusiones d
         INNER JOIN semanas s ON s.id = d.semana_id
         INNER JOIN cursos c ON c.id = s.curso_id
         WHERE d.estado = 'abierta'
           AND d.fecha_fin IS NOT NULL
           AND d.fecha_fin < CURDATE()"
    );
    $stmtVenc->execute();
    foreach ($stmtVenc->fetchAll(PDO::FETCH_ASSOC) as $dv) {
        $resultado['debates']++;
        $did = (int) $dv['id'];

        $db->prepare("UPDATE discusiones SET estado = 'cerrada', cerrada_en = fecha_fin WHERE id = :id AND estado = 'abierta'")
           ->execute([':id' => $did]);

        $stEstF = $db->prepare(
            "SELECT u.id, u.nombres, u.apellidos, u.email
             FROM asignaciones a
             INNER JOIN usuarios u ON u.id = a.estudiante_id
             WHERE a.curso_id = :curso_id AND u.estado = 1 AND u.email IS NOT NULL AND u.email <> ''"
        );
        $stEstF->execute([':curso_id' => (int) $dv['curso_id']]);

        foreach ($stEstF->fetchAll(PDO::FETCH_ASSOC) as $est) {
            $uid = (int) $est['id'];
            $stYa = $db->prepare(
                "SELECT 1 FROM debate_notificaciones_log
                 WHERE discusion_id = :d AND usuario_id = :u AND tipo = 'finalizado'"
            );
            $stYa->execute([':d' => $did, ':u' => $uid]);
            if ($stYa->fetchColumn()) continue;

            $asuntoF = 'Debate finalizado — ' . $dv['titulo'];
            $cuerpoF = '<p>Hola <strong>' . htmlspecialchars(trim($est['nombres'] . ' ' . $est['apellidos'])) . '</strong>,</p>'
                . '<p>El debate <strong>' . htmlspecialchars($dv['titulo']) . '</strong> del curso <strong>'
                . htmlspecialchars($dv['curso_nombre']) . '</strong> ha <strong>finalizado</strong> y ya no acepta participaciones.</p>'
                . '<p>Puedes revisar las intervenciones desde el sistema.</p>';
            if ($mailer->send($est['email'], trim($est['nombres'] . ' ' . $est['apellidos']), $asuntoF, $cuerpoF)) {
                $db->prepare("INSERT IGNORE INTO debate_notificaciones_log (discusion_id, usuario_id, fecha, tipo) VALUES (:d, :u, :f, 'finalizado')")
                   ->execute([':d' => $did, ':u' => $uid, ':f' => $hoy]);
                $resultado['enviados']++;
            }
        }
    }

    // 2) Recordatorios diarios (solo debates con vigencia de mas de 1 dia)
    $stmt = $db->prepare(
        "SELECT d.id, d.titulo,
                s.curso_id, s.numero_semana, c.nombre AS curso_nombre
         FROM discusiones d
         INNER JOIN semanas s ON s.id = d.semana_id
         INNER JOIN cursos c ON c.id = s.curso_id
         WHERE d.estado = 'abierta'
           AND CURDATE() BETWEEN COALESCE(d.fecha_inicio, CURDATE()) AND COALESCE(d.fecha_fin, CURDATE())"
    );
    $stmt->execute();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $resultado['debates']++;
        $discusionId = (int) $d['id'];

        // Recordatorios diarios solo si la vigencia dura mas de un dia
        $stRango = $db->prepare('SELECT fecha_inicio, fecha_fin FROM discusiones WHERE id = :id');
        $stRango->execute([':id' => $discusionId]);
        $rango = $stRango->fetch(PDO::FETCH_ASSOC);
        if ($rango && !empty($rango['fecha_inicio']) && !empty($rango['fecha_fin'])) {
            $dias = (strtotime($rango['fecha_fin']) - strtotime($rango['fecha_inicio'])) / 86400;
            if ($dias <= 1) continue;
        }

        $stUlt = $db->prepare("SELECT MAX(id) FROM mensajes_discusion WHERE discusion_id = :id");
        $stUlt->execute([':id' => $discusionId]);
        $ultimoMensajeId = (int) $stUlt->fetchColumn();

        $stEst = $db->prepare(
            "SELECT u.id, u.nombres, u.apellidos, u.email
             FROM asignaciones a
             INNER JOIN usuarios u ON u.id = a.estudiante_id
             WHERE a.curso_id = :curso_id AND u.estado = 1 AND u.email IS NOT NULL AND u.email <> ''"
        );
        $stEst->execute([':curso_id' => (int) $d['curso_id']]);

        foreach ($stEst->fetchAll(PDO::FETCH_ASSOC) as $est) {
            $uid = (int) $est['id'];

            $stLeida = $db->prepare(
                "SELECT ultimo_mensaje_id FROM discusion_leida WHERE discusion_id = :d AND usuario_id = :u"
            );
            $stLeida->execute([':d' => $discusionId, ':u' => $uid]);
            $leidoHasta = (int) ($stLeida->fetchColumn() ?: 0);
            if ($ultimoMensajeId > 0 && $leidoHasta >= $ultimoMensajeId) {
                $resultado['omitidos']++;
                continue;
            }

            $stLog = $db->prepare(
                "SELECT 1 FROM debate_notificaciones_log
                 WHERE discusion_id = :d AND usuario_id = :u AND fecha = :f AND tipo = 'recordatorio'"
            );
            $stLog->execute([':d' => $discusionId, ':u' => $uid, ':f' => $hoy]);
            if ($stLog->fetchColumn()) continue;

            $vigencia = '';
            $stF = $db->prepare('SELECT fecha_fin FROM discusiones WHERE id = :id');
            $stF->execute([':id' => $discusionId]);
            $fechaFin = $stF->fetchColumn();
            if ($fechaFin) {
                $vigencia = '<p><strong>Vigencia:</strong> hasta el ' . date('d/m/Y', strtotime($fechaFin)) . '</p>';
            }

            $asunto = 'Recordatorio: debate pendiente — ' . $d['titulo'];
            $cuerpo = '<p>Hola <strong>' . htmlspecialchars(trim($est['nombres'] . ' ' . $est['apellidos'])) . '</strong>,</p>'
                . '<p>Tienes un <strong>debate pendiente</strong> en el curso <strong>'
                . htmlspecialchars($d['curso_nombre']) . '</strong> (semana ' . (int) $d['numero_semana'] . ').</p>'
                . '<p><strong>Tema:</strong> ' . htmlspecialchars($d['titulo']) . '</p>'
                . $vigencia
                . '<p>No olvides participar antes del cierre.</p>';

            if ($mailer->send($est['email'], trim($est['nombres'] . ' ' . $est['apellidos']), $asunto, $cuerpo)) {
                $db->prepare("INSERT IGNORE INTO debate_notificaciones_log (discusion_id, usuario_id, fecha, tipo) VALUES (:d, :u, :f, 'recordatorio')")
                   ->execute([':d' => $discusionId, ':u' => $uid, ':f' => $hoy]);
                $resultado['enviados']++;
            }
        }
    }

    return $resultado;
}
