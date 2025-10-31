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
                title: '¿Eliminar factura?',
                text: 'Esta acción eliminará la factura y sus detalles de cobro. No se puede deshacer.',
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

    // ---- Lógica específica de facturación ----
    (function () {
        const form = document.getElementById('form-facturacion');
        if (!form) return;

        const operacion = document.getElementById('operacion');
        const idInput = document.getElementById('id_factura');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const filaActivaSpan = document.getElementById('fila-activa');
        const idClienteSelect = document.getElementById('id_cliente');
        const idOrdenSelect = document.getElementById('id_orden');
        const montoOrdenDisplay = document.getElementById('monto_orden_display');
        const btnAgregarCobro = document.getElementById('btn-agregar-cobro');
        const detallesCobroContainer = document.getElementById('detalles-cobro-container');
        const clienteInfoDisplay = document.getElementById('cliente-info-display');
        const nitDisplay = document.getElementById('nit-display');
        const detallesOrdenContainer = document.getElementById('detalles-orden-container');
        const detallesOrdenContent = document.getElementById('detalles-orden-content');

        let contadorDetallesCobro = 0;

        function limpiar() {
            form.reset();
            idInput.value = '';
            operacion.value = 'crear';
            if (btnGuardar) btnGuardar.classList.remove('d-none');
            if (btnActualizar) btnActualizar.classList.add('d-none');
            if (btnCancelar) btnCancelar.classList.add('d-none');
            if (filaActivaSpan) filaActivaSpan.textContent = 'ninguna';
            
            // Establecer fecha actual por defecto
            const now = new Date();
            const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            document.getElementById('fecha_emision').value = localDateTime;
            
            // Limpiar detalles de cobro
            detallesCobroContainer.innerHTML = '';
            contadorDetallesCobro = 0;
            
            // Ocultar info cliente
            clienteInfoDisplay.classList.add('d-none');
            
            // Ocultar detalles de orden
            detallesOrdenContainer.style.display = 'none';
            
            // Actualizar monto display
            actualizarMontoDisplay();
            
            document.querySelectorAll('#tabla-facturas tbody tr').forEach(r => r.classList.remove('table-primary'));
            
            // Agregar un detalle de cobro vacío por defecto
            agregarDetalleCobro();
        }

        function actualizarInfoCliente() {
            const selectedOption = idClienteSelect.selectedOptions[0];
            if (selectedOption && selectedOption.value) {
                const nit = selectedOption.dataset.nit || '';
                nitDisplay.textContent = nit;
                clienteInfoDisplay.classList.remove('d-none');
            } else {
                clienteInfoDisplay.classList.add('d-none');
            }
        }

        function mostrarDetallesOrden(idOrden) {
            const orden = window.ordenesDisponibles.find(o => o.id_orden == idOrden);
            if (orden) {
                // En una implementación real, aquí harías una petición AJAX para obtener los detalles
                // Por ahora, mostramos información básica
                detallesOrdenContent.innerHTML = `
                    <div class="detalle-orden-item">
                        <strong>Orden #${orden.id_orden}</strong> - Mesa ${orden.numero_mesa}
                    </div>
                    <div class="detalle-orden-item">
                        <strong>Total:</strong> Q${parseFloat(orden.total).toFixed(2)}
                    </div>
                    <div class="detalle-orden-item">
                        <strong>Fecha:</strong> ${new Date(orden.fecha_orden).toLocaleString()}
                    </div>
                    <div class="detalle-orden-item">
                        <em>Los detalles completos de la orden se cargarán al guardar la factura</em>
                    </div>
                `;
                detallesOrdenContainer.style.display = 'block';
            } else {
                detallesOrdenContainer.style.display = 'none';
            }
        }

        function validar() {
            const codigoSerie = (form.codigo_serie.value || '').trim();
            const fechaEmision = (form.fecha_emision.value || '').trim();
            const idCliente = parseInt(form.id_cliente.value, 10);
            const idOrden = parseInt(form.id_orden.value, 10);

            if (!codigoSerie) {
                showAlert('error', 'Validación', 'El código de serie es requerido');
                return false;
            }
            if (!fechaEmision) {
                showAlert('error', 'Validación', 'La fecha de emisión es requerida');
                return false;
            }
            if (isNaN(idCliente) || idCliente <= 0) {
                showAlert('error', 'Validación', 'Debe seleccionar un cliente');
                return false;
            }
            if (isNaN(idOrden) || idOrden <= 0) {
                showAlert('error', 'Validación', 'Debe seleccionar una orden válida');
                return false;
            }

            // Validar detalles de cobro
            const detalles = obtenerDetallesCobro();
            if (detalles.length === 0) {
                showAlert('error', 'Validación', 'Debe agregar al menos un método de cobro');
                return false;
            }

            const totalDetalles = detalles.reduce((sum, detalle) => sum + parseFloat(detalle.monto_detalle_q), 0);
            const montoOrden = parseFloat(idOrdenSelect.selectedOptions[0]?.dataset.total || 0);

            if (Math.abs(totalDetalles - montoOrden) > 0.01) {
                showAlert('error', 'Validación', 
                    `El total de los métodos de cobro (Q${totalDetalles.toFixed(2)}) debe coincidir con el monto de la orden (Q${montoOrden.toFixed(2)})`);
                return false;
            }

            return true;
        }

        function actualizarMontoDisplay() {
            const selectedOption = idOrdenSelect.selectedOptions[0];
            const monto = selectedOption ? parseFloat(selectedOption.dataset.total || 0) : 0;
            montoOrdenDisplay.textContent = `Q${monto.toFixed(2)}`;
            
            // Mostrar detalles de la orden si hay una seleccionada
            if (selectedOption && selectedOption.value) {
                mostrarDetallesOrden(selectedOption.value);
            } else {
                detallesOrdenContainer.style.display = 'none';
            }
        }

        function agregarDetalleCobro(detalle = null) {
            const detalleId = contadorDetallesCobro++;
            const html = `
                <div class="detalle-cobro-item" data-detalle-id="${detalleId}">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label">Tipo de Cobro</label>
                            <select name="detalles_cobro[${detalleId}][id_tipo_cobro]" class="form-select tipo-cobro-select" required>
                                <option value="">Seleccionar tipo...</option>
                                ${window.tiposCobro.map(tc => 
                                    `<option value="${tc.id_tipo_cobro}" ${detalle && detalle.id_tipo_cobro == tc.id_tipo_cobro ? 'selected' : ''}>
                                        ${tc.tipo_cobro}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Monto</label>
                            <input type="number" name="detalles_cobro[${detalleId}][monto_detalle_q]" 
                                   class="form-control monto-cobro-input" 
                                   step="0.01" min="0.01" 
                                   value="${detalle ? detalle.monto_detalle_q : ''}" 
                                   placeholder="0.00" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-eliminar-cobro">
                                ✕
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            detallesCobroContainer.insertAdjacentHTML('beforeend', html);

            // Agregar evento al botón eliminar
            const nuevoDetalle = detallesCobroContainer.querySelector(`[data-detalle-id="${detalleId}"]`);
            nuevoDetalle.querySelector('.btn-eliminar-cobro').addEventListener('click', function() {
                nuevoDetalle.remove();
                recalcularIndices();
            });

            // Agregar eventos para recalcular totales
            nuevoDetalle.querySelector('.monto-cobro-input').addEventListener('input', actualizarMontoDisplay);
        }

        function obtenerDetallesCobro() {
            const detalles = [];
            const inputs = detallesCobroContainer.querySelectorAll('.detalle-cobro-item');
            
            inputs.forEach(detalle => {
                const idTipoCobro = detalle.querySelector('.tipo-cobro-select').value;
                const monto = detalle.querySelector('.monto-cobro-input').value;
                
                if (idTipoCobro && monto) {
                    detalles.push({
                        id_tipo_cobro: parseInt(idTipoCobro),
                        monto_detalle_q: parseFloat(monto)
                    });
                }
            });
            
            return detalles;
        }

        function recalcularIndices() {
            const detalles = detallesCobroContainer.querySelectorAll('.detalle-cobro-item');
            detalles.forEach((detalle, index) => {
                const select = detalle.querySelector('.tipo-cobro-select');
                const input = detalle.querySelector('.monto-cobro-input');
                
                select.name = `detalles_cobro[${index}][id_tipo_cobro]`;
                input.name = `detalles_cobro[${index}][monto_detalle_q]`;
                detalle.dataset.detalleId = index;
            });
            contadorDetallesCobro = detalles.length;
        }

        // Event Listeners
        if (idClienteSelect) {
            idClienteSelect.addEventListener('change', actualizarInfoCliente);
        }

        if (idOrdenSelect) {
            idOrdenSelect.addEventListener('change', actualizarMontoDisplay);
        }

        if (btnAgregarCobro) {
            btnAgregarCobro.addEventListener('click', function() {
                agregarDetalleCobro();
            });
        }

        // Editar factura
        document.querySelectorAll('.editar-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const codigoSerie = btn.dataset.codigoSerie || '';
                const fechaEmision = btn.dataset.fechaEmision || '';
                const idCliente = btn.dataset.idCliente || '';
                const idOrden = btn.dataset.idOrden || '';
                let detallesCobro = [];

                try {
                    detallesCobro = JSON.parse(btn.dataset.detallesCobro || '[]');
                } catch (e) {
                    console.error('Error parsing detalles cobro:', e);
                }

                const doFill = () => {
                    idInput.value = id || '';
                    form.codigo_serie.value = codigoSerie;
                    form.fecha_emision.value = fechaEmision;
                    form.id_cliente.value = idCliente;
                    form.id_orden.value = idOrden;
                    operacion.value = 'actualizar';

                    // Actualizar info cliente
                    actualizarInfoCliente();
                    
                    // Actualizar monto display
                    actualizarMontoDisplay();

                    // Limpiar y cargar detalles de cobro
                    detallesCobroContainer.innerHTML = '';
                    contadorDetallesCobro = 0;
                    detallesCobro.forEach(detalle => {
                        agregarDetalleCobro(detalle);
                    });

                    if (btnGuardar) btnGuardar.classList.add('d-none');
                    if (btnActualizar) btnActualizar.classList.remove('d-none');
                    if (btnCancelar) btnCancelar.classList.remove('d-none');

                    document.querySelectorAll('#tabla-facturas tbody tr').forEach(r => r.classList.remove('table-primary'));
                    const tr = btn.closest('tr');
                    if (tr) { 
                        tr.classList.add('table-primary'); 
                        if (filaActivaSpan) filaActivaSpan.textContent = tr.cells[0].textContent; 
                    }
                };

                confirmAction({
                    title: 'Editar factura',
                    text: '¿Deseas editar esta factura?',
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
                title: 'Guardar factura',
                text: '¿Deseas guardar esta factura?',
                icon: 'question',
                confirmText: 'Guardar',
                cancelText: 'Cancelar'
            }).then(ok => { if (ok) { operacion.value = 'crear'; form.submit(); }});
        });

        if (btnActualizar) btnActualizar.addEventListener('click', function () {
            if (!validar()) return;
            confirmAction({
                title: 'Actualizar factura',
                text: '¿Deseas guardar los cambios en esta factura?',
                icon: 'question',
                confirmText: 'Actualizar',
                cancelText: 'Cancelar'
            }).then(ok => { if (ok) { operacion.value = 'actualizar'; form.submit(); }});
        });

        // Inicializar
        limpiar();
        
        // Agregar un detalle de cobro vacío por defecto
        agregarDetalleCobro();
    })();

});