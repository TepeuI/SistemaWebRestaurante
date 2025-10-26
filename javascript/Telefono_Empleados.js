document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-telefono');
    const inputs = form.querySelectorAll('input, select, textarea');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idTelefonoInput = document.getElementById('id_telefono');
    const idEmpleadoInput = document.getElementById('id_empleado');
    const numeroInput = document.getElementById('numero_telefono');
    const btnMostrarLista = document.getElementById('btn-mostrar-lista');
    const contenedorLista = document.getElementById('lista-telefonos');

    // 🔒 Bloquear campos al inicio
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

    // 🔓 Habilitar campos al presionar "Nuevo"
    function habilitarCampos() {
        inputs.forEach(input => {
            if (input.type !== 'hidden') input.disabled = false;
        });
        btnGuardar.disabled = false;
        btnActualizar.disabled = false;
        btnCancelar.style.display = 'inline-block';
        btnNuevo.disabled = true;
        inputs[0].focus();
        // Habilitar acciones de la tabla (editar/eliminar)
        setTableActionsDisabled(false);
    }

    // 🚫 Restaurar bloqueo con "Cancelar"
    btnCancelar.addEventListener('click', () => {
        bloquearCampos();
        btnNuevo.disabled = false;
        form.reset();
    });

    // 🟢 Evento "Nuevo"
    btnNuevo.addEventListener('click', () => {
        habilitarCampos();
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    // Bloquear todo al cargar
    bloquearCampos();

    // 🧩 Botón Guardar
    btnGuardar.addEventListener('click', function() {
        if (validarFormulario()) {
            operacionInput.value = 'crear';
            form.submit();
        }
    });

    // 🧩 Botón Actualizar
    btnActualizar.addEventListener('click', function() {
        if (validarFormulario()) {
            operacionInput.value = 'actualizar';
            form.submit();
        }
    });

    // 🧩 Función para limpiar el formulario
    function limpiarFormulario() {
        form.reset();
        idTelefonoInput.value = '';
        operacionInput.value = 'crear';
    }

    // 🧩 Mostrar botones
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

    // 🧩 Validar formulario
    function validarFormulario() {
        const empleadoVal = idEmpleadoInput.value.trim();
        const numeroVal = numeroInput.value.trim();

        let empleadoId = null;
        if (empleadoVal) {
            const m = empleadoVal.match(/^\s*(\d+)/);
            if (m) empleadoId = m[1];
        }

        if (!empleadoVal) { alert('El empleado es requerido'); return false; }
        if (!empleadoId) { alert('Selecciona un empleado válido (ej: "1 - Nombre")'); return false; }
        if (!numeroVal) { alert('El número de teléfono es requerido'); return false; }

        return true;
    }

    // 🧩 Editar desde la lista
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const empleado = this.getAttribute('data-empleado');
            const numero = this.getAttribute('data-numero');

            const doFill = () => {
                idTelefonoInput.value = id || '';
                if (typeof EMPLEADOS_MAP !== 'undefined' && empleado) {
                    const nombre = EMPLEADOS_MAP[empleado] || '';
                    idEmpleadoInput.value = (empleado && nombre) ? empleado + ' - ' + nombre : empleado;
                } else {
                    idEmpleadoInput.value = empleado || '';
                }
                numeroInput.value = numero || '';
                habilitarCampos();
                mostrarBotonesActualizar();
                numeroInput.focus();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar teléfono',
                    text: '¿Deseas editar este teléfono?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) doFill();
                });
            } else {
                doFill();
            }
        });
    });

    // 🧩 Confirmar eliminación
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const formEl = this;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar teléfono?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) formEl.submit();
                });
            } else {
                if (confirm('¿Eliminar teléfono?')) formEl.submit();
            }
        });
    });

    // Habilita/deshabilita botones de editar y eliminar en la tabla
    function setTableActionsDisabled(disabled) {
        const editBtns = document.querySelectorAll('.editar-btn');
        editBtns.forEach(b => b.disabled = !!disabled);
        const deleteBtns = document.querySelectorAll('form[data-eliminar="true"] button[type="submit"], form[data-eliminar="true"] button');
        deleteBtns.forEach(b => b.disabled = !!disabled);
    }

    // 🧩 Mostrar/ocultar lista
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
