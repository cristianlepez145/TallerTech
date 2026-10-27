<?php
/**
 * TallerTech - Inicio de Sesión (Login Visual Split Screen)
 * Pantalla completa en modo oscuro con mosaico fotográfico y autenticación PDO
 */

require_once 'includes/conexion.php';
require_once 'includes/auth.php';

// Si el usuario ya está autenticado, redirigir según su rol correspondiente
if (isset($_SESSION['id_usuario'])) {
    if (es_carpintero()) {
        header('Location: admin_pedidos.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$mensaje_error = '';
$correo = '';

// Procesamiento de formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($correo) || empty($password)) {
        $mensaje_error = 'Por favor, llena ambos campos para continuar.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = 'Ingresa un formato de correo electrónico válido.';
    } else {
        try {
            // Sentencia preparada para prevenir inyección SQL
            $sql = "SELECT id_usuario, nombre, correo, password_hash, rol FROM usuarios WHERE correo = :correo LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':correo' => $correo]);
            
            $usuario = $stmt->fetch();

            // Verificación segura de contraseña con hash
            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                // Prevenir ataques de fijación de sesión (Session Fixation)
                session_regenerate_id(true);

                // Guardar datos del usuario autenticado en sesión
                $_SESSION['id_usuario'] = (int)$usuario['id_usuario'];
                $_SESSION['nombre']     = $usuario['nombre'];
                $_SESSION['correo']     = $usuario['correo'];
                $_SESSION['rol']        = $usuario['rol'] ?? 'cliente';

                // Registrar evento en la bitácora de auditoría
                registrar_log(
                    $pdo, 
                    'LOGIN_EXITOSO', 
                    "Usuario '{$usuario['nombre']}' ({$usuario['correo']}) inició sesión con rol [{$_SESSION['rol']}].", 
                    (int)$usuario['id_usuario']
                );

                // Redirigir según el rol del usuario
                if (es_carpintero()) {
                    header('Location: admin_pedidos.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                // Registrar intento fallido en logs para auditoría de seguridad
                registrar_log($pdo, 'LOGIN_FALLIDO', "Intento fallido con correo: {$correo}");
                $mensaje_error = 'Correo electrónico o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            error_log('Error en login PDO: ' . $e->getMessage());
            $mensaje_error = 'Ocurrió un error en el servidor. Intenta de nuevo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | TallerTech</title>
    
    <!-- Estilos específicos de Login (Pantalla completa, Mosaico y Formulario) -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<div class="login-page-container">
    <div class="login-card">
        
        <!-- Columna Izquierda: Mosaico de Fotos de Carpintería y Espacios (Masonry Moderno) -->
        <div class="login-mosaic-col" aria-hidden="true">
            <div class="mosaic-grid">
                
                <!-- Foto 1: Cocina de madera elegante -->
                <div class="mosaic-item mosaic-item--tall-1">
                    <img 
                        src="https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80" 
                        alt="Cocina contemporánea en madera selecta"
                        loading="eager"
                    >
                    <div class="mosaic-overlay"></div>
                </div>

                <!-- Foto 2: Mueble contemporáneo y repisa -->
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

                <!-- Foto 4: Mueble de baño suspendido -->
                <div class="mosaic-item mosaic-item--tall-2">
                    <img 
                        src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=600&q=80" 
                        alt="Vanity de baño en madera estufada"
                        loading="lazy"
                    >
                    <div class="mosaic-overlay"></div>
                </div>

            </div>
        </div>

        <!-- Columna Derecha: Formulario Centrado -->
        <div class="login-form-col">
            <div class="login-form-inner">
                
                <!-- Logo del Árbol + Texto TallerTech -->
                <a href="index.php" class="login-brand" title="TallerTech">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L6 8h3.5l-4 6h4l-3.5 5h12l-3.5-5h4l-4-6H18L12 2z" fill="rgba(196, 136, 73, 0.22)" stroke="#c48849" />
                        <path d="M11 21v3h2v-3" stroke="#c48849" stroke-width="2.5" />
                    </svg>
                    <span class="login-brand-title">TallerTech</span>
                </a>

                <div class="login-welcome">
                    <h1 class="login-welcome-title">Bienvenido</h1>
                    <p class="login-welcome-subtitle">Ingresa a tu cuenta para gestionar tus cotizaciones y pedidos.</p>
                </div>

                <!-- Mensaje de error discreto y estilizado en rojo suave -->
                <?php if (!empty($mensaje_error)): ?>
                    <div class="login-error-alert" role="alert">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?= htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Autenticación -->
                <form method="POST" action="login.php" autocomplete="on">
                    
                    <!-- Campo Email -->
                    <div class="form-group">
                        <label for="correo" class="form-label">Email</label>
                        <input 
                            type="email" 
                            id="correo" 
                            name="correo" 
                            class="form-input" 
                            placeholder="nombre@ejemplo.com" 
                            value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>" 
                            required 
                            autofocus
                        >
                    </div>

                    <!-- Campo Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="••••••••" 
                            required
                        >
                    </div>

                    <!-- Botón Píldora LOG IN -->
                    <button type="submit" class="btn-login-pill">
                        LOG IN
                    </button>

                    <!-- Fila Inferior con Enlaces -->
                    <div class="login-links-row">
                        <a href="#" class="auth-link" title="Recuperar contraseña">Forgot Password</a>
                        <a href="Registro.php" class="auth-link" title="Crear una nueva cuenta">Sign up</a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

</body>
</html>
