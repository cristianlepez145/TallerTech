<?php
/**
 * TallerTech - Galería de Trabajos Hechos
 * Muestrario de piezas reales fabricadas en el taller familiar.
 * Las fotos se sirven directamente desde almacenamiento en disco local (uploads/trabajos/).
 */

$titulo = "Trabajos Hechos - Galería de Proyectos | TallerTech";
require_once 'includes/header.php';

// Cargar catálogo de trabajos desde uploads/trabajos/trabajos.json
$ruta_json_trabajos = __DIR__ . '/uploads/trabajos/trabajos.json';
$trabajos = [];
if (file_exists($ruta_json_trabajos)) {
    $raw_json = @file_get_contents($ruta_json_trabajos);
    $trabajos = json_decode($raw_json, true) ?: [];
}

// Mapeo amigable de categorías para enlace al cotizador
$map_cotizador = [
    'mesas'       => 'mesas',
    'cocinas'     => 'cocinas',
    'closets'     => 'cajoneras',
    'puertas'     => 'puertas',
    'camas'       => 'camas',
    'escritorios' => 'escritorios',
    'especiales'  => 'mesas'
];
?>

<!-- Estilos específicos de la Galería -->
<link rel="stylesheet" href="assets/css/galeria.css">

<div class="container py-4">

    <!-- Encabezado de la Galería -->
    <header class="galeria-hero">
        <span class="galeria-badge-pill">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="m9 12 2 2 4-4"></path>
            </svg>
            <span>Hecho a Mano en Nuestro Taller</span>
        </span>
        <h1 class="galeria-main-title">Muestrario de Trabajos Terminados</h1>
        <p class="galeria-main-subtitle">
            Cada mueble es elaborado con maderas seleccionadas, ensambles precisos y acabados duraderos pensados para tu hogar u oficina.
        </p>
    </header>

    <!-- Barra de Filtros por Categoría -->
    <nav class="galeria-filter-bar" aria-label="Filtro de trabajos">
        <button type="button" class="galeria-filter-btn active" data-filter="todos">Todos</button>
        <button type="button" class="galeria-filter-btn" data-filter="mesas">Mesas &amp; Comedores</button>
        <button type="button" class="galeria-filter-btn" data-filter="cocinas">Cocinas &amp; Alacenas</button>
        <button type="button" class="galeria-filter-btn" data-filter="closets">Clósets &amp; Cajoneras</button>
        <button type="button" class="galeria-filter-btn" data-filter="puertas">Puertas &amp; Portones</button>
        <button type="button" class="galeria-filter-btn" data-filter="camas">Bases de Cama</button>
        <button type="button" class="galeria-filter-btn" data-filter="escritorios">Estudios &amp; Escritorios</button>
        <button type="button" class="galeria-filter-btn" data-filter="especiales">Proyectos Especiales</button>
    </nav>

    <!-- Grid de Trabajos -->
    <?php if (empty($trabajos)): ?>
        <div class="text-center py-5">
            <div class="p-5 rounded-4 bg-dark border border-secondary border-opacity-25 max-w-lg mx-auto" style="max-width: 500px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-warning mb-3">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <h3 class="h5 text-white fw-bold mb-2">Próximamente fotos de nuevos trabajos</h3>
                <p class="small text-secondary mb-4">Estamos preparando las fotografías de nuestras últimas entregas en obra.</p>
                <a href="cotizaciones.php" class="btn btn-warning rounded-pill px-4 fw-bold">Cotizar tu propio mueble</a>
            </div>
        </div>
    <?php else: ?>
        <section class="galeria-grid" id="galeriaGrid" aria-label="Catálogo de trabajos hechos">
            <?php foreach ($trabajos as $item): 
                $cat = $item['categoria'] ?? 'especiales';
                $quoteCat = $map_cotizador[$cat] ?? 'mesas';
            ?>
                <article 
                    class="galeria-card" 
                    data-category="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"
                    data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                    data-foto="<?= htmlspecialchars($item['foto'], ENT_QUOTES, 'UTF-8') ?>"
                    data-madera="<?= htmlspecialchars($item['madera'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-categoria-label="<?= htmlspecialchars($item['categoria_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-desc="<?= htmlspecialchars($item['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-quote-url="cotizaciones.php?categoria=<?= urlencode($quoteCat) ?>"
                >
                    <div class="galeria-img-wrap">
                        <img src="<?= htmlspecialchars($item['foto'], ENT_QUOTES, 'UTF-8') ?>" 
                             alt="<?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>" 
                             loading="lazy">
                        <span class="galeria-badge-cat">
                            <?= htmlspecialchars($item['categoria_label'] ?? ucfirst($cat), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div class="galeria-card-body">
                        <h3 class="galeria-card-title"><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                        
                        <?php if (!empty($item['madera'])): ?>
                            <div class="galeria-card-wood">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                                <span><?= htmlspecialchars($item['madera'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($item['descripcion'])): ?>
                            <p class="galeria-card-desc"><?= htmlspecialchars($item['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>

                        <div class="galeria-card-footer">
                            <span class="btn-galeria-zoom">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    <line x1="11" y1="8" x2="11" y2="14"></line>
                                    <line x1="8" y1="11" x2="14" y2="11"></line>
                                </svg>
                                <span>Ampliar</span>
                            </span>

                            <a href="cotizaciones.php?categoria=<?= urlencode($quoteCat) ?>" 
                               class="btn-galeria-quote" 
                               onclick="event.stopPropagation();">
                                Cotizar Similar &rarr;
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <!-- Banner Inferior de Llamado a la Acción -->
    <div class="p-4 p-md-5 rounded-4 border border-secondary border-opacity-25 text-center mt-5" style="background: #181513;">
        <h2 class="h4 fw-bold text-white mb-2">¿Tienes una idea o diseño en mente?</h2>
        <p class="text-secondary mb-4 mx-auto" style="max-width: 600px;">
            Platícanos las medidas de tu espacio o envíanos una foto de referencia. Te asesoramos con la madera y el acabado ideal.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="cotizaciones.php" class="btn btn-warning rounded-pill px-4 py-2 fw-bold">
                Cotizar Proyecto en Línea
            </a>
            <a href="citas.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 text-white">
                Agendar Visita para Medidas
            </a>
        </div>
    </div>

</div>

<!-- Modal / Lightbox de Visualización de Foto -->
<div class="modal fade" id="modalFotoTrabajo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content galeria-modal-content">
            <div class="galeria-modal-header d-flex justify-content-between align-items-center">
                <span class="badge bg-warning text-dark px-3 py-1 fw-bold rounded-pill" id="modalCatLabel">Trabajo de Taller</span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <img src="" alt="" id="modalImg" class="galeria-modal-img">
            <div class="galeria-modal-body">
                <h3 class="galeria-modal-title" id="modalTitulo"></h3>
                <div class="galeria-modal-wood" id="modalMadera"></div>
                <p class="galeria-modal-desc" id="modalDesc"></p>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                    <a href="cotizaciones.php" id="modalQuoteBtn" class="btn btn-warning rounded-pill px-4 fw-bold">
                        Cotizar una pieza similar &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script interactivo de Filtros y Modal -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Filtrado interactivo por categoría
    const filterBtns = document.querySelectorAll('.galeria-filter-btn');
    const cards = document.querySelectorAll('.galeria-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-filter');

            cards.forEach(card => {
                const cat = card.getAttribute('data-category');
                if (filter === 'todos' || cat === filter) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Activar filtro desde parámetro en la URL (?categoria=...)
    const urlParams = new URLSearchParams(window.location.search);
    const catParam = urlParams.get('categoria');
    if (catParam) {
        const matchingBtn = document.querySelector(`.galeria-filter-btn[data-filter="${catParam}"]`);
        if (matchingBtn) {
            matchingBtn.click();
        }
    }

    // 2. Apertura de Lightbox / Modal
    const modalEl = document.getElementById('modalFotoTrabajo');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modalEl);
        const modalImg = document.getElementById('modalImg');
        const modalTitulo = document.getElementById('modalTitulo');
        const modalCatLabel = document.getElementById('modalCatLabel');
        const modalMadera = document.getElementById('modalMadera');
        const modalDesc = document.getElementById('modalDesc');
        const modalQuoteBtn = document.getElementById('modalQuoteBtn');

        cards.forEach(card => {
            card.addEventListener('click', () => {
                const foto = card.getAttribute('data-foto');
                const titulo = card.getAttribute('data-titulo');
                const catLabel = card.getAttribute('data-categoria-label');
                const madera = card.getAttribute('data-madera');
                const desc = card.getAttribute('data-desc');
                const quoteUrl = card.getAttribute('data-quote-url');

                modalImg.src = foto;
                modalImg.alt = titulo;
                modalTitulo.textContent = titulo;
                modalCatLabel.textContent = catLabel || 'Mueble de Taller';
                modalMadera.textContent = madera ? `🪵 Madera: ${madera}` : '';
                modalDesc.textContent = desc || '';
                if (quoteUrl) {
                    modalQuoteBtn.href = quoteUrl;
                }

                bsModal.show();
            });
        });
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>
