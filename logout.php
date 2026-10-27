<?php
/**
 * TallerTech - Cierre de Sesión Seguro
 */

require_once 'includes/conexion.php';
require_once 'includes/auth.php';

// Registrar cierre de sesión en bitácora antes de destruir datos
if (isset($_SESSION['id_usuario']) && isset($pdo)) {
    $uid = (int)$_SESSION['id_usuario'];
    $nombre = $_SESSION['nombre'] ?? 'Usuario';
    registrar_log($pdo, 'LOGOUT', "Usuario '{$nombre}' cerró sesión exitosamente.", $uid);
}

// Vaciar todas las variables de sesión
$_SESSION = [];

// Invalidar la cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al inicio de sesión
header('Location: login.php');
exit;
