<?php
/**
 * TallerTech - Perfil del Usuario
 * Vista Dark Mode para consultar información de cuenta, rol y accesos rápidos
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/conexion.php';
require_once 'includes/auth.php';

// Requiere sesión activa
verificar_autenticacion('login.php');

$id_usuario = (int)$_SESSION['id_usuario'];
$mensaje_exito = '';
$mensaje_error = '';

// Consultar datos actualizados del usuario en la base de datos
try {
    $stmt = $pdo->prepare("SELECT id_usuario, nombre, correo, telefono, rol FROM usuarios WHERE id_usuario = :id LIMIT 1");
    $stmt->execute([':id' => $id_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header('Location: logout.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error al consultar perfil: " . $e->getMessage());
    $usuario = [
        'nombre'   => $_SESSION['nombre'] ?? 'Usuario',
        'correo'   => $_SESSION['correo'] ?? '',
        'telefono' => '',
        'rol'      => $_SESSION['rol'] ?? 'cliente'
    ];
}

// Procesar actualización de datos de contacto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_contacto'])) {
    $nuevo_nombre   = trim($_POST['nombre'] ?? '');
    $nuevo_telefono = trim($_POST['telefono'] ?? '');

    if (empty($nuevo_nombre)) {
        $mensaje_error = 'El nombre no puede estar vacío.';
    } else {
        try {
            $stmtUpd = $pdo->prepare("UPDATE usuarios SET nombre = :nombre, telefono = :telefono WHERE id_usuario = :id");
            $stmtUpd->execute([
                ':nombre'   => $nuevo_nombre,
                ':telefono' => $nuevo_telefono,
                ':id'       => $id_usuario
            ]);

            $_SESSION['nombre'] = $nuevo_nombre;
            $usuario['nombre']   = $nuevo_nombre;
            $usuario['telefono'] = $nuevo_telefono;

            registrar_log($pdo, 'PERFIL_ACTUALIZADO', "Usuario actualizó sus datos de contacto.", $id_usuario);
            $mensaje_exito = 'Tus datos de contacto han sido actualizados exitosamente.';
        } catch (PDOException $e) {
            error_log("Error al actualizar perfil: " . $e->getMessage());
            $mensaje_error = 'No fue posible guardar los cambios. Intenta de nuevo.';
        }
    }
}

// Consultar conteo de actividad
$totalPedidos = 0;
$totalCotizaciones = 0;
$totalCitas = 0;

try {
    $stmtPedCount = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE id_usuario = :id");
    $stmtPedCount->execute([':id' => $id_usuario]);
    $totalPedidos = (int)$stmtPedCount->fetchColumn();

    $stmtCotCount = $pdo->prepare("SELECT COUNT(*) FROM cotizaciones WHERE id_usuario = :id");
    $stmtCotCount->execute([':id' => $id_usuario]);
    $totalCotizaciones = (int)$stmtCotCount->fetchColumn();

    $stmtCitaCount = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE id_usuario = :id");
    $stmtCitaCount->execute([':id' => $id_usuario]);
    $totalCitas = (int)$stmtCitaCount->fetchColumn();
} catch (Exception $e) {
    // Silencioso
}

$titulo_pagina = 'Mi Perfil | TallerTech';
$pagina_actual = 'perfil.php';

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Banners de Notificación -->
            <?php if (!empty($mensaje_exito)): ?>
                <div class="alert alert-success bg-success bg-opacity-10 border border-success border-opacity-25 text-success rounded-4 p-3 mb-4 d-flex align-items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <div><?= htmlspecialchars($mensaje_exito, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje_error)): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger rounded-4 p-3 mb-4 d-flex align-items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div><?= htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <!-- Tarjeta Principal de Perfil -->
            <div class="rounded-4 shadow-lg p-4 p-md-5 mb-4" style="background: #1c1917; border: 1px solid #38322b;">
                
                <div class="d-flex flex-column flex-sm-row align-items-center gap-4 mb-4 pb-4 border-bottom" style="border-color: #2e2823 !important;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #26221e; border: 2px solid #c88a4b; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #c88a4b; font-weight: bold;">
                        <?= mb_strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1, 'UTF-8')) ?>
                    </div>
                    <div class="text-center text-sm-start flex-grow-1">
                        <h1 class="h3 fw-bold mb-1" style="color: #f2ede4;"><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="mb-2" style="color: #b5ada4;"><?= htmlspecialchars($usuario['correo'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-start">
                            <span class="badge rounded-pill px-3 py-1" style="background: rgba(200, 138, 75, 0.12); color: #c88a4b; border: 1px solid rgba(200, 138, 75, 0.3);">
                                Rol: <?= htmlspecialchars(ucfirst($usuario['rol'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="badge rounded-pill px-3 py-1" style="background: rgba(255, 255, 255, 0.05); color: #80776e; border: 1px solid #38322b;">
                                ID Usuario: #<?= (int)$usuario['id_usuario'] ?>
                            </span>
                        </div>
                    </div>
                    <?php if (es_carpintero()): ?>
                        <div>
                            <a href="admin_pedidos.php" class="btn rounded-pill px-4 fw-bold shadow-sm" style="background-color: #c88a4b; color: #141210;">
                                Panel Carpintero ↗
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Resumen de Actividad -->
                <div class="row g-3 mb-4 text-center">
                    <div class="col-4">
                        <div class="p-3 rounded-4" style="background: #221f1c; border: 1px solid #38322b;">
                            <div class="h4 fw-bold mb-0" style="color: #f2ede4;"><?= $totalPedidos ?></div>
                            <small style="color: #b5ada4;">Pedidos</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-4" style="background: #221f1c; border: 1px solid #38322b;">
                            <div class="h4 fw-bold mb-0" style="color: #f2ede4;"><?= $totalCotizaciones ?></div>
                            <small style="color: #b5ada4;">Cotizaciones</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-4" style="background: #221f1c; border: 1px solid #38322b;">
                            <div class="h4 fw-bold mb-0" style="color: #f2ede4;"><?= $totalCitas ?></div>
                            <small style="color: #b5ada4;">Citas</small>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Edición de Contacto -->
                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">Información de Contacto</h2>
                <form method="POST" action="perfil.php">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label small fw-semibold" style="color: #b5ada4;">Nombre Completo</label>
                            <input type="text" id="nombre" name="nombre" class="form-control rounded-3 py-2" style="background: #141210; color: #f2ede4; border: 1px solid #3d352e;" value="<?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="correo" class="form-label small fw-semibold" style="color: #b5ada4;">Correo Electrónico (Inmutable)</label>
                            <input type="email" id="correo" class="form-control rounded-3 py-2" style="background: #141210; color: #80776e; border: 1px solid #2e2823;" value="<?= htmlspecialchars($usuario['correo'], ENT_QUOTES, 'UTF-8') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label small fw-semibold" style="color: #b5ada4;">Teléfono / WhatsApp</label>
                            <input type="tel" id="telefono" name="telefono" class="form-control rounded-3 py-2" style="background: #141210; color: #f2ede4; border: 1px solid #3d352e;" placeholder="+52 55 1234 5678" value="<?= htmlspecialchars($usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" name="actualizar_contacto" class="btn rounded-pill px-4 py-2 w-100 fw-bold" style="background-color: #c88a4b; color: #141210; border: none;">
                                Guardar Datos de Contacto
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Accesos Rápidos del Usuario -->
                <div class="pt-3 border-top d-flex flex-wrap gap-2 justify-content-between align-items-center" style="border-color: #2e2823 !important;">
                    <div class="d-flex gap-2">
                        <a href="pedidos.php" class="btn btn-sm rounded-pill px-3 py-2 text-decoration-none fw-semibold" style="border: 1px solid rgba(200, 138, 75, 0.4); color: #c88a4b;">
                            Ver mis Pedidos ↗
                        </a>
                        <a href="citas.php" class="btn btn-sm rounded-pill px-3 py-2 text-decoration-none fw-semibold" style="border: 1px solid #3d352e; color: #b5ada4;">
                            Mis Citas de Medición ↗
                        </a>
                    </div>
                    <a href="logout.php" class="btn btn-sm rounded-pill px-4 py-2 fw-semibold text-decoration-none" style="background: rgba(179, 74, 59, 0.15); border: 1px solid rgba(179, 74, 59, 0.35); color: #e89a8f;">
                        Cerrar Sesión
                    </a>
                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
