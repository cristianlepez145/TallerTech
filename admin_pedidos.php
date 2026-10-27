<?php
/**
 * TallerTech - Panel de Control del Carpintero (Administrador)
 * Vista operativa para gestión de órdenes de fabricación, revisión de cotizaciones,
 * citas con planos/croquis adjuntos y bitácora de auditoría del sistema (Logs).
 */

require_once 'includes/conexion.php';
require_once 'includes/auth.php';

// Control de Acceso Estricto: Únicamente accesible por 'carpintero' o 'admin'
verificar_rol(['carpintero', 'admin']);

$mensaje_exito = '';
$mensaje_error = '';

// =============================================================================
// PROCESAMIENTO DE ACCIONES ADMINISTRATIVAS DEL CARPINTERO (POST)
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['action'] ?? '';

    // 1. Actualizar Orden de Pedido (Estado, Fecha Estimada, Anticipo y Total)
    if ($accion === 'actualizar_pedido') {
        $id_pedido      = (int)($_POST['id_pedido'] ?? 0);
        $nuevo_estado   = trim($_POST['estado'] ?? '');
        $nueva_fecha    = trim($_POST['fecha_estimada'] ?? '');
        $nuevo_anticipo = (float)($_POST['anticipo'] ?? 0);
        $nuevo_total    = (float)($_POST['total'] ?? 0);

        if ($id_pedido <= 0 || empty($nuevo_estado)) {
            $mensaje_error = 'Datos del pedido incompletos o inválidos.';
        } else {
            try {
                $sqlExtra = ($nuevo_estado === 'completado') ? ", completado_en = NOW()" : "";
                $sql = "UPDATE pedidos 
                        SET estado = :estado, 
                            fecha_estimada = :fecha, 
                            anticipo = :anticipo, 
                            total = :total 
                            {$sqlExtra}
                        WHERE id_pedido = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':estado'   => $nuevo_estado,
                    ':fecha'    => !empty($nueva_fecha) ? $nueva_fecha : null,
                    ':anticipo' => $nuevo_anticipo,
                    ':total'    => $nuevo_total,
                    ':id'       => $id_pedido
                ]);

                // Registrar en la bitácora de auditoría
                registrar_log(
                    $pdo,
                    'CAMBIO_ESTADO_PEDIDO',
                    "Carpintero actualizó pedido #PED-{$id_pedido}: Estado [{$nuevo_estado}], Fecha entrega [{$nueva_fecha}], Anticipo \${$nuevo_anticipo} MXN, Total \${$nuevo_total} MXN."
                );

                $mensaje_exito = "Pedido <strong>#PED-{$id_pedido}</strong> actualizado correctamente.";
            } catch (PDOException $e) {
                error_log("Error al actualizar pedido: " . $e->getMessage());
                $mensaje_error = 'Ocurrió un error en el servidor al actualizar el pedido.';
            }
        }
    }

    // 2. Actualizar / Validar Cotización de Cliente
    elseif ($accion === 'actualizar_cotizacion') {
        $id_cotizacion = (int)($_POST['id_cotizacion'] ?? 0);
        $nuevo_estado  = trim($_POST['estado'] ?? 'estimada');
        $nuevo_total   = (float)($_POST['costo_total'] ?? 0);
        $notas         = trim($_POST['notas'] ?? '');

        if ($id_cotizacion <= 0) {
            $mensaje_error = 'ID de cotización no válido.';
        } else {
            try {
                $sql = "UPDATE cotizaciones 
                        SET estado = :estado, costo_total = :total, notas = :notas 
                        WHERE id_cotizacion = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':estado' => $nuevo_estado,
                    ':total'  => $nuevo_total,
                    ':notas'  => $notas,
                    ':id'     => $id_cotizacion
                ]);

                registrar_log(
                    $pdo,
                    'ACTUALIZAR_COTIZACION',
                    "Carpintero actualizó cotización #COT-{$id_cotizacion}: Estado [{$nuevo_estado}], Monto formal \${$nuevo_total} MXN."
                );

                $mensaje_exito = "Cotización <strong>#COT-{$id_cotizacion}</strong> validada y actualizada.";
            } catch (PDOException $e) {
                error_log("Error al actualizar cotización: " . $e->getMessage());
                $mensaje_error = 'No fue posible guardar los cambios de la cotización.';
            }
        }
    }

    // 3. Actualizar Cita de Medición
    elseif ($accion === 'actualizar_cita') {
        $id_cita      = (int)($_POST['id_cita'] ?? 0);
        $nuevo_estado = trim($_POST['estado'] ?? 'pendiente');

        if ($id_cita <= 0) {
            $mensaje_error = 'Identificador de cita no válido.';
        } else {
            try {
                $sql = "UPDATE citas SET estado = :estado WHERE id_cita = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':estado' => $nuevo_estado, ':id' => $id_cita]);

                registrar_log(
                    $pdo,
                    'ACTUALIZAR_CITA',
                    "Carpintero actualizó cita #CITA-{$id_cita} al estado [{$nuevo_estado}]."
                );

                $mensaje_exito = "Cita #{$id_cita} actualizada exitosamente.";
            } catch (PDOException $e) {
                error_log("Error al actualizar cita: " . $e->getMessage());
                $mensaje_error = 'Error al actualizar el estado de la cita.';
            }
        }
    }
}

