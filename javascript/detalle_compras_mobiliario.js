// DetalleComprasMobiliario.js — gestión de formulario de detalle de compras de mobiliario con SweetAlert2

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-detalle');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idCompraOriginalInput = document.getElementById('id_compra_mobiliario_original');
    const idMobiliarioOriginalInput = document.getElementById('id_mobiliario_original');
    const cantidadInput = document.getElementById('cantidad_de_compra');
    const costoInput = document.getElementById('costo_unitario');
    const montoDisplay = document.getElementById('monto_total_display');

    // Calcular monto total automáticamente
    function calcularMontoTotal() {
        const cantidad = parseFloat(cantidadInput.value) || 0;
        const costo = parseFloat(costoInput.value) || 0;
        const montoTotal = cantidad * costo;
        montoDisplay.value = 'Q ' + montoTotal.toFixed(2);
    }

    cantidadInput.addEventListener('input', calcularMontoTotal);
    costoInput.addEventListener('input', calcularMontoTotal);

    // Validación de cantidad y costo
    if (cantidadInput) {
        cantidadInput.addEventListener('input', function () {
            // Asegurar que el valor sea positivo
            if (this.value < 1) {
                this.value = 1;
            }
        });
    }

    if (costoInput) {
        costoInput.addEventListener('input', function () {
            // Asegurar que el valor sea positivo
            if (this.value < 0) {
                this.value = 0;
            }
        });
    }

    // Botones
    if (btnNuevo) btnNuevo.addEventListener('click', function () {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    if (btnGuardar) btnGuardar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'crear_detalle';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar detalle de compra',
                    text: '¿Deseas registrar este detalle de compra de mobiliario?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, registrar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => { 
                    if (result.isConfirmed) doSubmit(); 
                });
            } else {
                if (confirm('¿Deseas registrar este detalle de compra de mobiliario?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_detalle';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar detalle de compra',
                    text: '¿Deseas guardar los cambios en este detalle de compra?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, actualizar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d'
                }).then((result) => { 
                    if (result.isConfirmed) doSubmit(); 
                });
            } else {
                if (confirm('¿Deseas guardar los cambios?')) doSubmit();
            }
        }
    });

    if (btnCancelar) btnCancelar.addEventListener('click', function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Cancelar cambios',
                text: '¿Estás seguro de que deseas cancelar? Se perderán los cambios no guardados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'Continuar editando',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    limpiarFormulario();
                    mostrarBotonesGuardar();
                }
            });
        } else {
            if (confirm('¿Cancelar cambios?')) {
                limpiarFormulario();
                mostrarBotonesGuardar();
            }
        }
    });

    // Editar desde la tabla
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const compra = this.getAttribute('data-compra');
            const mobiliario = this.getAttribute('data-mobiliario');
            const cantidad = this.getAttribute('data-cantidad');
            const costo = this.getAttribute('data-costo');

            const doFill = () => {
                if (idCompraOriginalInput) idCompraOriginalInput.value = compra || '';
                if (idMobiliarioOriginalInput) idMobiliarioOriginalInput.value = mobiliario || '';
                document.getElementById('id_compra_mobiliario').value = compra || '';
                document.getElementById('id_mobiliario').value = mobiliario || '';
                document.getElementById('cantidad_de_compra').value = cantidad || '1';
                document.getElementById('costo_unitario').value = costo || '0.00';
                calcularMontoTotal();

                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar detalle de compra',
                    text: `¿Deseas editar el detalle de compra #${compra || ''}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, editar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) doFill();
                });
            } else {
                doFill();
            }
        });
    });

    function limpiarFormulario() {
        if (form) form.reset();
        if (idCompraOriginalInput) idCompraOriginalInput.value = '';
        if (idMobiliarioOriginalInput) idMobiliarioOriginalInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_detalle';
        // Establecer valores por defecto
        document.getElementById('cantidad_de_compra').value = '1';
        document.getElementById('costo_unitario').value = '';
        calcularMontoTotal();
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('id_compra_mobiliario').focus();
    }

    function mostrarBotonesGuardar() {
        if (btnGuardar) { 
            btnGuardar.style.display = 'inline-block'; 
            btnGuardar.disabled = false; 
        }
        if (btnActualizar) { 
            btnActualizar.style.display = 'none'; 
            btnActualizar.disabled = true; 
        }
        if (btnCancelar) btnCancelar.style.display = 'none';
    }

    function mostrarBotonesActualizar() {
        if (btnGuardar) { 
            btnGuardar.style.display = 'none'; 
            btnGuardar.disabled = true; 
        }
        if (btnActualizar) { 
            btnActualizar.style.display = 'inline-block'; 
            btnActualizar.disabled = false; 
        }
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    function validarFormulario() {
        const compra = document.getElementById('id_compra_mobiliario').value;
        const mobiliario = document.getElementById('id_mobiliario').value;
        const cantidad = cantidadInput.value;
        const costo = costoInput.value;

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Campo requerido', 
                    text: msg,
                    confirmButtonColor: '#ffc107'
                });
            } else {
                alert(msg);
            }
        };

        if (!compra) { 
            showWarning('La compra es requerida'); 
            document.getElementById('id_compra_mobiliario').focus(); 
            return false; 
        }
        if (!mobiliario) { 
            showWarning('El mobiliario es requerido'); 
            document.getElementById('id_mobiliario').focus(); 
            return false; 
        }
        if (!cantidad || cantidad < 1) { 
            showWarning('La cantidad debe ser mayor a 0'); 
            document.getElementById('cantidad_de_compra').focus(); 
            return false; 
        }
        if (!costo || costo <= 0) { 
            showWarning('El costo unitario debe ser mayor a 0'); 
            document.getElementById('costo_unitario').focus(); 
            return false; 
        }

        return true;
    }

    // Confirmar eliminación con SweetAlert
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const frm = this;
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar detalle de compra?',
                    text: 'Esta acción no se puede deshacer. El detalle de compra será eliminado permanentemente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    dangerMode: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        frm.submit();
                    }
                });
            } else {
                if (confirm('¿Eliminar detalle de compra? Esta acción no se puede deshacer.')) {
                    frm.submit();
                }
            }
        });
    });

    // Mostrar mensaje enviado desde el servidor (si existe)
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const icon = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' : 'error';
            if (typeof Swal !== 'undefined') {
                Swal.fire({ 
                    title: icon === 'success' ? 'Éxito' : 'Atención', 
                    text: m.text, 
                    icon: icon,
                    confirmButtonColor: icon === 'success' ? '#28a745' : '#dc3545'
                });
            } else {
                alert(m.text);
            }
            // limpiar para no mostrar de nuevo
            try { delete window.__mensaje; } catch(e) { window.__mensaje = null; }
        }
    } catch (e) { /* no bloquear la carga si falla */ }

    // Inicializar estado del formulario
    limpiarFormulario();
});