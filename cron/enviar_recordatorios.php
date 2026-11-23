<?php
/**
 * TallerTech - Tarea Programada (CLI / Cron)
 * Envío de recordatorios automáticos de citas próximas (ventana de 24 horas).
 * 
 * Uso:
 *   php cron/enviar_recordatorios.php
 *   php cron/enviar_recordatorios.php --dry-run
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Acceso restringido: Este script solo puede ejecutarse desde terminal o crontab (CLI).\n");
}

define('IGNORAR_ERROR_CONEXION_CLI', true);

require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Notificaciones.php';

use TallerTech\Notificaciones;

$esDryRun = in_array('--dry-run', $argv ?? []);

echo "\n" . str_repeat('=', 65) . "\n";
echo "   🪵 TALLERTECH - CRON DE RECORDATORIOS DE CITAS (24 HORAS)\n";
echo str_repeat('=', 65) . "\n";

$cfg = Notificaciones::obtenerConfig();
echo "Hora actual (PHP):  " . date('Y-m-d H:i:s') . " ({$cfg['timezone']})\n";
echo "Ventana búsqueda:   Desde " . date('Y-m-d H:i:s') . " hasta " . date('Y-m-d H:i:s', strtotime('+24 hours')) . "\n";
echo "Destinatario:       {$cfg['notify_to']}\n";
echo "Modo de ejecución:  " . ($esDryRun ? "DRY-RUN (Simulación sin enviar correos)" : "PRODUCCIÓN (Envíos reales)") . "\n";
echo str_repeat('-', 65) . "\n";

if (!isset($pdo) || $pdo === null) {
    echo "❌ Error fatal: No hay conexión con la base de datos (TiDB Cloud).\n";
    exit(1);
}

$resultado = Notificaciones::procesarRecordatoriosProximos($pdo, $esDryRun, true);

echo "\n--- RESUMEN DE EJECUCIÓN ---\n";
echo "Citas en ventana (24h):  " . $resultado['total_encontradas'] . "\n";
echo "Correos enviados:        " . $resultado['enviadas'] . "\n";
echo "Errores detectados:      " . $resultado['errores'] . "\n";

if (!empty($resultado['detalles'])) {
    echo "\nDetalle por cita:\n";
    foreach ($resultado['detalles'] as $det) {
        $id = $det['id_cita'] ?? '?';
        $cli = $det['cliente'] ?? 'Cliente';
        $status = $det['accion'] ?? $det['estado'] ?? $det['error'] ?? 'Procesada';
        echo " - #CITA-{$id} ({$cli}): {$status}\n";
    }
} else {
    echo "ℹ️  No hay citas pendientes de recordatorio para las próximas 24 horas.\n";
}

echo str_repeat('=', 65) . "\n\n";
exit($resultado['errores'] > 0 ? 1 : 0);
