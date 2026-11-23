<?php
/**
 * TallerTech - Preguntas Frecuentes (FAQ)
 * Vista informativa Dark Mode con acordeón de dudas comunes
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$titulo_pagina = 'Preguntas Frecuentes | TallerTech';
$pagina_actual = 'preguntas-frecuentes.php';

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <!-- Encabezado -->
            <div class="text-center mb-5">
                <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 mb-3">
                    Centro de Ayuda &bull; TallerTech
                </span>
                <h1 class="display-6 fw-bold text-white mb-3">Preguntas Frecuentes</h1>
                <p class="text-secondary lead mx-auto" style="max-width: 600px;">
                    Todo lo que necesitas saber sobre nuestras cotizaciones paramétricas, visitas de medición, esquema de cobro y tiempos en taller.
                </p>
            </div>

            <!-- Acordeón de FAQs -->
            <div class="accordion accordion-flush" id="faqAccordion">

                <!-- FAQ 1 -->
                <div class="accordion-item bg-dark border border-secondary border-opacity-25 rounded-4 mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button bg-dark text-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            1. ¿Cómo se calcula el costo estimado en el Cotizador en línea?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary" style="background: #181818;">
                            Nuestro cotizador utiliza un modelo paramétrico que calcula los metros cuadrados de desarrollo a partir de las dimensiones de tu espacio (alto, ancho y profundidad), aplicando multiplicadores según el tipo de madera o melamina seleccionada, costos de insumos de sellado y horas hombre de maquinado en taller.
                        </div>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="accordion-item bg-dark border border-secondary border-opacity-25 rounded-4 mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed bg-dark text-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            2. ¿Por qué es obligatorio el anticipo del 60%?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary" style="background: #181818;">
                            Dado que cada mueble se fabrica 100% a la medida de tu espacio, el anticipo del 60% se destina de inmediato a la compra de madera estufada, tableros seleccionados y herrajes importados de cierre suave. El 40% restante se liquida únicamente contra entrega e instalación a tu entera satisfacción.
                        </div>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="accordion-item bg-dark border border-secondary border-opacity-25 rounded-4 mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed bg-dark text-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            3. ¿Cómo funciona la cita de medición en domicilio o taller?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary" style="background: #181818;">
                            Puedes agendar una visita técnica desde el módulo de Citas. Entre semana las visitas a domicilio son vespertinas (a partir de las 6:00 PM) y los fines de semana de 9:00 AM a 6:00 PM. Nuestro maestro carpintero revisa desniveles de muros, instalaciones eléctricas y corrobora tolerancias milimétricas antes del corte.
                        </div>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="accordion-item bg-dark border border-secondary border-opacity-25 rounded-4 mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed bg-dark text-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            4. ¿Cuánto tiempo tarda la fabricación de un mueble?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary" style="background: #181818;">
                            El tiempo estándar de fabricación oscila entre 2 y 4 semanas dependiendo de la complejidad y el acabado (lacas poliuretánicas o aceites orgánicos). En tu panel de pedidos podrás consultar el avance en tiempo real a través del Stepper de 3 etapas.
                        </div>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="accordion-item bg-dark border border-secondary border-opacity-25 rounded-4 mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed bg-dark text-white fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                            5. ¿Qué garantía tienen los muebles fabricados por TallerTech?
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-secondary" style="background: #181818;">
                            Ofrecemos 12 meses de garantía contra defectos estructurales en ensambles de madera y hasta 2 años en herrajes de cierre suave y correderas de extensión telescópica.
                        </div>
                    </div>
                </div>

            </div>

            <!-- Llamada a la Acción -->
            <div class="p-4 p-md-5 rounded-4 mt-5 text-center" style="background: #1c1c1c; border: 1px solid #2d2d2d;">
                <h3 class="h4 fw-bold text-white mb-2">¿Tienes una consulta específica sobre tu proyecto?</h3>
                <p class="text-secondary mb-4">Agenda una cita técnica de medición o calcula tu presupuesto preliminar en segundos.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="cotizaciones.php" class="btn btn-warning rounded-pill px-4 py-2 fw-bold">
                        Ir al Cotizador en Línea ↗
                    </a>
                    <a href="citas.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                        Agendar Cita de Medición
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
