/**
 * TallerTech - Cotizador Paramétrico y Selector de Muebles Estandarizados
 * Maneja la interacción del Showcase Hero, cambio dinámico de imágenes y cálculo paramétrico
 */

document.addEventListener('DOMContentLoaded', () => {
    // Definición de las 5 Categorías Estandarizadas de TallerTech
    const CATEGORIAS_CONFIG = {
        'cajoneras': {
            idMueble: 5,
            nombre: 'Cajoneras Estandarizadas',
            precioBaseM2: 5200,
            dimensiones: { alto: 0.90, ancho: 0.80, prof: 0.45 },
            imagen: 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=800&q=80',
            descripcion: 'Correderas de extensión telescópica, frentes con ensamble milimétrico y tiradores integrados.'
        },
        'puertas': {
            idMueble: 6,
            nombre: 'Puertas (Interiores y Exteriores)',
            precioBaseM2: 3900,
            dimensiones: { alto: 2.10, ancho: 0.90, prof: 0.05 },
            imagen: 'https://images.unsplash.com/photo-1513694203232-719a280e022f?auto=format&fit=crop&w=800&q=80',
            descripcion: 'Bastidor macizo estufado con aislamiento termoacústico y sellado de poliuretano.'
        },
        'mesas': {
            idMueble: 7,
            nombre: 'Mesas (Comedor y Centro)',
            precioBaseM2: 4500,
            dimensiones: { alto: 0.75, ancho: 1.80, prof: 0.90 },
            imagen: 'https://images.unsplash.com/photo-1615066390971-03e4e1c36ddf?auto=format&fit=crop&w=800&q=80',
            descripcion: 'Cubierta cepillada en madera sólida con ensamble tradicional y estructura reforzada.'
        },
        'camas': {
            idMueble: 8,
            nombre: 'Bases de Cama',
            precioBaseM2: 4200,
            dimensiones: { alto: 0.40, ancho: 1.50, prof: 1.90 },
            imagen: 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80',
            descripcion: 'Estructura silenciosa de encastre robusto, largueros de apoyo sólido y cabecera artesanal.'
        },
        'escritorios': {
            idMueble: 9,
            nombre: 'Escritorios Contemporáneos',
            precioBaseM2: 4800,
            dimensiones: { alto: 0.75, ancho: 1.40, prof: 0.60 },
            imagen: 'https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?auto=format&fit=crop&w=800&q=80',
            descripcion: 'Superficie ergonómica para trabajo diario, pasacables pulido y compartimento organizador.'
        }
    };

    const MULTIPLICADORES_MATERIAL = {
        'melamina': 1.0,      // Melamina 16mm
        'mdf_laqueado': 1.35, // MDF acabado laca automotriz/poliuretano
        'pino': 1.55,         // Madera maciza de pino estufado
        'parota_roble': 2.10  // Parota o Roble selecto
    };

    // Referencias del Showcase
    const categoryItems = document.querySelectorAll('.category-select-item');
    const showcaseImg = document.getElementById('showcase-img');
    const showcaseTitle = document.getElementById('showcase-title');
    const showcaseDesc = document.getElementById('showcase-desc');
    const btnIniciarCotizacion = document.getElementById('btn-iniciar-cotizacion');

    // Referencias del Formulario
    const selectCategoria = document.getElementById('categoria');
    const inputAlto = document.getElementById('alto');
    const inputAncho = document.getElementById('ancho');
    const inputProf = document.getElementById('profundidad');
    const selectMaterial = document.getElementById('material');
    const checkInstalacion = document.getElementById('instalacion');
    const checkEnvio = document.getElementById('envio');

    // Referencias del Panel de Resumen
    const valMateriales = document.getElementById('val-materiales');
    const valManoObra = document.getElementById('val-mano-obra');
    const valServicios = document.getElementById('val-servicios');
    const valTotal = document.getElementById('val-total');
    const valAnticipo = document.getElementById('val-anticipo');
    const valSaldo = document.getElementById('val-saldo');

    // Campos ocultos para base de datos
    const inputCostoTotal = document.getElementById('input_costo_total');
    const inputAnticipo = document.getElementById('input_anticipo');
    const inputIdMueble = document.getElementById('input_id_mueble');

    // Formateador de moneda MXN
    const formatMoney = (val) => new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2
    }).format(val);

    // Función para activar una categoría estandarizada
    function seleccionarCategoria(catKey) {
        const config = CATEGORIAS_CONFIG[catKey];
        if (!config) return;

        // 1. Actualizar estado activo en la lista de items
        categoryItems.forEach(item => {
            if (item.dataset.category === catKey) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // 2. Transición suave de la imagen destacada
        if (showcaseImg) {
            showcaseImg.style.opacity = '0.3';
            showcaseImg.style.transform = 'scale(0.98)';
            setTimeout(() => {
                showcaseImg.src = config.imagen;
                showcaseImg.alt = config.nombre;
                showcaseImg.style.opacity = '1';
                showcaseImg.style.transform = 'scale(1)';
            }, 180);
        }

        // 3. Actualizar textos de la imagen destacada
        if (showcaseTitle) showcaseTitle.textContent = config.nombre;
        if (showcaseDesc) showcaseDesc.textContent = config.descripcion;

        // 4. Sincronizar el selector del formulario
        if (selectCategoria) {
            selectCategoria.value = catKey;
        }

        // 5. Cargar dimensiones estándar del tipo de mueble
        if (inputAlto) inputAlto.value = config.dimensiones.alto.toFixed(2);
        if (inputAncho) inputAncho.value = config.dimensiones.ancho.toFixed(2);
        if (inputProf) inputProf.value = config.dimensiones.prof.toFixed(2);

        // 6. Asignar ID mueble para base de datos
        if (inputIdMueble) inputIdMueble.value = config.idMueble;

        // 7. Recalcular cotización
        calcularCotizacion();
    }

    // Función de cálculo paramétrico de la cotización
    function calcularCotizacion() {
        const catKey = selectCategoria ? selectCategoria.value : 'cajoneras';
        const config = CATEGORIAS_CONFIG[catKey] || CATEGORIAS_CONFIG['cajoneras'];

        const alto = Math.max(0.2, parseFloat(inputAlto ? inputAlto.value : 1.0) || 1.0);
        const ancho = Math.max(0.2, parseFloat(inputAncho ? inputAncho.value : 1.0) || 1.0);
        const prof = Math.max(0.04, parseFloat(inputProf ? inputProf.value : 0.4) || 0.4);

        const materialKey = selectMaterial ? selectMaterial.value : 'melamina';
        const multMaterial = MULTIPLICADORES_MATERIAL[materialKey] || 1.0;

        // Factor de profundidad paramétrica
        const factorProfundidad = 1 + (prof - 0.3) * 0.5;
        const areaTrabajoM2 = (alto * ancho) * Math.max(0.7, factorProfundidad);

        // Costo base por área y material
        const costoBase = areaTrabajoM2 * config.precioBaseM2 * multMaterial;

        // Desglose técnico de carpintería:
        // 55% Insumos y materiales (madera, adhesivos, herrajes, tintas, sellador)
        const costoMateriales = Math.round(costoBase * 0.55);

        // 45% Mano de obra especializada (corte, maquinado, armado, pulido)
        const costoManoObra = Math.round(costoBase * 0.45);

        // Servicios adicionales
        let costoServicios = 0;
        if (checkInstalacion && checkInstalacion.checked) {
            costoServicios += Math.round(Math.max(900, costoBase * 0.08));
        }
        if (checkEnvio && checkEnvio.checked) {
            costoServicios += 650;
        }

        // Gran Total Estimado
        const totalEstimado = costoMateriales + costoManoObra + costoServicios;

        // Esquema de cobro de TallerTech: 60% Anticipo obligatorio y 40% Saldo contra entrega
        const anticipoRequerido = Math.round(totalEstimado * 0.60);
        const saldoContraEntrega = totalEstimado - anticipoRequerido;

        // Renderizar valores en el resumen
        if (valMateriales) valMateriales.textContent = formatMoney(costoMateriales);
        if (valManoObra) valManoObra.textContent = formatMoney(costoManoObra);
        if (valServicios) valServicios.textContent = formatMoney(costoServicios);
        if (valTotal) valTotal.textContent = formatMoney(totalEstimado);
        if (valAnticipo) valAnticipo.textContent = formatMoney(anticipoRequerido);
        if (valSaldo) valSaldo.textContent = formatMoney(saldoContraEntrega);

        // Actualizar inputs ocultos
        if (inputCostoTotal) inputCostoTotal.value = totalEstimado.toFixed(2);
        if (inputAnticipo) inputAnticipo.value = anticipoRequerido.toFixed(2);
        if (inputIdMueble) inputIdMueble.value = config.idMueble;
    }

    // Escuchadores de eventos para los 5 ítems clicables del Showcase
    categoryItems.forEach(item => {
        item.addEventListener('click', () => {
            const catKey = item.dataset.category;
            seleccionarCategoria(catKey);
        });
    });

    // Botón "Iniciar Cotización →" hace scroll suave a la calculadora
    if (btnIniciarCotizacion) {
        btnIniciarCotizacion.addEventListener('click', (e) => {
            e.preventDefault();
            const calcSection = document.getElementById('seccion-calculadora');
            if (calcSection) {
                calcSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // Escuchadores de cambio en el selector de categoría del formulario
    if (selectCategoria) {
        selectCategoria.addEventListener('change', () => {
            seleccionarCategoria(selectCategoria.value);
        });
    }

    // Escuchadores en tiempo real para inputs de dimensiones y materiales
    [inputAlto, inputAncho, inputProf, selectMaterial, checkInstalacion, checkEnvio].forEach(el => {
        if (el) {
            el.addEventListener('input', calcularCotizacion);
            el.addEventListener('change', calcularCotizacion);
        }
    });

    // Inicializar con la primera categoría estandarizada (Cajoneras)
    seleccionarCategoria('cajoneras');
});
