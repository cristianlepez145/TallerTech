/**
 * TallerTech - Reseñas de la Comunidad JS
 * Selector interactivo de estrellas, vista previa y validación del modal
 */

document.addEventListener('DOMContentLoaded', () => {
    const starButtons = document.querySelectorAll('.star-btn');
    const calificacionInput = document.getElementById('calificacionInput');
    const starRatingLabel = document.getElementById('starRatingLabel');
    const formResena = document.getElementById('formNuevaResena');

    const LABELS_ESTRELLAS = {
        1: '1 estrella - Experiencia insatisfactoria',
        2: '2 estrellas - Regular, detalles por mejorar',
        3: '3 estrellas - Bueno, acabados aceptables',
        4: '4 estrellas - Muy bueno, excelente calidad artesanal',
        5: '5 estrellas - ¡Excelente! Superó mis expectativas'
    };

    let puntuacionActual = calificacionInput ? parseInt(calificacionInput.value, 10) || 5 : 5;

    function pintarEstrellas(hastaValor, clase = 'is-active') {
        starButtons.forEach(btn => {
            const valor = parseInt(btn.getAttribute('data-value'), 10);
            if (valor <= hastaValor) {
                btn.classList.add(clase);
            } else {
                btn.classList.remove(clase);
            }
        });
    }

    function actualizarLabel(valor) {
        if (starRatingLabel && LABELS_ESTRELLAS[valor]) {
            starRatingLabel.textContent = LABELS_ESTRELLAS[valor];
        }
    }

    if (starButtons.length > 0) {
        // Inicializar con la puntuación por defecto
        pintarEstrellas(puntuacionActual, 'is-active');
        actualizarLabel(puntuacionActual);

        starButtons.forEach(btn => {
            // Hover: iluminar hasta la estrella actual
            btn.addEventListener('mouseenter', () => {
                const hoverVal = parseInt(btn.getAttribute('data-value'), 10);
                pintarEstrellas(hoverVal, 'is-hover');
                actualizarLabel(hoverVal);
            });

            // Mouseleave: restaurar la puntuación seleccionada
            btn.addEventListener('mouseleave', () => {
                starButtons.forEach(b => b.classList.remove('is-hover'));
                pintarEstrellas(puntuacionActual, 'is-active');
                actualizarLabel(puntuacionActual);
            });

            // Click: fijar puntuación
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                puntuacionActual = parseInt(btn.getAttribute('data-value'), 10);
                if (calificacionInput) {
                    calificacionInput.value = puntuacionActual;
                }
                pintarEstrellas(puntuacionActual, 'is-active');
                actualizarLabel(puntuacionActual);
            });
        });
    }

    // Validación básica del formulario antes de enviar
    if (formResena) {
        formResena.addEventListener('submit', (e) => {
            const textarea = document.getElementById('comentarioResena');
            if (textarea && textarea.value.trim().length < 10) {
                e.preventDefault();
                alert('Por favor comparte un testimonio más detallado (mínimo 10 caracteres).');
                textarea.focus();
            }
        });
    }
});

