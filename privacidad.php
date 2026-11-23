<?php
/**
 * TallerTech - Aviso de Privacidad
 * Vista Dark Mode de protección de datos personales
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$titulo_pagina = 'Aviso de Privacidad | TallerTech';
$pagina_actual = 'privacidad.php';

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="text-center mb-5">
                <span class="badge rounded-pill px-3 py-2 mb-3" style="background: rgba(200, 138, 75, 0.12); color: #c88a4b; border: 1px solid rgba(200, 138, 75, 0.3);">
                    Privacidad y Confidencialidad &bull; TallerTech
                </span>
                <h1 class="display-6 fw-bold mb-3" style="color: #f2ede4;">Aviso de Privacidad</h1>
                <p class="small" style="color: #80776e;">Protección de datos conforme a la legislación aplicable</p>
            </div>

            <div class="rounded-4 p-4 p-md-5" style="background: #1c1917; border: 1px solid #38322b; line-height: 1.8; color: #b5ada4;">

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">1. Identidad y Domicilio</h2>
                <p class="mb-4">
                    <strong>TallerTech Carpintería</strong>, con domicilio en Av. Los Artesanos #450, Col. Centro, CP 44100, es responsable del cuidado y confidencialidad de tus datos personales.
                </p>

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">2. Datos que Solicitamos</h2>
                <p class="mb-4">
                    Para preparar tus presupuestos y coordinar citas solicitamos: nombre, correo electrónico, teléfono/WhatsApp, dirección para visitas o entregas, y fotografías o croquis de referencia de tus espacios.
                </p>

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">3. Uso de la Información</h2>
                <p class="mb-4">
                    Tus datos se usan exclusivamente para: (a) preparar tus cotizaciones y proyectos de carpintería, (b) coordinar visitas de medición en domicilio o taller, (c) mantenerte al tanto del avance de tu mueble, y (d) coordinar la entrega e instalación.
                </p>

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">4. Confidencialidad</h2>
                <p class="mb-4">
                    No compartimos ni vendemos tu información ni tus imágenes o planos a terceros con fines de publicidad. La información técnica se resguarda de forma segura bajo cifrado de datos.
                </p>

                <h2 class="h5 fw-bold mb-3" style="color: #f2ede4;">5. Derechos ARCO y Dudas</h2>
                <p class="mb-0">
                    Puedes consultar, actualizar o solicitar la baja de tus datos en cualquier momento enviándonos un correo a <strong>contacto@tallertech.com</strong>.
                </p>

            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
