<?php
/**
 * TallerTech - Módulo de Reseñas de la Comunidad
 * Interfaz Dark Mode elegante, cuadrícula de 3 columnas de testimonios,
 * consulta dinámica en PDO con fallback de muestra y regla estricta:
 * ÚNICAMENTE clientes con al menos un pedido completado pueden publicar reseñas.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/conexion.php';

$mensaje_exito = '';
$mensaje_error = '';

// 1. Verificación de pedidos completados para el usuario actual
$tiene_pedido_completado = false;
$pedidos_completados = [];

if (isset($_SESSION['id_usuario'])) {
    $id_usuario = (int)$_SESSION['id_usuario'];

    try {
        // A) Buscar en la tabla de pedidos donde estado = 'completado'
        $stmtP = $pdo->prepare("
            SELECT id_pedido, mueble_nombre, DATE_FORMAT(completado_en, '%d/%m/%Y') AS fecha_entregado 
            FROM pedidos 
            WHERE id_usuario = :id AND estado = 'completado'
            ORDER BY completado_en DESC, id_pedido DESC
        ");
        $stmtP->execute([':id' => $id_usuario]);
        $pedidos_completados = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        // B) También verificar en cotizaciones si pedido_completado = 1
        $stmtC = $pdo->prepare("
            SELECT c.id_cotizacion, m.nombre AS mueble_nombre, DATE_FORMAT(c.fecha_estimada_termino, '%d/%m/%Y') AS fecha_entregado
            FROM cotizaciones c
            LEFT JOIN muebles_catalogo m ON c.id_mueble = m.id_mueble
            WHERE c.id_usuario = :id AND c.pedido_completado = 1
            ORDER BY c.id_cotizacion DESC
        ");
        $stmtC->execute([':id' => $id_usuario]);
        $cotizaciones_completadas = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cotizaciones_completadas as $cc) {
            $pedidos_completados[] = [
                'id_pedido'       => 'COT-' . $cc['id_cotizacion'],
                'mueble_nombre'   => !empty($cc['mueble_nombre']) ? $cc['mueble_nombre'] : 'Proyecto de Carpintería a Medida',
                'fecha_entregado' => $cc['fecha_entregado'] ?? date('d/m/Y')
            ];
        }

        if (!empty($pedidos_completados)) {
            $tiene_pedido_completado = true;
        }
    } catch (Exception $e) {
        error_log("Error al verificar pedidos completados: " . $e->getMessage());
    }
}

// 2. Procesamiento de Nueva Reseña (Regla de negocio: Solo si completó un pedido)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publicar_resena'])) {
    if (!isset($_SESSION['id_usuario'])) {
        $mensaje_error = 'Debes iniciar sesión para compartir tu reseña.';
    } elseif (!$tiene_pedido_completado) {
        $mensaje_error = 'Acceso denegado: Solo los clientes que han recibido y completado al menos un pedido pueden publicar una reseña.';
    } else {
        $calificacion = (int)($_POST['calificacion'] ?? 5);
        if ($calificacion < 1) $calificacion = 1;
        if ($calificacion > 5) $calificacion = 5;

        $mueble_proyecto = trim($_POST['mueble_proyecto'] ?? '');
        $comentario      = trim($_POST['comentario'] ?? '');

        if (empty($comentario)) {
            $mensaje_error = 'Por favor, escribe tu opinión o testimonio sobre el producto recibido.';
        } elseif (mb_strlen($comentario) < 10) {
            $mensaje_error = 'El comentario debe contener al menos 10 caracteres.';
        } else {
            try {
                $id_usuario   = (int)$_SESSION['id_usuario'];
                $nombre_autor = $_SESSION['nombre'] ?? 'Cliente Verificado TallerTech';

                $stmtInsert = $pdo->prepare("
                    INSERT INTO resenas (id_usuario, nombre_autor, calificacion, mueble_proyecto, comentario, estado)
                    VALUES (:id_usuario, :nombre_autor, :calificacion, :mueble, :comentario, 'publicado')
                ");
                $stmtInsert->execute([
                    ':id_usuario'    => $id_usuario,
                    ':nombre_autor'  => $nombre_autor,
                    ':calificacion'  => $calificacion,
                    ':mueble'        => !empty($mueble_proyecto) ? $mueble_proyecto : null,
                    ':comentario'    => $comentario
                ]);

                $mensaje_exito = '¡Muchas gracias por tu reseña! Tu experiencia ha sido compartida con la comunidad como cliente verificado.';
            } catch (PDOException $e) {
                error_log("Error al publicar reseña: " . $e->getMessage());
                $mensaje_error = 'Ocurrió un error en el servidor al publicar tu reseña. Por favor, intenta de nuevo.';
            }
        }
    }
}

// 3. Consulta de Reseñas Publicadas con PDO
$resenas = [];
try {
    $stmtQuery = $pdo->query("
        SELECT id_resena, id_usuario, nombre_autor, calificacion, mueble_proyecto, comentario, creado_en
        FROM resenas
        WHERE estado = 'publicado'
        ORDER BY creado_en DESC
    ");
    $resenas = $stmtQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al consultar reseñas: " . $e->getMessage());
    $resenas = [];
}

// 4. Datos de Prueba / Mockups si la base de datos aún no tiene registros
$resenas_mock = [
    [
        'id_resena'       => 1,
        'nombre_autor'    => 'Juan Pérez',
        'calificacion'    => 5,
        'mueble_proyecto' => 'Comedor Contemporáneo Parota',
        'comentario'      => 'La calidad de la madera de parota y los ensambles superaron por completo mis expectativas. Los acabados al aceite mate resaltan la veta natural con una elegancia increíble en nuestra sala principal.',
        'creado_en'       => '2026-09-16 14:30:00'
    ],
    [
        'id_resena'       => 2,
        'nombre_autor'    => 'Sofía Morales',
        'calificacion'    => 5,
        'mueble_proyecto' => 'Cocina Integral Nogal & Granito',
        'comentario'      => 'El proceso de toma de medidas y diseño 3D fue impecable. Cumplieron exactamente con los tiempos de entrega pactados y la instalación fue limpia, precisa y profesional en cada cajón.',
        'creado_en'       => '2026-09-28 11:15:00'
    ],
    [
        'id_resena'       => 3,
        'nombre_autor'    => 'Carlos Mendoza',
        'calificacion'    => 5,
        'mueble_proyecto' => 'Cajoneras & Clóset a Medida',
        'comentario'      => 'Excelente atención directa de los maestros carpinteros en taller. Los herrajes de cierre suave y las uniones tipo cola de milano demuestran la maestría artesanal que manejan.',
        'creado_en'       => '2026-10-04 16:45:00'
    ]
];

$usando_mockups = false;
if (empty($resenas)) {
    $usando_mockups = true;
    $resenas = $resenas_mock;
}

// 5. Métricas y estadísticas de calificación
$total_resenas = count($resenas);
$suma_calif = 0;
foreach ($resenas as $r) {
    $suma_calif += (int)$r['calificacion'];
}
$promedio_calif = ($total_resenas > 0) ? number_format($suma_calif / $total_resenas, 1) : '5.0';

// Función para formatear fechas al estilo "16 Sep, 2026"
function formatearFechaEspanol($fechaStr) {
    $ts = strtotime($fechaStr);
    if (!$ts) return $fechaStr;
    $meses = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ];
    $dia = date('d', $ts);
    $mesNum = (int)date('n', $ts);
    $mes = $meses[$mesNum] ?? date('M', $ts);
    $ano = date('Y', $ts);
    return "{$dia} {$mes}, {$ano}";
}

// Configuración para el Header Global de TallerTech
$titulo_pagina = 'Reseñas de la Comunidad | TallerTech';
$pagina_actual = 'resenas.php';
$css_adicional = 'assets/css/resenas.css';

require_once 'includes/header.php';
?>

<div class="resenas-page-wrapper">
    <div class="container">

        <!-- Banners de Notificación -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="resenas-alert success" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div><?= htmlspecialchars($mensaje_exito, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="resenas-alert error" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div><?= htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <!-- Encabezado de la Sección y Botón de Acción -->
        <header class="resenas-header-section">
            <div class="resenas-header-info">
                <span class="resenas-pill-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                    Experiencia &amp; Clientes Verificados
                </span>
                <h1 class="resenas-main-title">Reseñas de la Comunidad</h1>
                <p class="resenas-main-subtitle">
                    Lo que dicen nuestros clientes sobre sus productos terminados. Conoce testimonios auténticos de pedidos completados en TallerTech.
                </p>
            </div>

            <!-- Botón Píldora: Escribir reseña (Solo si completó un pedido) -->
            <div>
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <?php if ($tiene_pedido_completado): ?>
                        <button type="button" class="btn-pill-cta" data-bs-toggle="modal" data-bs-target="#modalNuevaResena">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Escribir una reseña</span>
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-pill-cta locked" data-bs-toggle="modal" data-bs-target="#modalSinPedido" title="Se requiere un pedido completado para reseñar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <span>Escribir una reseña</span>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn-pill-cta guest" title="Inicia sesión para compartir tu reseña">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        <span>Inicia sesión para opinar</span>
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Barra de Métricas y Resumen de Satisfacción -->
        <div class="resenas-stats-bar">
            <div class="stat-item">
                <div class="stat-score-circle">
                    <?= $promedio_calif ?>
                </div>
                <div class="stat-text-box">
                    <h4 class="stat-title">
                        <span>Puntuación Promedio</span>
                        <span class="stat-stars-gold">★★★★★</span>
                    </h4>
                    <p class="stat-desc">Basado en <?= $total_resenas ?> testimonios <?= $usando_mockups ? '(muestra inicial)' : 'verificados' ?></p>
                </div>
            </div>

            <div class="stat-divider"></div>

            <div class="stat-item">
                <div class="stat-text-box">
                    <h4 class="stat-title text-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Testimonios 100% Verificados
                    </h4>
                    <p class="stat-desc">Exclusivo para clientes con pedidos entregados e instalados.</p>
                </div>
            </div>

            <div class="stat-divider"></div>

            <div class="stat-item">
                <div class="stat-text-box">
                    <h4 class="stat-title text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        Puntualidad en Entrega
                    </h4>
                    <p class="stat-desc">Fabricación y montaje bajo calendario comprometido.</p>
                </div>
            </div>
        </div>

        <!-- Grid de Reseñas (3 Columnas Responsivas) -->
        <main class="resenas-grid" aria-label="Testimonios de clientes">
            <?php foreach ($resenas as $r): ?>
                <?php
                    $autor        = htmlspecialchars($r['nombre_autor'] ?? 'Cliente Anónimo', ENT_QUOTES, 'UTF-8');
                    $inicial      = mb_strtoupper(mb_substr($autor, 0, 1, 'UTF-8'));
                    $calif        = max(1, min(5, (int)($r['calificacion'] ?? 5)));
                    $proyecto     = !empty($r['mueble_proyecto']) ? htmlspecialchars($r['mueble_proyecto'], ENT_QUOTES, 'UTF-8') : 'Mueble a Medida';
                    $comentario   = htmlspecialchars($r['comentario'] ?? '', ENT_QUOTES, 'UTF-8');
                    $fechaFormato = formatearFechaEspanol($r['creado_en'] ?? date('Y-m-d'));
                ?>
                <article class="resena-card">
                    
                    <!-- Fila Superior: Estrellas Doradas + Proyecto -->
                    <div class="resena-card-header">
                        <div class="resena-stars-group" title="<?= $calif ?> de 5 estrellas" aria-label="<?= $calif ?> estrellas">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="<?= ($i <= $calif) ? 'fill: #e5a534;' : 'fill: #333333;' ?>">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <span class="resena-project-badge" title="<?= $proyecto ?>">
                            <?= $proyecto ?>
                        </span>
                    </div>

                    <!-- Cuerpo de la Tarjeta: Cita / Testimonio -->
                    <div class="resena-quote-wrapper">
                        <p class="resena-quote-text"><?= $comentario ?></p>
                    </div>

                    <!-- Pie de la Tarjeta: Avatar con Inicial, Nombre y Fecha -->
                    <footer class="resena-card-footer">
                        <div class="resena-author-box">
                            <div class="resena-avatar-circle" aria-hidden="true">
                                <?= $inicial ?>
                            </div>
                            <div class="resena-author-details">
                                <span class="resena-author-name"><?= $autor ?></span>
                                <time class="resena-date-text" datetime="<?= htmlspecialchars($r['creado_en'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <?= $fechaFormato ?>
                                </time>
                            </div>
                        </div>

                        <span class="resena-verified-pill" title="Pedido completado y verificado en TallerTech">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Pedido Completado
                        </span>
                    </footer>

                </article>
            <?php endforeach; ?>
        </main>

    </div>
</div>

<!-- ==========================================================================
     MODAL 1: PARA DEJAR RESEÑA (SOLO SI TIENE PEDIDO COMPLETADO)
     ========================================================================== -->
<?php if (isset($_SESSION['id_usuario']) && $tiene_pedido_completado): ?>
    <div class="modal fade" id="modalNuevaResena" tabindex="-1" aria-labelledby="modalNuevaResenaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content dark-theme-modal">
                
                <div class="modal-header dark-theme-header">
                    <div>
                        <h5 class="modal-title dark-theme-title" id="modalNuevaResenaLabel">Compartir mi Experiencia</h5>
                        <small class="text-secondary">Publicarás como: <strong class="text-white"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></strong> (Cliente Verificado)</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" action="resenas.php" id="formNuevaResena">
                    <input type="hidden" name="publicar_resena" value="1">
                    <input type="hidden" name="calificacion" id="calificacionInput" value="5">

                    <div class="modal-body dark-theme-body">

                        <!-- Selector Interactivo de Estrellas -->
                        <div class="star-rating-selector">
                            <span class="small text-secondary fw-semibold">Tu Calificación General</span>
                            <div class="stars-interactive-row" id="starsContainer">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <button type="button" class="star-btn is-active" data-value="<?= $s ?>" aria-label="<?= $s ?> estrellas">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                        </svg>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <span class="star-rating-hint" id="starRatingLabel">5 estrellas - ¡Excelente! Superó mis expectativas</span>
                        </div>

                        <!-- Selector del Pedido / Mueble Recibido -->
                        <div class="form-group-modal">
                            <label for="mueble_proyecto" class="form-label-modal">
                                Pedido / Mueble a Calificar <small>(Tus pedidos completados)</small>
                            </label>
                            <select class="form-select-modal" id="mueble_proyecto" name="mueble_proyecto" required>
                                <option value="">Selecciona el mueble que recibiste...</option>
                                <?php foreach ($pedidos_completados as $pc): ?>
                                    <option value="<?= htmlspecialchars($pc['mueble_nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($pc['mueble_nombre'], ENT_QUOTES, 'UTF-8') ?> (Entregado <?= htmlspecialchars($pc['fecha_entregado'], ENT_QUOTES, 'UTF-8') ?>)
                                    </option>
                                <?php endforeach; ?>
                                <option value="Proyecto Especial a Medida">Otro proyecto especial a medida</option>
                            </select>
                        </div>

                        <!-- Testimonio / Comentario -->
                        <div class="form-group-modal">
                            <label for="comentarioResena" class="form-label-modal">
                                Tu Opinión y Testimonio <small>(Mín. 10 caracteres)</small>
                            </label>
                            <textarea 
                                class="form-textarea-modal" 
                                id="comentarioResena" 
                                name="comentario" 
                                rows="4" 
                                placeholder="Cuéntanos sobre la calidad de las maderas, los ensambles, la atención y el resultado en tu espacio..." 
                                required
                            ></textarea>
                        </div>

                    </div>

                    <div class="modal-footer dark-theme-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 text-white" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-pill-cta py-2 px-4">
                            <span>Publicar Reseña</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================================================
     MODAL 2: INFORMATIVO (SI ESTÁ CONECTADO PERO NO TIENE PEDIDOS COMPLETADOS)
     ========================================================================== -->
<?php if (isset($_SESSION['id_usuario']) && !$tiene_pedido_completado): ?>
    <div class="modal fade" id="modalSinPedido" tabindex="-1" aria-labelledby="modalSinPedidoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content dark-theme-modal">
                <div class="modal-header dark-theme-header">
                    <h5 class="modal-title dark-theme-title" id="modalSinPedidoLabel">Reseñas de Clientes Verificados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body dark-theme-body text-center py-4">
                    <div class="modal-lock-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="#c48849" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <h4 class="text-white fw-bold mt-2 mb-2">Requiere un Pedido Completado</h4>
                    <p class="text-secondary small mb-4" style="line-height: 1.6; max-width: 420px; margin-left: auto; margin-right: auto;">
                        Para garantizar una comunidad 100% transparente y fidedigna, en TallerTech únicamente los clientes con al menos <strong>un pedido fabricado y entregado</strong> pueden calificar la experiencia.
                    </p>
                    <div class="d-flex flex-column gap-2 max-w-sm mx-auto" style="max-width: 320px;">
                        <a href="catalogo.php" class="btn btn-light rounded-pill fw-semibold py-2">Explorar Catálogo de Muebles</a>
                        <a href="cotizaciones.php" class="btn btn-outline-secondary rounded-pill text-white py-2">Cotizar un Proyecto a Medida</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Script interactivo de estrellas y modal -->
<script src="assets/js/resenas.js"></script>

<?php 
require_once 'includes/footer.php'; 
?>
