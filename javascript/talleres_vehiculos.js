// TalleresVehiculos.js — gestión de formulario de talleres con SweetAlert2

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-taller');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idTallerInput = document.getElementById('id_taller');

    // Formateo de teléfono
    const telefonoInput = document.getElementById('telefono');
    if (telefonoInput) {
        telefonoInput.addEventListener('input', function () {
            // Permitir solo números, espacios y guiones
            this.value = this.value.replace(/[^\d\s\-]/g, '');
        });
    }

    // Validación de email
    const correoInput = document.getElementById('correo');
    if (correoInput) {
        correoInput.addEventListener('blur', function () {
            const email = this.value.trim();
            if (email && !isValidEmail(email)) {
                this.setCustomValidity('Por favor ingrese un correo electrónico válido');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
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
                    title: 'Guardar taller',
                    text: '¿Deseas guardar este taller?',
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
                if (confirm('¿Deseas guardar este taller?')) doSubmit();
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
                    title: 'Actualizar taller',
                    text: '¿Deseas guardar los cambios en este taller?',
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
            const nombre = this.getAttribute('data-nombre');
            const telefono = this.getAttribute('data-telefono');
            const correo = this.getAttribute('data-correo');
            const especialidad = this.getAttribute('data-especialidad');

            const doFill = () => {
                if (idTallerInput) idTallerInput.value = id || '';
                document.getElementById('nombre_taller').value = nombre || '';
                document.getElementById('telefono').value = telefono || '';
                document.getElementById('correo').value = correo || '';
                document.getElementById('id_especialidad').value = especialidad || '';

                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar taller',
                    text: `¿Deseas editar el taller "${nombre || id}"?`,
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
        if (idTallerInput) idTallerInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        document.getElementById('nombre_taller').focus();
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
        const nombre = document.getElementById('nombre_taller').value.trim();
        const correo = document.getElementById('correo').value.trim();
        const telefono = document.getElementById('telefono').value.trim();

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

        if (!nombre) { 
            showWarning('El nombre del taller es requerido'); 
            document.getElementById('nombre_taller').focus(); 
            return false; 
        }

        // Validar correo si se proporciona
        if (correo && !isValidEmail(correo)) {
            showWarning('Por favor ingrese un correo electrónico válido');
            document.getElementById('correo').focus();
            return false;
        }

        // Validar teléfono si se proporciona (mínimo 8 caracteres)
        if (telefono && telefono.replace(/\D/g, '').length < 8) {
            showWarning('El teléfono debe tener al menos 8 dígitos');
            document.getElementById('telefono').focus();
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
                    title: '¿Eliminar taller?',
                    text: 'Esta acción no se puede deshacer. El taller será eliminado permanentemente.',
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
                if (confirm('¿Eliminar taller? Esta acción no se puede deshacer.')) {
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
});