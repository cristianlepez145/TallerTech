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
                <span class="badge rounded-pill px-3 py-2 mb-3" style="background: rgba(200, 138, 75, 0.12); color: #c88a4b; border: 1px solid rgba(200, 138, 75, 0.3);">
                    Preguntas Frecuentes &bull; TallerTech
                </span>
                <h1 class="display-6 fw-bold mb-3" style="color: #f2ede4;">Preguntas Frecuentes</h1>
                <p class="lead mx-auto" style="max-width: 600px; color: #b5ada4;">
                    Dudas comunes sobre presupuestos, anticipos, visitas de medición y tiempos de entrega.
                </p>
            </div>

            <!-- Acordeón de FAQs -->
            <div class="accordion accordion-flush" id="faqAccordion">

                <!-- FAQ 1 -->
                <div class="accordion-item mb-3 rounded-4 overflow-hidden" style="background: #1c1917; border: 1px solid #38322b;">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="background: #1c1917; color: #f2ede4;">
                            1. ¿Cómo se calcula el costo estimado en el cotizador?
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="background: #221f1c; color: #b5ada4;">
                            Calculamos el costo considerando las medidas de tu espacio (alto, ancho y profundidad), el tipo de madera seleccionado, los herrajes y las horas de trabajo en banco del taller.
                        </div>
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="accordion-item mb-3 rounded-4 overflow-hidden" style="background: #1c1917; border: 1px solid #38322b;">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" style="background: #1c1917; color: #f2ede4;">
                            2. ¿Por qué se solicita un anticipo del 60%?
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="background: #221f1c; color: #b5ada4;">
                            Como cada proyecto se hace a la medida, el 60% inicial se usa para comprar la madera selecta y los herrajes de tu mueble. El 40% restante se liquida al entregar e instalar.
                        </div>
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="accordion-item mb-3 rounded-4 overflow-hidden" style="background: #1c1917; border: 1px solid #38322b;">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" style="background: #1c1917; color: #f2ede4;">
                            3. ¿Cómo funciona la cita de medición?
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="background: #221f1c; color: #b5ada4;">
                            Revisamos desniveles de muros, espacios de paso y tomamos medidas exactas para que el mueble ensamble a la perfección. También puedes visitarnos en el taller para ver muestras de madera física.
                        </div>
                    </div>
                </div>

                <!-- FAQ 4 -->
                <div class="accordion-item mb-3 rounded-4 overflow-hidden" style="background: #1c1917; border: 1px solid #38322b;">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" style="background: #1c1917; color: #f2ede4;">
                            4. ¿Cuánto tiempo tarda en fabricarse un mueble?
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="background: #221f1c; color: #b5ada4;">
                            Generalmente entre 2 y 4 semanas según el tamaño del proyecto y el tipo de barniz o aceite. Puedes ver el avance de tu mueble paso a paso en la sección de pedidos.
                        </div>
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="accordion-item mb-3 rounded-4 overflow-hidden" style="background: #1c1917; border: 1px solid #38322b;">
                    <h2 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive" style="background: #1c1917; color: #f2ede4;">
                            5. ¿Qué garantía tienen los trabajos?
                        </button>
                    </h2>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                        <div class="accordion-body" style="background: #221f1c; color: #b5ada4;">
                            Garantizamos la solidez estructural de los ensambles en madera y el correcto funcionamiento de correderas y bisagras. Respondemos directamente en taller por nuestro trabajo.
                        </div>
                    </div>
                </div>

            </div>

            <!-- Llamada a la Acción -->
            <div class="p-4 p-md-5 rounded-4 mt-5 text-center" style="background: #1c1917; border: 1px solid #38322b;">
                <h3 class="h4 fw-bold mb-2" style="color: #f2ede4;">¿Tienes alguna duda sobre tu mueble?</h3>
                <p class="mb-4" style="color: #b5ada4;">Cotiza tu idea sin compromiso o agenda una visita con el carpintero.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="cotizaciones.php" class="btn rounded-pill px-4 py-2 fw-bold text-dark text-decoration-none" style="background-color: #c88a4b;">
                        Calcular Presupuesto ↗
                    </a>
                    <a href="citas.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold text-decoration-none" style="border-color: #54493f; color: #f2ede4;">
                        Agendar Cita con el Carpintero
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
