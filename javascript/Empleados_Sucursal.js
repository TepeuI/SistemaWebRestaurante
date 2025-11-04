// <!--Ernesto David Samayoa Jocol 0901-22-3415-->
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Empleado_Sucursal.js] DOMContentLoaded: inicio');

    // Elementos base
    const form = document.getElementById('form-asignacion');
    const inputs = form ? form.querySelectorAll('input, select, textarea') : [];
    const operacionInput = document.getElementById('operacion');
    const idAsignacionInput = document.getElementById('id_asignacion');
    const empleadoInput = document.getElementById('id_empleado');
    const sucursalInput = document.getElementById('id_sucursal');
    const fechaInput = document.getElementById('fecha_asignacion');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnCancelar = document.getElementById('btn-cancelar');

    // ---------- Funciones UI ----------
    function limpiarFormulario() {
        if (form) form.reset();
        if (idAsignacionInput) idAsignacionInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        mostrarBotonesGuardar();
    }

    function habilitarCampos() {
        inputs.forEach(input => {
            if (input.type !== 'hidden') input.disabled = false;
        });
        if (btnGuardar) btnGuardar.disabled = false;
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    function mostrarBotonesGuardar() {
        if (btnGuardar) { btnGuardar.style.display = 'inline-block'; btnGuardar.disabled = false; }
        if (btnActualizar) { btnActualizar.style.display = 'none'; btnActualizar.disabled = true; }
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    function mostrarBotonesActualizar() {
        if (btnGuardar) { btnGuardar.style.display = 'none'; btnGuardar.disabled = true; }
        if (btnActualizar) { btnActualizar.style.display = 'inline-block'; btnActualizar.disabled = false; }
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    // ---------- Validación ----------
    function validarFormulario() {
        const empleado = empleadoInput ? empleadoInput.value.trim() : '';
        const sucursal = sucursalInput ? sucursalInput.value.trim() : '';
        const fecha = fechaInput ? fechaInput.value.trim() : '';

        const showWarning = (msg) => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'Campo requerido', text: msg });
            } else alert(msg);
        };

        if (!empleado) {
            showWarning('Debe seleccionar un empleado.');
            if (empleadoInput) empleadoInput.focus();
            return false;
        }

        if (!sucursal) {
            showWarning('Debe seleccionar una sucursal.');
            if (sucursalInput) sucursalInput.focus();
            return false;
        }

        if (!fecha) {
            showWarning('Debe ingresar la fecha de asignación.');
            if (fechaInput) fechaInput.focus();
            return false;
        }

        return true;
    }

    // ---------- Botones ----------
    if (btnGuardar) btnGuardar.addEventListener('click', function (evt) {
        evt.preventDefault();
        if (!form) return;
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'crear';
                form.submit();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Guardar asignación',
                    text: '¿Deseas registrar esta asignación?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then(res => { if (res.isConfirmed) doSubmit(); });
            } else if (confirm('¿Deseas registrar esta asignación?')) {
                doSubmit();
            }
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function (evt) {
        evt.preventDefault();
        if (!form) return;
        if (validarFormulario()) {
            const doSubmit = () => {
                if (operacionInput) operacionInput.value = 'actualizar';
                form.submit();
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Actualizar asignación',
                    text: '¿Deseas guardar los cambios?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then(res => { if (res.isConfirmed) doSubmit(); });
            } else if (confirm('¿Deseas guardar los cambios?')) {
                doSubmit();
            }
        }
    });

    if (btnCancelar) btnCancelar.addEventListener('click', function () {
        limpiarFormulario();
        if (btnCancelar) btnCancelar.style.display = 'none';
    });

    // ---------- Editar ----------
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id || '';
            const empleado = this.dataset.empleado || '';
            const sucursal = this.dataset.sucursal || '';
            const fecha = this.dataset.fecha || '';

            if (idAsignacionInput) idAsignacionInput.value = id;
            if (empleadoInput) empleadoInput.value = empleado;
            if (sucursalInput) sucursalInput.value = sucursal;
            if (fechaInput) fechaInput.value = fecha;

            habilitarCampos();
            mostrarBotonesActualizar();
            if (operacionInput) operacionInput.value = 'actualizar';
        });
    });

    // ---------- Eliminar ----------
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function (evt) {
            evt.preventDefault();
            const frm = this;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar asignación?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then(r => { if (r.isConfirmed) frm.submit(); });
            } else if (confirm('¿Eliminar asignación?')) {
                frm.submit();
            }
        });
    });

    // ---------- Mostrar mensaje desde el servidor ----------
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const icon = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' :
                (m.tipo === 'warning' ? 'warning' : 'error');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ title: icon === 'success' ? 'Éxito' : 'Atención', text: m.text, icon: icon });
            } else {
                alert(m.text);
            }
            try { delete window.__mensaje; } catch (e) { window.__mensaje = null; }
        }
    } catch (e) {
        console.warn('Error mostrando mensaje del servidor', e);
    }

    console.log('[Empleado_Sucursal.js] DOMContentLoaded: fin');
});

// ---------- Texto de ayuda en focus ----------
(function () {
    function initFormTextToggle() {
        var form = document.getElementById('form-asignacion');
        if (!form) return;

        var fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(function (f) {
            f.addEventListener('focus', function () {
                var container = f.closest('.col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col, .form-group') || f.parentElement;
                if (!container) return;
                var help = container.querySelector('small.form-text.help-text');
                if (help) help.classList.add('visible');
            });
            f.addEventListener('blur', function () {
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
