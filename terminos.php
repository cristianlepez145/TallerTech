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
                <span class="badge rounded-pill px-3 py-2 mb-3" style="background: rgba(200, 138, 75, 0.12); color: #c88a4b; border: 1px solid rgba(200, 138, 75, 0.3);">
                    Marco Legal &bull; TallerTech
                </span>
                <h1 class="display-6 fw-bold mb-3" style="color: #f2ede4;">Términos y Condiciones del Servicio</h1>
                <p class="small" style="color: #80776e;">Última actualización: Noviembre 2026</p>
            </div>

            <div class="rounded-4 p-4 p-md-5" style="background: #1c1917; border: 1px solid #38322b; line-height: 1.8; color: #b5ada4;">

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">1. Naturaleza de las Cotizaciones Preliminares</h2>
                <p class="mb-4">
                    Todo monto generado a través del cotizador en línea representa un estimado inicial. El presupuesto final se confirma por parte del carpintero tras verificar medidas exactas y disponibilidad de madera.
                </p>

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">2. Esquema de Anticipo (60% / 40%)</h2>
                <p class="mb-4">
                    Para iniciar la compra de madera y los trabajos en taller, se requiere un <strong>anticipo del 60%</strong> del costo aprobado. El <strong>40% restante</strong> se liquida contra entrega e instalación de tus muebles.
                </p>

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">3. Variaciones Naturales de la Madera</h2>
                <p class="mb-4">
                    Al trabajar con madera sólida natural (Roble, Parota, Nogal, Cedro y Pino), cada pieza tiene tonalidades, vetas y nudos únicos propios de la naturaleza del material.
                </p>

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">4. Citas y Visitas de Medición</h2>
                <p class="mb-4">
                    Las visitas se programan en bloques de aproximadamente 1 hora. Si necesitas cambiar o cancelar tu cita, te pedimos avisarnos con al menos 24 horas de antelación.
                </p>

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">5. Garantía Artesanal</h2>
                <p class="mb-0">
                    Ofrecemos 12 meses de garantía estructural en ensambles de madera y hasta 2 años en herrajes de cierre suave y correderas telescópicas bajo condiciones de uso normal.
                </p>

            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
