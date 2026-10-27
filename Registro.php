<?php
/**
 * TallerTech - Registro de Usuarios (Sign Up Split Screen)
 * Pantalla completa en modo oscuro con mosaico fotográfico y validación PDO
 */

// 1. Iniciar sesión y comprobar si el usuario ya está autenticado
require_once 'includes/conexion.php';
require_once 'includes/auth.php';

if (isset($_SESSION['id_usuario'])) {
    if (es_carpintero()) {
        header('Location: admin_pedidos.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$mensaje_error = '';
$nombre   = '';
$correo   = '';
$telefono = '';

// 3. Procesamiento del formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre           = trim($_POST['nombre'] ?? '');
    $correo           = trim($_POST['correo'] ?? '');
    $telefono         = trim($_POST['telefono'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // a) Validar que ningún campo obligatorio esté vacío
    if (empty($nombre) || empty($correo) || empty($telefono) || empty($password) || empty($confirm_password)) {
        $mensaje_error = 'Por favor, llena todos los campos del formulario.';
    }
    // b) Validar formato de correo electrónico
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'Por favor, ingresa un correo electrónico válido.';
    }
    // c) Validar longitud mínima de contraseña (al menos 8 caracteres)
    elseif (strlen($password) < 8) {
        $mensaje_error = 'La contraseña debe tener un mínimo de 8 caracteres.';
    }
    // Comprobar coincidencia exacta entre ambas contraseñas
    elseif ($password !== $confirm_password) {
        $mensaje_error = 'Las contraseñas introducidas no coinciden.';
    }
    else {
        try {
            // d) Evitar duplicados: Verificar si el correo ya existe
            $checkSql = "SELECT id_usuario FROM usuarios WHERE correo = :correo LIMIT 1";
            $stmtCheck = $pdo->prepare($checkSql);
            $stmtCheck->execute([':correo' => $correo]);

            if ($stmtCheck->fetch()) {
                $mensaje_error = 'Ese correo electrónico ya se encuentra registrado. Por favor, inicia sesión.';
            } else {
                // Hashear la contraseña con el algoritmo seguro por defecto
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $rol = 'cliente_prospecto'; // Rol por defecto acorde a la estructura ENUM

                // Inserción segura con marcadores preparados PDO
                $sql = "INSERT INTO usuarios (nombre, correo, telefono, password_hash, rol) 
                        VALUES (:nombre, :correo, :telefono, :password_hash, :rol)";
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([
                    ':nombre'        => $nombre,
                    ':correo'        => $correo,
                    ':telefono'      => $telefono,
                    ':password_hash' => $password_hash,
                    ':rol'           => $rol
                ]);

                $nuevo_id = (int)$pdo->lastInsertId();

                // Registrar en la bitácora de auditoría
                registrar_log(
                    $pdo, 
                    'REGISTRO_USUARIO', 
                    "Nuevo usuario registrado: '{$nombre}' ({$correo}) con rol [{$rol}].", 
                    $nuevo_id
                );

                // Inicio de sesión automático y seguro tras el registro
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $nuevo_id;
                $_SESSION['nombre']     = $nombre;
                $_SESSION['correo']     = $correo;
                $_SESSION['rol']        = $rol;

                // Redirigir a la página principal
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log('Error en registro PDO: ' . $e->getMessage());
            if ($e->getCode() == 23000) {
                $mensaje_error = 'Ese correo ya está registrado en la plataforma.';
            } else {
                $mensaje_error = 'Ocurrió un error en el servidor. Intenta de nuevo más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta | TallerTech</title>
    
    <!-- Estilos compartidos de autenticación (Split Screen y Dark Mode) -->
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-page-container">
    <div class="auth-card">
        
        <!-- Columna Izquierda: Mosaico de Fotos de Carpintería y Espacios (Masonry Moderno) -->
        <div class="auth-mosaic-col" aria-hidden="true">
            <div class="mosaic-grid">
                
                <!-- Foto 1: Cocina de madera contemporánea -->
                <div class="mosaic-item mosaic-item--tall-1">
                    <img 
                        src="https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80" 
                        alt="Cocina contemporánea en madera selecta"
                        loading="eager"
                    >
                    <div class="mosaic-overlay"></div>
                </div>

                <!-- Foto 2: Mueble contemporáneo y repisas -->
                <div class="mosaic-item mosaic-item--short-1">
                    <img 
                        src="https://images.unsplash.com/photo-1538688525198-9b88f6f53126?auto=format&fit=crop&w=600&q=80" 
                        alt="Mueble de diseño contemporáneo"
                        loading="eager"
                    >
                    <div class="mosaic-overlay"></div>
                </div>

                <!-- Foto 3: Mobiliario artesanal y comedor -->
                <div class="mosaic-item mosaic-item--short-2">
                    <img 
                        src="https://images.unsplash.com/photo-1592078615290-033ee584e267?auto=format&fit=crop&w=600&q=80" 
                        alt="Mobiliario de diseño y carpintería fina"
                        loading="lazy"
                    >
                    <div class="mosaic-overlay"></div>
                </div>

                <!-- Foto 4: Vanity de baño en madera estufada -->
                <div class="mosaic-item mosaic-item--tall-2">
                    <img 
                        src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=600&q=80" 
                        alt="Mueble de baño contemporáneo suspendido"
                        loading="lazy"
                    >
                    <div class="mosaic-overlay"></div>
                </div>

            </div>
        </div>

        <!-- Columna Derecha: Formulario de Registro Centrado -->
        <div class="auth-form-col">
            <div class="auth-form-inner">
                
                <!-- Logo del Árbol + Texto TallerTech -->
                <a href="index.php" class="auth-brand" title="TallerTech - Inicio">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L6 8h3.5l-4 6h4l-3.5 5h12l-3.5-5h4l-4-6H18L12 2z" fill="rgba(196, 136, 73, 0.22)" stroke="#c48849" />
                        <path d="M11 21v3h2v-3" stroke="#c48849" stroke-width="2.5" />
                    </svg>
                    <span class="auth-brand-title">TallerTech</span>
                </a>

                <div class="auth-header">
                    <h1 class="auth-title">Crear Cuenta</h1>
                    <p class="auth-subtitle">Regístrate para cotizar muebles a medida y gestionar tus pedidos.</p>
                </div>

                <!-- Mensaje de error discreto y estilizado en rojo suave -->
                <?php if (!empty($mensaje_error)): ?>
                    <div class="auth-alert auth-alert-error" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?= htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Alta de Usuario -->
                <form method="POST" action="Registro.php" autocomplete="on">
                    
                    <!-- Campo Nombre Completo -->
                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input 
                            type="text" 
                            id="nombre" 
                            name="nombre" 
                            class="form-input" 
                            placeholder="Ej. Juan Pérez" 
                            value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>" 
                            required 
                            autofocus
                        >
                    </div>

                    <!-- Campo Correo Electrónico -->
                    <div class="form-group">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input 
                            type="email" 
                            id="correo" 
                            name="correo" 
                            class="form-input" 
                            placeholder="nombre@ejemplo.com" 
                            value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>" 
                            required
                        >
                    </div>

                    <!-- Campo Teléfono / WhatsApp -->
                    <div class="form-group">
                        <label for="telefono" class="form-label">Teléfono / WhatsApp</label>
                        <input 
                            type="tel" 
                            id="telefono" 
                            name="telefono" 
                            class="form-input" 
                            placeholder="+52 55 1234 5678" 
                            value="<?= htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8') ?>" 
                            required
                        >
                    </div>

                    <!-- Campo Contraseña -->
                    <div class="form-group">
                        <label for="password" class="form-label">Contraseña</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Mínimo 8 caracteres" 
                            required
                        >
                    </div>

                    <!-- Campo Confirmar Contraseña -->
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-input" 
                            placeholder="Repite tu contraseña" 
                            required
                        >
                    </div>

                    <!-- Botón Píldora CREAR CUENTA -->
                    <button type="submit" class="btn-auth-pill">
                        CREAR CUENTA
                    </button>

                    <!-- Fila Inferior con Enlace de Iniciar Sesión -->
                    <div class="auth-footer-row">
                        <span>¿Ya tienes cuenta? </span>
                        <a href="login.php" class="auth-link">Inicia sesión</a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

</body>
</html>
