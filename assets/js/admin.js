/**
 * TallerTech - Lógica Interactiva del Panel de Carpintero (Admin)
 * Gestión de pestañas, filtros en vivo, modales de actualización y confirmaciones
 */

document.addEventListener('DOMContentLoaded', () => {

    // 1. Manejo de Pestañas (Tabs)
    const tabButtons = document.querySelectorAll('.admin-tab-btn');
    const tabPanes   = document.querySelectorAll('.admin-tab-pane');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');

            // Quitar clase activa de todos los botones y paneles
            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.add('d-none'));

            // Activar botón seleccionado y su respectivo panel
            btn.classList.add('active');
            const activePane = document.getElementById(`pane-${targetTab}`);
            if (activePane) {
                activePane.classList.remove('d-none');
            }

            // Recordar la última pestaña en la URL hash sin recargar
            if (history.replaceState) {
                history.replaceState(null, null, `#${targetTab}`);
            }
        });
    });

    // Activar pestaña según el hash de la URL si existe
    const currentHash = window.location.hash.replace('#', '');
    if (currentHash) {
        const matchingBtn = document.querySelector(`.admin-tab-btn[data-tab="${currentHash}"]`);
        if (matchingBtn) {
            matchingBtn.click();
        }
    }

    // 2. Filtrado en vivo por texto en tablas
    const searchInputs = document.querySelectorAll('.admin-search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const targetTableId = input.getAttribute('data-target-table');
            const table = document.getElementById(targetTableId);

            if (!table) return;
            const rows = table.querySelectorAll('tbody tr:not(.empty-row)');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // 3. Modal de Edición de Pedido (Cargar datos del dataset)
    const modalEditarPedido = document.getElementById('modalEditarPedido');
    if (modalEditarPedido) {
        modalEditarPedido.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            if (!button) return;

            const idPedido       = button.getAttribute('data-id');
            const estado         = button.getAttribute('data-estado');
            const fechaEstimada  = button.getAttribute('data-fecha');
            const anticipo       = button.getAttribute('data-anticipo');
            const total          = button.getAttribute('data-total');
            const mueble         = button.getAttribute('data-mueble');
            const cliente        = button.getAttribute('data-cliente');

            document.getElementById('edit_pedido_id').value         = idPedido;
            document.getElementById('edit_pedido_title').textContent = `#PED-${idPedido} - ${mueble}`;
            document.getElementById('edit_pedido_cliente').textContent = cliente;
            document.getElementById('edit_pedido_estado').value     = estado;
            document.getElementById('edit_pedido_fecha').value      = fechaEstimada;
            document.getElementById('edit_pedido_anticipo').value   = anticipo;
            document.getElementById('edit_pedido_total').value      = total;
        });
    }

    // 4. Modal de Edición de Cotización
    const modalEditarCotizacion = document.getElementById('modalEditarCotizacion');
    if (modalEditarCotizacion) {
        modalEditarCotizacion.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            if (!button) return;

            const idCot     = button.getAttribute('data-id');
            const estado    = button.getAttribute('data-estado');
            const total     = button.getAttribute('data-total');
            const notas     = button.getAttribute('data-notas');
            const cliente   = button.getAttribute('data-cliente');
            const mueble    = button.getAttribute('data-mueble');

            document.getElementById('edit_cot_id').value         = idCot;
            document.getElementById('edit_cot_title').textContent = `#COT-${idCot} - ${mueble}`;
            document.getElementById('edit_cot_cliente').textContent = cliente;
            document.getElementById('edit_cot_estado').value     = estado;
            document.getElementById('edit_cot_total').value      = total;
            document.getElementById('edit_cot_notas').value      = notas;
        });
    }

    // 5. Modal de Edición de Cita
    const modalEditarCita = document.getElementById('modalEditarCita');
    if (modalEditarCita) {
        modalEditarCita.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            if (!button) return;

            const idCita  = button.getAttribute('data-id');
            const estado  = button.getAttribute('data-estado');
            const cliente = button.getAttribute('data-cliente');
            const fecha   = button.getAttribute('data-fecha');

            document.getElementById('edit_cita_id').value         = idCita;
            document.getElementById('edit_cita_title').textContent = `Cita #${idCita} (${fecha})`;
            document.getElementById('edit_cita_cliente').textContent = cliente;
            document.getElementById('edit_cita_estado').value     = estado;
        });
    }

});
