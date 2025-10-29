// MantenimientoElectrodomesticos.js — gestión de formulario de mantenimiento de electrodomésticos con SweetAlert2

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-mantenimiento');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idMantenimientoInput = document.getElementById('id_mantenimiento_elect');
    const fechaInput = document.getElementById('fecha_mantenimiento');

    // Validación de costo
    const costoInput = document.getElementById('costo_mantenimiento_q');
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
                if (operacionInput) operacionInput.value = 'crear_mantenimiento_elect';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar mantenimiento',
                    text: '¿Deseas registrar este mantenimiento de electrodoméstico?',
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
                if (confirm('¿Deseas registrar este mantenimiento de electrodoméstico?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_mantenimiento_elect';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar mantenimiento',
                    text: '¿Deseas guardar los cambios en este mantenimiento?',
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
            const mobiliario = this.getAttribute('data-mobiliario');
            const taller = this.getAttribute('data-taller');
            const descripcion = this.getAttribute('data-descripcion');
            const fecha = this.getAttribute('data-fecha');
            const costo = this.getAttribute('data-costo');

            const doFill = () => {
                if (idMantenimientoInput) idMantenimientoInput.value = id || '';
                document.getElementById('id_mobiliario').value = mobiliario || '';
                document.getElementById('id_taller').value = taller || '';
                document.getElementById('descripcion_mantenimiento').value = descripcion || '';
                document.getElementById('fecha_mantenimiento').value = fecha || '';
                document.getElementById('costo_mantenimiento_q').value = costo || '0.00';

                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar mantenimiento',
                    text: `¿Deseas editar el mantenimiento #${id || ''}?`,
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
        if (idMantenimientoInput) idMantenimientoInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_mantenimiento_elect';
        // Establecer fecha actual por defecto
        fechaInput.valueAsDate = new Date();
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('id_mobiliario').focus();
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
        const mobiliario = document.getElementById('id_mobiliario').value;
        const descripcion = document.getElementById('descripcion_mantenimiento').value.trim();
        const fecha = fechaInput.value;
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

        if (!mobiliario) { 
            showWarning('El electrodoméstico es requerido'); 
            document.getElementById('id_mobiliario').focus(); 
            return false; 
        }
        if (!descripcion) { 
            showWarning('La descripción del mantenimiento es requerida'); 
            document.getElementById('descripcion_mantenimiento').focus(); 
            return false; 
        }
        if (!fecha) { 
            showWarning('La fecha de mantenimiento es requerida'); 
            fechaInput.focus(); 
            return false; 
        }
        if (!costo || costo < 0) { 
            showWarning('El costo debe ser mayor o igual a 0'); 
            costoInput.focus(); 
            return false; 
        }

        // Validar que la fecha no sea en el futuro
        const fechaSeleccionada = new Date(fecha);
        const ahora = new Date();
        if (fechaSeleccionada > ahora) {
            showWarning('La fecha de mantenimiento no puede ser en el futuro');
            fechaInput.focus();
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
                    title: '¿Eliminar mantenimiento?',
                    text: 'Esta acción no se puede deshacer. El mantenimiento será eliminado permanentemente.',
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
                if (confirm('¿Eliminar mantenimiento? Esta acción no se puede deshacer.')) {
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