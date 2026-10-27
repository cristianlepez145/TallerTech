<?php
/**
 * TallerTech - Header Global (Dark Mode)
 * Barra de navegación sticky con estética píldora y estado de sesión.
 */

// 1. Iniciar sesión y helpers de autenticación
require_once __DIR__ . '/auth.php';

// 2. Detección automática o personalizada de la página actual para la clase .active
$current_page = $pagina_actual ?? basename($_SERVER['PHP_SELF']);
$page_title   = $titulo_pagina ?? 'TallerTech | Carpintería y Diseño a Medida';
$cart_count   = isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])
    ? count($_SESSION['carrito'])
    : (int)($_SESSION['cart_count'] ?? 0);

$esAdminCarpintero = es_carpintero();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Bootstrap 5 CSS para componentes de utilidades y formularios -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Hoja de estilos del layout de TallerTech (Dark Mode, Header en Píldora y Footer) -->
    <link rel="stylesheet" href="assets/css/layout.css">

    <?php if (!empty($css_adicional)): ?>
        <?php foreach ((array)$css_adicional as $css_file): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css_file, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
<div class="page-wrapper">

    <!-- Header Sticky con Dark Mode y Backdrop Blur -->
    <header class="site-header">
        <div class="header-container">
            
            <!-- Lado Izquierdo: Logotipo del Árbol + TallerTech -->
            <a href="index.php" class="brand-logo" aria-label="TallerTech - Inicio">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <!-- Silueta geométrica de pino / árbol de carpintería -->
                    <path d="M12 2L6 8h3.5l-4 6h4l-3.5 5h12l-3.5-5h4l-4-6H18L12 2z" fill="rgba(196, 136, 73, 0.22)" stroke="#c48849" />
                    <path d="M11 21v3h2v-3" stroke="#c48849" stroke-width="2.5" />
                </svg>
                <span>TallerTech</span>
            </a>

            <!-- Centro: Barra de Navegación encapsulada en Píldora -->
            <nav class="header-nav" aria-label="Navegación principal">
                <div class="nav-pill">
                    <a href="index.php" class="nav-link-pill <?= ($current_page === 'index.php') ? 'active' : '' ?>">Inicio</a>
                    <a href="catalogo.php" class="nav-link-pill <?= ($current_page === 'catalogo.php') ? 'active' : '' ?>">Catálogo</a>
                    <a href="citas.php" class="nav-link-pill <?= ($current_page === 'citas.php') ? 'active' : '' ?>">Citas</a>
                    <a href="cotizaciones.php" class="nav-link-pill <?= ($current_page === 'cotizaciones.php') ? 'active' : '' ?>">Cotizaciones</a>
                    <a href="pedidos.php" class="nav-link-pill <?= ($current_page === 'pedidos.php') ? 'active' : '' ?>">Pedidos</a>
                    <a href="resenas.php" class="nav-link-pill <?= ($current_page === 'resenas.php') ? 'active' : '' ?>">Reseñas</a>

                    <?php if ($esAdminCarpintero): ?>
                        <!-- Acceso Destacado al Panel de Carpintero / Administración -->
                        <a href="admin_pedidos.php" class="nav-link-pill admin-nav-pill <?= (strpos($current_page, 'admin_') === 0) ? 'active' : '' ?>" title="Panel de Gestión del Carpintero">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                            </svg>
                            <span>Panel Carpintero</span>
                        </a>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Lado Derecho: Carrito de Compras + Sesión de Usuario -->
            <div class="header-actions">
                
                <!-- Icono de Carrito SVG con contador sutil -->
                <a href="carrito.php" class="cart-action" aria-label="Ver carrito de compras">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    <span class="cart-badge" id="cart-counter"><?= $cart_count ?></span>
                </a>

                <!-- Estado de Sesión -->
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <a href="perfil.php" class="user-profile-badge" title="Mi perfil">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($esAdminCarpintero): ?>
                            <span class="badge-role-tag">Carpintero</span>
                        <?php endif; ?>
                    </a>
                    <a href="logout.php" class="btn-auth-logout" title="Cerrar sesión">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        <span>Salir</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-auth-login">Iniciar Sesión</a>
                <?php endif; ?>

                <!-- Botón Toggle de Menú Móvil (Hamburguesa) -->
                <button type="button" class="mobile-toggle" aria-label="Abrir menú de navegación" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Menú Móvil Desplegable -->
        <div class="mobile-nav-drawer" id="mobileNavDrawer">
            <ul class="mobile-nav-list">
                <li><a href="index.php" class="mobile-nav-link <?= ($current_page === 'index.php') ? 'active' : '' ?>">Inicio</a></li>
                <li><a href="catalogo.php" class="mobile-nav-link <?= ($current_page === 'catalogo.php') ? 'active' : '' ?>">Catálogo</a></li>
                <li><a href="citas.php" class="mobile-nav-link <?= ($current_page === 'citas.php') ? 'active' : '' ?>">Citas &amp; Medidas</a></li>
                <li><a href="cotizaciones.php" class="mobile-nav-link <?= ($current_page === 'cotizaciones.php') ? 'active' : '' ?>">Cotizaciones</a></li>
                <li><a href="pedidos.php" class="mobile-nav-link <?= ($current_page === 'pedidos.php') ? 'active' : '' ?>">Pedidos</a></li>
                <li><a href="resenas.php" class="mobile-nav-link <?= ($current_page === 'resenas.php') ? 'active' : '' ?>">Reseñas</a></li>

                <?php if ($esAdminCarpintero): ?>
                    <li class="border-top border-secondary border-opacity-25 pt-2 mt-2">
                        <a href="admin_pedidos.php" class="mobile-nav-link text-warning fw-bold <?= (strpos($current_page, 'admin_') === 0) ? 'active' : '' ?>">
                            🛠️ Panel Carpintero (Administrador)
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="mobile-nav-actions">
                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <a href="perfil.php" class="mobile-nav-link">Mi Perfil (<?= htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</a>
                    <a href="logout.php" class="btn-auth-logout text-center">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="login.php" class="btn-auth-login text-center">Iniciar Sesión</a>
                    <a href="Registro.php" class="mobile-nav-link text-center">Crear Cuenta</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Contenedor Principal de la Vista -->
    <main class="main-content">
