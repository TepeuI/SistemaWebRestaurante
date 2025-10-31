// ReportesAccidentes.js — gestión de formulario de reportes de accidentes con SweetAlert2

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-accidentes');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idAccidenteInput = document.getElementById('id_accidente');
    const descripcionInput = document.getElementById('descripcion_accidente');
    const fechaHoraInput = document.getElementById('fecha_hora');

    // Botones
    if (btnNuevo) btnNuevo.addEventListener('click', function () {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    if (btnGuardar) btnGuardar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'crear_accidente';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Registrar accidente',
                    text: '¿Deseas registrar este reporte de accidente?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, registrar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    iconColor: '#dc3545'
                }).then((result) => { 
                    if (result.isConfirmed) doSubmit(); 
                });
            } else {
                if (confirm('¿Deseas registrar este reporte de accidente?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar_accidente';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar reporte de accidente',
                    text: '¿Deseas guardar los cambios en este reporte?',
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
            const viaje = this.getAttribute('data-viaje');
            const empleado = this.getAttribute('data-empleado');
            const descripcion = this.getAttribute('data-descripcion');
            const fecha = this.getAttribute('data-fecha');

            const doFill = () => {
                if (idAccidenteInput) idAccidenteInput.value = id || '';
                document.getElementById('id_viaje').value = viaje || '';
                document.getElementById('id_empleado').value = empleado || '';
                descripcionInput.value = descripcion || '';
                
                // Formatear fecha para el input datetime-local
                fechaHoraInput.value = fecha || '';

                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar reporte de accidente',
                    text: `¿Deseas editar el reporte de accidente #${id || ''}?`,
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
        if (idAccidenteInput) idAccidenteInput.value = '';
        if (operacionInput) operacionInput.value = 'crear_accidente';
        // Establecer fecha y hora actual por defecto
        const now = new Date();
        // Ajustar a la zona horaria local
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        fechaHoraInput.value = now.toISOString().slice(0, 16);
        mostrarBotonesGuardar();
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
        const viaje = document.getElementById('id_viaje').value;
        const empleado = document.getElementById('id_empleado').value;
        const descripcion = descripcionInput.value.trim();
        const fecha = fechaHoraInput.value;

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Validación requerida', 
                    text: msg,
                    confirmButtonColor: '#ffc107'
                });
            } else {
                alert(msg);
            }
        };

        if (!viaje) { 
            showWarning('El viaje relacionado es requerido'); 
            document.getElementById('id_viaje').focus(); 
            return false; 
        }
        if (!empleado) { 
            showWarning('El empleado que reporta es requerido'); 
            document.getElementById('id_empleado').focus(); 
            return false; 
        }
        if (!descripcion) { 
            showWarning('La descripción del accidente es requerida'); 
            descripcionInput.focus(); 
            return false; 
        }
        if (descripcion.length < 50) { 
            showWarning('La descripción debe tener al menos 50 caracteres'); 
            descripcionInput.focus(); 
            return false; 
        }
        if (!fecha) { 
            showWarning('La fecha y hora del accidente son requeridas'); 
            fechaHoraInput.focus(); 
            return false; 
        }

        // Validar que la fecha no sea en el futuro
        const fechaSeleccionada = new Date(fecha);
        const ahora = new Date();
        if (fechaSeleccionada > ahora) {
            showWarning('La fecha y hora del accidente no pueden ser en el futuro');
            fechaHoraInput.focus();
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
                    title: '¿Eliminar reporte de accidente?',
                    text: 'Esta acción no se puede deshacer. El reporte será eliminado permanentemente.',
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
                if (confirm('¿Eliminar reporte de accidente? Esta acción no se puede deshacer.')) {
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
    // Establecer fecha y hora actual por defecto al cargar la página
    if (fechaHoraInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        fechaHoraInput.value = now.toISOString().slice(0, 16);
    }
});