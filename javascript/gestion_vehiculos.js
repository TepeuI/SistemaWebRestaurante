// Vehiculos.js — gestión de formulario de vehículos con SweetAlert2

document.addEventListener('DOMContentLoaded', function () {
    // Elementos
    const form = document.getElementById('form-vehiculo');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idPlacaInput = document.getElementById('id_placa');
    const contenedorLista = document.getElementById('tabla-vehiculos');

    // Formateo de placa (opcional, si se desea un formato específico)
    const placaInput = document.getElementById('no_placas');
    if (placaInput) {
        placaInput.addEventListener('input', function () {
            // Convertir a mayúsculas automáticamente
            this.value = this.value.toUpperCase();
        });
    }

    // Sanitización de marca y modelo
    const marcaInput = document.getElementById('marca');
    const modeloInput = document.getElementById('modelo');
    
    function sanitizeTextField(el) {
        if (!el) return;
        el.addEventListener('input', function () {
            let v = this.value || '';
            // Permitir letras, números, espacios y algunos caracteres comunes
            v = v.replace(/[^A-Za-z0-9ÁÉÍÓÚÜÑáéíóúüñ\s\-\.]/g, '');
            v = v.replace(/\s+/g, ' ');
            this.value = v;
        });
    }
    
    sanitizeTextField(marcaInput);
    sanitizeTextField(modeloInput);

    // Validación de año
    const anioInput = document.getElementById('anio_vehiculo');
    if (anioInput) {
        anioInput.addEventListener('input', function () {
            const currentYear = new Date().getFullYear();
            const year = parseInt(this.value);
            if (this.value && (year < 1900 || year > currentYear + 1)) {
                this.setCustomValidity(`El año debe estar entre 1900 y ${currentYear + 1}`);
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Helpers UI
    function habilitarCampos() {
        inputs.forEach(input => {
            if (input.type !== 'hidden') input.disabled = false;
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
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
                    title: 'Guardar vehículo',
                    text: '¿Deseas guardar este vehículo?',
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
                if (confirm('¿Deseas guardar este vehículo?')) doSubmit();
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
                    title: 'Actualizar vehículo',
                    text: '¿Deseas guardar los cambios en este vehículo?',
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
            const placas = this.getAttribute('data-placas');
            const marca = this.getAttribute('data-marca');
            const modelo = this.getAttribute('data-modelo');
            const anio = this.getAttribute('data-anio');
            const descripcion = this.getAttribute('data-descripcion');
            const estado = this.getAttribute('data-estado');
            const mobiliario = this.getAttribute('data-mobiliario');

            const doFill = () => {
                if (idPlacaInput) idPlacaInput.value = id || '';
                document.getElementById('no_placas').value = placas || '';
                document.getElementById('marca').value = marca || '';
                document.getElementById('modelo').value = modelo || '';
                document.getElementById('anio_vehiculo').value = anio || '';
                document.getElementById('descripcion').value = descripcion || '';
                document.getElementById('estado').value = estado || 'ACTIVO';
                document.getElementById('id_mobiliario').value = mobiliario || '';

                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar vehículo',
                    text: `¿Deseas editar el vehículo con placa "${placas || id}"?`,
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
        if (idPlacaInput) idPlacaInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        // Establecer valores por defecto
        document.getElementById('estado').value = 'ACTIVO';
        document.getElementById('id_mobiliario').value = '';
        mostrarBotonesGuardar();
        
        // Enfocar el primer campo después de limpiar
        if (placaInput) placaInput.focus();
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
        const placas = document.getElementById('no_placas').value.trim();
        const marca = document.getElementById('marca').value.trim();
        const modelo = document.getElementById('modelo').value.trim();
        const anio = document.getElementById('anio_vehiculo').value;
        const estado = document.getElementById('estado').value;

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

        if (!placas) { 
            showWarning('La placa es requerida'); 
            if (placaInput) placaInput.focus(); 
            return false; 
        }

        if (!marca) { 
            showWarning('La marca es requerida'); 
            if (marcaInput) marcaInput.focus(); 
            return false; 
        }

        if (!modelo) { 
            showWarning('El modelo es requerido'); 
            if (modeloInput) modeloInput.focus(); 
            return false; 
        }

        if (!anio) { 
            showWarning('El año es requerido'); 
            if (anioInput) anioInput.focus(); 
            return false; 
        }

        // Validar rango del año
        const currentYear = new Date().getFullYear();
        const yearNum = parseInt(anio);
        if (yearNum < 1900 || yearNum > currentYear + 1) {
            showWarning(`El año debe estar entre 1900 y ${currentYear + 1}`);
            if (anioInput) anioInput.focus();
            return false;
        }

        if (!estado) { 
            showWarning('El estado es requerido'); 
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
                    title: '¿Eliminar vehículo?',
                    text: 'Esta acción no se puede deshacer. El vehículo será eliminado permanentemente.',
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
                if (confirm('¿Eliminar vehículo? Esta acción no se puede deshacer.')) {
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

    // Inicializar estado del formulario - SIN BLOQUEO INICIAL
    mostrarBotonesGuardar();
});

// Sistema de ayuda para formularios (igual que en empleados)
(function(){
    function initFormTextToggle() {
        var form = document.getElementById('form-vehiculo');
        if (!form) return;

        var fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(function(f){
            f.addEventListener('focus', function(){
                var container = f.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col, .form-group') || f.parentElement;
                if (!container) return;
                var help = container.querySelector('small.form-text.help-text');
                if (help) help.classList.add('visible');
            });
            f.addEventListener('blur', function(){
                var container = f.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col, .form-group') || f.parentElement;
                if (!container) return;
                var help = container.querySelector('small.form-text.help-text');
                if (help) help.classList.remove('visible');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormTextToggle);
    } else {
        initFormTextToggle();
    }
})();