// =============================================================================
// CONSULTAS A LA BASE DE DATOS (VISIBILIDAD GLOBAL PARA EL CARPINTERO)
// =============================================================================
$pedidos_db      = [];
$cotizaciones_db = [];
$citas_db        = [];
$logs_db         = [];

if (isset($pdo)) {
    // A) Consultar todos los pedidos de todos los clientes
    try {
        $stmtP = $pdo->query("
            SELECT p.*, 
                   u.nombre AS cliente_nombre, 
                   u.correo AS cliente_correo, 
                   u.telefono AS cliente_telefono,
                   DATE_FORMAT(p.creado_en, '%d %b, %Y') AS fecha_creado_fmt,
                   DATE_FORMAT(p.fecha_estimada, '%Y-%m-%d') AS fecha_estimada_raw,
                   DATE_FORMAT(p.fecha_estimada, '%d %b, %Y') AS fecha_estimada_fmt
            FROM pedidos p
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
            ORDER BY p.id_pedido DESC
        ");
        $pedidos_db = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log("Consulta pedidos admin: " . $e->getMessage());
    }

    // B) Consultar todas las cotizaciones
    try {
        $stmtC = $pdo->query("
            SELECT c.*, 
                   u.nombre AS cliente_nombre, 
                   u.correo AS cliente_correo, 
                   u.telefono AS cliente_telefono,
                   m.nombre AS mueble_catalogo_nombre,
                   DATE_FORMAT(c.creado_en, '%d %b, %Y') AS fecha_creado_fmt
            FROM cotizaciones c
            LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
            LEFT JOIN muebles_catalogo m ON c.id_mueble = m.id_mueble
            ORDER BY c.id_cotizacion DESC
        ");
        $cotizaciones_db = $stmtC->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log("Consulta cotizaciones admin: " . $e->getMessage());
    }

    // C) Consultar citas de toma de medidas
    try {
        $stmtCi = $pdo->query("
            SELECT ci.*, 
                   u.nombre AS cliente_nombre, 
                   u.correo AS cliente_correo, 
                   u.telefono AS cliente_telefono,
                   DATE_FORMAT(ci.fecha_hora, '%d/%m/%Y %H:%i') AS fecha_cita_fmt
            FROM citas ci
            LEFT JOIN usuarios u ON ci.id_usuario = u.id_usuario
            ORDER BY ci.fecha_hora DESC
        ");
        $citas_db = $stmtCi->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log("Consulta citas admin: " . $e->getMessage());
    }

    // D) Consultar Bitácora de Logs
    try {
        $stmtL = $pdo->query("
            SELECT l.*, 
                   u.nombre AS usuario_nombre, 
                   u.correo AS usuario_correo,
                   DATE_FORMAT(l.fecha, '%d/%m/%Y %H:%i:%s') AS fecha_fmt
            FROM logs l
            LEFT JOIN usuarios u ON l.usuario_id = u.id_usuario
            ORDER BY l.id DESC
            LIMIT 100
        ");
        $logs_db = $stmtL->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log("Consulta logs admin: " . $e->getMessage());
    }
}

// Fallback de demostración si la base de datos se encuentra vacía o en modo prototipo
$mostrarDemo = empty($pedidos_db) && empty($cotizaciones_db);

if ($mostrarDemo) {
    $pedidos_db = [
        [
            'id_pedido'           => 1042,
            'cliente_nombre'      => 'Carlos Morales',
            'cliente_correo'      => 'carlos@ejemplo.com',
            'cliente_telefono'    => '+52 55 1234 5678',
            'mueble_nombre'       => 'Cocina Integral Roble & Granito Negro',
            'total'               => 38500.00,
            'anticipo'            => 23100.00,
            'estado'              => 'en_produccion',
            'fecha_creado_fmt'    => '14 Oct, 2026',
            'fecha_estimada_raw'  => '2026-11-15',
            'fecha_estimada_fmt'  => '15 Nov, 2026',
            'direccion_entrega'   => 'Av. Insurgentes Sur 1420, Col. Actipan, CDMX'
        ],
        [
            'id_pedido'           => 1038,
            'cliente_nombre'      => 'Elena Vega',
            'cliente_correo'      => 'elena.vega@empresa.com',
            'cliente_telefono'    => '+52 55 9876 5432',
            'mueble_nombre'       => 'Escritorio Ejecutivo en L Parota Maciza',
            'total'               => 14200.00,
            'anticipo'            => 8520.00,
            'estado'              => 'en_produccion',
            'fecha_creado_fmt'    => '28 Sep, 2026',
            'fecha_estimada_raw'  => '2026-10-30',
            'fecha_estimada_fmt'  => '30 Oct, 2026',
            'direccion_entrega'   => 'Calle Durango 88, Col. Roma Norte, CDMX'
        ],
        [
            'id_pedido'           => 1025,
            'cliente_nombre'      => 'Roberto Domínguez',
            'cliente_correo'      => 'roberto.d@gmail.com',
            'cliente_telefono'    => '+52 55 4567 8901',
            'mueble_nombre'       => 'Comedor Contemporáneo 8 Sillas en Nogal',
            'total'               => 28900.00,
            'anticipo'            => 28900.00,
            'estado'              => 'completado',
            'fecha_creado_fmt'    => '14 Ago, 2026',
            'fecha_estimada_raw'  => '2026-09-05',
            'fecha_estimada_fmt'  => '05 Sep, 2026',
            'direccion_entrega'   => 'Paseo de la Reforma 222, Piso 14'
        ]
    ];

    $cotizaciones_db = [
        [
            'id_cotizacion'          => 48,
            'cliente_nombre'         => 'Mariana Castro',
            'cliente_correo'         => 'mariana.c@gmail.com',
            'cliente_telefono'       => '+52 55 6789 0123',
            'mueble_catalogo_nombre' => 'Cajoneras Estandarizadas',
            'costo_total'            => 9450.00,
            'estado'                 => 'estimada',
            'fecha_creado_fmt'       => '24 Oct, 2026',
            'notas'                  => 'Categoría: cajoneras | Medidas: 1.10m alto x 0.90m ancho x 0.50m prof. | Material: Melamina Estándar 16mm | Instalación: Sí | Envío: Sí | Notas: Color roble cenizo con correderas ocultas.'
        ]
    ];

    $citas_db = [
        [
            'id_cita'          => 22,
            'cliente_nombre'   => 'Fernando Soto',
            'cliente_correo'   => 'fsoto@arqstudio.com',
            'cliente_telefono' => '+52 55 7890 1234',
            'fecha_cita_fmt'   => '28/10/2026 18:30',
            'tipo_cita'        => 'domicilio',
            'direccion'        => 'Cda. de San Jerónimo 45, San Ángel, CDMX',
            'ruta_croquis'     => null,
            'notas'            => 'Toma de medidas para vestidor completo en madera de cedro.',
            'estado'           => 'confirmada'
        ]
    ];

    $logs_db = [
        [
            'id'             => 101,
            'usuario_nombre' => 'Carpintero Master',
            'usuario_correo' => 'taller@tallertech.com',
            'accion'         => 'CAMBIO_ESTADO_PEDIDO',
            'detalle'        => 'Carpintero actualizó pedido #PED-1042 a estado [en_produccion].',
            'ip'             => '127.0.0.1',
            'fecha_fmt'      => date('d/m/Y H:i:s')
        ],
        [
            'id'             => 100,
            'usuario_nombre' => 'Carlos Morales',
            'usuario_correo' => 'carlos@ejemplo.com',
            'accion'         => 'ANTICIPO_REGISTRADO',
            'detalle'        => 'Cliente aprobó cotización #45 y generó pedido #PED-1042 con anticipo del 60% ($23,100.00 MXN).',
            'ip'             => '189.201.45.12',
            'fecha_fmt'      => date('d/m/Y H:i:s', strtotime('-1 day'))
        ]
    ];
}

// Cálculo de Métricas (KPIs)
$totalPedidos        = count($pedidos_db);
$pedidosEnProduccion = count(array_filter($pedidos_db, fn($p) => ($p['estado'] ?? '') === 'en_produccion'));
$pedidosPendientes   = count(array_filter($pedidos_db, fn($p) => ($p['estado'] ?? '') === 'pendiente_anticipo'));
$cotizacionesPend    = count(array_filter($cotizaciones_db, fn($c) => ($c['estado'] ?? '') === 'estimada' || ($c['estado'] ?? '') === 'en_progreso'));
$citasActivas        = count(array_filter($citas_db, fn($ci) => ($ci['estado'] ?? '') === 'pendiente' || ($ci['estado'] ?? '') === 'confirmada'));

$totalRecaudado = array_sum(array_map(fn($p) => (float)($p['anticipo'] ?? 0), $pedidos_db));
$totalSaldos    = array_sum(array_map(fn($p) => max(0, (float)($p['total'] ?? 0) - (float)($p['anticipo'] ?? 0)), $pedidos_db));

// Configuración de Header
$titulo_pagina = 'Panel de Carpintero (Administrador) | TallerTech';
$pagina_actual = 'admin_pedidos.php';
$css_adicional = 'assets/css/admin.css';

require_once 'includes/header.php';
?>

<div class="admin-page-wrapper">
    <div class="container">

        <!-- Encabezado del Panel del Carpintero -->
        <header class="admin-header-box">
            <div>
                <span class="admin-carpenter-pill">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                    Taller &amp; Fabricación
                </span>
                <h1 class="admin-header-title">Panel de Control del Carpintero</h1>
                <p class="admin-header-subtitle">
                    Monitoreo general de proyectos, control de órdenes de trabajo, aprobación de presupuestos y bitácora técnica.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="pedidos.php" class="btn btn-outline-secondary rounded-pill px-3 py-2 text-decoration-none small fw-semibold" target="_blank" title="Ver cómo lo ve el cliente">
                    <span>Vista del Cliente ↗</span>
                </a>
                <a href="cotizaciones.php" class="btn btn-outline-warning rounded-pill px-3 py-2 text-decoration-none small fw-semibold">
                    <span>+ Nueva Cotización</span>
                </a>
            </div>
        </header>

        <!-- Banners de Notificación de Acciones -->
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

        <!-- Tarjetas de Métricas (KPIs) -->
        <section class="admin-kpi-grid" aria-label="Métricas del taller">
            
            <!-- KPI 1: En Fabricación -->
            <article class="admin-kpi-card kpi-blue">
                <div class="kpi-top-row">
                    <span class="kpi-label">En Fabricación</span>
                    <div class="kpi-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="3" rx="2"></rect>
                            <path d="M7 7h10"></path>
                            <path d="M7 12h10"></path>
                            <path d="M7 17h10"></path>
                        </svg>
                    </div>
                </div>
                <div class="kpi-value"><?= $pedidosEnProduccion ?></div>
                <div class="kpi-subtext">Órdenes activas en taller</div>
            </article>

            <!-- KPI 2: Cotizaciones por Revisar -->
            <article class="admin-kpi-card kpi-amber">
                <div class="kpi-top-row">
                    <span class="kpi-label">Cotizaciones Pendientes</span>
                    <div class="kpi-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="kpi-value"><?= $cotizacionesPend ?></div>
                <div class="kpi-subtext">Requieren validación técnica</div>
            </article>

            <!-- KPI 3: Citas de Medición -->
            <article class="admin-kpi-card kpi-purple">
                <div class="kpi-top-row">
                    <span class="kpi-label">Citas Agendadas</span>
                    <div class="kpi-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                </div>
                <div class="kpi-value"><?= $citasActivas ?></div>
                <div class="kpi-subtext">Visitas en domicilio/taller</div>
            </article>

            <!-- KPI 4: Anticipos Recaudados -->
            <article class="admin-kpi-card kpi-green">
                <div class="kpi-top-row">
                    <span class="kpi-label">Anticipos en Taller</span>
                    <div class="kpi-icon-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                </div>
                <div class="kpi-value">$<?= number_format($totalRecaudado, 2) ?></div>
                <div class="kpi-subtext">Saldos por cobrar: $<?= number_format($totalSaldos, 2) ?></div>
            </article>

        </section>

        <!-- Barra de Pestañas de Navegación del Panel -->
        <nav class="admin-tabs-nav" aria-label="Secciones del panel">
            <button type="button" class="admin-tab-btn active" data-tab="pedidos">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                    <path d="M3 6h18"></path>
                </svg>
                <span>Órdenes de Fabricación</span>
                <span class="tab-badge-counter"><?= $totalPedidos ?></span>
            </button>
            <button type="button" class="admin-tab-btn" data-tab="cotizaciones">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                </svg>
                <span>Cotizaciones de Clientes</span>
                <span class="tab-badge-counter"><?= count($cotizaciones_db) ?></span>
            </button>
            <button type="button" class="admin-tab-btn" data-tab="citas">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                </svg>
                <span>Citas &amp; Medidas</span>
                <span class="tab-badge-counter"><?= count($citas_db) ?></span>
            </button>
            <button type="button" class="admin-tab-btn" data-tab="logs">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
                <span>Bitácora de Logs</span>
                <span class="tab-badge-counter"><?= count($logs_db) ?></span>
            </button>
        </nav>

        <!-- ======================================================================
             PESTAÑA 1: ÓRDENES DE FABRICACIÓN (PEDIDOS)
             ====================================================================== -->
        <section class="admin-tab-pane" id="pane-pedidos">
            <div class="admin-surface-card">
                
                <div class="admin-toolbar-row">
                    <div>
                        <h2 class="h5 fw-bold text-white mb-1">Órdenes de Trabajo en Taller</h2>
                        <p class="small text-secondary mb-0">Controla avance de cortes, ensambles, pagos y fechas de entrega.</p>
                    </div>
                    <input 
                        type="search" 
                        class="admin-search-input" 
                        placeholder="Buscar pedido, cliente, mueble..." 
                        data-target-table="table-pedidos"
                    >
                </div>

                <div class="table-responsive-admin">
                    <table class="table-admin" id="table-pedidos">
                        <thead>
                            <tr>
                                <th>Orden #</th>
                                <th>Cliente</th>
                                <th>Proyecto / Mueble</th>
                                <th>Anticipo / Total</th>
                                <th>Saldo Pendiente</th>
                                <th>Entrega Estimada</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidos_db)): ?>
                                <tr class="empty-row">
                                    <td colspan="8" class="text-center text-secondary py-5">
                                        No hay órdenes de fabricación registradas en la base de datos.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pedidos_db as $p): ?>
                                    <?php
                                        $idPed      = (int)$p['id_pedido'];
                                        $total      = (float)$p['total'];
                                        $anticipo   = (float)$p['anticipo'];
                                        $saldo      = max(0, $total - $anticipo);
                                        $estado     = $p['estado'] ?? 'pendiente_anticipo';
                                        $rawFecha   = $p['fecha_estimada_raw'] ?? date('Y-m-d');
                                        $fechaFmt   = $p['fecha_estimada_fmt'] ?? 'Por definir';
                                        $telefono   = preg_replace('/[^0-9]/', '', $p['cliente_telefono'] ?? '');
                                        
                                        $badgeClass = match ($estado) {
                                            'en_produccion'      => 'status-produccion',
                                            'completado'         => 'status-completado',
                                            'cancelado'          => 'status-cancelado',
                                            default              => 'status-pendiente'
                                        };
                                        $estadoTexto = match ($estado) {
                                            'en_produccion'      => 'En Fabricación',
                                            'completado'         => 'Entregado',
                                            'cancelado'          => 'Cancelado',
                                            default              => 'Pendiente Anticipo'
                                        };
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="text-white">#PED-<?= str_pad($idPed, 4, '0', STR_PAD_LEFT) ?></strong>
                                            <div class="small text-muted"><?= htmlspecialchars($p['fecha_creado_fmt'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-white"><?= htmlspecialchars($p['cliente_nombre'] ?? 'Cliente') ?></div>
                                            <div class="small text-secondary"><?= htmlspecialchars($p['cliente_correo'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <div class="text-white"><?= htmlspecialchars($p['mueble_nombre'] ?? 'Mueble a Medida') ?></div>
                                            <?php if (!empty($p['direccion_entrega'])): ?>
                                                <div class="small text-secondary text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($p['direccion_entrega']) ?>">
                                                    📍 <?= htmlspecialchars($p['direccion_entrega']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong class="text-success">$<?= number_format($anticipo, 2) ?></strong>
                                            <div class="small text-muted">de $<?= number_format($total, 2) ?> MXN</div>
                                        </td>
                                        <td>
                                            <span class="<?= ($saldo > 0) ? 'text-warning fw-bold' : 'text-secondary' ?>">
                                                $<?= number_format($saldo, 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-white"><?= $fechaFmt ?></div>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $badgeClass ?>">
                                                <?= $estadoTexto ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <?php if (!empty($telefono)): ?>
                                                    <a 
                                                        href="https://wa.me/<?= $telefono ?>?text=<?= urlencode("Hola " . ($p['cliente_nombre'] ?? '') . ", te contactamos desde TallerTech respecto a tu pedido #PED-{$idPed}.") ?>" 
                                                        target="_blank" 
                                                        class="btn-action-tool whatsapp-btn" 
                                                        title="Contactar al cliente por WhatsApp"
                                                    >
                                                        WhatsApp
                                                    </a>
                                                <?php endif; ?>

                                                <button 
                                                    type="button" 
                                                    class="btn-action-tool" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditarPedido"
                                                    data-id="<?= $idPed ?>"
                                                    data-mueble="<?= htmlspecialchars($p['mueble_nombre'] ?? '', ENT_QUOTES) ?>"
                                                    data-cliente="<?= htmlspecialchars($p['cliente_nombre'] ?? '', ENT_QUOTES) ?>"
                                                    data-estado="<?= $estado ?>"
                                                    data-fecha="<?= $rawFecha ?>"
                                                    data-anticipo="<?= $anticipo ?>"
                                                    data-total="<?= $total ?>"
                                                >
                                                    Gestionar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>

        <!-- ======================================================================
             PESTAÑA 2: COTIZACIONES DE CLIENTES
             ====================================================================== -->
        <section class="admin-tab-pane d-none" id="pane-cotizaciones">
            <div class="admin-surface-card">
                
                <div class="admin-toolbar-row">
                    <div>
                        <h2 class="h5 fw-bold text-white mb-1">Cotizaciones y Solicitudes de Presupuesto</h2>
                        <p class="small text-secondary mb-0">Revisa dimensiones técnicas y valida precios antes del corte.</p>
                    </div>
                    <input 
                        type="search" 
                        class="admin-search-input" 
                        placeholder="Buscar cotización..." 
                        data-target-table="table-cotizaciones"
                    >
                </div>

                <div class="table-responsive-admin">
                    <table class="table-admin" id="table-cotizaciones">
                        <thead>
                            <tr>
                                <th>Cotización #</th>
                                <th>Cliente</th>
                                <th>Mueble Estandarizado</th>
                                <th>Especificaciones Técnicas / Notas</th>
                                <th>Monto Estimado</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cotizaciones_db)): ?>
                                <tr class="empty-row">
                                    <td colspan="7" class="text-center text-secondary py-5">
                                        No hay cotizaciones pendientes en la base de datos.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cotizaciones_db as $c): ?>
                                    <?php
                                        $idCot      = (int)$c['id_cotizacion'];
                                        $monto      = (float)$c['costo_total'];
                                        $estadoCot  = $c['estado'] ?? 'estimada';
                                        $muebleNom  = !empty($c['mueble_catalogo_nombre']) ? $c['mueble_catalogo_nombre'] : 'Proyecto a Medida';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="text-white">#COT-<?= str_pad($idCot, 4, '0', STR_PAD_LEFT) ?></strong>
                                            <div class="small text-muted"><?= htmlspecialchars($c['fecha_creado_fmt'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-white"><?= htmlspecialchars($c['cliente_nombre'] ?? 'Cliente') ?></div>
                                            <div class="small text-secondary"><?= htmlspecialchars($c['cliente_correo'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <span class="text-white fw-semibold"><?= htmlspecialchars($muebleNom) ?></span>
                                        </td>
                                        <td>
                                            <div class="log-detail-box" title="<?= htmlspecialchars($c['notas'] ?? '') ?>">
                                                <?= htmlspecialchars($c['notas'] ?? 'Sin notas registradas') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong class="text-warning">$<?= number_format($monto, 2) ?> MXN</strong>
                                        </td>
                                        <td>
                                            <span class="badge-status status-pendiente">
                                                <?= htmlspecialchars(ucfirst($estadoCot)) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button 
                                                type="button" 
                                                class="btn-action-tool"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarCotizacion"
                                                data-id="<?= $idCot ?>"
                                                data-mueble="<?= htmlspecialchars($muebleNom, ENT_QUOTES) ?>"
                                                data-cliente="<?= htmlspecialchars($c['cliente_nombre'] ?? '', ENT_QUOTES) ?>"
                                                data-total="<?= $monto ?>"
                                                data-estado="<?= $estadoCot ?>"
                                                data-notas="<?= htmlspecialchars($c['notas'] ?? '', ENT_QUOTES) ?>"
                                            >
                                                Validar Precio
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>

        <!-- ======================================================================
             PESTAÑA 3: CITAS DE TOMA DE MEDIDAS & CROQUIS
             ====================================================================== -->
        <section class="admin-tab-pane d-none" id="pane-citas">
            <div class="admin-surface-card">
                
                <div class="admin-toolbar-row">
                    <div>
                        <h2 class="h5 fw-bold text-white mb-1">Citas de Medición &amp; Planos de Clientes</h2>
                        <p class="small text-secondary mb-0">Revisa visitas programadas y archivos técnicos adjuntos.</p>
                    </div>
                    <input 
                        type="search" 
                        class="admin-search-input" 
                        placeholder="Buscar cita..." 
                        data-target-table="table-citas"
                    >
                </div>

                <div class="table-responsive-admin">
                    <table class="table-admin" id="table-citas">
                        <thead>
                            <tr>
                                <th>Cita #</th>
                                <th>Cliente</th>
                                <th>Fecha &amp; Hora</th>
                                <th>Modalidad / Dirección</th>
                                <th>Croquis / Archivos</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($citas_db)): ?>
                                <tr class="empty-row">
                                    <td colspan="7" class="text-center text-secondary py-5">
                                        No hay citas agendadas actualmente.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($citas_db as $ci): ?>
                                    <?php
                                        $idCita = (int)$ci['id_cita'];
                                        $estadoCita = $ci['estado'] ?? 'pendiente';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="text-white">#CITA-<?= $idCita ?></strong>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-white"><?= htmlspecialchars($ci['cliente_nombre'] ?? 'Cliente') ?></div>
                                            <div class="small text-secondary"><?= htmlspecialchars($ci['cliente_telefono'] ?? $ci['cliente_correo'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <div class="text-white fw-semibold"><?= htmlspecialchars($ci['fecha_cita_fmt'] ?? '') ?></div>
                                            <div class="small text-secondary"><?= htmlspecialchars($ci['tipo_cita'] ?? 'domicilio') ?></div>
                                        </td>
                                        <td>
                                            <div class="small text-white text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($ci['direccion'] ?? '') ?>">
                                                <?= htmlspecialchars($ci['direccion'] ?? 'En taller TallerTech') ?>
                                            </div>
                                            <?php if (!empty($ci['notas'])): ?>
                                                <div class="small text-secondary text-truncate" style="max-width: 260px;">
                                                    📝 <?= htmlspecialchars($ci['notas']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($ci['ruta_croquis']) && file_exists(__DIR__ . '/' . $ci['ruta_croquis'])): ?>
                                                <a href="<?= htmlspecialchars($ci['ruta_croquis']) ?>" target="_blank" class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1 small">
                                                    Ver Croquis ↗
                                                </a>
                                            <?php else: ?>
                                                <span class="small text-muted">Sin archivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-status status-pendiente">
                                                <?= htmlspecialchars(ucfirst($estadoCita)) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button 
                                                type="button" 
                                                class="btn-action-tool"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditarCita"
                                                data-id="<?= $idCita ?>"
                                                data-cliente="<?= htmlspecialchars($ci['cliente_nombre'] ?? '', ENT_QUOTES) ?>"
                                                data-fecha="<?= htmlspecialchars($ci['fecha_cita_fmt'] ?? '', ENT_QUOTES) ?>"
                                                data-estado="<?= $estadoCita ?>"
                                            >
                                                Estado
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>

        <!-- ======================================================================
             PESTAÑA 4: BITÁCORA DE AUDITORÍA (LOGS DEL SISTEMA)
             ====================================================================== -->
        <section class="admin-tab-pane d-none" id="pane-logs">
            <div class="admin-surface-card">
                
                <div class="admin-toolbar-row">
                    <div>
                        <h2 class="h5 fw-bold text-white mb-1">Bitácora de Logs &amp; Auditoría del Sistema</h2>
                        <p class="small text-secondary mb-0">Registro histórico inmutable de toda la actividad en la base de datos Aiven.</p>
                    </div>
                    <input 
                        type="search" 
                        class="admin-search-input" 
                        placeholder="Buscar por acción o usuario..." 
                        data-target-table="table-logs"
                    >
                </div>

                <div class="table-responsive-admin">
                    <table class="table-admin" id="table-logs">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha &amp; Hora</th>
                                <th>Usuario Responsable</th>
                                <th>Acción</th>
                                <th>Detalle del Evento</th>
                                <th>IP Cliente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs_db)): ?>
                                <tr class="empty-row">
                                    <td colspan="6" class="text-center text-secondary py-5">
                                        No se han registrado eventos en la tabla de logs todavía.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs_db as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="text-muted small">#<?= (int)$log['id'] ?></span>
                                        </td>
                                        <td>
                                            <div class="text-white small fw-semibold"><?= htmlspecialchars($log['fecha_fmt'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <div class="text-white small fw-bold">
                                                <?= htmlspecialchars($log['usuario_nombre'] ?? 'Visitante / Sistema') ?>
                                            </div>
                                            <?php if (!empty($log['usuario_correo'])): ?>
                                                <div class="small text-secondary"><?= htmlspecialchars($log['usuario_correo']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="log-badge-action">
                                                <?= htmlspecialchars($log['accion'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="log-detail-box">
                                                <?= htmlspecialchars($log['detalle'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="small text-secondary font-monospace">
                                                <?= htmlspecialchars($log['ip'] ?? '127.0.0.1') ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>

    </div>
</div>

<!-- ==========================================================================
     MODALES INTERACTIVOS PARA EL CARPINTERO
     ========================================================================== -->

<!-- Modal 1: Gestionar Pedido -->
<div class="modal fade" id="modalEditarPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content admin-modal-dark">
            <form method="POST" action="admin_pedidos.php">
                <input type="hidden" name="action" value="actualizar_pedido">
                <input type="hidden" name="id_pedido" id="edit_pedido_id" value="">

                <div class="modal-header admin-modal-header">
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="edit_pedido_title">Gestionar Pedido</h5>
                        <span class="small text-warning" id="edit_pedido_cliente"></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_pedido_estado" class="admin-form-label">Estado de la Orden</label>
                        <select name="estado" id="edit_pedido_estado" class="admin-form-select" required>
                            <option value="pendiente_anticipo">Pendiente de Anticipo (Etapa 1)</option>
                            <option value="en_produccion">En Fabricación / Taller (Etapa 2)</option>
                            <option value="completado">Completado y Entregado (Etapa 3)</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_pedido_fecha" class="admin-form-label">Fecha Estimada de Entrega</label>
                        <input type="date" name="fecha_estimada" id="edit_pedido_fecha" class="admin-form-input">
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label for="edit_pedido_anticipo" class="admin-form-label">Anticipo Pagado ($)</label>
                            <input type="number" step="0.01" name="anticipo" id="edit_pedido_anticipo" class="admin-form-input" required>
                        </div>
                        <div class="col-6">
                            <label for="edit_pedido_total" class="admin-form-label">Costo Total ($)</label>
                            <input type="number" step="0.01" name="total" id="edit_pedido_total" class="admin-form-input" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer admin-modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Validar Cotización -->
<div class="modal fade" id="modalEditarCotizacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content admin-modal-dark">
            <form method="POST" action="admin_pedidos.php">
                <input type="hidden" name="action" value="actualizar_cotizacion">
                <input type="hidden" name="id_cotizacion" id="edit_cot_id" value="">

                <div class="modal-header admin-modal-header">
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="edit_cot_title">Validar Cotización</h5>
                        <span class="small text-warning" id="edit_cot_cliente"></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_cot_estado" class="admin-form-label">Estado de Cotización</label>
                        <select name="estado" id="edit_cot_estado" class="admin-form-select" required>
                            <option value="estimada">Estimada (Pendiente de revisión)</option>
                            <option value="en_progreso">En Progreso / Presupuestando</option>
                            <option value="aprobada">Aprobada por el Taller</option>
                            <option value="rechazada">Rechazada</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_cot_total" class="admin-form-label">Presupuesto Formal Validado ($ MXN)</label>
                        <input type="number" step="0.01" name="costo_total" id="edit_cot_total" class="admin-form-input" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_cot_notas" class="admin-form-label">Detalles y Especificaciones Técnicas</label>
                        <textarea name="notas" id="edit_cot_notas" rows="3" class="admin-form-textarea"></textarea>
                    </div>
                </div>

                <div class="modal-footer admin-modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Actualizar Cotización</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Estado de Cita -->
<div class="modal fade" id="modalEditarCita" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content admin-modal-dark">
            <form method="POST" action="admin_pedidos.php">
                <input type="hidden" name="action" value="actualizar_cita">
                <input type="hidden" name="id_cita" id="edit_cita_id" value="">

                <div class="modal-header admin-modal-header">
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="edit_cita_title">Estado de Cita</h5>
                        <span class="small text-warning" id="edit_cita_cliente"></span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_cita_estado" class="admin-form-label">Estado de la Visita</label>
                        <select name="estado" id="edit_cita_estado" class="admin-form-select" required>
                            <option value="pendiente">Pendiente de Confirmar</option>
                            <option value="confirmada">Confirmada con Cliente</option>
                            <option value="realizada">Medición Realizada con Éxito</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer admin-modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">Guardar Estado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script interactivo del Panel de Carpintero -->
<script src="assets/js/admin.js"></script>

<?php
require_once 'includes/footer.php';
?>
