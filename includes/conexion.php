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

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $opciones);
} catch (PDOException $e) {
    // Si la conexión falla (ej. entorno local sin BD levantada), permitir continuar en modo demo si aplica
    if (php_sapi_name() === 'cli' && defined('IGNORAR_ERROR_CONEXION_CLI')) {
        $pdo = null;
    } else {
        die("Error de conexión: " . $e->getMessage());
    }
}
