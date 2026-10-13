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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($correo) || empty($password)) {
        $mensaje = "<div class='alert alert-warning'>Por favor, llena ambos campos.</div>";
    } else {
        try {
            // Buscamos al usuario por su correo
            $sql = "SELECT id_usuario, nombre, password_hash, rol FROM usuarios WHERE correo = :correo";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':correo' => $correo]);
            
            // fetch() nos trae una sola fila (si existe) como un array
            $usuario = $stmt->fetch();

            // Si $usuario tiene datos y la contraseña introducida coincide con el hash guardado...
            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                
                // ¡Éxito! Guardamos sus datos en variables de sesión
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nombre'] = $usuario['nombre'];
                $_SESSION['rol'] = $usuario['rol'];

                // Lo redirigimos a la página principal
                header('Location: index.php');
                exit;
            } else {
                $mensaje = "<div class='alert alert-danger'>Correo o contraseña incorrectos.</div>";
            }
        } catch (PDOException $e) {
            $mensaje = "<div class='alert alert-danger'>Error en el servidor: " . $e->getMessage() . "</div>";
        }
    }
}

require_once 'includes/header.php';
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
                        <input type="email" class="form-control" id="correo" name="correo" required>
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
                    <a href="registro.php" class="text-decoration-none">¿No tienes cuenta? Regístrate aquí</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>