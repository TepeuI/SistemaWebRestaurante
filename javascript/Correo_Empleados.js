document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-correo');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idCorreoInput = document.getElementById('id_correo');
    const idEmpleadoInput = document.getElementById('id_empleado');
    const correoInput = document.getElementById('direccion_correo');
    const btnMostrarLista = document.getElementById('btn-mostrar-lista');
    const contenedorLista = document.getElementById('lista-correos');
    const inputs = form.querySelectorAll('input, select, textarea');

    // Bloquear campos al inicio
    function bloquearCampos() {
        inputs.forEach(input => {
            if (input.type !== 'hidden') input.disabled = true;
        });
        btnGuardar.disabled = true;
        btnActualizar.disabled = true;
        btnCancelar.style.display = 'none';
        // Deshabilitar acciones de la tabla (editar/eliminar)
        setTableActionsDisabled(true);
    }

    // Habilitar campos al presionar "Nuevo"
    function habilitarCampos() {
        inputs.forEach(input => {
            if (input.type !== 'hidden') input.disabled = false;
        });
        btnGuardar.disabled = false;
        btnActualizar.disabled = false;
        btnCancelar.style.display = 'inline-block';
        btnNuevo.disabled = true;
        inputs[0].focus(); // coloca el cursor en el primer campo
        // Habilitar acciones de la tabla (editar/eliminar)
        setTableActionsDisabled(false);
    }

    // Restaurar bloqueo con "Cancelar"
    btnCancelar.addEventListener('click', () => {
        bloquearCampos();
        btnNuevo.disabled = false;
        form.reset();
    });

    // Evento "Nuevo"
    btnNuevo.addEventListener('click', () => {
        habilitarCampos();
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    // Bloquear todo al cargar
    bloquearCampos();

    // Botón Guardar
    btnGuardar.addEventListener('click', function() {
        if (validarFormulario()) {
            operacionInput.value = 'crear';
            form.submit();
        }
    });

    // Botón Actualizar
    btnActualizar.addEventListener('click', function() {
        if (validarFormulario()) {
            operacionInput.value = 'actualizar';
            form.submit();
        }
    });

    // Función para limpiar el formulario
    function limpiarFormulario() {
        form.reset();
        idCorreoInput.value = '';
        operacionInput.value = 'crear';
    }

    // Mostrar botones
    function mostrarBotonesGuardar() {
        btnGuardar.style.display = 'inline-block';
        btnActualizar.style.display = 'none';
        btnCancelar.style.display = 'inline-block';
    }

    function mostrarBotonesActualizar() {
        btnGuardar.style.display = 'none';
        btnActualizar.style.display = 'inline-block';
        btnCancelar.style.display = 'inline-block';
    }

    // Validación de correo
    function validarEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function validarFormulario() {
        const empleadoVal = idEmpleadoInput.value.trim();
        const correoVal = correoInput.value.trim();

        let empleadoId = null;
        if (empleadoVal) {
            const m = empleadoVal.match(/^\s*(\d+)/);
            if (m) empleadoId = m[1];
        }

        if (!empleadoVal) { alert('El empleado es requerido'); return false; }
        if (!empleadoId) { alert('Selecciona un empleado válido (ej: "1 - Nombre")'); return false; }
        if (!correoVal) { alert('La dirección de correo es requerida'); return false; }
        if (!validarEmail(correoVal)) { alert('Ingrese una dirección de correo válida'); return false; }

        return true;
    }

    // Edición desde la lista
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const empleado = this.getAttribute('data-empleado');
            const correo = this.getAttribute('data-correo');

            if (idCorreoInput) idCorreoInput.value = id || '';
            if (idEmpleadoInput) {
                const nombre = EMPLEADOS_MAP?.[empleado] || '';
                idEmpleadoInput.value = (empleado && nombre) ? empleado + ' - ' + nombre : empleado;
            }
            if (correoInput) correoInput.value = correo || '';

            habilitarCampos();
            mostrarBotonesActualizar();
        });
    });

    // Habilita/deshabilita botones de editar y eliminar en la tabla
    function setTableActionsDisabled(disabled) {
        const editBtns = document.querySelectorAll('.editar-btn');
        editBtns.forEach(b => b.disabled = !!disabled);
        const deleteBtns = document.querySelectorAll('form[data-eliminar="true"] button[type="submit"], form[data-eliminar="true"] button');
        deleteBtns.forEach(b => b.disabled = !!disabled);
    }

    // Confirmar eliminación
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const formEl = this;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar correo?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) formEl.submit();
                });
            } else {
                if (confirm('¿Eliminar correo?')) formEl.submit();
            }
        });
    });

    // Mostrar/ocultar lista
    if (btnMostrarLista && contenedorLista) {
        btnMostrarLista.addEventListener('click', function() {
            if (contenedorLista.style.display === 'none' || contenedorLista.style.display === '') {
                contenedorLista.style.display = 'block';
                btnMostrarLista.textContent = 'Ocultar lista';
            } else {
                contenedorLista.style.display = 'none';
                btnMostrarLista.textContent = 'Mostrar lista';
            }
        });
    }
});
