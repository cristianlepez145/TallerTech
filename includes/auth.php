<?php
/**
 * TallerTech - Módulo de Autenticación, Roles y Auditoría (Logs)
 * Proporciona helpers de sesión, control de acceso basado en roles (RBAC)
 * y persistencia centralizada en la tabla de logs.
 */

// 1. Inicialización segura de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Obtiene los datos del usuario autenticado en la sesión actual
 * @return array|null
 */
function usuario_actual(): ?array {
    if (!isset($_SESSION['id_usuario'])) {
        return null;
    }

    return [
        'id_usuario' => (int)$_SESSION['id_usuario'],
        'nombre'     => $_SESSION['nombre'] ?? 'Usuario',
        'correo'     => $_SESSION['correo'] ?? '',
        'rol'        => $_SESSION['rol'] ?? 'cliente'
    ];
}

/**
 * Comprueba si el usuario autenticado tiene rol de carpintero o administrador
 * @return bool
 */
function es_carpintero(): bool {
    $rol = $_SESSION['rol'] ?? '';
    return in_array($rol, ['carpintero', 'admin'], true);
}

/**
 * Comprueba si el usuario autenticado es cliente
 * @return bool
 */
function es_cliente(): bool {
    $rol = $_SESSION['rol'] ?? '';
    return in_array($rol, ['cliente', 'cliente_prospecto'], true);
}

/**
 * Middleware: Requiere que el usuario tenga sesión iniciada
 * @param string $redirect Ruta de redirección en caso de no estar autenticado
 */
function verificar_autenticacion(string $redirect = 'login.php'): void {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: {$redirect}");
        exit;
    }
}

/**
 * Middleware: Requiere que el usuario tenga un rol específico (ej. carpintero o admin)
 * @param array $roles_permitidos Lista de roles con permiso
 * @param string $redirect Ruta de redirección si no tiene permiso
 */
function verificar_rol(array $roles_permitidos = ['carpintero', 'admin'], string $redirect = 'index.php'): void {
    if (!isset($_SESSION['id_usuario'])) {
        header("Location: login.php");
        exit;
    }

    $rolActual = $_SESSION['rol'] ?? 'cliente';
    if (!in_array($rolActual, $roles_permitidos, true)) {
        header("Location: {$redirect}");
        exit;
    }
}

/**
 * Registra un evento en la tabla `logs` de la base de datos de manera resiliente.
 * No interrumpe la ejecución del sistema si la tabla aún no se ha creado en la base de datos.
 * 
 * @param PDO $pdo Instancia de conexión PDO
 * @param string $accion Nombre clave del evento (ej. 'LOGIN_EXITOSO', 'CAMBIO_ESTADO_PEDIDO')
 * @param string|null $detalle Descripción adicional, JSON o datos del registro afectado
 * @param int|null $usuario_id ID del usuario responsable (toma el de la sesión si es null)
 * @return bool True si se insertó con éxito, False en caso contrario
 */
function registrar_log(PDO $pdo, string $accion, ?string $detalle = null, ?int $usuario_id = null): bool {
    try {
        if ($usuario_id === null && isset($_SESSION['id_usuario'])) {
            $usuario_id = (int)$_SESSION['id_usuario'];
        }

        // Obtener la IP remota del cliente
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (strpos($ip, ',') !== false) {
            $ipParts = explode(',', $ip);
            $ip = trim($ipParts[0]);
        }

        $sql = "INSERT INTO logs (usuario_id, accion, detalle, ip, fecha) 
                VALUES (:usuario_id, :accion, :detalle, :ip, NOW())";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':usuario_id' => $usuario_id,
            ':accion'     => mb_strtoupper($accion, 'UTF-8'),
            ':detalle'    => $detalle,
            ':ip'         => substr($ip, 0, 45)
        ]);
    } catch (Throwable $e) {
        // Fallback silencioso al log del servidor para evitar caídas si la tabla logs no existe aún
        error_log("Aviso: No fue posible guardar el log en BD ({$accion}): " . $e->getMessage());
        return false;
    }
}
