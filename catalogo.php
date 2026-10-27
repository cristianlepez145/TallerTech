<?php
/**
 * TallerTech - Catálogo de Proyectos & Muestrario de Materiales
 * Idioma visual Dark Mode TallerTech, filtros en píldora y Modal Quick View
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/conexion.php';

// Catálogo de proyectos y muestrario de materiales (preparado para ser consultado desde MySQL)
$proyectos = [
    [
        'id'                  => 1,
        'titulo'              => 'Cocina Integral Roble y Granito',
        'categoria_slug'      => 'cocinas_closets',
        'categoria_label'     => 'Clósets y Cocinas',
        'descripcion'         => 'Diseño contemporáneo con frentes en roble macizo estufado, cubierta de granito natural y herrajes de cierre suave importados.',
        'madera'              => 'Roble Blanco Americano',
        'acabado'             => 'Poliuretano mate anti-humedad',
        'tiempo'              => '3 a 4 semanas',
        'imagen'              => 'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'cocinas'
    ],
    [
        'id'                  => 2,
        'titulo'              => 'Mesa de Comedor en Parota Maciza',
        'categoria_slug'      => 'carpinteria_gral',
        'categoria_label'     => 'Mesas y Sillas',
        'descripcion'         => 'Tablón continuo con borde vivo natural (live-edge), ensamble de caja y espiga y base geométrica reforzada.',
        'madera'              => 'Parota / Huanacaxtle',
        'acabado'             => 'Aceite de linaza y cera vegetal',
        'tiempo'              => '2 a 3 semanas',
        'imagen'              => 'https://images.unsplash.com/photo-1615066390971-03e4e1c36ddf?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'mesas'
    ],
    [
        'id'                  => 3,
        'titulo'              => 'Escritorio Minimalista Nogal y Acero',
        'categoria_slug'      => 'camas_escritorios',
        'categoria_label'     => 'Bases y Escritorios',
        'descripcion'         => 'Estación de trabajo ergonómica con pasacables integrado en madera, gaveta oculta y perfilería esbelta.',
        'madera'              => 'Nogal Negro Americano',
        'acabado'             => 'Laca poliuretano semimate',
        'tiempo'              => '10 a 14 días hábiles',
        'imagen'              => 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'escritorios'
    ],
    [
        'id'                  => 4,
        'titulo'              => 'Puerta Principal Pivotante en Cedro',
        'categoria_slug'      => 'carpinteria_gral',
        'categoria_label'     => 'Puertas y Carpintería',
        'descripcion'         => 'Puerta monumental con eje pivotante de uso rudo, paneles machihembrados y doble sello de intemperie.',
        'madera'              => 'Cedro Rojo Estufado',
        'acabado'             => 'Barniz marino con filtro UV',
        'tiempo'              => '2 a 3 semanas',
        'imagen'              => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'puertas'
    ],
    [
        'id'                  => 5,
        'titulo'              => 'Cajonera Flotante con Guías Ocultas',
        'categoria_slug'      => 'carpinteria_gral',
        'categoria_label'     => 'Cajoneras y Muebles',
        'descripcion'         => 'Mueble suspendido con 4 cajones de extracción total, corte a 45 grados en esquinas y tirador invisible.',
        'madera'              => 'Tzalam & MDF Alta Densidad',
        'acabado'             => 'Poliéster satinado alta resistencia',
        'tiempo'              => '10 a 12 días hábiles',
        'imagen'              => 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'cajoneras'
    ],
    [
        'id'                  => 6,
        'titulo'              => 'Clóset Vestidor a Medida con Luz LED',
        'categoria_slug'      => 'cocinas_closets',
        'categoria_label'     => 'Clósets y Cocinas',
        'descripcion'         => 'Sistema modular de almacenamiento con zapateras extraíbles, pantaloneros y canales de iluminación indirecta.',
        'madera'              => 'Melamina Texturizada y Pino',
        'acabado'             => 'Texturizado mate anti-rayas',
        'tiempo'              => '3 semanas',
        'imagen'              => 'https://images.unsplash.com/photo-1558997519-83ea9252def8?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'a_medida'
    ],
    [
        'id'                  => 7,
        'titulo'              => 'Base de Cama King con Cabecera Integrada',
        'categoria_slug'      => 'camas_escritorios',
        'categoria_label'     => 'Bases y Escritorios',
        'descripcion'         => 'Plataforma silenciosa con esquinas redondeadas, largueros de encastre firme y mesas de noche voladas.',
        'madera'              => 'Pino Estufado y Encino',
        'acabado'             => 'Aceite cera ecológico',
        'tiempo'              => '2 semanas',
        'imagen'              => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'camas'
    ],
    [
        'id'                  => 8,
        'titulo'              => 'Muestrario: Nogal Americano Premium',
        'categoria_slug'      => 'materiales',
        'categoria_label'     => 'Muestrario de Materiales',
        'descripcion'         => 'Madera noble de grano cerrado, tonalidad marrón chocolate con vetas sutiles púrpuras. Ideal para mobiliario de autor.',
        'madera'              => 'Juglans Nigra (Nogal)',
        'acabado'             => 'Muestra pulida al aceite',
        'tiempo'              => 'Stock disponible en taller',
        'imagen'              => 'https://images.unsplash.com/photo-1546484396-fb3fc6f95f98?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'a_medida'
    ],
    [
        'id'                  => 9,
        'titulo'              => 'Muestrario: Parota / Huanacaxtle',
        'categoria_slug'      => 'materiales',
        'categoria_label'     => 'Muestrario de Materiales',
        'descripcion'         => 'Especie tropical sustentable de rápido crecimiento, con llamativo contraste entre albura crema y duramen castaño.',
        'madera'              => 'Enterolobium Cyclocarpum',
        'acabado'             => 'Sellador nitro y cera dura',
        'tiempo'              => 'Stock disponible en taller',
        'imagen'              => 'https://images.unsplash.com/photo-1589939705384-5185137a7f0f?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'a_medida'
    ],
    [
        'id'                  => 10,
        'titulo'              => 'Muestrario: Roble / Encino Blanco',
        'categoria_slug'      => 'materiales',
        'categoria_label'     => 'Muestrario de Materiales',
        'descripcion'         => 'Madera de gran dureza y resistencia a la flexión. Aporta luminosidad, textura profunda y carácter contemporáneo.',
        'madera'              => 'Quercus Alba (Roble)',
        'acabado'             => 'Poliuretano al agua ultra mate',
        'tiempo'              => 'Stock disponible en taller',
        'imagen'              => 'https://images.unsplash.com/photo-1533090161767-e6ffed986c88?auto=format&fit=crop&w=900&q=80',
        'categoria_cotizador' => 'a_medida'
    ]
];

// Configuración de vista para el layout general (activa el ítem Catalogo en la navbar pill)
$titulo_pagina = 'Catálogo de Proyectos & Materiales | TallerTech';
$pagina_actual = 'catalogo.php';
$css_adicional = 'assets/css/catalogo.css';

require_once 'includes/header.php';
?>

<div class="catalogo-page-wrapper">
    <div class="container">

        <!-- Encabezado de la Sección -->
        <header class="catalogo-header-box">
            <span class="catalogo-badge-pill">Colección &amp; Acabados Finos</span>
            <h1 class="catalogo-main-title">Catálogo de Proyectos &amp; Materiales</h1>
            <p class="catalogo-main-subtitle">
                Explora nuestros trabajos terminados y la selección de maderas y acabados para tu próximo proyecto.
            </p>
        </header>

        <!-- Barra de Filtros Interactiva (Botones Estilo Píldora) -->
        <nav class="catalogo-filter-bar" aria-label="Filtros de catálogo">
            <button type="button" class="filter-pill-btn active" data-filter="todos">Todos</button>
            <button type="button" class="filter-pill-btn" data-filter="carpinteria_gral">Cajoneras, Puertas, Mesas y Sillas</button>
            <button type="button" class="filter-pill-btn" data-filter="cocinas_closets">Clósets y Cocinas Integrales</button>
            <button type="button" class="filter-pill-btn" data-filter="camas_escritorios">Bases de cama y Escritorios</button>
            <button type="button" class="filter-pill-btn" data-filter="materiales">Muestrario de Materiales</button>
        </nav>

        <!-- Grid de Proyectos y Muestrario (Cards estilo TallerTech) -->
        <section class="catalogo-grid" id="catalogoGrid" aria-label="Galería de proyectos">
            <?php foreach ($proyectos as $p): ?>
                <article 
                    class="catalogo-card" 
                    data-category="<?= htmlspecialchars($p['categoria_slug'], ENT_QUOTES, 'UTF-8') ?>"
                    data-title="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                    data-cat-label="<?= htmlspecialchars($p['categoria_label'], ENT_QUOTES, 'UTF-8') ?>"
                    data-img="<?= htmlspecialchars($p['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                    data-desc="<?= htmlspecialchars($p['descripcion'], ENT_QUOTES, 'UTF-8') ?>"
                    data-madera="<?= htmlspecialchars($p['madera'], ENT_QUOTES, 'UTF-8') ?>"
                    data-acabado="<?= htmlspecialchars($p['acabado'], ENT_QUOTES, 'UTF-8') ?>"
                    data-tiempo="<?= htmlspecialchars($p['tiempo'], ENT_QUOTES, 'UTF-8') ?>"
                    data-quote-url="cotizaciones.php?categoria=<?= urlencode($p['categoria_cotizador']) ?>"
                    tabindex="0"
                    role="button"
                    aria-label="Ver detalles de <?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                >
                    <!-- Imagen de Alta Resolución -->
                    <img 
                        src="<?= htmlspecialchars($p['imagen'], ENT_QUOTES, 'UTF-8') ?>" 
                        alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>" 
                        class="catalogo-card-img"
                        loading="lazy"
                    >
                    
                    <!-- Overlay degradado oscuro para legibilidad -->
                    <div class="catalogo-card-overlay"></div>

                    <!-- Contenido textual inferior -->
                    <div class="catalogo-card-content">
                        <span class="catalogo-card-category"><?= htmlspecialchars($p['categoria_label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <h3 class="catalogo-card-title"><?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                        
                        <div class="catalogo-card-actions">
                            <span class="catalogo-btn-action">
                                <span>Ver detalles</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
                            <span class="catalogo-card-badge-pill">Ficha Técnica</span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

    </div>
</div>

<!-- ==========================================================================
     MODAL / VISTA RÁPIDA DE PROYECTO (DARK MODE ELEGANTE)
     ========================================================================== -->
<div class="catalogo-modal-backdrop" id="catalogoModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="catalogo-modal-card">
        
        <!-- Botón cerrar -->
        <button type="button" class="modal-close-btn" id="modalCloseBtn" aria-label="Cerrar vista rápida">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <div class="modal-body-layout">
            
            <!-- Columna Izquierda: Imagen Grande -->
            <div class="modal-media-col">
                <img src="" alt="" id="modalImg" class="modal-media-img">
            </div>

            <!-- Columna Derecha: Ficha Técnica y Cotización -->
            <div class="modal-info-col">
                <div>
                    <span class="modal-badge-cat" id="modalBadge">Categoría</span>
                    <h3 class="modal-project-title" id="modalTitle">Título del Proyecto</h3>
                    <p class="modal-project-desc" id="modalDesc">Descripción técnica del trabajo realizado por el taller.</p>

                    <!-- Ficha Técnica -->
                    <ul class="specs-list">
                        <li class="specs-item">
                            <span class="specs-label">Material / Madera:</span>
                            <span class="specs-value" id="modalMadera">-</span>
                        </li>
                        <li class="specs-item">
                            <span class="specs-label">Acabado:</span>
                            <span class="specs-value" id="modalAcabado">-</span>
                        </li>
                        <li class="specs-item">
                            <span class="specs-label">Tiempo Estimado:</span>
                            <span class="specs-value" id="modalTiempo">-</span>
                        </li>
                    </ul>
                </div>

                <!-- Botón Píldora Blanco -->
                <a href="cotizaciones.php" id="modalBtnQuote" class="modal-btn-quote">
                    <span>Cotizar este diseño</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>

        </div>

    </div>
</div>

<!-- Script de filtrado y modal -->
<script src="assets/js/catalogo.js"></script>

<?php 
require_once 'includes/footer.php'; 
?>

