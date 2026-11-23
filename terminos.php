<?php
/**
 * TallerTech - Términos y Condiciones del Servicio
 * Vista contractual Dark Mode con las reglas de negocio
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$titulo_pagina = 'Términos y Condiciones | TallerTech';
$pagina_actual = 'terminos.php';

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="text-center mb-5">
                <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 mb-3">
                    Marco Legal &bull; TallerTech
                </span>
                <h1 class="display-6 fw-bold text-white mb-3">Términos y Condiciones del Servicio</h1>
                <p class="text-secondary small">Última actualización: Noviembre 2026</p>
            </div>

            <div class="card bg-dark border border-secondary border-opacity-25 rounded-4 p-4 p-md-5 text-secondary" style="line-height: 1.8;">

                <h2 class="h5 fw-bold text-white mb-3">1. Naturaleza de las Cotizaciones Preliminares</h2>
                <p class="mb-4">
                    Todo monto generado a través de nuestro cotizador automático en línea representa un estimado paramétrico preliminar. Todo presupuesto está sujeto a confirmación técnica definitiva por parte del maestro carpintero tras corroborar medidas en obra y disponibilidad de lotes de madera.
                </p>

                <h2 class="h5 fw-bold text-white mb-3">2. Esquema de Cobro y Anticipo Obligatorio (60% / 40%)</h2>
                <p class="mb-4">
                    Para iniciar los procesos de compra de madera, cortes y ensambles en taller, es indispensable que el cliente autorice la cotización formal y cubra un <strong>anticipo obligatorio del 60%</strong> del costo total aprobado. El <strong>40% restante</strong> se liquida en su totalidad al momento de la entrega o conclusión de la instalación en domicilio.
                </p>

                <h2 class="h5 fw-bold text-white mb-3">3. Variaciones Naturales de la Madera</h2>
                <p class="mb-4">
                    Al tratarse de maderas macizas naturales (Roble, Parota, Nogal, Cedro y Pino), el cliente acepta que existen variaciones inherentes en tonalidades, vetas, nudos y densidades entre diferentes tablones, lo cual constituye el valor estético y artesanal único de cada pieza de autor.
                </p>

                <h2 class="h5 fw-bold text-white mb-3">4. Política de Citas y Visitas de Medición</h2>
                <p class="mb-4">
                    Las citas técnicas agendadas mediante nuestra plataforma se reservan por bloques exclusivos de 1 hora. En caso de requerir cancelación o reprogramación, el cliente deberá gestionarlo con al menos 24 horas de anticipación.
                </p>

                <h2 class="h5 fw-bold text-white mb-3">5. Garantías y Reclamaciones</h2>
                <p class="mb-0">
                    TallerTech ofrece 12 meses de garantía estructural sobre ensambles y 2 años sobre herrajes importados. La garantía no cubre daños por humedad extrema no declarada, exposición directa al fuego o maltrato físico ajeno al uso habitacional recomendado.
                </p>

            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
