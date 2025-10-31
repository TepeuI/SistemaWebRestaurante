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
        const idMesaSelect = document.getElementById('id_mesa');
        const btnAgregarDetalle = document.getElementById('btn-agregar-detalle');
        const detallesContainer = document.getElementById('detalles-orden-container');
        const totalDisplay = document.getElementById('total_display');

        let contadorDetalles = 0;

        function limpiar() {
            form.reset();
            idInput.value = '';
            operacion.value = 'crear';
            if (btnGuardar) btnGuardar.classList.remove('d-none');
            if (btnActualizar) btnActualizar.classList.add('d-none');
            if (btnCancelar) btnCancelar.classList.add('d-none');
            if (filaActivaSpan) filaActivaSpan.textContent = 'ninguna';
            
            // Limpiar detalles
            detallesContainer.innerHTML = '';
            contadorDetalles = 0;
            
            // Actualizar total display
            actualizarTotalDisplay();
            
            document.querySelectorAll('#tabla-ordenes tbody tr').forEach(r => r.classList.remove('table-primary'));
            
            // Agregar un detalle vacío por defecto
            agregarDetalle();
        }

        function validar() {
            const idMesa = parseInt(form.id_mesa.value, 10);

            if (isNaN(idMesa) || idMesa <= 0) {
                showAlert('error', 'Validación', 'Debe seleccionar una mesa');
                return false;
            }

            // Validar que haya al menos un detalle con producto
            const detalles = obtenerDetalles();
            if (detalles.length === 0) {
                showAlert('error', 'Validación', 'Debe agregar al menos un plato o bebida a la orden');
                return false;
            }

            // Validar que cada detalle tenga al menos un producto y cantidad válida
            for (let detalle of detalles) {
                if ((!detalle.id_plato || detalle.id_plato <= 0) && (!detalle.id_bebida || detalle.id_bebida <= 0)) {
                    showAlert('error', 'Validación', 'Cada detalle debe tener al menos un plato o bebida seleccionado');
                    return false;
                }
                
                if (!detalle.cantidad || detalle.cantidad <= 0) {
                    showAlert('error', 'Validación', 'La cantidad debe ser mayor a 0 en todos los detalles');
                    return false;
                }
            }

            return true;
        }

        function calcularSubtotal(detalleElement) {
            const platoSelect = detalleElement.querySelector('.plato-select');
            const bebidaSelect = detalleElement.querySelector('.bebida-select');
            const cantidadInput = detalleElement.querySelector('.cantidad-input');
            const subtotalDisplay = detalleElement.querySelector('.subtotal-display');
            
            const precioPlato = platoSelect.selectedOptions[0] ? parseFloat(platoSelect.selectedOptions[0].dataset.precio || 0) : 0;
            const precioBebida = bebidaSelect.selectedOptions[0] ? parseFloat(bebidaSelect.selectedOptions[0].dataset.precio || 0) : 0;
            const cantidad = parseInt(cantidadInput.value, 10) || 1;
            
            const subtotal = (precioPlato + precioBebida) * cantidad;
            subtotalDisplay.textContent = `Q${subtotal.toFixed(2)}`;
            
            return subtotal;
        }

        function calcularTotalOrden() {
            let total = 0;
            const detalles = detallesContainer.querySelectorAll('.detalle-item');
            
            detalles.forEach(detalle => {
                total += calcularSubtotal(detalle);
            });
            
            return total;
        }

        function actualizarTotalDisplay() {
            const total = calcularTotalOrden();
            totalDisplay.textContent = `Q${total.toFixed(2)}`;
        }

        function agregarDetalle(detalle = null) {
            const detalleId = contadorDetalles++;
            const html = `
                <div class="detalle-item" data-detalle-id="${detalleId}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Plato</label>
                            <select name="detalles[${detalleId}][id_plato]" class="form-select plato-select">
                                <option value="">Seleccionar plato...</option>
                                ${window.platos.map(plato => 
                                    `<option value="${plato.id_plato}" 
                                            data-precio="${plato.precio_unitario}"
                                            ${detalle && detalle.id_plato == plato.id_plato ? 'selected' : ''}>
                                        ${plato.nombre_plato} - Q${parseFloat(plato.precio_unitario).toFixed(2)}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bebida</label>
                            <select name="detalles[${detalleId}][id_bebida]" class="form-select bebida-select">
                                <option value="">Seleccionar bebida...</option>
                                ${window.bebidas.map(bebida => 
                                    `<option value="${bebida.id_bebida}" 
                                            data-precio="${bebida.precio_unitario}"
                                            ${detalle && detalle.id_bebida == bebida.id_bebida ? 'selected' : ''}>
                                        ${bebida.descripcion} - Q${parseFloat(bebida.precio_unitario).toFixed(2)}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Cantidad</label>
                            <input type="number" name="detalles[${detalleId}][cantidad]" 
                                   class="form-control cantidad-input" 
                                   min="1" value="${detalle ? detalle.cantidad : 1}" required>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Subtotal</label>
                            <div class="subtotal-display">Q0.00</div>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-eliminar-detalle">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            detallesContainer.insertAdjacentHTML('beforeend', html);

            // Agregar eventos al nuevo detalle
            const nuevoDetalle = detallesContainer.querySelector(`[data-detalle-id="${detalleId}"]`);
            
            // Eventos para recalcular subtotales y total
            nuevoDetalle.querySelector('.plato-select').addEventListener('change', function() {
                calcularSubtotal(nuevoDetalle);
                actualizarTotalDisplay();
            });
            
            nuevoDetalle.querySelector('.bebida-select').addEventListener('change', function() {
                calcularSubtotal(nuevoDetalle);
                actualizarTotalDisplay();
            });
            
            nuevoDetalle.querySelector('.cantidad-input').addEventListener('input', function() {
                calcularSubtotal(nuevoDetalle);
                actualizarTotalDisplay();
            });

            // Evento para eliminar detalle
            nuevoDetalle.querySelector('.btn-eliminar-detalle').addEventListener('click', function() {
                if (detallesContainer.querySelectorAll('.detalle-item').length > 1) {
                    nuevoDetalle.remove();
                    recalcularIndices();
                    actualizarTotalDisplay();
                } else {
                    showAlert('error', 'Validación', 'Debe haber al menos un detalle en la orden');
                }
            });

            // Calcular subtotal inicial
            calcularSubtotal(nuevoDetalle);
            actualizarTotalDisplay();
        }

        function obtenerDetalles() {
            const detalles = [];
            const elementos = detallesContainer.querySelectorAll('.detalle-item');
            
            elementos.forEach(elemento => {
                const idPlato = elemento.querySelector('.plato-select').value;
                const idBebida = elemento.querySelector('.bebida-select').value;
                const cantidad = elemento.querySelector('.cantidad-input').value;
                
                if ((idPlato && idPlato > 0) || (idBebida && idBebida > 0)) {
                    detalles.push({
                        id_plato: parseInt(idPlato) || 0,
                        id_bebida: parseInt(idBebida) || 0,
                        cantidad: parseInt(cantidad) || 1
                    });
                }
            });
            
            return detalles;
        }

        function recalcularIndices() {
            const detalles = detallesContainer.querySelectorAll('.detalle-item');
            detalles.forEach((detalle, index) => {
                const platoSelect = detalle.querySelector('.plato-select');
                const bebidaSelect = detalle.querySelector('.bebida-select');
                const cantidadInput = detalle.querySelector('.cantidad-input');
                
                platoSelect.name = `detalles[${index}][id_plato]`;
                bebidaSelect.name = `detalles[${index}][id_bebida]`;
                cantidadInput.name = `detalles[${index}][cantidad]`;
                detalle.dataset.detalleId = index;
            });
            contadorDetalles = detalles.length;
        }

        // Event Listeners
        if (btnAgregarDetalle) {
            btnAgregarDetalle.addEventListener('click', function() {
                agregarDetalle();
            });
        }

        // Editar orden
        document.querySelectorAll('.editar-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const mesa = btn.dataset.mesa || '';
                const descripcion = btn.dataset.descripcion || '';
                let detalles = [];

                try {
                    detalles = JSON.parse(btn.dataset.detalles || '[]');
                } catch (e) {
                    console.error('Error parsing detalles:', e);
                }

                const doFill = () => {
                    idInput.value = id || '';
                    form.id_mesa.value = mesa;
                    form.descripcion.value = descripcion;
                    operacion.value = 'actualizar';

                    // Limpiar y cargar detalles
                    detallesContainer.innerHTML = '';
                    contadorDetalles = 0;
                    detalles.forEach(detalle => {
                        agregarDetalle(detalle);
                    });

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