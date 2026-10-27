/**
 * TallerTech - Catálogo JS
 * Filtrado dinámico por píldoras y Modal Quick View de proyectos
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Filtrado de Categorías
    const filterButtons = document.querySelectorAll('.filter-pill-btn');
    const projectCards = document.querySelectorAll('.catalogo-card');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const filterValue = btn.dataset.filter;

            // Actualizar botón activo
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filtrar tarjetas con transición suave
            projectCards.forEach(card => {
                const cardCat = card.dataset.category;

                if (filterValue === 'todos' || cardCat === filterValue) {
                    card.style.display = 'flex';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 200);
                }
            });
        });
    });

    // 2. Modal de Vista Rápida (Quick View)
    const modalBackdrop = document.getElementById('catalogoModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalImg = document.getElementById('modalImg');
    const modalBadge = document.getElementById('modalBadge');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalMadera = document.getElementById('modalMadera');
    const modalAcabado = document.getElementById('modalAcabado');
    const modalTiempo = document.getElementById('modalTiempo');
    const modalBtnQuote = document.getElementById('modalBtnQuote');

    function openModal(card) {
        if (!modalBackdrop) return;

        const data = card.dataset;

        if (modalImg) {
            modalImg.src = data.img || '';
            modalImg.alt = data.title || 'Proyecto TallerTech';
        }
        if (modalBadge) modalBadge.textContent = data.catLabel || 'Proyecto';
        if (modalTitle) modalTitle.textContent = data.title || 'Mobiliario';
        if (modalDesc) modalDesc.textContent = data.desc || '';
        if (modalMadera) modalMadera.textContent = data.madera || 'Madera seleccionada';
        if (modalAcabado) modalAcabado.textContent = data.acabado || 'Poliuretano mate';
        if (modalTiempo) modalTiempo.textContent = data.tiempo || '2 a 3 semanas';
        if (modalBtnQuote) {
            modalBtnQuote.href = data.quoteUrl || 'cotizaciones.php';
        }

        modalBackdrop.classList.add('is-open');
        document.body.style.overflow = 'hidden'; // Prevenir scroll de fondo
    }

    function closeModal() {
        if (!modalBackdrop) return;
        modalBackdrop.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    // Escuchador de clic en cada tarjeta para abrir el modal
    projectCards.forEach(card => {
        card.addEventListener('click', (e) => {
            // Evitar conflictos si hace clic en enlaces anidados
            if (e.target.closest('a')) {
                e.stopPropagation();
            }
            openModal(card);
        });
    });

    // Cerrar modal al hacer clic en botón de cierre
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }

    // Cerrar modal al hacer clic en el fondo oscuro
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', (e) => {
            if (e.target === modalBackdrop) {
                closeModal();
            }
        });
    }

    // Cerrar modal al presionar tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalBackdrop && modalBackdrop.classList.contains('is-open')) {
            closeModal();
        }
    });
});

