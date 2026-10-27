<?php
/**
 * TallerTech - Página Principal (Main Page / Catálogo)
 * Vista protegida por sesión con Hero Banner y Grid de Categorías
 */

// 1. Iniciar sesión y proteger la vista si el usuario no está autenticado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

// 2. Parámetros de la vista para el layout
$titulo_pagina = 'TallerTech | Redefine tu espacio con elegancia';
$pagina_actual = 'index.php';
$css_adicional = 'assets/css/home.css';

// 3. Cargar el Header global (con navegación en píldora, sesión y estilos)
require_once 'includes/header.php';
?>

<div class="container py-4">

    <!-- Sección Hero (Banner Principal) -->
    <section class="hero-section">
        <div class="hero-banner-card">
            <!-- Imagen de fondo contemporánea y luminosa -->
            <img 
                src="https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1600&q=80" 
                alt="Espacio contemporáneo con muebles de madera" 
                class="hero-banner-bg"
                loading="eager"
            >
            
            <!-- Overlay oscuro sutil a la izquierda -->
            <div class="hero-banner-overlay"></div>

            <!-- Contenido textual superpuesto a la izquierda -->
            <div class="hero-banner-content">
                <h1 class="hero-banner-title">
                    Redefine tu espacio con elegancia.
                </h1>
                <p class="hero-banner-subtitle">
                    Descubre materiales de primera calidad y diseños contemporáneos pensados para inspirar cada rincón de tu hogar.
                </p>
                <a href="catalogo.php" class="btn-pill-white">
                    <span>Explorar Catalogo</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Sección de Categorías (Cards Grid de 3 Columnas) -->
    <section class="categories-section">
        <div class="categories-grid">

            <!-- Card 1: Cocinas -->
            <a href="catalogo.php?categoria=cocinas" class="category-card" aria-label="Explorar muebles para Cocinas">
                <img 
                    src="https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=800&q=80" 
                    alt="Cocina contemporánea de madera" 
                    class="category-card-img"
                    loading="lazy"
                >
                <div class="category-card-overlay"></div>
                <div class="category-card-footer">
                    <h3 class="category-card-title">Cocinas</h3>
                    <span class="category-card-arrow" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="7" y1="17" x2="17" y2="7"></line>
                            <polyline points="7 7 17 7 17 17"></polyline>
                        </svg>
                    </span>
                </div>
            </a>

            <!-- Card 2: Baños -->
            <a href="catalogo.php?categoria=banos" class="category-card" aria-label="Explorar muebles para Baños">
                <img 
                    src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=800&q=80" 
                    alt="Mueble de baño en madera y diseño moderno" 
                    class="category-card-img"
                    loading="lazy"
                >
                <div class="category-card-overlay"></div>
                <div class="category-card-footer">
                    <h3 class="category-card-title">Baños</h3>
                    <span class="category-card-arrow" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="7" y1="17" x2="17" y2="7"></line>
                            <polyline points="7 7 17 7 17 17"></polyline>
                        </svg>
                    </span>
                </div>
            </a>

            <!-- Card 3: Salas de Estar -->
            <a href="catalogo.php?categoria=salas" class="category-card" aria-label="Explorar muebles para Salas de Estar">
                <img 
                    src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?auto=format&fit=crop&w=800&q=80" 
                    alt="Sala de estar moderna con muebles a medida" 
                    class="category-card-img"
                    loading="lazy"
                >
                <div class="category-card-overlay"></div>
                <div class="category-card-footer">
                    <h3 class="category-card-title">Salas de Estar</h3>
                    <span class="category-card-arrow" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="7" y1="17" x2="17" y2="7"></line>
                            <polyline points="7 7 17 7 17 17"></polyline>
                        </svg>
                    </span>
                </div>
            </a>

        </div>
    </section>

</div>

<?php 
// 4. Cargar el Footer global
require_once 'includes/footer.php'; 
?>
