/**
 * TallerTech - Mis Pedidos y Proyectos JS
 * Controlador de filtros interactivos por estado y modal de detalles de proyecto
 */

document.addEventListener('DOMContentLoaded', () => {
    // Referencias de filtrado
    const filterButtons = document.querySelectorAll('.btn-filter-pill');
    const orderCards = document.querySelectorAll('.order-card');

    // 1. Filtrado dinámico por estado de pedido
    filterButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const filter = btn.getAttribute('data-filter');

            // Actualizar clase activa en los botones de filtro
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filtrar tarjetas
            orderCards.forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                if (filter === 'all' || cardStatus === filter) {
                    card.style.display = 'block';
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.style.transition = 'opacity 0.25s ease';
                        card.style.opacity = '1';
                    }, 10);
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // 2. Modal de Detalles Técnicos y Croquis
    const btnVerDetalles = document.querySelectorAll('.btn-open-details');
    const modalTitle = document.getElementById('detailsModalTitle');
    const modalCode = document.getElementById('detailsModalCode');
    const modalSpecs = document.getElementById('detailsModalSpecs');
    const modalTotal = document.getElementById('detailsModalTotal');
    const modalAnticipo = document.getElementById('detailsModalAnticipo');
    const modalSaldo = document.getElementById('detailsModalSaldo');
    const modalNotes = document.getElementById('detailsModalNotes');
    const modalImg = document.getElementById('detailsModalImg');

    btnVerDetalles.forEach(btn => {
        btn.addEventListener('click', () => {
            if (modalTitle) modalTitle.textContent = btn.getAttribute('data-title') || 'Detalles del Proyecto';
            if (modalCode) modalCode.textContent = btn.getAttribute('data-code') || '';
            if (modalSpecs) modalSpecs.textContent = btn.getAttribute('data-specs') || 'Especificaciones a medida';
            if (modalTotal) modalTotal.textContent = btn.getAttribute('data-total') || '$0.00 MXN';
            if (modalAnticipo) modalAnticipo.textContent = btn.getAttribute('data-anticipo') || '$0.00 MXN';
            if (modalSaldo) modalSaldo.textContent = btn.getAttribute('data-saldo') || '$0.00 MXN';
            if (modalNotes) modalNotes.textContent = btn.getAttribute('data-notes') || 'Sin notas técnicas registradas.';
            if (modalImg) {
                const imgSrc = btn.getAttribute('data-image');
                if (imgSrc) {
                    modalImg.src = imgSrc;
                    modalImg.style.display = 'block';
                } else {
                    modalImg.style.display = 'none';
                }
            }
        });
    });

    // 3. Preparación del Modal de Aprobación de Anticipo (60%)
    const btnAprobarList = document.querySelectorAll('.btn-trigger-anticipo');
    const inputCotizacionId = document.getElementById('anticipoCotizacionId');
    const labelAnticipoMonto = document.getElementById('anticipoMontoLabel');
    const labelProyectoAnticipo = document.getElementById('anticipoProyectoLabel');

    btnAprobarList.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const monto = btn.getAttribute('data-anticipo-val');
            const titulo = btn.getAttribute('data-title');

            if (inputCotizacionId) inputCotizacionId.value = id;
            if (labelAnticipoMonto) labelAnticipoMonto.textContent = monto;
            if (labelProyectoAnticipo) labelProyectoAnticipo.textContent = titulo;
        });
    });
});

