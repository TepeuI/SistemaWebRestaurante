// ViajesVehiculos.js — gestión de formulario de viajes con SweetAlert2

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-viaje');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idViajeInput = document.getElementById('id_viaje');

    // Validación de tiempo aproximado
    const tiempoInput = document.getElementById('tiempo_aproximado_min');
    if (tiempoInput) {
        tiempoInput.addEventListener('input', function () {
            // Asegurar que el valor sea positivo
            if (this.value < 1) {
                this.value = 1;
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
                if (operacionInput) operacionInput.value = 'crear';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardar viaje',
                    text: '¿Deseas guardar este viaje?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => { 
                    if (result.isConfirmed) doSubmit(); 
                });
            } else {
                if (confirm('¿Deseas guardar este viaje?')) doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function () {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar';
                form.submit();
            };
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar viaje',
                    text: '¿Deseas guardar los cambios en este viaje?',
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
            const ruta = this.getAttribute('data-ruta');
            const vehiculo = this.getAttribute('data-vehiculo');
            const piloto = this.getAttribute('data-piloto');
            const acompanante = this.getAttribute('data-acompanante');
            const fecha = this.getAttribute('data-fecha');
            const tiempo = this.getAttribute('data-tiempo');
            const descripcion = this.getAttribute('data-descripcion');

            const doFill = () => {
                if (idViajeInput) idViajeInput.value = id || '';
                document.getElementById('id_ruta').value = ruta || '';
                document.getElementById('id_vehiculo').value = vehiculo || '';
                document.getElementById('id_empleado_piloto').value = piloto || '';
                document.getElementById('id_empleado_acompanante').value = acompanante || '';
                document.getElementById('fecha_hora_salida').value = fecha || '';
                document.getElementById('tiempo_aproximado_min').value = tiempo || '';
                document.getElementById('descripcion_viaje').value = descripcion || '';

                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar viaje',
                    text: `¿Deseas editar el viaje #${id || ''}?`,
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
        if (idViajeInput) idViajeInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        // Establecer fecha y hora actual por defecto
        const now = new Date();
        // Ajustar a la zona horaria local
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('fecha_hora_salida').value = now.toISOString().slice(0, 16);
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
        const ruta = document.getElementById('id_ruta').value;
        const vehiculo = document.getElementById('id_vehiculo').value;
        const piloto = document.getElementById('id_empleado_piloto').value;
        const fecha = document.getElementById('fecha_hora_salida').value;
        const tiempo = document.getElementById('tiempo_aproximado_min').value;

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

        if (!ruta) { 
            showWarning('Seleccione una ruta'); 
            document.getElementById('id_ruta').focus(); 
            return false; 
        }
        if (!vehiculo) { 
            showWarning('Seleccione un vehículo'); 
            document.getElementById('id_vehiculo').focus(); 
            return false; 
        }
        if (!piloto) { 
            showWarning('Seleccione un empleado piloto'); 
            document.getElementById('id_empleado_piloto').focus(); 
            return false; 
        }
        if (!fecha) { 
            showWarning('La fecha y hora de salida son requeridas'); 
            document.getElementById('fecha_hora_salida').focus(); 
            return false; 
        }

        // Validar que la fecha no sea en el pasado
        const fechaSeleccionada = new Date(fecha);
        const ahora = new Date();
        if (fechaSeleccionada < ahora) {
            showWarning('La fecha y hora de salida no pueden ser en el pasado');
            document.getElementById('fecha_hora_salida').focus();
            return false;
        }

        // Validar tiempo aproximado si se proporciona
        if (tiempo && tiempo < 1) {
            showWarning('El tiempo aproximado debe ser mayor a 0 minutos');
            document.getElementById('tiempo_aproximado_min').focus();
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
                    title: '¿Eliminar viaje?',
                    text: 'Esta acción no se puede deshacer. El viaje será eliminado permanentemente.',
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
                if (confirm('¿Eliminar viaje? Esta acción no se puede deshacer.')) {
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
    if (document.getElementById('fecha_hora_salida')) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('fecha_hora_salida').value = now.toISOString().slice(0, 16);
    }
});