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
                <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 mb-3">
                    Privacidad y Confidencialidad &bull; TallerTech
                </span>
                <h1 class="display-6 fw-bold text-white mb-3">Aviso de Privacidad</h1>
                <p class="text-secondary small">Cumplimiento con la Ley Federal de Protección de Datos Personales</p>
            </div>

            <div class="card bg-dark border border-secondary border-opacity-25 rounded-4 p-4 p-md-5 text-secondary" style="line-height: 1.8;">

                <h2 class="h5 fw-bold text-white mb-3">1. Identidad y Domicilio del Responsable</h2>
                <p class="mb-4">
                    <strong>TallerTech S.A.S. de C.V.</strong>, con domicilio en Av. Los Artesanos #450, Col. Centro, CP 44100, es responsable del uso y protección de sus datos personales conforme a los principios de licitud, consentimiento e información.
                </p>

                <h2 class="h5 fw-bold text-white mb-3">2. Datos Personales Recabados</h2>
                <p class="mb-4">
                    Para las finalidades señaladas en el presente aviso, recabamos: nombre completo, correo electrónico, número de teléfono o WhatsApp, dirección física de entrega y fotografías, croquis o bocetos técnicos adjuntados para la fabricación de sus muebles.
                </p>

                <h2 class="h5 fw-bold text-white mb-3">3. Finalidades del Tratamiento</h2>
                <p class="mb-4">
                    Sus datos son tratados con el propósito exclusivo de: (a) generar órdenes de cotización y pedidos de carpintería, (b) agendar visitas técnicas de medición a domicilio, (c) mantener el canal de comunicación y notificaciones por WhatsApp o correo sobre el avance de sus muebles, y (d) emitir comprobantes de anticipo y entrega.
                </p>

                <h2 class="h5 fw-bold text-white mb-3">4. Confidencialidad y No Transferencia</h2>
                <p class="mb-4">
                    TallerTech no vende, renta ni transfiere sus datos personales ni sus planos arquitectónicos o bocetos a terceros con fines publicitarios. Toda información técnica cargada en la plataforma se almacena en servidores con cifrado TLS/SSL.
                </p>

                <h2 class="h5 fw-bold text-white mb-3">5. Derechos ARCO y Contacto</h2>
                <p class="mb-0">
                    Usted tiene derecho a conocer qué datos personales tenemos de usted, para qué los usamos y las condiciones de su tratamiento (Acceso, Rectificación, Cancelación y Oposición). Para ejercer cualquiera de estos derechos, puede escribirnos a <strong>contacto@tallertech.com</strong>.
                </p>

            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
