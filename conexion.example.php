<?php
// Credenciales de tu base de datos en Aiven
$host = 'tu-host-en.aivencloud.com';
$port = '12345'; // El puerto que te asigne Aiven (suele ser distinto a 3306)
$dbname = 'defaultdb'; // O el nombre que le hayas puesto a tu DB
$username = 'avnadmin'; // Tu usuario de Aiven
$password = 'TU_CONTRASEÑA_AQUI';

try {
    // Se establece la conexión usando PDO
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Si Aiven requiere SSL, descomenta la siguiente línea y pon la ruta al certificado
        // PDO::MYSQL_ATTR_SSL_CA    => '/ruta/al/ca.pem'
    ];

    $pdo = new PDO($dsn, $username, $password, $opciones);
    // Descomenta la siguiente línea solo para probar que funciona, luego bórrala:
    // echo "¡Conexión a la base de datos de TallerTech exitosa!";
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>