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
                <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 mb-3">
                    Nuestra Historia &bull; Pasión Artesanal
                </span>
                <h1 class="display-5 fw-bold text-white mb-3">Maestría en Madera &amp; Precisión Tecnológica</h1>
                <p class="text-secondary lead mx-auto" style="max-width: 680px;">
                    En TallerTech fusionamos las técnicas tradicionales de la ebanistería con el cálculo paramétrico moderno para crear muebles de autor que perduran por generaciones.
                </p>
            </div>

            <!-- Grid de Imagen y Filosofía -->
            <div class="row g-4 align-items-center mb-5">
                <div class="col-md-6">
                    <img src="https://images.unsplash.com/photo-1540555700478-4be289fbecef?auto=format&fit=crop&w=800&q=80" alt="Carpintero en taller artesanal" class="img-fluid rounded-4 shadow-lg border border-secondary border-opacity-25 w-100" style="max-height: 380px; object-fit: cover;">
                </div>
                <div class="col-md-6">
                    <h2 class="h3 fw-bold text-white mb-3">Nuestra Esencia</h2>
                    <p class="text-secondary mb-3">
                        Nacimos en el corazón de Jalisco como un taller familiar comprometido con el rescate del oficio carpintero. Frente a la proliferación de muebles desechables y aglomerados de baja densidad, en TallerTech apostamos por maderas estufadas de primera selección (Roble, Nogal, Parota y Cedro) trabajadas con ensambles de espiga y cola de milano.
                    </p>
                    <p class="text-secondary mb-0">
                        Cada proyecto que ingresa a nuestra plataforma cuenta con una orden de trabajo única, seguimiento paso a paso con registro fotográfico y atención personalizada directa del maestro ebanista.
                    </p>
                </div>
            </div>

            <!-- 3 Pilares -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100" style="background: #1a1a1a; border: 1px solid #2d2d2d;">
                        <div class="text-warning mb-3" style="font-size: 2rem;">🪵</div>
                        <h3 class="h5 fw-bold text-white mb-2">Madera Sustentable</h3>
                        <p class="text-secondary small mb-0">Maderas macizas con certificación de aprovechamiento forestal responsable y secado en estufa para evitar torceduras.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100" style="background: #1a1a1a; border: 1px solid #2d2d2d;">
                        <div class="text-warning mb-3" style="font-size: 2rem;">📐</div>
                        <h3 class="h5 fw-bold text-white mb-2">Corte Milimétrico</h3>
                        <p class="text-secondary small mb-0">Maquinado de alta precisión en frentes, ingletes a 45 grados y tolerancias perfectas para tus espacios habitables.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded-4 h-100" style="background: #1a1a1a; border: 1px solid #2d2d2d;">
                        <div class="text-warning mb-3" style="font-size: 2rem;">🛡️</div>
                        <h3 class="h5 fw-bold text-white mb-2">Garantía Escrita</h3>
                        <p class="text-secondary small mb-0">Compromiso formal con entrega puntual, instalación limpia y garantía estructural en todos nuestros acabados.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
