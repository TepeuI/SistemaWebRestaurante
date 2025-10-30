document.addEventListener('DOMContentLoaded', function () {
    // Mostrar una alerta
    function showAlert(type, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'),
                title: title || (type === 'success' ? 'Éxito' : 'Atención'),
                text: text || '',
                confirmButtonText: 'Aceptar'
            });
        } else {
            alert((title ? title + '\n' : '') + (text || ''));
        }
    }

    // Confirmación (devuelve Promise<boolean>)
    function confirmAction(opts) {
        opts = opts || {};
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                title: opts.title || '¿Confirmar acción?',
                text: opts.text || '',
                icon: opts.icon || 'question',
                showCancelButton: true,
                confirmButtonText: opts.confirmText || 'Sí',
                cancelButtonText: opts.cancelText || 'Cancelar'
            }).then(r => !!r.isConfirmed);
        } else {
            return Promise.resolve(confirm((opts.title ? opts.title + '\n' : '') + (opts.text || '')));
        }
    }

    // Hooks para formularios que declaran data-eliminar="true"
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function (evt) {
            evt.preventDefault();
            const frm = this;
            confirmAction({
                title: '¿Eliminar orden?',
                text: 'Esta acción eliminará la orden permanentemente.',
                icon: 'warning',
                confirmText: 'Eliminar',
                cancelText: 'Cancelar'
            }).then(confirmed => {
                if (confirmed) frm.submit();
            });
        });
    });

    // Mostrar mensaje enviado desde servidor
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const tipo = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' : (m.tipo === 'error' ? 'error' : 'info');
            showAlert(tipo, tipo === 'success' ? 'Éxito' : 'Atención', m.text || '');
            try { delete window.__mensaje; } catch (e) { window.__mensaje = null; }
        }
    } catch (e) { /* ignorar errores */ }

    // ---- Lógica específica de órdenes ----
    (function () {
        const form = document.getElementById('form-ordenes');
        if (!form) return;

        const operacion = document.getElementById('operacion');
        const idInput = document.getElementById('id_orden');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const filaActivaSpan = document.getElementById('fila-activa');
        const idPlatoSelect = document.getElementById('id_plato');
        const idBebidaSelect = document.getElementById('id_bebida');
        const cantidadInput = document.getElementById('cantidad');
        const totalDisplay = document.getElementById('total_display');

        function limpiar() {
            form.reset();
            idInput.value = '';
            operacion.value = 'crear';
            if (btnGuardar) btnGuardar.classList.remove('d-none');
            if (btnActualizar) btnActualizar.classList.add('d-none');
            if (btnCancelar) btnCancelar.classList.add('d-none');
            if (filaActivaSpan) filaActivaSpan.textContent = 'ninguna';
            
            // Establecer cantidad por defecto
            cantidadInput.value = 1;
            
            // Actualizar total display
            actualizarTotalDisplay();
            
            document.querySelectorAll('#tabla-ordenes tbody tr').forEach(r => r.classList.remove('table-primary'));
        }

        function validar() {
            const idMesa = parseInt(form.id_mesa.value, 10);
            const idPlato = parseInt(form.id_plato.value, 10);
            const idBebida = parseInt(form.id_bebida.value, 10);
            const cantidad = parseInt(form.cantidad.value, 10);

            if (isNaN(idMesa) || idMesa <= 0) {
                showAlert('error', 'Validación', 'Debe seleccionar una mesa');
                return false;
            }

            if ((isNaN(idPlato) || idPlato <= 0) && (isNaN(idBebida) || idBebida <= 0)) {
                showAlert('error', 'Validación', 'Debe seleccionar al menos un plato o una bebida');
                return false;
            }

            if (isNaN(cantidad) || cantidad <= 0) {
                showAlert('error', 'Validación', 'La cantidad debe ser mayor a 0');
                return false;
            }

            return true;
        }

        function calcularTotal() {
            let total = 0;
            const cantidad = parseInt(cantidadInput.value, 10) || 1;

            // Sumar precio del plato si está seleccionado
            if (idPlatoSelect.selectedOptions[0] && idPlatoSelect.value) {
                const precioPlato = parseFloat(idPlatoSelect.selectedOptions[0].dataset.precio || 0);
                total += precioPlato * cantidad;
            }

            // Sumar precio de la bebida si está seleccionada
            if (idBebidaSelect.selectedOptions[0] && idBebidaSelect.value) {
                const precioBebida = parseFloat(idBebidaSelect.selectedOptions[0].dataset.precio || 0);
                total += precioBebida * cantidad;
            }

            return total;
        }

        function actualizarTotalDisplay() {
            const total = calcularTotal();
            totalDisplay.textContent = `Q${total.toFixed(2)}`;
        }

        // Event Listeners para actualizar total cuando cambien los selects o cantidad
        if (idPlatoSelect) {
            idPlatoSelect.addEventListener('change', actualizarTotalDisplay);
        }

        if (idBebidaSelect) {
            idBebidaSelect.addEventListener('change', actualizarTotalDisplay);
        }

        if (cantidadInput) {
            cantidadInput.addEventListener('input', actualizarTotalDisplay);
        }

        // Editar orden
        document.querySelectorAll('.editar-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const mesa = btn.dataset.mesa || '';
                const plato = btn.dataset.plato || '';
                const bebida = btn.dataset.bebida || '';
                const descripcion = btn.dataset.descripcion || '';
                const cantidad = btn.dataset.cantidad || '1';

                const doFill = () => {
                    idInput.value = id || '';
                    form.id_mesa.value = mesa;
                    form.id_plato.value = plato;
                    form.id_bebida.value = bebida;
                    form.descripcion.value = descripcion;
                    form.cantidad.value = cantidad;
                    operacion.value = 'actualizar';

                    // Actualizar total display
                    actualizarTotalDisplay();

                    if (btnGuardar) btnGuardar.classList.add('d-none');
                    if (btnActualizar) btnActualizar.classList.remove('d-none');
                    if (btnCancelar) btnCancelar.classList.remove('d-none');

                    document.querySelectorAll('#tabla-ordenes tbody tr').forEach(r => r.classList.remove('table-primary'));
                    const tr = btn.closest('tr');
                    if (tr) { 
                        tr.classList.add('table-primary'); 
                        if (filaActivaSpan) filaActivaSpan.textContent = tr.cells[0].textContent; 
                    }
                };

                confirmAction({
                    title: 'Editar orden',
                    text: '¿Deseas editar esta orden?',
                    icon: 'question',
                    confirmText: 'Sí',
                    cancelText: 'Cancelar'
                }).then(ok => { if (ok) doFill(); });
            });
        });

        if (btnNuevo) btnNuevo.addEventListener('click', limpiar);
        if (btnCancelar) btnCancelar.addEventListener('click', limpiar);

        if (btnGuardar) btnGuardar.addEventListener('click', function () {
            if (!validar()) return;
            confirmAction({
                title: 'Guardar orden',
                text: '¿Deseas guardar esta orden?',
                icon: 'question',
                confirmText: 'Guardar',
                cancelText: 'Cancelar'
            }).then(ok => { if (ok) { operacion.value = 'crear'; form.submit(); }});
        });

        if (btnActualizar) btnActualizar.addEventListener('click', function () {
            if (!validar()) return;
            confirmAction({
                title: 'Actualizar orden',
                text: '¿Deseas guardar los cambios en esta orden?',
                icon: 'question',
                confirmText: 'Actualizar',
                cancelText: 'Cancelar'
            }).then(ok => { if (ok) { operacion.value = 'actualizar'; form.submit(); }});
        });

        // Inicializar
        limpiar();
    })();

});