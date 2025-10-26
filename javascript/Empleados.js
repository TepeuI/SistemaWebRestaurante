document.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos del DOM con comprobaciones para evitar errores si faltan
    const form = document.getElementById('form-empleado');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idEmpleadoInput = document.getElementById('id_empleado');
    const idDepartamentoInput = document.getElementById('id_departamento');
    const idPuestoInput = document.getElementById('id_puesto');
    const btnMostrarLista = document.getElementById('btn-mostrar-lista');
    const contenedorLista = document.getElementById('lista-empleados');

    if (btnNuevo) btnNuevo.addEventListener('click', function() {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    if (btnGuardar) btnGuardar.addEventListener('click', function() {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            if (operacionInput) operacionInput.value = 'crear';
            form.submit();
        }
    });

    if (btnActualizar) btnActualizar.addEventListener('click', function() {
        if (!form) return console.warn('Formulario no encontrado');
        if (validarFormulario()) {
            if (operacionInput) operacionInput.value = 'actualizar';
            form.submit();
        }
    });

    if (btnCancelar) btnCancelar.addEventListener('click', function() {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });
    // Editar: conectar botones de la tabla al formulario con confirmación SweetAlert
    // Conectar botones de edición si existen en la tabla
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const dpi = this.getAttribute('data-dpi');
            const nombre = this.getAttribute('data-nombre');
            const apellido = this.getAttribute('data-apellido');
            const departamento = this.getAttribute('data-departamento');
            const puesto = this.getAttribute('data-puesto');

            const doFill = () => {
                idEmpleadoInput.value = id;
                document.getElementById('dpi').value = dpi || '';
                document.getElementById('nombre_empleado').value = nombre || '';
                document.getElementById('apellido_empleado').value = apellido || '';
                // Poner 'id - nombre' si existe el mapa
                if (typeof DEPARTAMENTOS_MAP !== 'undefined' && departamento && DEPARTAMENTOS_MAP.hasOwnProperty(departamento)) {
                    idDepartamentoInput.value = departamento + ' - ' + DEPARTAMENTOS_MAP[departamento];
                } else {
                    idDepartamentoInput.value = departamento || '';
                }
                if (typeof PUESTOS_MAP !== 'undefined' && puesto && PUESTOS_MAP.hasOwnProperty(puesto)) {
                    idPuestoInput.value = puesto + ' - ' + PUESTOS_MAP[puesto];
                } else {
                    idPuestoInput.value = puesto || '';
                }

                mostrarBotonesActualizar();
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar empleado',
                    text: '¿Deseas editar al empleado "' + (nombre || id) + '"?',
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

    function limpiarFormulario() {
        if (form) form.reset();
        if (idEmpleadoInput) idEmpleadoInput.value = '';
        if (operacionInput) operacionInput.value = 'crear';
        // Asegurar que se muestren los botones correctos
        mostrarBotonesGuardar();
    }

    function mostrarBotonesGuardar() {
        if (btnGuardar) btnGuardar.style.display = 'inline-block';
        if (btnActualizar) btnActualizar.style.display = 'none';
        if (btnCancelar) btnCancelar.style.display = 'none';
    }

    function mostrarBotonesActualizar() {
        if (btnGuardar) btnGuardar.style.display = 'none';
        if (btnActualizar) btnActualizar.style.display = 'inline-block';
        if (btnCancelar) btnCancelar.style.display = 'inline-block';
    }

    function validarFormulario() {
        const dpiEl = document.getElementById('dpi');
        const nombreEl = document.getElementById('nombre_empleado');
        const apellidoEl = document.getElementById('apellido_empleado');
        const departamentoEl = document.getElementById('id_departamento');

        const dpi = dpiEl ? dpiEl.value.trim() : '';
        const nombre = nombreEl ? nombreEl.value.trim() : '';
        const apellido = apellidoEl ? apellidoEl.value.trim() : '';
        const departamento = departamentoEl ? departamentoEl.value : '';

        if (!dpi) { alert('El DPI es requerido'); return false; }
        if (!nombre) { alert('El nombre es requerido'); return false; }
        if (!apellido) { alert('El apellido es requerido'); return false; }
        if (!departamento) { alert('El departamento es requerido'); return false; }

        return true;
    }

    // Interceptar formularios de eliminación y mostrar SweetAlert confirm
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const form = this;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar empleado?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm('¿Eliminar empleado?')) form.submit();
            }
        });
    });

    // Mostrar/ocultar lista de empleados
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

    // Mostrar/ocultar lista de sucursales (en la misma página de empleados)
    const btnMostrarSucursales = document.getElementById('btn-mostrar-sucursales');
    const listaSucursales = document.getElementById('lista-sucursales');
    if (btnMostrarSucursales && listaSucursales) {
        btnMostrarSucursales.addEventListener('click', function() {
            if (listaSucursales.style.display === 'none' || listaSucursales.style.display === '') {
                listaSucursales.style.display = 'block';
                btnMostrarSucursales.textContent = 'Ocultar lista sucursales';
                listaSucursales.scrollIntoView({ behavior: 'smooth' });
            } else {
                listaSucursales.style.display = 'none';
                btnMostrarSucursales.textContent = 'Mostrar lista sucursales';
            }
        });
    }
});