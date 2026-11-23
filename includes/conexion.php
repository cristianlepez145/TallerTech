<?php
// Leer las variables del archivo .env o tallertech.env de manera segura
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath) && file_exists(__DIR__ . '/../tallertech.env')) {
    $envPath = __DIR__ . '/../tallertech.env';
}
$env = (file_exists($envPath) && is_readable($envPath)) ? @parse_ini_file($envPath) : [];
if (!is_array($env)) {
    $env = [];
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
        die("
            <div style='background:#121212; color:#ffffff; font-family:system-ui,-apple-system,sans-serif; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem; text-align:center;'>
                <div style='width:64px; height:64px; border-radius:50%; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); display:flex; align-items:center; justify-content:center; font-size:1.75rem; color:#f87171; margin-bottom:1.5rem;'>⚠️</div>
                <h1 style='font-size:1.75rem; font-weight:700; margin-bottom:0.75rem;'>Servicio Temporalmente No Disponible</h1>
                <p style='color:#a1a1aa; max-width:480px; line-height:1.6; margin-bottom:1.5rem;'>No fue posible establecer conexión con el servidor de datos de TallerTech. Por favor, verifica tu conexión o intenta de nuevo más tarde.</p>
                <a href='index.php' style='color:#c48849; text-decoration:underline; font-weight:600;'>Reintentar cargar el sitio</a>
            </div>
        ");
    }
}
