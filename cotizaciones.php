<?php
/**
 * TallerTech - Módulo de Cotizaciones por Categoría y Reglas de Negocio
 * Idioma visual Dark Mode TallerTech, selector de muebles estandarizados y persistencia PDO
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/conexion.php';

$mensaje_exito = '';
$mensaje_error = '';

// Procesar guardado de cotización cuando el usuario lo solicita
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_cotizacion'])) {
    if (!isset($_SESSION['id_usuario'])) {
        $mensaje_error = 'Debes <a href="login.php" class="text-white text-decoration-underline fw-bold">iniciar sesión</a> o <a href="Registro.php" class="text-white text-decoration-underline fw-bold">crear una cuenta</a> para guardar este estimado y solicitar la revisión del taller.';
    } else {
        $id_usuario     = (int)$_SESSION['id_usuario'];
        $id_mueble      = (int)($_POST['id_mueble'] ?? 5);
        $costo_total    = (float)($_POST['costo_total'] ?? 0);
        $categoria_txt  = trim($_POST['categoria'] ?? 'Mueble Estandarizado');
        $alto           = (float)($_POST['alto'] ?? 0);
        $ancho          = (float)($_POST['ancho'] ?? 0);
        $profundidad    = (float)($_POST['profundidad'] ?? 0);
        $material_txt   = trim($_POST['material'] ?? 'Melamina');
        $instalacion    = isset($_POST['instalacion']) ? 'Sí' : 'No';
        $envio          = isset($_POST['envio']) ? 'Sí' : 'No';
        $notas_cliente  = trim($_POST['notas'] ?? '');

        if ($costo_total <= 0) {
            $mensaje_error = 'El costo total estimado no es válido. Por favor, verifica las dimensiones.';
        } else {
            try {
                // Notas técnicas estructuradas
                $detallesTecnicos = "Categoría: {$categoria_txt} | Medidas: {$alto}m alto x {$ancho}m ancho x {$profundidad}m prof. | Material: {$material_txt} | Instalación: {$instalacion} | Envío: {$envio}";
                if (!empty($notas_cliente)) {
                    $detallesTecnicos .= " | Notas adicionales: " . $notas_cliente;
                }

                // Inserción preparada con PDO
                $sql = "INSERT INTO cotizaciones (id_usuario, id_mueble, estado, costo_total, anticipo_pagado, notas) 
                        VALUES (:id_usuario, :id_mueble, 'estimada', :costo_total, 0.00, :notas)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_usuario'  => $id_usuario,
                    ':id_mueble'   => $id_mueble,
                    ':costo_total' => $costo_total,
                    ':notas'       => $detallesTecnicos
                ]);

                $id_cotizacion = (int)$pdo->lastInsertId();
                $mensaje_exito = "¡Excelente! Tu cotización #{$id_cotizacion} ha sido registrada con estado <strong>estimada</strong>. El equipo del taller revisará los detalles técnicos para contactarte a la brevedad.";

            } catch (PDOException $e) {
                error_log("Error al registrar cotización: " . $e->getMessage());
                $mensaje_error = 'Ocurrió un error en el servidor al guardar la cotización. Por favor, intenta de nuevo.';
            }
        }
    }
}

// Configuración de vista para el layout global
$titulo_pagina = 'Cotizaciones por Categoría | TallerTech';
$pagina_actual = 'cotizaciones.php';
$css_adicional = 'assets/css/cotizaciones.css';

require_once 'includes/header.php';
?>

<div class="cotizaciones-page">
    <div class="container">

        <!-- Banners de Notificación -->
        <?php if (!empty($mensaje_exito)): ?>
            <div class="notification-banner success" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div><?= $mensaje_exito ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
            <div class="notification-banner error" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div><?= $mensaje_error ?></div>
            </div>
        <?php endif; ?>

        <!-- Encabezado de la Sección -->
        <header class="cotizaciones-header-main">
            <span class="cotizaciones-tag">Reglas de Negocio &amp; Estimaciones</span>
            <h1 class="cotizaciones-hero-title">Módulo de Cotizaciones y Reglas de Negocio</h1>
            <p class="cotizaciones-hero-subtitle">
                Conoce los lineamientos técnicos paramétricos y los esquemas de pago establecidos para la elaboración de mobiliario artesanal en TallerTech.
            </p>
        </header>

        <!-- ==========================================================================
             SECCIÓN 1: REGLAS DE NEGOCIO (3 TARJETAS SUPERIORES)
             ========================================================================== -->
        <section class="business-rules-section" aria-label="Reglas de negocio del taller">
            <div class="business-rules-grid">

                <!-- Tarjeta 1: Cálculo Paramétrico -->
                <article class="business-rule-card">
                    <div class="business-rule-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="16" height="20" x="4" y="2" rx="2"/>
                            <line x1="8" x2="16" y1="6" y2="6"/>
                            <line x1="16" x2="16" y1="14" y2="18"/>
                            <path d="M16 10h.01"/>
                            <path d="M12 10h.01"/>
                            <path d="M8 10h.01"/>
                            <path d="M12 14h.01"/>
                            <path d="M8 14h.01"/>
                            <path d="M12 18h.01"/>
                            <path d="M8 18h.01"/>
                        </svg>
                    </div>
                    <h3 class="business-rule-title">Cálculo Paramétrico</h3>
                    <p class="business-rule-desc">
                        El precio se calcula con base en dimensiones (hojas/tablones), insumos (sellador, barniz, tinta), tiempo de elaboración e instalación, y distancia de entrega.
                    </p>
                </article>

                <!-- Tarjeta 2: Emisión de Estimados -->
                <article class="business-rule-card">
                    <div class="business-rule-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/>
                            <path d="M14 8H8"/>
                            <path d="M16 12H8"/>
                            <path d="M13 16H8"/>
                        </svg>
                    </div>
                    <h3 class="business-rule-title">Emisión de Estimados</h3>
                    <p class="business-rule-desc">
                        El sistema genera una cotización estimada al instante para el cliente, estableciendo claramente mediante un aviso que el precio final está sujeto a revisión.
                    </p>
                </article>

                <!-- Tarjeta 3: Esquema de Cobro -->
                <article class="business-rule-card">
                    <div class="business-rule-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m11 17 2 2a1 1 0 0 0 1.42 0l4.58-4.59a1 1 0 0 0 0-1.41l-2.59-2.6a1 1 0 0 0-1.41 0L13.59 12"/>
                            <path d="m18 11 3-3a1 1 0 0 0 0-1.42L18.41 4a1 1 0 0 0-1.41 0L13.5 7.5"/>
                            <path d="m9 15-2-2a1 1 0 0 1 0-1.41L12.5 6"/>
                            <path d="m3 9 4.5 4.5"/>
                            <path d="m6 18 3 3"/>
                        </svg>
                    </div>
                    <h3 class="business-rule-title">Esquema de Cobro</h3>
                    <p class="business-rule-desc">
                        Para autorizar e iniciar formalmente cualquier proyecto aprobado en el sistema, se requiere registrar un anticipo obligatorio del 60% del costo total.
                    </p>
                </article>

            </div>
        </section>

        <!-- ==========================================================================
             SECCIÓN 2: SHOWCASE HERO - COTIZACIÓN AUTOMÁTICA POR CATEGORÍA
             ========================================================================== -->
        <section class="category-showcase-section" aria-label="Muebles estandarizados cotizables">
            <div class="category-showcase-card">
                <div class="category-showcase-grid">

                    <!-- Columna Izquierda: Explicación y Categorías Disponibles -->
                    <div class="showcase-content-col">
                        <h2 class="showcase-heading">Cotización Automática por Categoría</h2>
                        <p class="showcase-intro-text">
                            El módulo de cotizaciones automáticas no abarcará todo tipo de trabajos a medida, sino que estará habilitado específicamente para los siguientes tipos de muebles estandarizados:
                        </p>

                        <!-- Lista Interactiva de Categorías -->
                        <div class="categories-selector-list" role="radiogroup" aria-label="Seleccionar categoría de mueble">
                            
                            <!-- 1. Cajoneras -->
                            <div class="category-select-item active" data-category="cajoneras" role="radio" aria-checked="true" tabindex="0">
                                <div class="category-item-info">
                                    <div class="category-item-bullet"></div>
                                    <span class="category-item-title">Cajoneras</span>
                                </div>
                                <span class="category-item-hint">3 a 5 cajones telescópicos</span>
                            </div>

                            <!-- 2. Puertas -->
                            <div class="category-select-item" data-category="puertas" role="radio" aria-checked="false" tabindex="0">
                                <div class="category-item-info">
                                    <div class="category-item-bullet"></div>
                                    <span class="category-item-title">Puertas (Interiores y Exteriores)</span>
                                </div>
                                <span class="category-item-hint">Bastidor y marcos macizos</span>
                            </div>

                            <!-- 3. Mesas -->
                            <div class="category-select-item" data-category="mesas" role="radio" aria-checked="false" tabindex="0">
                                <div class="category-item-info">
                                    <div class="category-item-bullet"></div>
                                    <span class="category-item-title">Mesas (Comedor, centro)</span>
                                </div>
                                <span class="category-item-hint">Cubiertas cepilladas en madera sólida</span>
                            </div>

                            <!-- 4. Bases de cama -->
                            <div class="category-select-item" data-category="camas" role="radio" aria-checked="false" tabindex="0">
                                <div class="category-item-info">
                                    <div class="category-item-bullet"></div>
                                    <span class="category-item-title">Bases de cama</span>
                                </div>
                                <span class="category-item-hint">Estructura firme con cabecera artesanal</span>
                            </div>

                            <!-- 5. Escritorios -->
                            <div class="category-select-item" data-category="escritorios" role="radio" aria-checked="false" tabindex="0">
                                <div class="category-item-info">
                                    <div class="category-item-bullet"></div>
                                    <span class="category-item-title">Escritorios</span>
                                </div>
                                <span class="category-item-hint">Diseño contemporáneo y ergonómico</span>
                            </div>

                        </div>

                        <!-- Nota al pie atenuada -->
                        <p class="showcase-footnote">
                            * Otros proyectos más complejos requerirán cotización manual por parte del administrador.
                        </p>

                        <!-- Botón Píldora Blanco -->
                        <a href="#seccion-calculadora" id="btn-iniciar-cotizacion" class="btn-pill-white">
                            <span>Iniciar Cotización</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>

                    <!-- Columna Derecha: Imagen Dinámica del Mueble -->
                    <div class="showcase-image-col">
                        <img 
                            id="showcase-img"
                            src="https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=800&q=80" 
                            alt="Cajoneras Estandarizadas"
                            class="showcase-featured-img"
                            loading="eager"
                        >
                        <div class="showcase-image-overlay"></div>
                        <div class="showcase-image-badge">
                            <h4 id="showcase-title" class="showcase-badge-title">Cajoneras Estandarizadas</h4>
                            <p id="showcase-desc" class="showcase-badge-desc">Correderas de extensión telescópica, frentes con ensamble milimétrico y tiradores integrados.</p>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- ==========================================================================
             SECCIÓN 3: CALCULADORA PARAMÉTRICA DETALLADA
             ========================================================================== -->
        <section id="seccion-calculadora" class="calculator-wrapper-box" aria-label="Calculadora paramétrica">
            
            <div class="calc-section-header">
                <h3 class="calc-section-title">Ajuste Paramétrico y Cotización en Vivo</h3>
                <p class="calc-section-subtitle">
                    Modifica dimensiones, material y requerimientos para calibrar el presupuesto estimado en tiempo real.
                </p>
            </div>

            <form method="POST" action="cotizaciones.php" id="form-cotizador">
                
                <!-- Campos ocultos de persistencia en base de datos -->
                <input type="hidden" name="solicitar_cotizacion" value="1">
                <input type="hidden" name="id_mueble" id="input_id_mueble" value="5">
                <input type="hidden" name="costo_total" id="input_costo_total" value="0.00">
                <input type="hidden" name="anticipo_estimado" id="input_anticipo" value="0.00">

                <div class="calc-layout-grid">

                    <!-- Columna Izquierda: Parámetros del Proyecto -->
                    <div class="calc-form-card">
                        
                        <!-- Categoría Seleccionada -->
                        <div class="form-group-item">
                            <label for="categoria" class="form-label-item">Mueble Estandarizado</label>
                            <select id="categoria" name="categoria" class="form-select-element" required>
                                <option value="cajoneras">Cajoneras Estandarizadas</option>
                                <option value="puertas">Puertas (Interiores y Exteriores)</option>
                                <option value="mesas">Mesas (Comedor, centro)</option>
                                <option value="camas">Bases de cama con cabecera</option>
                                <option value="escritorios">Escritorios Contemporáneos</option>
                            </select>
                        </div>

                        <!-- Dimensiones en Metros -->
                        <div class="form-group-item">
                            <label class="form-label-item">
                                Dimensiones Requeridas <small>(Metros)</small>
                            </label>
                            <div class="dimensions-three-col">
                                <div>
                                    <label for="alto" class="form-label-item"><small>Alto (m)</small></label>
                                    <input type="number" step="0.05" min="0.20" max="4.00" id="alto" name="alto" class="form-input-element" value="0.90" required>
                                </div>
                                <div>
                                    <label for="ancho" class="form-label-item"><small>Ancho (m)</small></label>
                                    <input type="number" step="0.05" min="0.20" max="6.00" id="ancho" name="ancho" class="form-input-element" value="0.80" required>
                                </div>
                                <div>
                                    <label for="profundidad" class="form-label-item"><small>Profundidad (m)</small></label>
                                    <input type="number" step="0.05" min="0.04" max="2.50" id="profundidad" name="profundidad" class="form-input-element" value="0.45" required>
                                </div>
                            </div>
                        </div>

                        <!-- Material y Acabado -->
                        <div class="form-group-item">
                            <label for="material" class="form-label-item">Material y Acabado</label>
                            <select id="material" name="material" class="form-select-element" required>
                                <option value="melamina" selected>Melamina Estándar 16mm (Texturizada de alta durabilidad)</option>
                                <option value="mdf_laqueado">MDF Laqueado (Poliuretano satinado anti-humedad)</option>
                                <option value="pino">Madera Sólida de Pino Estufado (Acabado artesanal)</option>
                                <option value="parota_roble">Madera Selecta / Parota / Roble (Gama premium natural)</option>
                            </select>
                        </div>

                        <!-- Servicios Adicionales -->
                        <div class="form-group-item">
                            <label class="form-label-item">Servicios Opcionales</label>
                            <div class="additional-services-box">
                                <label class="custom-service-checkbox">
                                    <input type="checkbox" id="instalacion" name="instalacion" value="1" checked>
                                    <span>Instalación y montaje profesional en sitio</span>
                                </label>
                                <label class="custom-service-checkbox">
                                    <input type="checkbox" id="envio" name="envio" value="1" checked>
                                    <span>Envío y flete protegido a domicilio</span>
                                </label>
                            </div>
                        </div>

                        <!-- Notas y Detalles de Diseño -->
                        <div class="form-group-item mb-0">
                            <label for="notas" class="form-label-item">
                                Detalles Específicos o Requerimientos <small>(Opcional)</small>
                            </label>
                            <textarea id="notas" name="notas" class="form-input-element" rows="3" placeholder="Ej. Tono nogal americano, herrajes de amortiguación oculta, orientación específica..."></textarea>
                        </div>

                    </div>

                    <!-- Columna Derecha: Resumen de Precios y Anticipo 60/40 -->
                    <aside class="calc-summary-card" aria-label="Resumen de inversión estimada">
                        
                        <div class="summary-card-header">
                            <span class="summary-card-badge">Cálculo Paramétrico en Tiempo Real</span>
                            <h4 class="summary-card-title">Resumen del Estimado</h4>
                        </div>

                        <!-- Lista de Desglose de Inversión -->
                        <ul class="summary-breakdown-list">
                            <li class="summary-breakdown-row">
                                <span>Insumos y Materiales:</span>
                                <strong id="val-materiales">$0.00 MXN</strong>
                            </li>
                            <li class="summary-breakdown-row">
                                <span>Mano de Obra Especializada:</span>
                                <strong id="val-mano-obra">$0.00 MXN</strong>
                            </li>
                            <li class="summary-breakdown-row">
                                <span>Servicios (Montaje / Flete):</span>
                                <strong id="val-servicios">$0.00 MXN</strong>
                            </li>
                            <li class="summary-breakdown-row total-row">
                                <span>Total Estimado:</span>
                                <span class="total-amount" id="val-total">$0.00 MXN</span>
                            </li>
                        </ul>

                        <!-- Cuadro de Esquema de Cobro: Anticipo 60% y Saldo 40% -->
                        <div class="payment-terms-box">
                            <div class="payment-term-col">
                                <span class="payment-term-label">Anticipo Requerido (60%)</span>
                                <span class="payment-term-val highlight" id="val-anticipo">$0.00 MXN</span>
                            </div>
                            <div class="payment-term-col">
                                <span class="payment-term-label">Saldo a la Entrega (40%)</span>
                                <span class="payment-term-val" id="val-saldo">$0.00 MXN</span>
                            </div>
                        </div>

                        <!-- Aviso Legal / Disclaimer -->
                        <div class="calc-disclaimer-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <p class="calc-disclaimer-text">
                                <strong>Aviso:</strong> Este monto es un estimado paramétrico. El precio final será validado formalmente por el taller tras la toma de medidas o especificaciones técnicas definitivas.
                            </p>
                        </div>

                        <!-- Botón de Envío / Solicitud -->
                        <button type="submit" class="btn-submit-quote">
                            <span>Solicitar Revisión de Cotización</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>

                        <?php if (!isset($_SESSION['id_usuario'])): ?>
                            <div class="login-hint-text">
                                <a href="login.php">Inicia sesión</a> para vincular esta cotización a tu cuenta.
                            </div>
                        <?php endif; ?>

                    </aside>

                </div>

            </form>

        </section>

    </div>
</div>

<!-- Script interactivo del Cotizador y Showcase -->
<script src="assets/js/cotizador.js"></script>

<?php 
require_once 'includes/footer.php'; 
?>
