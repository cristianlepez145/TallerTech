<?php
// 1. Iniciamos la sesión para poder guardar los datos del usuario logueado
session_start();

// Si el usuario ya está logueado, lo mandamos directo al inicio para que no vea el login
if (isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}

require_once 'includes/conexion.php';
$mensaje = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($correo) || empty($password)) {
        $mensaje = "<div class='alert alert-warning'>Por favor, llena ambos campos.</div>";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "<div class='alert alert-warning'>Por favor, ingresa un correo electrónico válido.</div>";
    } else {
        try {
            // Buscamos al usuario por su correo con sentencia preparada
            $sql = "SELECT id_usuario, nombre, correo, password_hash, rol FROM usuarios WHERE correo = :correo LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':correo' => $correo]);
            
            // fetch() nos trae una sola fila como array asociativo
            $usuario = $stmt->fetch();

            // Si el usuario existe y la contraseña introducida coincide con el hash guardado...
            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                
                // Regenerar el ID de sesión para prevenir fijación de sesión (Session Fixation)
                session_regenerate_id(true);

                // ¡Éxito! Guardamos sus datos en variables de sesión
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['correo'] = $usuario['correo'];
                $_SESSION['rol'] = $usuario['rol'];

                // Lo redirigimos a la página principal
                header('Location: index.php');
                exit;
            } else {
                // Mensaje genérico para no exponer si el correo existe o no
                $mensaje = "<div class='alert alert-danger'>Correo o contraseña incorrectos.</div>";
            }
        } catch (PDOException $e) {
            error_log("Error en inicio de sesión: " . $e->getMessage());
            $mensaje = "<div class='alert alert-danger'>Ocurrió un error en el servidor. Por favor, intenta de nuevo más tarde.</div>";
        }
    }
}

if (file_exists(__DIR__ . '/includes/header.php')) {
    require_once __DIR__ . '/includes/header.php';
}
?>

<!-- Formulario de Login HTML -->
<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Iniciar Sesión</h3>
                
                <?= $mensaje ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?= htmlspecialchars($correo, ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Entrar</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="Registro.php" class="text-decoration-none">¿No tienes cuenta? Regístrate aquí</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
if (file_exists(__DIR__ . '/includes/footer.php')) {
    require_once __DIR__ . '/includes/footer.php';
}
?>