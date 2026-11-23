<?php
/**
 * TallerTech - Módulo de Notificaciones por Correo Electrónico
 * Integración con PHPMailer vía SMTP Gmail para alertas de taller y clientes.
 * 
 * Eventos cubiertos:
 * 1. Nueva cita agendada.
 * 2. Anticipo registrado / confirmado.
 * 3. Recordatorio preventivo de cita en las próximas 24 horas.
 */

namespace TallerTech;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;
use PDO;
use Throwable;

// Asegurar carga de dependencias de Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

class Notificaciones {

    /**
     * Cache de configuración leída desde .env
     */
    private static ?array $config = null;

    /**
     * Obtener configuración SMTP desde el archivo .env o variables de entorno
     */
    public static function obtenerConfig(): array {
        if (self::$config !== null) {
            return self::$config;
        }

        $envPath = __DIR__ . '/../.env';
        if (!file_exists($envPath) && file_exists(__DIR__ . '/../tallertech.env')) {
            $envPath = __DIR__ . '/../tallertech.env';
        }

        $env = [];
        if (file_exists($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        [$key, $val] = explode('=', $line, 2);
                        $key = trim($key);
                        $val = trim($val, " \t\n\r\0\x0B\"'");
                        $env[$key] = $val;
                    }
                }
            }
        }

        $pass = trim($env['SMTP_PASS'] ?? getenv('SMTP_PASS') ?: '');
        // Limpiar corchetes accidentales si se configuró como <password>
        if (str_starts_with($pass, '<') && str_ends_with($pass, '>')) {
            $pass = substr($pass, 1, -1);
        }

        $timezone = $env['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'America/Mexico_City';
        if (function_exists('date_default_timezone_set')) {
            @date_default_timezone_set($timezone);
        }

        self::$config = [
            'host'       => $env['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'smtp.gmail.com',
            'port'       => (int)($env['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587),
            'encryption' => strtolower($env['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION') ?: 'tls'),
            'user'       => $env['SMTP_USER'] ?? getenv('SMTP_USER') ?: 'cristianlepezp145@gmail.com',
            'pass'       => $pass,
            'from_name'  => $env['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: 'TallerTech Notificaciones',
            'notify_to'  => $env['NOTIFY_EMAIL'] ?? getenv('NOTIFY_EMAIL') ?: 'cristianlepezp145@gmail.com',
            'timezone'   => $timezone,
            'app_url'    => rtrim($env['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost:8000', '/'),
        ];

        return self::$config;
    }

    /**
     * Instanciar y configurar el objeto PHPMailer
     */
    private static function crearMailer(bool $esDebugCLI = false): ?PHPMailer {
        $cfg = self::obtenerConfig();

        if (empty($cfg['pass'])) {
            error_log("[TallerTech Notificaciones] SMTP_PASS no está configurado en .env.");
            return null;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['user'];
            $mail->Password   = $cfg['pass'];

            if ($cfg['encryption'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port       = $cfg['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 8; // Timeout estricto de 8s para no colgar peticiones web

            // NUNCA activar debug en llamadas web para evitar corromper respuestas JSON o HTML
            if ($esDebugCLI) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            } else {
                $mail->SMTPDebug = SMTP::DEBUG_OFF;
            }

            $mail->setFrom($cfg['user'], $cfg['from_name']);
            $mail->isHTML(true);

            return $mail;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error al inicializar PHPMailer: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Limpia un teléfono a solo dígitos y genera enlace directo de WhatsApp (+52 México por defecto)
     */
    public static function generarLinkWhatsApp(?string $telefono, string $mensajePredefinido = ''): ?string {
        if (empty($telefono)) {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $telefono);
        if (empty($digitos)) {
            return null;
        }

        // Si son 10 dígitos (número estándar mexicano), anteponer código país 52
        if (strlen($digitos) === 10) {
            $digitos = '52' . $digitos;
        } elseif (strlen($digitos) === 12 && str_starts_with($digitos, '52')) {
            // Ya tiene 52
        } elseif (strlen($digitos) === 13 && str_starts_with($digitos, '521')) {
            // Formato anterior celular México
            $digitos = '52' . substr($digitos, 3);
        }

        $url = 'https://wa.me/' . $digitos;
        if (!empty($mensajePredefinido)) {
            $url .= '?text=' . rawurlencode($mensajePredefinido);
        }

        return $url;
    }

    /**
     * Generador del Layout HTML del correo con la identidad visual cálida de carpintería
     */
    public static function renderPlantillaHtml(string $tituloPrincipal, string $subtitulo, array $filasDetalle, ?string $btnTexto = null, ?string $btnUrl = null, ?string $notaPie = null): string {
        $safeTitulo    = htmlspecialchars($tituloPrincipal, ENT_QUOTES, 'UTF-8');
        $safeSubtitulo = htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8');
        $safeNotaPie   = !empty($notaPie) ? htmlspecialchars($notaPie, ENT_QUOTES, 'UTF-8') : 'Notificación automática generada por TallerTech Carpintería.';

        $filasHtml = '';
        foreach ($filasDetalle as $item) {
            $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
            $valor = htmlspecialchars($item['valor'] ?? '', ENT_QUOTES, 'UTF-8');
            $esDestacado = !empty($item['destacado']);
            $colorVal = $esDestacado ? '#c88a4b' : '#1e1b18';
            $weightVal = $esDestacado ? 'bold' : 'normal';

            $filasHtml .= "
                <tr>
                    <td style='padding: 10px 14px; border-bottom: 1px solid #ebe6df; color: #6b655f; font-size: 13px; font-weight: 600; width: 35%; vertical-align: top;'>
                        {$label}
                    </td>
                    <td style='padding: 10px 14px; border-bottom: 1px solid #ebe6df; color: {$colorVal}; font-size: 14px; font-weight: {$weightVal}; width: 65%;'>
                        {$valor}
                    </td>
                </tr>
            ";
        }

        $botonHtml = '';
        if (!empty($btnTexto) && !empty($btnUrl)) {
            $safeBtnTexto = htmlspecialchars($btnTexto, ENT_QUOTES, 'UTF-8');
            $safeBtnUrl   = htmlspecialchars($btnUrl, ENT_QUOTES, 'UTF-8');
            $botonHtml = "
                <div style='text-align: center; margin: 28px 0 10px;'>
                    <a href='{$safeBtnUrl}' target='_blank' style='display: inline-block; background-color: #c88a4b; color: #141210; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 9999px; box-shadow: 0 4px 10px rgba(200, 138, 75, 0.3);'>
                        {$safeBtnTexto} &rarr;
                    </a>
                </div>
            ";
        }

        return "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$safeTitulo}</title>
</head>
<body style='margin: 0; padding: 0; background-color: #f5f2ed; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #1e1b18;'>
    <table role='presentation' width='100%' cellspacing='0' cellpadding='0' border='0' style='background-color: #f5f2ed; padding: 30px 15px;'>
        <tr>
            <td align='center'>
                <table role='presentation' width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 14px; overflow: hidden; border: 1px solid #e5dfd5; box-shadow: 0 6px 18px rgba(0,0,0,0.05);' cellspacing='0' cellpadding='0' border='0'>
                    
                    <!-- Encabezado con estética de taller -->
                    <tr>
                        <td style='background-color: #1e1b18; padding: 26px 30px; text-align: left; border-bottom: 3px solid #c88a4b;'>
                            <table width='100%' cellspacing='0' cellpadding='0' border='0'>
                                <tr>
                                    <td>
                                        <div style='font-size: 20px; font-weight: 800; color: #f2ede4; letter-spacing: -0.02em;'>
                                            🪵 TALLERTECH
                                        </div>
                                        <div style='font-size: 12px; color: #c88a4b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 3px;'>
                                            Carpintería Fina &amp; Muebles a Medida
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Cuerpo Principal -->
                    <tr>
                        <td style='padding: 30px 30px 20px;'>
                            <h1 style='font-size: 20px; font-weight: 700; color: #1e1b18; margin: 0 0 8px 0; line-height: 1.3;'>
                                {$safeTitulo}
                            </h1>
                            <p style='font-size: 14px; color: #6b655f; margin: 0 0 22px 0; line-height: 1.5;'>
                                {$safeSubtitulo}
                            </p>

                            <!-- Tabla de Detalles -->
                            <table width='100%' cellspacing='0' cellpadding='0' border='0' style='border: 1px solid #ebe6df; border-radius: 8px; overflow: hidden; background-color: #faf8f5;'>
                                {$filasHtml}
                            </table>

                            <!-- Botón de Acción Directa -->
                            {$botonHtml}
                        </td>
                    </tr>

                    <!-- Pie de Correo -->
                    <tr>
                        <td style='background-color: #faf8f5; padding: 18px 30px; text-align: center; border-top: 1px solid #ebe6df;'>
                            <p style='font-size: 12px; color: #8c827a; margin: 0; line-height: 1.5;'>
                                {$safeNotaPie}
                            </p>
                            <p style='font-size: 11px; color: #b5ada3; margin: 6px 0 0 0;'>
                                TiDB Cloud &bull; Notificación Segura &bull; " . date('Y-m-d H:i') . "
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
";
    }

    /**
     * Construir versión alternativa en texto plano
     */
    public static function renderPlantillaTexto(string $tituloPrincipal, string $subtitulo, array $filasDetalle, ?string $btnTexto = null, ?string $btnUrl = null): string {
        $out = "=== TALLERTECH CARPINTERÍA ===\n";
        $out .= "{$tituloPrincipal}\n";
        $out .= "{$subtitulo}\n\n";
        $out .= "DETALLES:\n";
        foreach ($filasDetalle as $item) {
            $label = $item['label'] ?? '';
            $valor = $item['valor'] ?? '';
            $out .= "- {$label}: {$valor}\n";
        }
        if (!empty($btnTexto) && !empty($btnUrl)) {
            $out .= "\n{$btnTexto}: {$btnUrl}\n";
        }
        $out .= "\n--\nTallerTech Notificaciones automáticas.\n";
        return $out;
    }

    /**
     * Envío genérico hacia el correo del taller (alerta interna)
     */
    public static function enviarNotificacionTaller(
        string $asunto,
        string $titulo,
        string $subtitulo,
        array $filasDetalle,
        ?string $btnTexto = null,
        ?string $btnUrl = null,
        ?string $notaPie = null
    ): bool {
        try {
            $mail = self::crearMailer();
            if (!$mail) {
                return false;
            }

            $cfg = self::obtenerConfig();
            $mail->clearAddresses();
            $mail->clearReplyTos();

            $mail->addAddress($cfg['notify_to'], 'Taller TallerTech');
            $mail->Subject = $asunto;
            $mail->Body    = self::renderPlantillaHtml(
                $titulo,
                $subtitulo,
                $filasDetalle,
                $btnTexto,
                $btnUrl,
                $notaPie ?: 'Notificación interna automática del taller.'
            );
            $mail->AltBody = self::renderPlantillaTexto(
                $titulo,
                $subtitulo,
                $filasDetalle,
                $btnTexto,
                $btnUrl
            );

            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error al enviar notificación al taller: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envío genérico hacia el correo del cliente
     */
    public static function enviarNotificacionCliente(
        string $emailDestino,
        string $nombreCliente,
        string $asunto,
        string $titulo,
        string $subtitulo,
        array $filasDetalle,
        ?string $btnTexto = null,
        ?string $btnUrl = null,
        ?string $notaPie = null
    ): bool {
        $emailDestino = trim($emailDestino);
        if (!filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            error_log("[TallerTech Notificaciones] Correo de cliente inválido o ausente: '{$emailDestino}'. Se omite envío al cliente.");
            return false;
        }

        try {
            $mail = self::crearMailer();
            if (!$mail) {
                return false;
            }

            $cfg = self::obtenerConfig();
            $mail->clearAddresses();
            $mail->clearReplyTos();

            $nombreLimpio = trim(preg_replace('/[\r\n]+/', '', $nombreCliente));
            $mail->addAddress($emailDestino, $nombreLimpio ?: 'Cliente');

            // Configurar Reply-To hacia el taller para que las respuestas del cliente lleguen directo al taller
            $mail->addReplyTo($cfg['notify_to'], $cfg['from_name']);

            $mail->Subject = $asunto;
            $mail->Body    = self::renderPlantillaHtml(
                $titulo,
                $subtitulo,
                $filasDetalle,
                $btnTexto,
                $btnUrl,
                $notaPie ?: 'TallerTech &bull; Carpintería Fina y Muebles a Medida.'
            );
            $mail->AltBody = self::renderPlantillaTexto(
                $titulo,
                $subtitulo,
                $filasDetalle,
                $btnTexto,
                $btnUrl
            );

            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error al enviar correo al cliente ({$emailDestino}): " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // DISPARADOR 1: NUEVA CITA AGENDADA
    // =========================================================================
    /**
     * Envía alerta de nueva cita agendada a cristianlepezp145@gmail.com
     */
    public static function notificarNuevaCita(PDO $pdo, int $idCita): bool {
        try {
            $stmt = $pdo->prepare("
                SELECT ci.*, 
                       u.nombre AS cliente_nombre, 
                       u.correo AS cliente_correo, 
                       u.telefono AS cliente_telefono
                FROM citas ci
                LEFT JOIN usuarios u ON ci.id_usuario = u.id_usuario
                WHERE ci.id_cita = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $idCita]);
            $cita = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cita) {
                error_log("[TallerTech Notificaciones] notificarNuevaCita: Cita #{$idCita} no encontrada.");
                return false;
            }

            $mail = self::crearMailer();
            if (!$mail) {
                return false;
            }

            $cfg = self::obtenerConfig();
            $mail->addAddress($cfg['notify_to']);

            $clienteNombre   = $cita['cliente_nombre'] ?? 'Cliente';
            $clienteCorreo   = $cita['cliente_correo'] ?? 'No especificado';
            $clienteTelefono = $cita['cliente_telefono'] ?? 'No especificado';
            $tipoCita        = ($cita['tipo_cita'] === 'taller') ? 'Visita en el Taller' : 'Visita a Domicilio';
            $fechaHoraTs     = strtotime($cita['fecha_hora']);
            $fechaFmt        = date('d/m/Y', $fechaHoraTs);
            $horaFmt         = date('H:i', $fechaHoraTs) . ' hrs';
            $direccion       = !empty($cita['direccion']) ? $cita['direccion'] : 'N/A (Visita en Taller)';
            $notas           = !empty($cita['notas']) ? $cita['notas'] : 'Sin observaciones adicionales';

            $asunto = "[TallerTech] Nueva cita agendada - {$clienteNombre}";
            $mail->Subject = $asunto;

            $msgWhatsApp = "Hola {$clienteNombre}, recibimos tu solicitud de cita en TallerTech para el {$fechaFmt} a las {$horaFmt}. ¡Con gusto te atendemos!";
            $linkWa = self::generarLinkWhatsApp($clienteTelefono, $msgWhatsApp);

            $filas = [
                ['label' => 'Folio de Cita',       'valor' => "#CITA-{$idCita}", 'destacado' => true],
                ['label' => 'Cliente',             'valor' => $clienteNombre],
                ['label' => 'Teléfono',            'valor' => $clienteTelefono],
                ['label' => 'Correo Electrónico',  'valor' => $clienteCorreo],
                ['label' => 'Fecha Programada',    'valor' => $fechaFmt, 'destacado' => true],
                ['label' => 'Hora',                'valor' => $horaFmt],
                ['label' => 'Tipo de Visita',      'valor' => $tipoCita],
                ['label' => 'Dirección de Obra',   'valor' => $direccion],
                ['label' => 'Notas del Proyecto',  'valor' => $notas],
                ['label' => 'Estado Inicial',      'valor' => ucfirst($cita['estado'] ?? 'pendiente')],
            ];

            $mail->Body    = self::renderPlantillaHtml(
                "Nueva Cita Agendada #CITA-{$idCita}",
                "Un cliente ha reservado una fecha en el calendario de TallerTech.",
                $filas,
                $linkWa ? 'Responder por WhatsApp al Cliente' : null,
                $linkWa,
                'Revisa el panel de carpintero para confirmar la cita o registrar notas técnicas.'
            );
            $mail->AltBody = self::renderPlantillaTexto(
                "Nueva Cita Agendada #CITA-{$idCita}",
                "Un cliente ha reservado una fecha en el calendario de TallerTech.",
                $filas,
                $linkWa ? 'Responder por WhatsApp' : null,
                $linkWa
            );

            $mail->send();

            // Auditoría interna opcional
            if (function_exists('registrar_log')) {
                registrar_log(
                    $pdo,
                    'EMAIL_NUEVA_CITA',
                    "Notificación por correo enviada a {$cfg['notify_to']} para cita #CITA-{$idCita}.",
                    (int)($cita['id_usuario'] ?? null)
                );
            }

            return true;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error al enviar correo de nueva cita #{$idCita}: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // DISPARADOR 2: PAGO O REGISTRO DE ANTICIPO
    // =========================================================================
    /**
     * Envía alerta de anticipo recibido a cristianlepezp145@gmail.com
     */
    public static function notificarAnticipo(
        PDO $pdo,
        int $idCotizacion,
        float $montoAnticipo,
        float $costoTotal,
        ?string $muebleNombre = null,
        ?int $idPedido = null
    ): bool {
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       u.nombre AS cliente_nombre, 
                       u.correo AS cliente_correo, 
                       u.telefono AS cliente_telefono
                FROM cotizaciones c
                LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                WHERE c.id_cotizacion = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $idCotizacion]);
            $cot = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cot) {
                error_log("[TallerTech Notificaciones] notificarAnticipo: Cotización #{$idCotizacion} no encontrada.");
                return false;
            }

            $mail = self::crearMailer();
            if (!$mail) {
                return false;
            }

            $cfg = self::obtenerConfig();
            $mail->addAddress($cfg['notify_to']);

            $clienteNombre   = $cot['cliente_nombre'] ?? 'Cliente';
            $clienteTelefono = $cot['cliente_telefono'] ?? 'No especificado';
            $clienteCorreo   = $cot['cliente_correo'] ?? 'No especificado';
            $saldoPendiente  = max(0, $costoTotal - $montoAnticipo);
            $proyecto        = !empty($muebleNombre) ? $muebleNombre : 'Proyecto de Carpintería a Medida';
            $pedidoFolio     = $idPedido ? "#PED-{$idPedido}" : 'En trámite';

            $asunto = "[TallerTech] Anticipo recibido - Cotización #{$idCotizacion}";
            $mail->Subject = $asunto;

            $msgWhatsApp = "Hola {$clienteNombre}, confirmamos la recepción de tu anticipo de $" . number_format($montoAnticipo, 2) . " MXN para la orden {$pedidoFolio}. ¡Iniciamos con el habilitado de maderas en el taller!";
            $linkWa = self::generarLinkWhatsApp($clienteTelefono, $msgWhatsApp);

            $filas = [
                ['label' => 'Cotización Folio',      'valor' => "#COT-{$idCotizacion}", 'destacado' => true],
                ['label' => 'Orden de Fabricación',  'valor' => $pedidoFolio],
                ['label' => 'Cliente',               'valor' => $clienteNombre],
                ['label' => 'Teléfono',              'valor' => $clienteTelefono],
                ['label' => 'Correo Electrónico',    'valor' => $clienteCorreo],
                ['label' => 'Proyecto / Mueble',     'valor' => $proyecto],
                ['label' => 'Anticipo Recibido',     'valor' => '$' . number_format($montoAnticipo, 2) . ' MXN', 'destacado' => true],
                ['label' => 'Costo Total Aprobado',  'valor' => '$' . number_format($costoTotal, 2) . ' MXN'],
                ['label' => 'Saldo a Liquidar',      'valor' => '$' . number_format($saldoPendiente, 2) . ' MXN'],
                ['label' => 'Fecha y Hora de Pago',  'valor' => date('d/m/Y H:i') . ' hrs'],
            ];

            $mail->Body    = self::renderPlantillaHtml(
                "Anticipo Confirmado - {$pedidoFolio}",
                "Se ha registrado el pago de anticipo para el proyecto. El pedido pasa a etapa de fabricación.",
                $filas,
                $linkWa ? 'Enviar Confirmación por WhatsApp' : null,
                $linkWa,
                'El anticipo cubre compra de materiales, dimensionado de madera y puesta en taller.'
            );
            $mail->AltBody = self::renderPlantillaTexto(
                "Anticipo Confirmado - {$pedidoFolio}",
                "Se ha registrado el pago de anticipo para el proyecto. El pedido pasa a etapa de fabricación.",
                $filas,
                $linkWa ? 'Enviar Confirmación por WhatsApp' : null,
                $linkWa
            );

            $mail->send();

            if (function_exists('registrar_log')) {
                registrar_log(
                    $pdo,
                    'EMAIL_ANTICIPO_PAGADO',
                    "Notificación de anticipo $" . number_format($montoAnticipo, 2) . " MXN para cotización #COT-{$idCotizacion} enviada a {$cfg['notify_to']}.",
                    (int)($cot['id_usuario'] ?? null)
                );
            }

            return true;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error al enviar correo de anticipo cotización #{$idCotizacion}: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // DISPARADOR 3: RECORDATORIO DE CITA EN PRÓXIMAS 24 HORAS
    // =========================================================================
    /**
     * Envía recordatorio por correo para una cita específica
     */
    public static function notificarRecordatorioCita(PHPMailer $mail, array $cita): bool {
        $cfg = self::obtenerConfig();
        $mail->clearAddresses();
        $mail->addAddress($cfg['notify_to']);

        $idCita          = (int)$cita['id_cita'];
        $clienteNombre   = $cita['cliente_nombre'] ?? 'Cliente';
        $clienteTelefono = $cita['cliente_telefono'] ?? 'No especificado';
        $clienteCorreo   = $cita['cliente_correo'] ?? 'No especificado';
        $tipoCita        = ($cita['tipo_cita'] === 'taller') ? 'Visita en el Taller' : 'Visita a Domicilio';
        $fechaHoraTs     = strtotime($cita['fecha_hora']);
        $fechaFmt        = date('d/m/Y', $fechaHoraTs);
        $horaFmt         = date('H:i', $fechaHoraTs) . ' hrs';
        $direccion       = !empty($cita['direccion']) ? $cita['direccion'] : 'N/A (Visita en Taller)';
        $notas           = !empty($cita['notas']) ? $cita['notas'] : 'Sin observaciones';

        $asunto = "[TallerTech] Recordatorio: Cita de medición en 24h - {$clienteNombre}";
        $mail->Subject = $asunto;

        $msgWhatsApp = "Hola {$clienteNombre}, te recordamos tu cita de medición con TallerTech programada para mañana {$fechaFmt} a las {$horaFmt}. ¡Quedamos a tus órdenes!";
        $linkWa = self::generarLinkWhatsApp($clienteTelefono, $msgWhatsApp);

        $filas = [
            ['label' => 'Folio de Cita',       'valor' => "#CITA-{$idCita}", 'destacado' => true],
            ['label' => 'Cliente',             'valor' => $clienteNombre],
            ['label' => 'Teléfono',            'valor' => $clienteTelefono],
            ['label' => 'Correo Electrónico',  'valor' => $clienteCorreo],
            ['label' => 'Fecha y Hora Cita',   'valor' => "{$fechaFmt} a las {$horaFmt}", 'destacado' => true],
            ['label' => 'Modalidad',           'valor' => $tipoCita],
            ['label' => 'Lugar / Dirección',   'valor' => $direccion],
            ['label' => 'Notas del Taller',    'valor' => $notas],
            ['label' => 'Estado',              'valor' => ucfirst($cita['estado'] ?? 'confirmada')],
        ];

        $mail->Body    = self::renderPlantillaHtml(
            "Recordatorio de Cita Próxima (24h)",
            "Esta cita se llevará a cabo dentro de las próximas 24 horas. Prepara cinta métrica, croquis y muestrario.",
            $filas,
            $linkWa ? 'Confirmar Cita con el Cliente vía WhatsApp' : null,
            $linkWa,
            'Recuerda verificar dirección y croquis antes de salir a la visita.'
        );
        $mail->AltBody = self::renderPlantillaTexto(
            "Recordatorio de Cita Próxima (24h)",
            "Esta cita se llevará a cabo dentro de las próximas 24 horas.",
            $filas,
            $linkWa ? 'Confirmar Cita por WhatsApp' : null,
            $linkWa
        );

        $mail->send();
        return true;
    }

    /**
     * Procesa todas las citas que ocurrirán en las próximas 24h sin recordatorio previo.
     * Incorpora bloqueo optimista para prevenir envíos duplicados / condiciones de carrera.
     */
    public static function procesarRecordatoriosProximos(PDO $pdo, bool $esDryRun = false, bool $esDebugCLI = false): array {
        $resultado = [
            'total_encontradas' => 0,
            'enviadas'          => 0,
            'errores'           => 0,
            'detalles'          => []
        ];

        try {
            $cfg = self::obtenerConfig();
            $ahora = date('Y-m-d H:i:s');
            $en24Horas = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Consulta con parámetros calculados en PHP para evitar desfases con UTC en TiDB Cloud
            $sql = "
                SELECT ci.id_cita, ci.id_usuario, ci.fecha_hora, ci.tipo_cita, ci.direccion, ci.notas, ci.estado,
                       u.nombre AS cliente_nombre, u.correo AS cliente_correo, u.telefono AS cliente_telefono
                FROM citas ci
                LEFT JOIN usuarios u ON ci.id_usuario = u.id_usuario
                WHERE ci.recordatorio_enviado = 0
                  AND ci.estado NOT IN ('cancelada', 'completada')
                  AND ci.fecha_hora >= :ahora
                  AND ci.fecha_hora <= :en24h
                ORDER BY ci.fecha_hora ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ahora' => $ahora,
                ':en24h' => $en24Horas
            ]);
            $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resultado['total_encontradas'] = count($citas);

            if ($esDryRun) {
                foreach ($citas as $c) {
                    $resultado['detalles'][] = [
                        'id_cita'    => (int)$c['id_cita'],
                        'cliente'    => $c['cliente_nombre'],
                        'fecha_hora' => $c['fecha_hora'],
                        'accion'     => '[DRY-RUN] Detectada cita para recordatorio (sin envío real)'
                    ];
                }
                return $resultado;
            }

            if (empty($citas)) {
                return $resultado;
            }

            $mail = self::crearMailer($esDebugCLI);
            if (!$mail) {
                $resultado['errores'] = count($citas);
                return $resultado;
            }

            $stmtLock = $pdo->prepare("
                UPDATE citas 
                SET recordatorio_enviado = 1, recordatorio_enviado_en = NOW() 
                WHERE id_cita = :id AND recordatorio_enviado = 0
            ");

            $stmtRevert = $pdo->prepare("
                UPDATE citas 
                SET recordatorio_enviado = 0, recordatorio_enviado_en = NULL 
                WHERE id_cita = :id
            ");

            foreach ($citas as $c) {
                $idCita = (int)$c['id_cita'];

                // Bloqueo optimista: asegurar que ninguna otra instancia procese la misma cita
                $stmtLock->execute([':id' => $idCita]);
                if ($stmtLock->rowCount() !== 1) {
                    // Otra ejecución ya la tomó o ya fue marcada
                    continue;
                }

                try {
                    self::notificarRecordatorioCita($mail, $c);
                    $resultado['enviadas']++;
                    $resultado['detalles'][] = [
                        'id_cita' => $idCita,
                        'cliente' => $c['cliente_nombre'],
                        'estado'  => 'Enviado con éxito'
                    ];

                    if (function_exists('registrar_log')) {
                        registrar_log(
                            $pdo,
                            'EMAIL_RECORDATORIO_24H',
                            "Recordatorio 24h enviado para cita #CITA-{$idCita} ({$c['cliente_nombre']}).",
                            (int)($c['id_usuario'] ?? null)
                        );
                    }
                } catch (Throwable $e) {
                    // Revertir marca de enviado si falló el transporte SMTP
                    $stmtRevert->execute([':id' => $idCita]);
                    $resultado['errores']++;
                    $resultado['detalles'][] = [
                        'id_cita' => $idCita,
                        'cliente' => $c['cliente_nombre'],
                        'error'   => $e->getMessage()
                    ];
                    error_log("[TallerTech Notificaciones] Error al enviar recordatorio para cita #{$idCita}: " . $e->getMessage());
                }
            }

        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error en procesarRecordatoriosProximos: " . $e->getMessage());
            $resultado['errores']++;
        }

        return $resultado;
    }

    /**
     * Pseudo-cron ligero para dashboard web: se ejecuta como máximo 1 vez cada 10 minutos
     */
    public static function verificarRecordatoriosPendientes(PDO $pdo): void {
        try {
            $lockFile = sys_get_temp_dir() . '/tallertech_cron.lock';
            if (file_exists($lockFile)) {
                $ultimoRun = (int)@file_get_contents($lockFile);
                if ((time() - $ultimoRun) < 600) { // 10 minutos
                    return;
                }
            }

            @file_put_contents($lockFile, (string)time());
            self::procesarRecordatoriosProximos($pdo, false, false);
        } catch (Throwable $e) {
            // Falla silenciosa para jamás bloquear la carga del dashboard del carpintero
            error_log("[TallerTech Notificaciones] Pseudo-cron error: " . $e->getMessage());
        }
    }

    // =========================================================================
    // SECCIÓN: NOTIFICACIONES DIRECTAS AL CLIENTE
    // =========================================================================

    /**
     * Aviso 1 (Prioritario): Cotización revisada por el carpintero y lista para ser aprobada
     */
    public static function notificarClienteCotizacionLista(PDO $pdo, int $idCotizacion): bool {
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       u.nombre AS cliente_nombre, 
                       u.correo AS cliente_correo, 
                       u.telefono AS cliente_telefono,
                       m.nombre AS mueble_nombre
                FROM cotizaciones c
                LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                LEFT JOIN muebles_catalogo m ON c.id_mueble = m.id_mueble
                WHERE c.id_cotizacion = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $idCotizacion]);
            $cot = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cot || empty($cot['cliente_correo'])) {
                return false;
            }

            $cfg = self::obtenerConfig();
            $clienteNombre = $cot['cliente_nombre'] ?? 'Cliente';
            $costoTotal    = (float)$cot['costo_total'];
            $anticipoReq   = round($costoTotal * 0.60, 2);
            $proyecto      = $cot['mueble_nombre'] ?? 'Proyecto de Carpintería a Medida';
            $notas         = !empty($cot['notas']) ? $cot['notas'] : 'Especificaciones técnicas revisadas por el carpintero.';

            $filas = [
                ['label' => 'Folio de Cotización',          'valor' => "#COT-{$idCotizacion}", 'destacado' => true],
                ['label' => 'Proyecto / Mueble',            'valor' => $proyecto],
                ['label' => 'Presupuesto Total Aprobado',    'valor' => '$' . number_format($costoTotal, 2) . ' MXN', 'destacado' => true],
                ['label' => 'Anticipo Requerido (60%)',     'valor' => '$' . number_format($anticipoReq, 2) . ' MXN'],
                ['label' => 'Notas del Maestro Carpintero', 'valor' => $notas],
                ['label' => 'Estado',                       'valor' => 'Listo para tu Aprobación'],
            ];

            $asunto  = "Tu cotización #COT-{$idCotizacion} en TallerTech ya está lista para revisar";
            $titulo  = "¡Tu cotización ya está lista!";
            $sub     = "Hola {$clienteNombre}, nuestro carpintero ha revisado y validado tu cotización. Ya puedes consultar los costos finales y autorizar el inicio de fabricación.";
            $btnLink = $cfg['app_url'] . '/pedidos.php';

            $enviado = self::enviarNotificacionCliente(
                $cot['cliente_correo'],
                $clienteNombre,
                $asunto,
                $titulo,
                $sub,
                $filas,
                'Ver y Aceptar Cotización',
                $btnLink,
                'Al autorizar tu cotización con el 60% de anticipo se inicia de inmediato el habilitado de maderas en el taller.'
            );

            if ($enviado && function_exists('registrar_log')) {
                registrar_log(
                    $pdo,
                    'EMAIL_CLIENTE_COTIZACION_LISTA',
                    "Aviso de cotización lista #COT-{$idCotizacion} enviado al cliente ({$cot['cliente_correo']}).",
                    (int)($cot['id_usuario'] ?? null)
                );
            }

            return $enviado;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error notificarClienteCotizacionLista: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aviso 2: Confirmación de cita agendada enviada al cliente
     */
    public static function notificarClienteNuevaCita(PDO $pdo, int $idCita): bool {
        try {
            $stmt = $pdo->prepare("
                SELECT ci.*, 
                       u.nombre AS cliente_nombre, 
                       u.correo AS cliente_correo, 
                       u.telefono AS cliente_telefono
                FROM citas ci
                LEFT JOIN usuarios u ON ci.id_usuario = u.id_usuario
                WHERE ci.id_cita = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $idCita]);
            $cita = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cita || empty($cita['cliente_correo'])) {
                return false;
            }

            $cfg = self::obtenerConfig();
            $clienteNombre = $cita['cliente_nombre'] ?? 'Cliente';
            $fechaHoraTs   = strtotime($cita['fecha_hora']);
            $fechaFmt      = date('d/m/Y', $fechaHoraTs);
            $horaFmt       = date('H:i', $fechaHoraTs) . ' hrs';
            $tipoCita      = ($cita['tipo_cita'] === 'taller') ? 'Visita en el Taller' : 'Visita a Domicilio';
            $direccion     = !empty($cita['direccion']) ? $cita['direccion'] : 'Taller TallerTech';
            $notas         = !empty($cita['notas']) ? $cita['notas'] : 'Toma de medidas y muestra de maderas';

            $filas = [
                ['label' => 'Folio de Cita',         'valor' => "#CITA-{$idCita}", 'destacado' => true],
                ['label' => 'Fecha Programada',      'valor' => "{$fechaFmt} a las {$horaFmt}", 'destacado' => true],
                ['label' => 'Modalidad',             'valor' => $tipoCita],
                ['label' => 'Ubicación / Destino',   'valor' => $direccion],
                ['label' => 'Notas u Observaciones', 'valor' => $notas],
                ['label' => 'Estado',                'valor' => 'Registrada en Agenda'],
            ];

            $asunto  = "Confirmación de tu cita en TallerTech";
            $titulo  = "¡Cita Reservada con Éxito!";
            $sub     = "Hola {$clienteNombre}, hemos registrado tu cita en la agenda de TallerTech. El carpintero revisará los croquis o notas previas y te atenderá puntualmente.";
            $btnLink = $cfg['app_url'] . '/citas.php';

            $enviado = self::enviarNotificacionCliente(
                $cita['cliente_correo'],
                $clienteNombre,
                $asunto,
                $titulo,
                $sub,
                $filas,
                'Ver Mis Citas',
                $btnLink,
                'Te enviaremos un recordatorio 24 horas antes de tu visita. Si necesitas reprogramar, contáctanos por WhatsApp.'
            );

            if ($enviado && function_exists('registrar_log')) {
                registrar_log(
                    $pdo,
                    'EMAIL_CLIENTE_CITA_CONFIRMADA',
                    "Confirmación de cita #CITA-{$idCita} enviada al cliente ({$cita['cliente_correo']}).",
                    (int)($cita['id_usuario'] ?? null)
                );
            }

            return $enviado;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error notificarClienteNuevaCita: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aviso 3: Comprobante de anticipo recibido enviado al cliente
     */
    public static function notificarClienteAnticipoRecibido(
        PDO $pdo,
        int $idCotizacion,
        float $montoAnticipo,
        float $costoTotal,
        ?string $muebleNombre = null,
        ?int $idPedido = null
    ): bool {
        try {
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       u.nombre AS cliente_nombre, 
                       u.correo AS cliente_correo, 
                       u.telefono AS cliente_telefono
                FROM cotizaciones c
                LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
                WHERE c.id_cotizacion = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $idCotizacion]);
            $cot = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cot || empty($cot['cliente_correo'])) {
                return false;
            }

            $cfg = self::obtenerConfig();
            $clienteNombre  = $cot['cliente_nombre'] ?? 'Cliente';
            $saldoRestante  = max(0, $costoTotal - $montoAnticipo);
            $proyecto       = $muebleNombre ?: 'Proyecto de Carpintería a Medida';
            $pedidoFolio    = $idPedido ? "#PED-{$idPedido}" : 'En proceso';

            $filas = [
                ['label' => 'Folio de Cotización',       'valor' => "#COT-{$idCotizacion}"],
                ['label' => 'Orden de Fabricación',       'valor' => $pedidoFolio, 'destacado' => true],
                ['label' => 'Proyecto / Mueble',          'valor' => $proyecto],
                ['label' => 'Monto Anticipo Confirmado',  'valor' => '$' . number_format($montoAnticipo, 2) . ' MXN', 'destacado' => true],
                ['label' => 'Costo Total del Proyecto',   'valor' => '$' . number_format($costoTotal, 2) . ' MXN'],
                ['label' => 'Saldo Restante al Liquidar', 'valor' => '$' . number_format($saldoRestante, 2) . ' MXN'],
                ['label' => 'Fecha de Confirmación',      'valor' => date('d/m/Y H:i') . ' hrs'],
                ['label' => 'Estatus del Pedido',         'valor' => 'En Fabricación / Taller'],
            ];

            $asunto  = "Recibimos tu anticipo - Cotización #COT-{$idCotizacion} | TallerTech";
            $titulo  = "Comprobante de Anticipo Recibido";
            $sub     = "Hola {$clienteNombre}, confirmamos con éxito la recepción de tu anticipo. Tu orden de trabajo ya fue programada en taller para corte y ensamble.";
            $btnLink = $cfg['app_url'] . '/pedidos.php';

            $enviado = self::enviarNotificacionCliente(
                $cot['cliente_correo'],
                $clienteNombre,
                $asunto,
                $titulo,
                $sub,
                $filas,
                'Ver Avance de Mi Pedido',
                $btnLink,
                'El 40% restante se liquidará una vez entregado e instalado tu mueble a entera satisfacción.'
            );

            if ($enviado && function_exists('registrar_log')) {
                registrar_log(
                    $pdo,
                    'EMAIL_CLIENTE_ANTICIPO_RECIBIDO',
                    "Comprobante de anticipo de $" . number_format($montoAnticipo, 2) . " MXN enviado al cliente ({$cot['cliente_correo']}).",
                    (int)($cot['id_usuario'] ?? null)
                );
            }

            return $enviado;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error notificarClienteAnticipoRecibido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aviso 4a: Cambio de estado del pedido (fabricación, acabados, en camino, completado)
     */
    public static function notificarClienteCambioEstadoPedido(
        PDO $pdo,
        int $idPedido,
        string $nuevoEstado,
        ?string $fechaEstimada = null
    ): bool {
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, 
                       u.nombre AS cliente_nombre, 
                       u.correo AS cliente_correo, 
                       u.telefono AS cliente_telefono
                FROM pedidos p
                LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE p.id_pedido = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $idPedido]);
            $ped = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ped || empty($ped['cliente_correo'])) {
                return false;
            }

            $estadosMap = [
                'pendiente_anticipo' => 'Pendiente de Anticipo',
                'en_produccion'      => 'En Fabricación / Carpintería',
                'en_acabados'        => 'En Proceso de Lijado y Barnizado',
                'en_camino'          => 'Listo para Entrega / En Camino',
                'completado'         => 'Entregado e Instalado',
                'cancelado'          => 'Cancelado'
            ];
            $estadoLabel = $estadosMap[$nuevoEstado] ?? ucfirst(str_replace('_', ' ', $nuevoEstado));

            $cfg = self::obtenerConfig();
            $clienteNombre = $ped['cliente_nombre'] ?? 'Cliente';
            $fechaEntrega  = !empty($fechaEstimada) ? date('d/m/Y', strtotime($fechaEstimada)) : 'Por coordinar con el taller';

            $filas = [
                ['label' => 'Orden de Fabricación',   'valor' => "#PED-{$idPedido}", 'destacado' => true],
                ['label' => 'Proyecto / Mueble',       'valor' => $ped['mueble_nombre'] ?? 'Mueble a Medida'],
                ['label' => 'Nuevo Estado',            'valor' => $estadoLabel, 'destacado' => true],
                ['label' => 'Fecha Estimada Entrega',  'valor' => $fechaEntrega],
            ];

            $asunto  = "Actualización de tu pedido #PED-{$idPedido}: {$estadoLabel} | TallerTech";
            $titulo  = "Avance en tu Orden #PED-{$idPedido}";
            $sub     = "Hola {$clienteNombre}, tenemos una actualización sobre el proceso de fabricación de tus muebles en el taller.";
            $btnLink = $cfg['app_url'] . '/pedidos.php';

            $enviado = self::enviarNotificacionCliente(
                $ped['cliente_correo'],
                $clienteNombre,
                $asunto,
                $titulo,
                $sub,
                $filas,
                'Ver Estado del Pedido',
                $btnLink,
                'Puedes dar seguimiento a las fases de corte, ensamble y barniz desde tu cuenta.'
            );

            if ($enviado && function_exists('registrar_log')) {
                registrar_log(
                    $pdo,
                    'EMAIL_CLIENTE_ESTADO_PEDIDO',
                    "Aviso de cambio de estado a [{$estadoLabel}] en pedido #PED-{$idPedido} enviado a ({$ped['cliente_correo']}).",
                    (int)($ped['id_usuario'] ?? null)
                );
            }

            return $enviado;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error notificarClienteCambioEstadoPedido: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aviso 4b: Cambio de estado de la cita (confirmada, completada, cancelada)
     */
    public static function notificarClienteCambioEstadoCita(
        PDO $pdo,
        int $idCita,
        string $nuevoEstado,
        ?string $notas = null
    ): bool {
        try {
            $stmt = $pdo->prepare("
                SELECT ci.*, 
                       u.nombre AS cliente_nombre, 
                       u.correo AS cliente_correo, 
                       u.telefono AS cliente_telefono
                FROM citas ci
                LEFT JOIN usuarios u ON ci.id_usuario = u.id_usuario
                WHERE ci.id_cita = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $idCita]);
            $cita = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cita || empty($cita['cliente_correo'])) {
                return false;
            }

            $estadosMap = [
                'confirmada' => 'Confirmada por el Carpintero',
                'completada' => 'Visita y Medición Realizada con Éxito',
                'cancelada'  => 'Cita Cancelada',
                'pendiente'  => 'En Revisión'
            ];
            $estadoLabel = $estadosMap[$nuevoEstado] ?? ucfirst($nuevoEstado);

            $cfg = self::obtenerConfig();
            $clienteNombre = $cita['cliente_nombre'] ?? 'Cliente';
            $fechaHoraTs   = strtotime($cita['fecha_hora']);
            $fechaFmt      = date('d/m/Y H:i', $fechaHoraTs) . ' hrs';

            $filas = [
                ['label' => 'Folio de Cita',    'valor' => "#CITA-{$idCita}", 'destacado' => true],
                ['label' => 'Fecha y Hora',     'valor' => $fechaFmt],
                ['label' => 'Nuevo Estado',     'valor' => $estadoLabel, 'destacado' => true],
                ['label' => 'Observaciones',    'valor' => !empty($notas) ? $notas : 'Sin observaciones adicionales'],
            ];

            $asunto  = "Actualización de tu cita #CITA-{$idCita}: {$estadoLabel} | TallerTech";
            $titulo  = "Estatus de tu Cita #CITA-{$idCita}";
            $sub     = "Hola {$clienteNombre}, el carpintero ha actualizado el estatus de tu cita de medición.";
            $btnLink = $cfg['app_url'] . '/citas.php';

            $enviado = self::enviarNotificacionCliente(
                $cita['cliente_correo'],
                $clienteNombre,
                $asunto,
                $titulo,
                $sub,
                $filas,
                'Consultar Mis Citas',
                $btnLink,
                'Si tienes dudas sobre tu horario o requieres reprogramar, puedes contactarnos por WhatsApp.'
            );

            if ($enviado && function_exists('registrar_log')) {
                registrar_log(
                    $pdo,
                    'EMAIL_CLIENTE_ESTADO_CITA',
                    "Aviso de cita #CITA-{$idCita} [{$estadoLabel}] enviado a ({$cita['cliente_correo']}).",
                    (int)($cita['id_usuario'] ?? null)
                );
            }

            return $enviado;
        } catch (Throwable $e) {
            error_log("[TallerTech Notificaciones] Error notificarClienteCambioEstadoCita: " . $e->getMessage());
            return false;
        }
    }
}
