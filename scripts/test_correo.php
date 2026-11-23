<?php
/**
 * TallerTech - Script de Diagnóstico y Prueba de Correo SMTP (CLI)
 * 
 * Uso:
 *   php scripts/test_correo.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Acceso denegado: Este script de diagnóstico solo puede ejecutarse desde la terminal (CLI).\n");
}

require_once __DIR__ . '/../includes/Notificaciones.php';

use TallerTech\Notificaciones;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

echo "\n" . str_repeat('=', 70) . "\n";
echo "   🪵 TALLERTECH - DIAGNÓSTICO Y PRUEBA DE ENVÍO SMTP (GMAIL)\n";
echo str_repeat('=', 70) . "\n\n";

// 1. Validar variables de configuración
echo "[1/3] Verificando variables de entorno en .env...\n";
$cfg = Notificaciones::obtenerConfig();

$variablesRequeridas = [
    'SMTP_HOST'       => $cfg['host'],
    'SMTP_PORT'       => $cfg['port'],
    'SMTP_ENCRYPTION' => $cfg['encryption'],
    'SMTP_USER'       => $cfg['user'],
    'SMTP_PASS'       => $cfg['pass'],
    'NOTIFY_EMAIL'    => $cfg['notify_to'],
    'APP_TIMEZONE'    => $cfg['timezone'],
    'APP_URL'         => $cfg['app_url']
];

$faltantes = [];
foreach ($variablesRequeridas as $k => $v) {
    if (empty($v) && $v !== 0) {
        $faltantes[] = $k;
    }
}

echo "  - Servidor SMTP:   {$cfg['host']}:{$cfg['port']} ({$cfg['encryption']})\n";
echo "  - Usuario SMTP:    {$cfg['user']}\n";
echo "  - Remitente:       {$cfg['from_name']}\n";
echo "  - Destinatario:    {$cfg['notify_to']}\n";
echo "  - URL Base App:    {$cfg['app_url']}\n";
echo "  - Zona horaria:    {$cfg['timezone']}\n";

if (!empty($faltantes)) {
    echo "\n⚠️  ATENCIÓN: Las siguientes variables no tienen valor configurado en .env:\n";
    foreach ($faltantes as $f) {
        echo "   ❌ {$f}\n";
    }

    if (in_array('SMTP_PASS', $faltantes)) {
        echo "\n💡 INSTRUCCIÓN PARA CONFIGURAR 'SMTP_PASS' (App Password de Google):\n";
        echo "   1. Ingresa a tu cuenta de Google: https://myaccount.google.com/security\n";
        echo "   2. Activa la 'Verificación en 2 pasos' si no la tienes activada.\n";
        echo "   3. Ve a 'Contraseñas de aplicaciones' (App Passwords): https://myaccount.google.com/apppasswords\n";
        echo "   4. Crea una contraseña con el nombre 'TallerTech' (Google generará 16 letras, ej: abcd efgh ijkl mnop).\n";
        echo "   5. Abre tu archivo .env y asígnala: SMTP_PASS=abcdefghijklmnop\n";
        echo "   6. Vuelve a ejecutar este script: php scripts/test_correo.php\n\n";
    }

    echo str_repeat('=', 70) . "\n\n";
    exit(1);
}

echo "  ✅ Todas las variables de configuración están presentes.\n\n";

// 2. Probar conexión y autenticación SMTP
echo "[2/3] Conectando con {$cfg['host']}:{$cfg['port']} vía {$cfg['encryption']}...\n";

$mail = new PHPMailer(true);

try {
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
    $mail->Timeout    = 10;

    // Activar debug detallado en consola CLI
    $mail->SMTPDebug  = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = function($str, $level) {
        echo "     [SMTP] " . trim($str) . "\n";
    };

    $mail->setFrom($cfg['user'], $cfg['from_name']);
    $mail->addAddress($cfg['notify_to']);

    // 3. Preparar plantilla HTML de prueba
    echo "\n[3/3] Enviando correo de prueba a {$cfg['notify_to']}...\n";

    $mail->Subject = "[TallerTech] Diagnóstico de Notificaciones SMTP Exitoso";

    $filasDetalle = [
        ['label' => 'Servidor SMTP',       'valor' => "{$cfg['host']}:{$cfg['port']}", 'destacado' => true],
        ['label' => 'Cifrado de canal',    'valor' => strtoupper($cfg['encryption'])],
        ['label' => 'Usuario autenticado', 'valor' => $cfg['user']],
        ['label' => 'Destinatario',        'valor' => $cfg['notify_to']],
        ['label' => 'Fecha y hora',        'valor' => date('d/m/Y H:i:s') . " ({$cfg['timezone']})", 'destacado' => true],
        ['label' => 'Estado del servicio', 'valor' => 'OPERATIVO Y CONECTADO'],
    ];

    $linkWa = Notificaciones::generarLinkWhatsApp('5512345678', 'Prueba de integración WhatsApp TallerTech');

    $mail->Body = Notificaciones::renderPlantillaHtml(
        "¡Conexión SMTP Establecida con Éxito!",
        "El sistema de notificaciones de TallerTech ha validado correctamente las credenciales y el transporte SMTP de Gmail.",
        $filasDetalle,
        "Probar Botón de WhatsApp",
        $linkWa,
        "Si recibiste este correo, los 3 disparadores (Citas, Anticipos y Recordatorios 24h) funcionarán con normalidad."
    );

    $mail->AltBody = Notificaciones::renderPlantillaTexto(
        "¡Conexión SMTP Establecida con Éxito!",
        "El sistema de notificaciones de TallerTech ha validado correctamente las credenciales.",
        $filasDetalle
    );

    $mail->send();

    echo "\n" . str_repeat('-', 70) . "\n";
    echo "🎉 ¡PRUEBA EXITOSA! El correo llegó correctamente al servidor de Google.\n";
    echo "   Revisa la bandeja de entrada de: {$cfg['notify_to']}\n";
    echo str_repeat('=', 70) . "\n\n";
    exit(0);

} catch (Throwable $e) {
    echo "\n" . str_repeat('-', 70) . "\n";
    echo "❌ ERROR EN LA COMUNICACIÓN SMTP:\n";
    echo "   Detalle del error: " . $e->getMessage() . "\n\n";

    echo "🔍 DIAGNÓSTICO COMÚN:\n";
    if (str_contains($e->getMessage(), 'Username and Password not accepted') || str_contains($e->getMessage(), '535-5.7.8')) {
        echo "   • El usuario o la contraseña de aplicación de Gmail son incorrectos.\n";
        echo "   • Asegúrate de que SMTP_USER sea tu cuenta completa (ej. cristianlepezp145@gmail.com).\n";
        echo "   • Asegúrate de haber generado un 'App Password' de 16 caracteres en Google (no tu contraseña personal).\n";
    } elseif (str_contains($e->getMessage(), 'Could not connect') || str_contains($e->getMessage(), 'Connection timed out')) {
        echo "   • El servidor o proveedor de red podría tener bloqueado el puerto {$cfg['port']}.\n";
        echo "   • Intenta cambiar en .env: SMTP_PORT=465 y SMTP_ENCRYPTION=ssl\n";
    }
    echo str_repeat('=', 70) . "\n\n";
    exit(1);
}
