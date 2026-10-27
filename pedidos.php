<?php
/**
 * TallerTech - Módulo de Seguimiento de Pedidos y Proyectos
 * Interfaz Dark Mode, Stepper interactivo de 3 etapas, filtros por estado,
 * desglose de anticipo/saldo y gestión de cotizaciones en fabricación.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/conexion.php';
require_once 'includes/auth.php';

$mensaje_exito = '';
$mensaje_error = '';

// 1. Procesamiento de Aprobación de Cotización y Registro de Anticipo (60%)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['aprobar_anticipo'])) {
    if (!isset($_SESSION['id_usuario'])) {
        $mensaje_error = 'Debes iniciar sesión para autorizar proyectos.';
    } else {
        $id_cotizacion = (int)($_POST['id_cotizacion'] ?? 0);
        $id_usuario    = (int)$_SESSION['id_usuario'];

        if ($id_cotizacion <= 0) {
            $mensaje_error = 'Identificador de cotización no válido.';
        } else {
            try {
                // Verificar que la cotización pertenezca al usuario
                $stmtCheck = $pdo->prepare("SELECT id_cotizacion, id_mueble, costo_total, notas FROM cotizaciones WHERE id_cotizacion = :id AND id_usuario = :uid LIMIT 1");
                $stmtCheck->execute([':id' => $id_cotizacion, ':uid' => $id_usuario]);
                $cot = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (!$cot) {
                    $mensaje_error = 'No se encontró la cotización solicitada.';
                } else {
                    $costoTotal = (float)$cot['costo_total'];
                    $anticipo60 = round($costoTotal * 0.60, 2);

                    // A) Actualizar cotización a 'aprobada' con el anticipo registrado
                    $stmtUpd = $pdo->prepare("
                        UPDATE cotizaciones 
                        SET estado = 'aprobada', anticipo_pagado = :anticipo, fecha_estimada_termino = DATE_ADD(CURRENT_DATE, INTERVAL 21 DAY)
                        WHERE id_cotizacion = :id
                    ");
                    $stmtUpd->execute([
                        ':anticipo' => $anticipo60,
                        ':id'       => $id_cotizacion
                    ]);

                    // B) Registrar orden formal en la tabla pedidos
                    $stmtMueble = $pdo->prepare("SELECT nombre FROM muebles_catalogo WHERE id_mueble = :mid LIMIT 1");
                    $stmtMueble->execute([':mid' => $cot['id_mueble']]);
                    $muebleRow = $stmtMueble->fetch(PDO::FETCH_ASSOC);
                    $muebleNombre = $muebleRow['nombre'] ?? 'Proyecto de Carpintería a Medida';

                    $stmtInsertPedido = $pdo->prepare("
                        INSERT INTO pedidos (id_usuario, id_cotizacion, mueble_nombre, total, anticipo, estado, fecha_estimada)
                        VALUES (:id_usuario, :id_cotizacion, :mueble_nombre, :total, :anticipo, 'en_produccion', DATE_ADD(CURRENT_DATE, INTERVAL 21 DAY))
                    ");
                    $stmtInsertPedido->execute([
                        ':id_usuario'    => $id_usuario,
                        ':id_cotizacion' => $id_cotizacion,
                        ':mueble_nombre' => $muebleNombre,
                        ':total'         => $costoTotal,
                        ':anticipo'      => $anticipo60
                    ]);

                    $idNuevoPedido = $pdo->lastInsertId();

                    // Registrar en la bitácora de auditoría
                    registrar_log(
                        $pdo,
                        'ANTICIPO_REGISTRADO',
                        "Cliente aprobó cotización #COT-{$id_cotizacion} y generó pedido #PED-{$idNuevoPedido} con anticipo de $" . number_format($anticipo60, 2) . " MXN.",
                        $id_usuario
                    );
                    $mensaje_exito = "¡Anticipo del 60% registrado con éxito ($" . number_format($anticipo60, 2) . " MXN)! Tu proyecto ha generado la orden de trabajo <strong>#PED-{$idNuevoPedido}</strong> y ha pasado a la etapa <strong>En Fabricación / Taller</strong>.";
                }
            } catch (PDOException $e) {
                error_log("Error al aprobar anticipo: " . $e->getMessage());
                $mensaje_error = 'Ocurrió un error al procesar el anticipo en el servidor.';
            }
        }
    }
}

// 2. Función auxiliar para asignar imágenes de alta calidad según el mueble
function obtenerFotoMueble($nombreMueble) {
    $nombreLower = mb_strtolower($nombreMueble, 'UTF-8');
    if (strpos($nombreLower, 'cocina') !== false) {
        return 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80';
    } elseif (strpos($nombreLower, 'baño') !== false || strpos($nombreLower, 'vanity') !== false) {
        return 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=600&q=80';
    } elseif (strpos($nombreLower, 'puerta') !== false) {
        return 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=600&q=80';
    } elseif (strpos($nombreLower, 'mesa') !== false || strpos($nombreLower, 'comedor') !== false) {
        return 'https://images.unsplash.com/photo-1577140917170-285929fb55b7?auto=format&fit=crop&w=600&q=80';
    } elseif (strpos($nombreLower, 'cama') !== false || strpos($nombreLower, 'recámara') !== false) {
        return 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=600&q=80';
    } elseif (strpos($nombreLower, 'escritorio') !== false) {
        return 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=600&q=80';
    } elseif (strpos($nombreLower, 'cajonera') !== false || strpos($nombreLower, 'closet') !== false || strpos($nombreLower, 'clóset') !== false) {
        return 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=600&q=80';
    }
    return 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=600&q=80';
}

// 3. Consulta de pedidos y cotizaciones del usuario en sesión
$pedidos_usuario = [];
$estaAutenticado = isset($_SESSION['id_usuario']);

if ($estaAutenticado) {
    $id_usuario = (int)$_SESSION['id_usuario'];

    // A) Pedidos formales en producción o entregados
    try {
        $stmtP = $pdo->prepare("
            SELECT p.*, DATE_FORMAT(p.creado_en, '%d %b, %Y') AS fecha_formato
            FROM pedidos p
            WHERE p.id_usuario = :id
            ORDER BY p.creado_en DESC, p.id_pedido DESC
        ");
        $stmtP->execute([':id' => $id_usuario]);
        $rowsP = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rowsP as $r) {
            $estadoUi = 'fabricacion';
            $step = 2;
            $estadoTexto = 'En proceso de trabajo';

            if ($r['estado'] === 'completado') {
                $estadoUi = 'entregado';
                $step = 3;
                $estadoTexto = 'Entregado';
            } elseif ($r['estado'] === 'pendiente_anticipo') {
                $estadoUi = 'cotizacion';
                $step = 1;
                $estadoTexto = 'En proceso de cotización';
            }

            $total = (float)$r['total'];
            $anticipo = (float)$r['anticipo'];
            $saldo = max(0, $total - $anticipo);

            $pedidos_usuario[] = [
                'id_mostrado'      => '#PED-' . str_pad($r['id_pedido'], 4, '0', STR_PAD_LEFT),
                'id_real'          => $r['id_pedido'],
                'tipo'             => 'pedido',
                'nombre'           => $r['mueble_nombre'],
                'dimensiones'      => 'Dimensiones y especificaciones técnicas validadas',
                'costo_total'      => $total,
                'anticipo'         => $anticipo,
                'saldo'            => $saldo,
                'estado_ui'        => $estadoUi,
                'estado_texto'     => $estadoTexto,
                'step_activo'      => $step,
                'fecha'            => $r['fecha_formato'] ?? date('d M, Y'),
                'notas'            => $r['direccion_entrega'] ? 'Dirección de entrega: ' . $r['direccion_entrega'] : 'Fabricación artesanal con ensamble de precisión.',
                'foto'             => obtenerFotoMueble($r['mueble_nombre'])
            ];
        }
    } catch (Exception $e) {
        error_log("Error al consultar pedidos: " . $e->getMessage());
    }

    // B) Cotizaciones activas (que aún no hayan generado un pedido duplicado)
    try {
        $stmtC = $pdo->prepare("
            SELECT c.*, m.nombre AS mueble_nombre
            FROM cotizaciones c
            LEFT JOIN muebles_catalogo m ON c.id_mueble = m.id_mueble
            WHERE c.id_usuario = :id
            ORDER BY c.id_cotizacion DESC
        ");
        $stmtC->execute([':id' => $id_usuario]);
        $rowsC = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rowsC as $c) {
            // Si ya existe un pedido asociado a esta cotización, la omitimos para no duplicar la tarjeta
            $yaEnPedidos = false;
            foreach ($pedidos_usuario as $pu) {
                if ($pu['tipo'] === 'pedido' && isset($r['id_cotizacion']) && $r['id_cotizacion'] == $c['id_cotizacion']) {
                    $yaEnPedidos = true;
                    break;
                }
            }
            if ($yaEnPedidos) continue;

            $estadoUi = 'cotizacion';
            $step = 1;
            $estadoTexto = 'En proceso de cotización';

            if ($c['pedido_completado'] == 1) {
                $estadoUi = 'entregado';
                $step = 3;
                $estadoTexto = 'Entregado';
            } elseif ($c['estado'] === 'aprobada' || $c['estado'] === 'en_progreso') {
                $estadoUi = 'fabricacion';
                $step = 2;
                $estadoTexto = 'En proceso de trabajo';
            }

            $costoTotal = (float)$c['costo_total'];
            $anticipoPagado = (float)$c['anticipo_pagado'];
            if ($anticipoPagado == 0 && ($estadoUi === 'fabricacion' || $estadoUi === 'entregado')) {
                $anticipoPagado = $costoTotal * 0.60;
            }
            $saldo = max(0, $costoTotal - $anticipoPagado);

            // Parsear dimensiones de las notas
            $dimensiones = 'Fabricación a medida TallerTech';
            if (preg_match('/Medidas:\s*([^|]+)/i', $c['notas'] ?? '', $matches)) {
                $dimensiones = trim($matches[1]);
            }

            $muebleNombre = !empty($c['mueble_nombre']) ? $c['mueble_nombre'] : 'Proyecto a Medida';

            $pedidos_usuario[] = [
                'id_mostrado'      => '#COT-' . str_pad($c['id_cotizacion'], 4, '0', STR_PAD_LEFT),
                'id_real'          => $c['id_cotizacion'],
                'tipo'             => 'cotizacion',
                'nombre'           => $muebleNombre,
                'dimensiones'      => $dimensiones,
                'costo_total'      => $costoTotal,
                'anticipo'         => $anticipoPagado,
                'saldo'            => $saldo,
                'estado_ui'        => $estadoUi,
                'estado_texto'     => $estadoTexto,
                'step_activo'      => $step,
                'fecha'            => 'Reciente',
                'notas'            => $c['notas'] ?? 'Sin notas registradas',
                'foto'             => obtenerFotoMueble($muebleNombre)
            ];
        }
    } catch (Exception $e) {
        error_log("Error al consultar cotizaciones: " . $e->getMessage());
    }
}

// 4. Tarjetas de Ejemplo Representativas para validar los 3 estados (Demostración visual)
$pedidos_demo = [
    [
        'id_mostrado'      => '#COT-1042',
        'id_real'          => 1042,
        'tipo'             => 'cotizacion',
        'nombre'           => 'Cocina Integral Roble & Granito Negro',
        'dimensiones'      => '2.80m ancho x 2.20m alto x 0.65m prof.',
        'costo_total'      => 38500.00,
        'anticipo'         => 0.00,
        'saldo'            => 38500.00,
        'estado_ui'        => 'cotizacion',
        'estado_texto'     => 'En proceso de cotización',
        'step_activo'      => 1,
        'fecha'            => '12 Oct, 2026',
        'notas'            => 'Madera de roble estufado con herrajes de cierre suave y cubierta de cuarzo negro.',
        'foto'             => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id_mostrado'      => '#PED-1038',
        'id_real'          => 1038,
        'tipo'             => 'pedido',
        'nombre'           => 'Escritorio Ejecutivo en L Parota Maciza',
        'dimensiones'      => '1.80m ancho x 0.75m alto x 0.70m prof.',
        'costo_total'      => 14200.00,
        'anticipo'         => 8520.00,
        'saldo'            => 5680.00,
        'estado_ui'        => 'fabricacion',
        'estado_texto'     => 'En proceso de trabajo',
        'step_activo'      => 2,
        'fecha'            => '28 Sep, 2026',
        'notas'            => 'Acabado en poliuretano mate con pasacables metálico y cajonera integrada.',
        'foto'             => 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=600&q=80'
    ],
    [
        'id_mostrado'      => '#PED-1025',
        'id_real'          => 1025,
        'tipo'             => 'pedido',
        'nombre'           => 'Comedor Contemporáneo 8 Sillas en Nogal',
        'dimensiones'      => '2.40m largo x 1.10m ancho x 0.78m alto',
        'costo_total'      => 28900.00,
        'anticipo'         => 17340.00,
        'saldo'            => 11560.00,
        'estado_ui'        => 'entregado',
        'estado_texto'     => 'Entregado',
        'step_activo'      => 3,
        'fecha'            => '14 Ago, 2026',
        'notas'            => 'Entrega e instalación completa en domicilio con tapicería hidrofóbica en sillas.',
        'foto'             => 'https://images.unsplash.com/photo-1577140917170-285929fb55b7?auto=format&fit=crop&w=600&q=80'
    ]
];

// Si el usuario no tiene registros reales, usaremos la demo visual para garantizar diseño
$mostrarDemo = empty($pedidos_usuario);
$pedidos_a_mostrar = $mostrarDemo ? $pedidos_demo : $pedidos_usuario;

// Contadores de filtros
$countTotal       = count($pedidos_a_mostrar);
$countCotizacion  = count(array_filter($pedidos_a_mostrar, fn($p) => $p['estado_ui'] === 'cotizacion'));
$countFabricacion = count(array_filter($pedidos_a_mostrar, fn($p) => $p['estado_ui'] === 'fabricacion'));
$countEntregados  = count(array_filter($pedidos_a_mostrar, fn($p) => $p['estado_ui'] === 'entregado'));

// Configuración para Header General
$titulo_pagina = 'Mis Pedidos & Proyectos | TallerTech';
$pagina_actual = 'pedidos.php';
$css_adicional = 'assets/css/pedidos.css';

require_once 'includes/header.php';
?>

<div class="pedidos-page-wrapper">
    <div class="container">

        <!-- Banners de Notificación -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="alert alert-success bg-success bg-opacity-10 border border-success border-opacity-25 text-success rounded-4 p-3 mb-4 d-flex align-items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div><?= $mensaje_exito ?></div>
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

        <?php if (!$estaAutenticado): ?>
            <!-- Caso: Usuario NO Autenticado -> Tarjeta Centrada Elegante -->
            <div class="auth-required-box my-5">
                <div class="empty-state-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <h2 class="empty-state-title">Seguimiento de Pedidos y Proyectos</h2>
                <p class="empty-state-desc">
                    Inicia sesión con tu cuenta de TallerTech para consultar el estado en tiempo real de tus cotizaciones, órdenes de fabricación y fechas de entrega de tus muebles.
                </p>
                <div class="d-flex flex-column align-items-center gap-3">
                    <a href="login.php" class="btn-order-action primary-pill px-5 py-3">
                        <span>Iniciar Sesión en mi Cuenta</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <span class="small text-secondary">
                        ¿Aún no tienes cuenta? <a href="Registro.php" class="text-white text-decoration-underline fw-bold">Regístrate aquí</a>
                    </span>
                </div>
            </div>

            <!-- Previsualización de Demostración del Flujo para Visitantes -->
            <div class="text-center my-5 pt-4 border-top border-secondary border-opacity-25">
                <span class="badge rounded-pill bg-dark border border-secondary text-secondary px-3 py-2">
                    Demostración visual del seguimiento en vivo
                </span>
                <h3 class="text-white fw-bold mt-2">Así monitoreas tus proyectos en TallerTech</h3>
            </div>
        <?php endif; ?>

        <?php if (es_carpintero()): ?>
            <div class="d-flex align-items-center justify-content-between p-3 mb-4 rounded-4 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                <div class="d-flex align-items-center gap-2 text-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span class="small fw-semibold">Has iniciado sesión con permisos de <strong>Carpintero / Administrador</strong>. Estás viendo la perspectiva del cliente.</span>
                </div>
                <a href="admin_pedidos.php" class="btn btn-sm btn-warning rounded-pill px-3 py-1 fw-bold text-dark text-decoration-none">
                    Ir al Panel de Taller →
                </a>
            </div>
        <?php endif; ?>

        <!-- Encabezado Principal de Pedidos -->
        <header class="pedidos-header-section">
            <span class="pedidos-pill-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
                    <path d="M3 6h18"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
                Panel de Fabricación
            </span>
            <h1 class="pedidos-main-title">Mis Pedidos y Proyectos</h1>
            <p class="pedidos-main-subtitle">
                Consulta el estado en tiempo real de tus cotizaciones y muebles en fabricación. Monitorea avances, anticipos y fechas estimadas de entrega.
            </p>
        </header>

        <!-- Filtros Rápidos por Estado (Botones Píldora) -->
        <nav class="filters-pill-bar" aria-label="Filtros de pedidos">
            <button type="button" class="btn-filter-pill active" data-filter="all">
                <span>Todos</span>
                <span class="filter-count-badge"><?= $countTotal ?></span>
            </button>
            <button type="button" class="btn-filter-pill" data-filter="cotizacion">
                <span>En Cotización</span>
                <span class="filter-count-badge"><?= $countCotizacion ?></span>
            </button>
            <button type="button" class="btn-filter-pill" data-filter="fabricacion">
                <span>En Fabricación</span>
                <span class="filter-count-badge"><?= $countFabricacion ?></span>
            </button>
            <button type="button" class="btn-filter-pill" data-filter="entregado">
                <span>Entregados</span>
                <span class="filter-count-badge"><?= $countEntregados ?></span>
            </button>
        </nav>

        <?php if ($estaAutenticado && empty($pedidos_usuario)): ?>
            <!-- Estado Vacío (Empty State) para el usuario autenticado sin proyectos -->
            <div class="pedidos-empty-state mb-5">
                <div class="empty-state-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                </div>
                <h3 class="empty-state-title">Aún no tienes pedidos o cotizaciones activas</h3>
                <p class="empty-state-desc">
                    Comienza personalizando tus muebles a medida en nuestro cotizador o agenda una visita para toma de medidas en tu espacio.
                </p>
                <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                    <a href="cotizaciones.php" class="btn-order-action primary-pill">
                        <span>Solicitar una cotización →</span>
                    </a>
                    <a href="catalogo.php" class="btn-order-action secondary">
                        <span>Ver Catálogo de Muebles</span>
                    </a>
                </div>
            </div>

            <!-- Aviso de Demostración Visual -->
            <div class="d-flex align-items-center justify-content-between p-3 rounded-4 border border-secondary border-opacity-25 bg-dark mb-4">
                <div class="small text-secondary">
                    <strong class="text-white">Modo Demostración Activo:</strong> Mostrando ejemplos visuales de las 3 etapas del flujo para referencia técnica.
                </div>
            </div>
        <?php endif; ?>

        <!-- Lista de Tarjetas de Pedido -->
        <div class="pedidos-list-container" id="ordersContainer">
            <?php foreach ($pedidos_a_mostrar as $p): ?>
                <?php
                    $idMostrado   = htmlspecialchars($p['id_mostrado'], ENT_QUOTES, 'UTF-8');
                    $nombreMueble = htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8');
                    $dims         = htmlspecialchars($p['dimensiones'], ENT_QUOTES, 'UTF-8');
                    $costoTotal   = number_format($p['costo_total'], 2);
                    $anticipo     = number_format($p['anticipo'], 2);
                    $saldo        = number_format($p['saldo'], 2);
                    $estadoUi     = htmlspecialchars($p['estado_ui'], ENT_QUOTES, 'UTF-8');
                    $estadoTexto  = htmlspecialchars($p['estado_texto'], ENT_QUOTES, 'UTF-8');
                    $stepActivo   = (int)$p['step_activo'];
                    $foto         = htmlspecialchars($p['foto'], ENT_QUOTES, 'UTF-8');
                    $fecha        = htmlspecialchars($p['fecha'], ENT_QUOTES, 'UTF-8');
                    $notas        = htmlspecialchars($p['notas'], ENT_QUOTES, 'UTF-8');
                ?>
                <article class="order-card" data-status="<?= $estadoUi ?>">
                    
                    <!-- 1. Encabezado de la Tarjeta -->
                    <div class="order-card-header">
                        <div class="order-meta-left">
                            <span class="order-code-badge"><?= $idMostrado ?></span>
                            <span class="order-date-text">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <?= $fecha ?>
                            </span>
                        </div>
                        <span class="status-pill-badge <?= $estadoUi ?>">
                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:currentColor;"></span>
                            <?= $estadoTexto ?>
                        </span>
                    </div>

                    <!-- 2. Cuerpo de la Tarjeta: Foto + Detalles + Desglose Financiero -->
                    <div class="order-card-body">
                        
                        <!-- Miniatura del Proyecto -->
                        <div class="order-thumb-wrapper">
                            <img src="<?= $foto ?>" alt="<?= $nombreMueble ?>" class="order-thumb-img" loading="lazy">
                        </div>

                        <!-- Detalles Centrales -->
                        <div class="order-details-center">
                            <h3 class="order-project-title"><?= $nombreMueble ?></h3>
                            <div class="order-specs-text">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                                    <path d="M3 9h18"></path>
                                    <path d="M9 21V9"></path>
                                </svg>
                                <span><?= $dims ?></span>
                            </div>

                            <!-- Desglose de Anticipo (60%) y Saldo (40%) -->
                            <div class="order-finance-grid">
                                <div class="finance-col">
                                    <span class="finance-label">Costo Total</span>
                                    <span class="finance-val">$<?= $costoTotal ?> MXN</span>
                                </div>
                                <div class="finance-col">
                                    <span class="finance-label">Anticipo (60%)</span>
                                    <span class="finance-val accent">$<?= $anticipo ?> MXN</span>
                                </div>
                                <div class="finance-col">
                                    <span class="finance-label">Saldo Contra Entrega (40%)</span>
                                    <span class="finance-val">$<?= $saldo ?> MXN</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- 3. Línea de Tiempo / Stepper Visual (3 Etapas) -->
                    <div class="order-stepper-box">
                        <div class="stepper-timeline">
                            
                            <!-- Etapa 1: Cotización y Medidas -->
                            <div class="stepper-step <?= ($stepActivo >= 1) ? 'active status-' . $estadoUi : '' ?> <?= ($stepActivo > 1) ? 'completed' : '' ?>">
                                <div class="stepper-node">
                                    <?php if ($stepActivo > 1): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    <?php else: ?>
                                        1
                                    <?php endif; ?>
                                </div>
                                <div class="stepper-label-box">
                                    <span class="stepper-step-num">Etapa 1</span>
                                    <span class="stepper-step-name">Cotización y Medidas</span>
                                </div>
                            </div>

                            <!-- Conector 1 -> 2 -->
                            <div class="stepper-line <?= ($stepActivo > 1) ? 'completed' : '' ?> <?= ($stepActivo === 2) ? 'active' : '' ?>"></div>

                            <!-- Etapa 2: En Fabricación / Taller -->
                            <div class="stepper-step <?= ($stepActivo >= 2) ? 'active status-' . $estadoUi : '' ?> <?= ($stepActivo > 2) ? 'completed' : '' ?>">
                                <div class="stepper-node">
                                    <?php if ($stepActivo > 2): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    <?php else: ?>
                                        2
                                    <?php endif; ?>
                                </div>
                                <div class="stepper-label-box">
                                    <span class="stepper-step-num">Etapa 2</span>
                                    <span class="stepper-step-name">En Fabricación / Taller</span>
                                </div>
                            </div>

                            <!-- Conector 2 -> 3 -->
                            <div class="stepper-line <?= ($stepActivo === 3) ? 'completed' : '' ?>"></div>

                            <!-- Etapa 3: Entregado / Instalado -->
                            <div class="stepper-step <?= ($stepActivo === 3) ? 'completed active status-entregado' : '' ?>">
                                <div class="stepper-node">
                                    <?php if ($stepActivo === 3): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    <?php else: ?>
                                        3
                                    <?php endif; ?>
                                </div>
                                <div class="stepper-label-box">
                                    <span class="stepper-step-num">Etapa 3</span>
                                    <span class="stepper-step-name">Entregado / Instalado</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- 4. Acciones de la Tarjeta -->
                    <div class="order-card-actions">
                        <button 
                            type="button" 
                            class="btn-order-action secondary btn-open-details"
                            data-bs-toggle="modal" 
                            data-bs-target="#modalDetallesPedido"
                            data-code="<?= $idMostrado ?>"
                            data-title="<?= $nombreMueble ?>"
                            data-specs="<?= $dims ?>"
                            data-total="$<?= $costoTotal ?> MXN"
                            data-anticipo="$<?= $anticipo ?> MXN"
                            data-saldo="$<?= $saldo ?> MXN"
                            data-notes="<?= $notas ?>"
                            data-image="<?= $foto ?>"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="16" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12.01" y2="8"></line>
                            </svg>
                            <span>Ver Detalles / Croquis</span>
                        </button>

                        <?php if ($estadoUi === 'cotizacion'): ?>
                            <!-- Botón Píldora Blanco: Aprobar y Registrar Anticipo (60%) -->
                            <button 
                                type="button" 
                                class="btn-order-action primary-pill btn-trigger-anticipo"
                                data-bs-toggle="modal" 
                                data-bs-target="#modalAprobarAnticipo"
                                data-id="<?= $p['id_real'] ?>"
                                data-anticipo-val="$<?= number_format($p['costo_total'] * 0.60, 2) ?> MXN"
                                data-title="<?= $nombreMueble ?>"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                                <span>Aprobar y Registrar Anticipo (60%)</span>
                            </button>
                        <?php elseif ($estadoUi === 'entregado'): ?>
                            <!-- Enlace a Dejar Reseña -->
                            <a href="resenas.php" class="btn-order-action primary-pill">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                <span>Calificar Proyecto y Dejar Reseña</span>
                            </a>
                        <?php endif; ?>
                    </div>

                </article>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- ==========================================================================
     MODAL 1: VER DETALLES Y CROQUIS DEL PROYECTO
     ========================================================================== -->
<div class="modal fade" id="modalDetallesPedido" tabindex="-1" aria-labelledby="modalDetallesPedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content dark-theme-modal">
            
            <div class="modal-header dark-theme-header">
                <div>
                    <span class="badge rounded-pill bg-dark border border-secondary text-secondary small px-3 py-1 mb-1" id="detailsModalCode">#PED-0000</span>
                    <h5 class="modal-title text-white fw-bold" id="detailsModalTitle">Detalles del Proyecto</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body dark-theme-body">
                <div class="row g-4">
                    <div class="col-md-5">
                        <img src="" id="detailsModalImg" alt="Vista previa" class="img-fluid rounded-4 border border-secondary border-opacity-25 w-100" style="height: 200px; object-fit: cover;">
                        <div class="mt-3 p-3 rounded-3 bg-black border border-secondary border-opacity-25">
                            <span class="d-block small text-secondary fw-semibold mb-2">Desglose de Pago (60/40)</span>
                            <div class="d-flex justify-content-between small text-secondary mb-1">
                                <span>Costo Total:</span>
                                <strong class="text-white" id="detailsModalTotal">$0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between small text-secondary mb-1">
                                <span>Anticipo 60%:</span>
                                <strong class="text-warning" id="detailsModalAnticipo">$0.00</strong>
                            </div>
                            <div class="d-flex justify-content-between small text-secondary">
                                <span>Saldo Final 40%:</span>
                                <strong class="text-white" id="detailsModalSaldo">$0.00</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="text-white fw-bold mb-3">Especificaciones Técnicas Registradas</h6>
                        <div class="details-spec-item">
                            <span class="details-spec-label">Dimensiones:</span>
                            <span class="details-spec-value" id="detailsModalSpecs">-</span>
                        </div>
                        <div class="details-spec-item">
                            <span class="details-spec-label">Tipo de Fabricación:</span>
                            <span class="details-spec-value">Madera sólida y tableros estufados</span>
                        </div>
                        <div class="details-spec-item">
                            <span class="details-spec-label">Garantía:</span>
                            <span class="details-spec-value">1 año sobre ensambles y herrajes</span>
                        </div>

                        <div class="mt-3">
                            <span class="d-block small text-secondary fw-semibold mb-1">Notas de Producción / Entrega:</span>
                            <p class="small text-secondary bg-dark p-3 rounded-3 border border-secondary border-opacity-25" id="detailsModalNotes">
                                -
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer dark-theme-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 text-white" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: APROBAR Y REGISTRAR ANTICIPO (60%)
     ========================================================================== -->
<div class="modal fade" id="modalAprobarAnticipo" tabindex="-1" aria-labelledby="modalAprobarAnticipoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dark-theme-modal">
            
            <div class="modal-header dark-theme-header">
                <div>
                    <h5 class="modal-title text-white fw-bold" id="modalAprobarAnticipoLabel">Autorización de Fabricación</h5>
                    <small class="text-secondary">Paso reglamentario del taller para corte de maderas</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" action="pedidos.php" id="formAprobarAnticipo">
                <input type="hidden" name="aprobar_anticipo" value="1">
                <input type="hidden" name="id_cotizacion" id="anticipoCotizacionId" value="">

                <div class="modal-body dark-theme-body">
                    <div class="p-3 rounded-3 border border-secondary border-opacity-25 bg-black mb-3">
                        <span class="small text-secondary d-block">Proyecto a Autorizar:</span>
                        <strong class="text-white d-block fs-6" id="anticipoProyectoLabel">Proyecto</strong>
                        <span class="small text-secondary d-block mt-2">Monto de Anticipo (60%):</span>
                        <strong class="text-warning fs-5" id="anticipoMontoLabel">$0.00 MXN</strong>
                    </div>

                    <p class="small text-secondary line-height-base mb-3">
                        Al autorizar el anticipo del 60%, el proyecto pasa formalmente a la etapa <strong>En Fabricación / Taller</strong>. Se programará el dimensionado de tableros, calibrado de cepillo y ruteado de uniones.
                    </p>

                    <div class="p-2 px-3 rounded-2 bg-dark border border-secondary border-opacity-25 small text-secondary">
                        ✓ El 40% restante se liquida al concluir la entrega o instalación en tu domicilio.
                    </div>
                </div>

                <div class="modal-footer dark-theme-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 text-white" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-order-action primary-pill py-2 px-4">
                        <span>Confirmar Anticipo y Fabricar</span>
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

<!-- Script interactivo de filtros de pedidos y modales -->
<script src="assets/js/pedidos.js"></script>

<?php 
require_once 'includes/footer.php'; 
?>
