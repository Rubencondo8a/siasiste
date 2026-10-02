<?php

declare(strict_types=1);

require_once ROOT_PATH . 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    private function getSettings(): array
    {
        $defaults = [
            'smtp_host'       => '',
            'smtp_port'       => '587',
            'smtp_user'       => '',
            'smtp_pass'       => '',
            'smtp_encryption' => 'tls',
            'mail_from_email' => 'no-reply@sigametec.com',
            'mail_from_name'  => 'Sigametec',
        ];

        try {
            $stmt = $this->db->query('SELECT clave, valor FROM configuraciones');
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            return $defaults;
        }

        return array_merge($defaults, $rows);
    }

    public function isConfigured(): bool
    {
        $settings = $this->getSettings();
        return !empty($settings['smtp_host']);
    }

    public function send(string $toEmail, string $toName, string $subject, string $bodyHtml): bool
    {
        $settings = $this->getSettings();

        if (!$settings['smtp_host']) {
            logError('Correo no enviado: SMTP no configurado', ['to' => $toEmail, 'subject' => $subject]);
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $settings['smtp_host'];
            $mail->SMTPAuth   = ($settings['smtp_user'] !== '' || $settings['smtp_pass'] !== '');
            $mail->Username   = $settings['smtp_user'];
            $mail->Password   = $settings['smtp_pass'];
            $mail->Port       = (int) ($settings['smtp_port'] ?: 587);

            switch ($settings['smtp_encryption']) {
                case 'ssl':
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    break;
                case 'none':
                    $mail->SMTPSecure = false;
                    break;
                case 'tls':
                default:
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    break;
            }

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($settings['mail_from_email'], $settings['mail_from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $bodyHtml;

            return $mail->send();
        } catch (Exception $e) {
            logError('Error enviando correo', ['to' => $toEmail, 'error' => $mail->ErrorInfo]);
            return false;
        }
    }

    public function sendPasswordResetCode(string $toEmail, string $toName, string $codigo): bool
    {
        $subject = 'Tu código de recuperación de contraseña';
        $body = "
            <div style='font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px;'>
                <h2 style='color:#4E37E6;margin:0 0 8px;'>Sigametec</h2>
                <p style='color:#374151;font-size:15px;'>Hola <strong>{$toName}</strong>,</p>
                <p style='color:#374151;font-size:15px;'>Recibimos una solicitud para restablecer tu contraseña. Usa el siguiente código:</p>
                <div style='text-align:center;padding:16px;background:#f3f4f6;border-radius:8px;margin:16px 0;'>
                    <span style='font-size:32px;font-weight:700;letter-spacing:8px;color:#4E37E6;'>{$codigo}</span>
                </div>
                <p style='color:#374151;font-size:15px;'>Este código es válido por <strong>15 minutos</strong>. Si no solicitaste este cambio, ignora este correo.</p>
                <p style='color:#6b7280;font-size:13px;margin-top:24px;'>— Equipo Sigametec</p>
            </div>
        ";
        return $this->send($toEmail, $toName, $subject, $body);
    }

    public function sendProfileChangeNotification(string $toEmail, string $toName, string $tipo, array $cambios, string $ip, string $agente): bool
    {
        $titulo = $tipo === 'password' ? 'Contraseña cambiada' : 'Datos personales modificados';

        $filas = '';
        foreach ($cambios as $campo => $valor) {
            $filas .= "<tr>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;white-space:nowrap;'>{$campo}</td>
                <td style='padding:8px 12px;border-bottom:1px solid #e5e7eb;color:#111827;'>{$valor}</td>
            </tr>";
        }

        $fecha = date('d/m/Y H:i:s');

        $body = "
            <div style='font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px;'>
                <h2 style='color:#4E37E6;margin:0 0 8px;'>Sigametec</h2>
                <p style='color:#374151;font-size:15px;'>Hola <strong>{$toName}</strong>,</p>
                <p style='color:#374151;font-size:15px;'>Se detectó el siguiente cambio en tu cuenta:</p>
                <div style='background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:6px;margin:16px 0;'>
                    <strong style='color:#92400e;'>⚠ {$titulo}</strong>
                </div>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;'>
                    <thead>
                        <tr style='background:#f3f4f6;'>
                            <th style='padding:8px 12px;text-align:left;color:#6b7280;font-weight:600;'>Campo</th>
                            <th style='padding:8px 12px;text-align:left;color:#6b7280;font-weight:600;'>Nuevo valor</th>
                        </tr>
                    </thead>
                    <tbody>{$filas}</tbody>
                </table>
                <div style='background:#f9fafb;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:13px;color:#6b7280;'>
                    <p style='margin:0;'><strong>Fecha:</strong> {$fecha}</p>
                    <p style='margin:4px 0 0;'><strong>IP:</strong> {$ip}</p>
                    <p style='margin:4px 0 0;'><strong>Navegador:</strong> {$agente}</p>
                </div>
                <p style='color:#dc2626;font-size:14px;font-weight:600;'>Si tú no realizaste este cambio, restablece tu contraseña inmediatamente.</p>
                <p style='color:#6b7280;font-size:13px;margin-top:24px;'>— Equipo Sigametec</p>
            </div>
        ";
        return $this->send($toEmail, $toName, $titulo, $body);
    }

    /**
     * Avisa al estudiante que el docente le otorgó un intento adicional
     * para un examen. Es de ultima instancia: solo uno por alumno.
     * El intento anterior NO se borra: se conserva como historial y la nota
     * que se muestra es la mejor obtenida.
     */
    public function sendIntentoExtra(string $toEmail, string $toName, string $examenTitulo, string $cursoNombre, int $nuevoLimite, string $motivo): bool
    {
        $subject = 'Se te otorgó un intento adicional — ' . $examenTitulo;

        $titulo  = htmlspecialchars($examenTitulo, ENT_QUOTES, 'UTF-8');
        $curso   = htmlspecialchars($cursoNombre, ENT_QUOTES, 'UTF-8');
        $motivo  = htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8');
        $nombre  = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $ventana = htmlspecialchars(date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');

        $body = "
            <div style='font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;padding:24px;border:1px solid #e5e7eb;border-radius:12px;'>
                <h2 style='color:#4E37E6;margin:0 0 8px;'>Sigametec</h2>
                <p style='color:#374151;font-size:15px;'>Hola <strong>{$nombre}</strong>,</p>
                <p style='color:#374151;font-size:15px;'>Tu docente te otorgó <strong>un intento adicional</strong> (es la última instancia) para el siguiente examen:</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;'>
                    <tbody>
                        <tr style='background:#f9fafb;'>
                            <td style='padding:8px 12px;font-weight:600;color:#6b7280;width:40%;'>Examen</td>
                            <td style='padding:8px 12px;color:#111827;'>{$titulo}</td>
                        </tr>
                        <tr>
                            <td style='padding:8px 12px;font-weight:600;color:#6b7280;'>Curso</td>
                            <td style='padding:8px 12px;color:#111827;'>{$curso}</td>
                        </tr>
                        <tr style='background:#f9fafb;'>
                            <td style='padding:8px 12px;font-weight:600;color:#6b7280;'>Intentos totales</td>
                            <td style='padding:8px 12px;color:#111827;font-weight:700;'>{$nuevoLimite}</td>
                        </tr>
                    </tbody>
                </table>
                <div style='background:#eef2ff;border-left:4px solid #4E37E6;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:14px;color:#3730a3;'>
                    <strong>Motivo del docente:</strong> {$motivo}
                </div>
                <div style='background:#fffbeb;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:14px;color:#92400e;'>
                    Tu intento anterior <strong>no se borra</strong>: queda como historial y la nota que se muestra es la mejor que hayas obtenido.
                </div>
                <p style='color:#374151;font-size:14px;'>Ingresa a <strong>Mis Exámenes</strong> para rendirlo. Recuerda que solo estará disponible dentro de la ventana de disponibilidad del examen.</p>
                <div style='font-size:13px;color:#6b7280;margin-top:16px;'>Otorgado el {$ventana}.</div>
                <p style='color:#6b7280;font-size:13px;margin-top:24px;'>— Equipo Sigametec</p>
            </div>
        ";
        return $this->send($toEmail, $toName, $subject, $body);
    }
}
