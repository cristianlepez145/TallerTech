<?php
// Leer las variables del archivo .env o tallertech.env de manera segura y robusta
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

$host     = $env['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$port     = $env['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$dbname   = $env['DB_NAME'] ?? getenv('DB_NAME') ?: '';
$username = $env['DB_USER'] ?? getenv('DB_USER') ?: '';
$password = $env['DB_PASS'] ?? getenv('DB_PASS') ?: '';
$socket   = $env['DB_SOCKET'] ?? getenv('DB_SOCKET') ?: '';

// Eliminar corchetes angulares accidentales en contraseñas de plantillas ej. <mi_password>
$password = trim($password);
if (str_starts_with($password, '<') && str_ends_with($password, '>')) {
    $password = substr($password, 1, -1);
}

try {
    if (!empty($socket) && file_exists($socket)) {
        $dsn = "mysql:unix_socket=$socket;dbname=$dbname;charset=utf8mb4";
    } else {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    }

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Detección y soporte TLS/SSL para proveedores en la nube como TiDB Cloud o Aiven
    $sslCa = $env['DB_SSL_CA'] ?? getenv('DB_SSL_CA') ?: '';
    if (empty($sslCa)) {
        $candidatosCA = [
            __DIR__ . '/cacert.pem',              // Paquete CA portátil incluido (compatible Windows, Mac, Linux, XAMPP, Laragon)
            '/etc/ssl/certs/ca-certificates.crt', // Debian / Ubuntu / Linux estándar
            '/etc/pki/tls/certs/ca-bundle.crt',   // RHEL / CentOS / Fedora
            '/etc/ssl/ca-bundle.pem',             // OpenSUSE
            '/etc/ssl/cert.pem',                  // macOS / FreeBSD / Alpine
        ];
        foreach ($candidatosCA as $ruta) {
            if (file_exists($ruta) && is_readable($ruta)) {
                $sslCa = $ruta;
                break;
            }
        }
    }

    if (!empty($sslCa) && file_exists($sslCa)) {
        $opciones[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO($dsn, $username, $password, $opciones);
} catch (PDOException $e) {
    error_log("Error de conexión PDO: " . $e->getMessage());
    // Si la conexión falla (ej. entorno local sin BD levantada), permitir continuar en modo demo si aplica
    if (php_sapi_name() === 'cli' && defined('IGNORAR_ERROR_CONEXION_CLI')) {
        $pdo = null;
    } else {
        http_response_code(503);
        $esLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost') || str_contains($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1');
        $detalleDev = '';
        if ($esLocalhost) {
            $faltaEnv = !file_exists($envPath);
            $msgDev = $faltaEnv 
                ? "No se encontró el archivo .env en el proyecto. Debes crear tu archivo .env copiando las credenciales de TiDB Cloud (o renombrar .env.example a .env)." 
                : "Error PDO: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            $detalleDev = "<div style='margin-top:1.5rem; padding:0.85rem 1.15rem; background:#1c1917; border:1px solid #c88a4b; border-radius:8px; font-family:monospace; font-size:0.8rem; color:#f2ede4; max-width:560px; word-break:break-word; text-align:left;'><strong>🪵 Diagnóstico técnico para desarrolladores:</strong><br><span style='color:#eab308;'>{$msgDev}</span></div>";
        }
        die("
            <div style='background:#121212; color:#ffffff; font-family:system-ui,-apple-system,sans-serif; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem; text-align:center;'>
                <div style='width:64px; height:64px; border-radius:50%; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); display:flex; align-items:center; justify-content:center; font-size:1.75rem; color:#f87171; margin-bottom:1.5rem;'>⚠️</div>
                <h1 style='font-size:1.75rem; font-weight:700; margin-bottom:0.75rem;'>Servicio Temporalmente No Disponible</h1>
                <p style='color:#a1a1aa; max-width:480px; line-height:1.6; margin-bottom:1.5rem;'>No fue posible establecer conexión con el servidor de datos de TallerTech. Por favor, verifica tu conexión o intenta de nuevo más tarde.</p>
                <a href='index.php' style='color:#c48849; text-decoration:underline; font-weight:600;'>Reintentar cargar el sitio</a>
                {$detalleDev}
            </div>
        ");
    }
}
