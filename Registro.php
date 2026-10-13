<?php
// Incluimos la conexión a la base de datos
require_once 'includes/conexion.php';

$mensaje = ''; // Variable para mostrar alertas

// Verificamos si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Por defecto, asignamos el rol básico de cliente
    $rol = 'cliente_prospecto';

    if (empty($nombre) || empty($correo) || empty($password)) {
        $mensaje = "<div class='alert alert-warning'>Todos los campos son obligatorios.</div>";
    } else {
        // Encriptamos la contraseña por seguridad
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Preparamos la consulta SQL
            $sql = "INSERT INTO usuarios (nombre, correo, password_hash, rol) VALUES (:nombre, :correo, :password_hash, :rol)";
            $stmt = $pdo->prepare($sql);
            
            // Ejecutamos pasando los valores reales
            $stmt->execute([
                ':nombre' => $nombre,
                ':correo' => $correo,
                ':password_hash' => $password_hash,
                ':rol' => $rol
            ]);

            $mensaje = "<div class='alert alert-success'>¡Registro exitoso! Ya puedes iniciar sesión.</div>";
            
        } catch (PDOException $e) {
            // Manejo de correos duplicados (código 23000 o 1062 en MySQL)
            if ($e->getCode() == 23000) {
                $mensaje = "<div class='alert alert-danger'>Ese correo electrónico ya está registrado.</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al registrar: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// Cargamos el header visual
require_once 'includes/header.php';
?>

<!-- Formulario de Registro HTML -->
<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Crear Cuenta</h3>
                
                <!-- Imprimimos las alertas (errores o éxitos) -->
                <?= $mensaje ?>

                <form method="POST" action="registro.php">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Registrarme</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">¿Ya tienes cuenta? Inicia sesión aquí</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Cargamos el footer visual
require_once 'includes/footer.php'; 
?>