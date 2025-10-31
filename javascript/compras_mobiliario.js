// ComprasMobiliario.js — gestión de formulario de compras de mobiliario con SweetAlert2

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-compras');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idCompraInput = document.getElementById('id_compra_mobiliario');

    // Validación de monto
    const montoInput = document.getElementById('monto_total_compra_q');
    if (montoInput) {
        montoInput.addEventListener('input', function () {
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
                if (operacionInput) operacionInput.value = 'crear_compra';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar compra',
                    text: '¿Deseas registrar esta compra de mobiliario?',
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
                if (confirm('¿Deseas registrar esta compra de mobiliario?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_compra';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar compra',
                    text: '¿Deseas guardar los cambios en esta compra?',
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
            const id = this.getAttribute('data-id');
            const proveedor = this.getAttribute('data-proveedor');
            const fecha = this.getAttribute('data-fecha');
            const monto = this.getAttribute('data-monto');

            const doFill = () => {
                if (idCompraInput) idCompraInput.value = id || '';
                document.getElementById('id_proveedor').value = proveedor || '';
                document.getElementById('fecha_de_compra').value = fecha || '';
                document.getElementById('monto_total_compra_q').value = monto || '';

                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar compra',
                    text: `¿Deseas editar la compra #${id || ''}?`,
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
        if (idCompraInput) idCompraInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_compra';
        // Establecer fecha actual por defecto
        document.getElementById('fecha_de_compra').valueAsDate = new Date();
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('id_proveedor').focus();
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
        const proveedor = document.getElementById('id_proveedor').value;
        const fecha = document.getElementById('fecha_de_compra').value;
        const monto = document.getElementById('monto_total_compra_q').value;

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

        if (!proveedor) { 
            showWarning('El proveedor es requerido'); 
            document.getElementById('id_proveedor').focus(); 
            return false; 
        }
        if (!fecha) { 
            showWarning('La fecha de compra es requerida'); 
            document.getElementById('fecha_de_compra').focus(); 
            return false; 
        }
        if (!monto || monto <= 0) { 
            showWarning('El monto total debe ser mayor a 0'); 
            document.getElementById('monto_total_compra_q').focus(); 
            return false; 
        }

        // Validar que la fecha no sea en el futuro
        const fechaSeleccionada = new Date(fecha);
        const ahora = new Date();
        if (fechaSeleccionada > ahora) {
            showWarning('La fecha de compra no puede ser en el futuro');
            document.getElementById('fecha_de_compra').focus();
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
                    title: '¿Eliminar compra?',
                    text: 'Esta acción no se puede deshacer. La compra será eliminada permanentemente.',
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
                if (confirm('¿Eliminar compra? Esta acción no se puede deshacer.')) {
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
    mostrarBotonesGuardar();
    // Establecer fecha actual por defecto al cargar la página
    if (document.getElementById('fecha_de_compra')) {
        document.getElementById('fecha_de_compra').valueAsDate = new Date();
    }
});