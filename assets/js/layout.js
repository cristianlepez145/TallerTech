/**
 * TallerTech - Layout JS
 * Controlador para navegación responsive y menú móvil
 */

document.addEventListener('DOMContentLoaded', () => {
    const mobileToggle = document.querySelector('.mobile-toggle');
    const mobileDrawer = document.querySelector('.mobile-nav-drawer');

    if (mobileToggle && mobileDrawer) {
        mobileToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = mobileDrawer.classList.toggle('is-open');
            mobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Cerrar menú al presionar tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mobileDrawer.classList.contains('is-open')) {
                mobileDrawer.classList.remove('is-open');
                mobileToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Cerrar menú al hacer clic fuera del header
        document.addEventListener('click', (e) => {
            if (!mobileDrawer.contains(e.target) && !mobileToggle.contains(e.target)) {
                if (mobileDrawer.classList.contains('is-open')) {
                    mobileDrawer.classList.remove('is-open');
                    mobileToggle.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }
});

