/**
 * TallerTech - Citas y Referencias de Diseños JS
 * Controlador de reglas de horario paramétrico, multi-file drag & drop y previews (JPG, PNG, WEBP, PDF)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Referencias del formulario
    const inputFecha = document.getElementById('fecha');
    const selectHora = document.getElementById('hora');
    const scheduleHint = document.getElementById('scheduleHint');

    const radioDomicilio = document.getElementById('tipo_domicilio');
    const radioTaller = document.getElementById('tipo_taller');
    const inputDireccion = document.getElementById('direccion');
    const direccionGroup = document.getElementById('direccionGroup');

    // Referencias de archivos (soporta tanto 'referencias' como 'croquis')
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('referencias') || document.getElementById('croquis');
    const filesPreviewContainer = document.getElementById('filesPreviewContainer');
    const filesListGrid = document.getElementById('filesListGrid');
    const filesCountBadge = document.getElementById('filesCountBadge');
    const btnClearAll = document.getElementById('btnClearAll');

    // ==========================================================================
    // 1. REGLAS DE HORARIO POR DÍA DE LA SEMANA
    // ==========================================================================

    const HORARIOS_ENTRE_SEMANA = [
        { val: '18:00', label: '06:00 PM - 07:00 PM' },
        { val: '19:00', label: '07:00 PM - 08:00 PM' },
        { val: '20:00', label: '08:00 PM - 09:00 PM' }
    ];

    const HORARIOS_FIN_SEMANA = [
        { val: '09:00', label: '09:00 AM - 10:00 AM' },
        { val: '10:00', label: '10:00 AM - 11:00 AM' },
        { val: '11:00', label: '11:00 AM - 12:00 PM' },
        { val: '12:00', label: '12:00 PM - 01:00 PM' },
        { val: '13:00', label: '01:00 PM - 02:00 PM' },
        { val: '14:00', label: '02:00 PM - 03:00 PM' },
        { val: '15:00', label: '03:00 PM - 04:00 PM' },
        { val: '16:00', label: '04:00 PM - 05:00 PM' },
        { val: '17:00', label: '05:00 PM - 06:00 PM' }
    ];

    function actualizarHorariosSegunFecha() {
        if (!inputFecha || !selectHora) return;

        const fechaSeleccionadaStr = inputFecha.value;
        if (!fechaSeleccionadaStr) {
            selectHora.innerHTML = '<option value="">Selecciona primero una fecha</option>';
            selectHora.disabled = true;
            if (scheduleHint) scheduleHint.textContent = 'Selecciona una fecha en el calendario';
            return;
        }

        const partes = fechaSeleccionadaStr.split('-');
        const fechaObj = new Date(partes[0], partes[1] - 1, partes[2]);
        const diaSemana = fechaObj.getDay(); // 0 = Domingo, 6 = Sábado

        selectHora.disabled = false;
        selectHora.innerHTML = '';

        let listaHorarios = [];
        let mensaje = '';

        if (diaSemana === 0 || diaSemana === 6) {
            listaHorarios = HORARIOS_FIN_SEMANA;
            mensaje = '✓ Horario fin de semana: Bloques de 1 hr entre 9:00 AM y 6:00 PM.';
        } else {
            listaHorarios = HORARIOS_ENTRE_SEMANA;
            mensaje = '✓ Horario entre semana: Visitas vespertinas a partir de las 6:00 PM.';
        }

        const placeholderOpt = document.createElement('option');
        placeholderOpt.value = '';
        placeholderOpt.textContent = 'Selecciona un bloque de hora (1 hora máx.)';
        selectHora.appendChild(placeholderOpt);

        listaHorarios.forEach(h => {
            const opt = document.createElement('option');
            opt.value = h.val;
            opt.textContent = h.label;
            selectHora.appendChild(opt);
        });

        if (scheduleHint) scheduleHint.textContent = mensaje;
    }

    if (inputFecha) {
        inputFecha.addEventListener('change', actualizarHorariosSegunFecha);
        if (inputFecha.value) {
            actualizarHorariosSegunFecha();
        }
    }

    // ==========================================================================
    // 2. TIPO DE CITA (DOMICILIO VS REUNIÓN EN TALLER)
    // ==========================================================================

    function actualizarTipoCita() {
        if (!inputDireccion) return;

        if (radioTaller && radioTaller.checked) {
            inputDireccion.value = 'Taller Central TallerTech (Av. Los Artesanos #450, Col. Centro)';
            inputDireccion.readOnly = true;
            inputDireccion.style.opacity = '0.75';
        } else {
            if (inputDireccion.value.includes('Taller Central')) {
                inputDireccion.value = '';
            }
            inputDireccion.readOnly = false;
            inputDireccion.style.opacity = '1';
            inputDireccion.placeholder = 'Calle, número exterior/interior, colonia y código postal';
        }
    }

    if (radioDomicilio) radioDomicilio.addEventListener('change', actualizarTipoCita);
    if (radioTaller) radioTaller.addEventListener('change', actualizarTipoCita);

    // ==========================================================================
    // 3. MULTI-FILE DRAG & DROP Y PREVIEWS (IMÁGENES & PDF DE REFERENCIAS)
    // ==========================================================================

    let stagedFiles = [];

    function formatearTamano(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function sincronizarInputFiles() {
        if (!fileInput) return;
        try {
            const dt = new DataTransfer();
            stagedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        } catch (e) {
            console.warn('DataTransfer no soportado completamente en este entorno:', e);
        }
    }

    function renderizarListaPreviews() {
        if (!filesPreviewContainer || !filesListGrid) return;

        if (stagedFiles.length === 0) {
            filesPreviewContainer.classList.remove('is-active');
            filesListGrid.innerHTML = '';
            if (filesCountBadge) filesCountBadge.textContent = '0 archivos seleccionados';
            return;
        }

        filesPreviewContainer.classList.add('is-active');
        if (filesCountBadge) {
            filesCountBadge.textContent = `${stagedFiles.length} archivo(s) de referencia listo(s)`;
        }

        filesListGrid.innerHTML = '';

        stagedFiles.forEach((file, index) => {
            const isImage = file.type.startsWith('image/');
            const isPdf = (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'));

            const card = document.createElement('div');
            card.className = 'file-preview-card';

            const mediaBox = document.createElement('div');
            mediaBox.className = 'file-preview-media';

            if (isImage) {
                const img = document.createElement('img');
                img.className = 'file-preview-thumb';
                img.alt = file.name;
                const reader = new FileReader();
                reader.onload = (e) => {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                mediaBox.appendChild(img);
            } else if (isPdf) {
                mediaBox.innerHTML = `
                    <div class="file-preview-icon-pdf">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                        <span>PDF</span>
                    </div>
                `;
            } else {
                mediaBox.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                        <polyline points="13 2 13 9 20 9"></polyline>
                    </svg>
                `;
            }

            const detailsBox = document.createElement('div');
            detailsBox.className = 'file-preview-details';
            detailsBox.innerHTML = `
                <div class="file-preview-name" title="${file.name}">${file.name}</div>
                <div class="file-preview-meta">
                    <span class="file-badge-type ${isPdf ? 'pdf' : 'image'}">${isPdf ? 'Documento PDF' : 'Imagen Diseño'}</span>
                    <span>${formatearTamano(file.size)}</span>
                </div>
            `;

            const btnDelete = document.createElement('button');
            btnDelete.type = 'button';
            btnDelete.className = 'file-preview-btn-delete';
            btnDelete.title = 'Quitar archivo';
            btnDelete.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            `;

            btnDelete.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                eliminarArchivo(index);
            });

            card.appendChild(mediaBox);
            card.appendChild(detailsBox);
            card.appendChild(btnDelete);

            filesListGrid.appendChild(card);
        });
    }

    function agregarArchivos(nuevosArchivos) {
        if (!nuevosArchivos || nuevosArchivos.length === 0) return;

        const extPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        const MAX_TAMANO = 15 * 1024 * 1024; // 15MB
        const MAX_TOTAL = 10;

        let advertencia = '';

        Array.from(nuevosArchivos).forEach(file => {
            if (stagedFiles.length >= MAX_TOTAL) {
                advertencia = `Se alcanzó el límite máximo de ${MAX_TOTAL} archivos.`;
                return;
            }

            const ext = file.name.split('.').pop().toLowerCase();
            if (!extPermitidas.includes(ext)) {
                advertencia = `El archivo "${file.name}" no es soportado. Solo imágenes (JPG, PNG, WEBP) o PDF.`;
                return;
            }

            if (file.size > MAX_TAMANO) {
                advertencia = `El archivo "${file.name}" supera los 15 MB permitidos.`;
                return;
            }

            // Evitar duplicados por nombre y tamaño
            const yaExiste = stagedFiles.some(f => f.name === file.name && f.size === file.size);
            if (!yaExiste) {
                stagedFiles.push(file);
            }
        });

        if (advertencia) {
            alert(advertencia);
        }

        sincronizarInputFiles();
        renderizarListaPreviews();
    }

    function eliminarArchivo(index) {
        if (index >= 0 && index < stagedFiles.length) {
            stagedFiles.splice(index, 1);
            sincronizarInputFiles();
            renderizarListaPreviews();
        }
    }

    function limpiarTodosLosArchivos() {
        stagedFiles = [];
        if (fileInput) fileInput.value = '';
        sincronizarInputFiles();
        renderizarListaPreviews();
    }

    if (fileInput) {
        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files.length > 0) {
                agregarArchivos(fileInput.files);
            }
        });
    }

    if (btnClearAll) {
        btnClearAll.addEventListener('click', (e) => {
            e.preventDefault();
            limpiarTodosLosArchivos();
        });
    }

    // Soporte Drag & Drop
    if (dropzone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                agregarArchivos(dt.files);
            }
        });
    }
});
