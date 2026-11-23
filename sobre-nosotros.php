<?php
/**
 * TallerTech - Sobre Nosotros
 * Vista institucional Dark Mode sobre la historia, valores y taller
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$titulo_pagina = 'Sobre Nosotros | TallerTech';
$pagina_actual = 'sobre-nosotros.php';

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Banner Hero -->
            <div class="text-center mb-5">
                <span class="badge rounded-pill px-3 py-2 mb-3" style="background: rgba(200, 138, 75, 0.12); color: #c88a4b; border: 1px solid rgba(200, 138, 75, 0.3);">
                    Nuestra Historia &bull; Pasión Artesanal
                </span>
                <h1 class="display-5 fw-bold mb-3" style="color: #f2ede4;">Carpintería Artesanal con Dedicación y Oficio</h1>
                <p class="lead mx-auto" style="max-width: 680px; color: #b5ada4;">
                    Somos un taller familiar enfocado en fabricar muebles a la medida con maderas selectas y ensambles tradicionales hechos para durar generaciones.
                </p>
            </div>

            <!-- Grid de Imagen y Filosofía -->
            <div class="row g-4 align-items-center mb-5">
                <div class="col-md-6">
                    <img src="https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80" alt="Carpintero en taller artesanal" class="img-fluid rounded-4 shadow-lg border w-100" style="max-height: 380px; object-fit: cover; border-color: #38322b !important;">
                </div>
                <div class="col-md-6">
                    <h2 class="h3 fw-bold mb-3" style="color: #f2ede4;">Nuestra Filosofía</h2>
                    <p class="mb-3" style="color: #b5ada4;">
                        Nacimos como un taller familiar comprometido con el rescate del oficio del carpintero. Trabajamos maderas estufadas de primera selección (Roble, Nogal, Parota y Cedro) cuidando cada ensamble de caja y espiga.
                    </p>
                    <p class="mb-0" style="color: #b5ada4;">
                        Cada proyecto que entra al taller se trabaja de forma personalizada, con comunicación directa con el carpintero para resolver cualquier detalle de tu espacio.
                    </p>
                </div>
            </div>

            <!-- 3 Pilares -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100" style="background: #1c1917; border: 1px solid #38322b;">
                        <div class="mb-3" style="color: #c88a4b;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2L6 8h3.5l-4 6h4l-3.5 5h12l-3.5-5h4l-4-6H18L12 2z"/>
                            </svg>
                        </div>
                        <h3 class="h5 fw-bold mb-2" style="color: #f2ede4;">Madera Selecta</h3>
                        <p class="small mb-0" style="color: #b5ada4;">Maderas macizas con secado en estufa para evitar deformaciones con los cambios de clima.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100" style="background: #1c1917; border: 1px solid #38322b;">
                        <div class="mb-3" style="color: #c88a4b;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="m4.93 4.93 4.24 4.24"/>
                                <path d="m14.83 9.17 4.24-4.24"/>
                                <path d="m14.83 14.83 4.24 4.24"/>
                                <path d="m9.17 14.83-4.24 4.24"/>
                            </svg>
                        </div>
                        <h3 class="h5 fw-bold mb-2" style="color: #f2ede4;">Ensamble Tradicional</h3>
                        <p class="small mb-0" style="color: #b5ada4;">Cortes precisos, uniones firmes y acabados pulidos a mano que garantizan resistencia.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100" style="background: #1c1917; border: 1px solid #38322b;">
                        <div class="mb-3" style="color: #c88a4b;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <h3 class="h5 fw-bold mb-2" style="color: #f2ede4;">Atención y Garantía</h3>
                        <p class="small mb-0" style="color: #b5ada4;">Compromiso con entrega acordada, instalación cuidadosa y soporte directo en tu taller local.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
