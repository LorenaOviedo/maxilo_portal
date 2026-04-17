<?php
/**
 * mailer.php — Configuración de PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/Exception.php';

function crearMailer(): PHPMailer
{
    $mail = new PHPMailer(true);

    // Servidor SMTP
    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
    $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 465);
    $mail->CharSet = 'UTF-8';

    // Remitente
    $fromEmail = $_ENV['MAIL_USERNAME'] ?? '';
    $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Sistema Maxilofacial Texcoco';
    $mail->setFrom($fromEmail, $fromName);
    $mail->addReplyTo($fromEmail, $fromName);

    return $mail;
}

/**
 * Enviar correo de recuperación de contraseña
 */
function enviarCorreoRecuperacion(string $email, string $nombre, string $token): bool
{
    try {
        $mail = crearMailer();
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña — Sistema Maxilofacial Texcoco';

        $enlace = SITE_URL . 'views/restablecer_password.php?token=' . urlencode($token);
        $anio = date('Y');

        $mail->Body = "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;'>
    <table width='100%' cellpadding='0' cellspacing='0'>
        <tr>
            <td align='center' style='padding:40px 20px;'>
                <table width='560' cellpadding='0' cellspacing='0'
                    style='background:#fff;border-radius:12px;overflow:hidden;
                           box-shadow:0 2px 12px rgba(0,0,0,.08);'>
 
                    <!-- Header -->
                    <tr>
                        <td style='background:#192D8C;padding:28px 32px;'>
                            <p style='margin:0;font-size:20px;font-weight:700;
                                      color:#fff;text-transform:uppercase;letter-spacing:1px;'>
                                Maxilofacial Texcoco
                            </p>
                            <p style='margin:4px 0 0;font-size:11px;color:rgba(255,255,255,.7);
                                      text-transform:uppercase;letter-spacing:.5px;'>
                                Ortodoncia · Cirugía Maxilofacial · Patología Oral
                            </p>
                        </td>
                    </tr>
 
                    <!-- Cuerpo -->
                    <tr>
                        <td style='padding:32px;'>
                            <p style='font-size:16px;font-weight:700;color:#212529;margin:0 0 8px;'>
                                Hola, " . htmlspecialchars($nombre) . "
                            </p>
                            <p style='font-size:14px;color:#495057;line-height:1.6;margin:0 0 24px;'>
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta.
                                Si no realizaste esta solicitud puedes ignorar este correo.
                            </p>
 
                            <!-- Botón -->
                            <table cellpadding='0' cellspacing='0' width='100%'>
                                <tr>
                                    <td align='center' style='padding:8px 0 28px;'>
                                        <a href='{$enlace}'
                                            style='display:inline-block;padding:14px 32px;
                                                   background:#192D8C;color:#fff;
                                                   text-decoration:none;border-radius:8px;
                                                   font-size:14px;font-weight:600;'>
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>
 
                            <p style='font-size:12px;color:#6c757d;line-height:1.6;margin:0 0 8px;'>
                                Este enlace expirará en <strong>30 minutos</strong>.
                            </p>
                        </td>
                    </tr>
 
                    <!-- Footer -->
                    <tr>
                        <td style='background:#f8f9fa;padding:16px 32px;border-top:1px solid #e9ecef;'>
                            <p style='margin:0;font-size:11px;color:#adb5bd;text-align:center;'>
                                Sistema Maxilofacial Texcoco &mdash; {$anio}<br>
                            </p>
                        </td>
                    </tr>
 
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";

        $mail->AltBody = "Hola {$nombre},\n\n"
            . "Para restablecer tu contraseña visita el siguiente enlace:\n{$enlace}\n\n"
            . "Este enlace expirará en 30 minutos.\n\n"
            . "Si no solicitaste este cambio ignora este correo.\n\n"
            . "Sistema Maxilofacial Texcoco";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mailer error: ' . $e->getMessage());
        return false;
    }
}