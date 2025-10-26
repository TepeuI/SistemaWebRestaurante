document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-sucursal');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idSucursalInput = document.getElementById('id_sucursal');
    const btnMostrarLista = document.getElementById('btn-mostrar-lista');
    const listaSucursales = document.getElementById('lista-sucursales');

    if (!form) return;

    // Asegurar que la lista esté oculta al cargar
    if (listaSucursales) listaSucursales.style.display = 'none';

    // Manejar botón mostrar/ocultar lista
    if (btnMostrarLista && listaSucursales) {
        btnMostrarLista.addEventListener('click', function() {
            const isHidden = listaSucursales.style.display === 'none' || listaSucursales.style.display === '';
            if (isHidden) {
                listaSucursales.style.display = 'block';
                btnMostrarLista.textContent = 'Ocultar lista';
                listaSucursales.scrollIntoView({ behavior: 'smooth' });
            } else {
                listaSucursales.style.display = 'none';
                btnMostrarLista.textContent = 'Mostrar lista';
            }
        });
    }

    // (display of department name under input removed per user request)

    btnNuevo.addEventListener('click', function() {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    btnGuardar.addEventListener('click', function() {
        if (validarFormulario()) {
            operacionInput.value = 'crear';
            form.submit();
        }
    });

    btnActualizar.addEventListener('click', function() {
        if (validarFormulario()) {
            operacionInput.value = 'actualizar';
            form.submit();
        }
    });

    btnCancelar.addEventListener('click', function() {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    // Interceptar formularios de eliminación y mostrar SweetAlert confirm
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function(evt) {
            evt.preventDefault();
            const form = this;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar sucursal?',
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
                if (confirm('¿Eliminar sucursal?')) form.submit();
            }
        });
    });

    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const direccion = this.getAttribute('data-direccion');
            const apertura = this.getAttribute('data-apertura');
            const cierre = this.getAttribute('data-cierre');
            const capacidad = this.getAttribute('data-capacidad');
            const telefono = this.getAttribute('data-telefono');
            const correo = this.getAttribute('data-correo');
            const departamento = this.getAttribute('data-departamento');

            // Mostrar SweetAlert confirm antes de rellenar el formulario
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Editar sucursal',
                    text: '¿Deseas editar la sucursal "' + (direccion || id) + '"? ',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        rellenarFormularioEdicion({id, direccion, apertura, cierre, capacidad, telefono, correo, departamento});
                    }
                });
            } else {
                rellenarFormularioEdicion({id, direccion, apertura, cierre, capacidad, telefono, correo, departamento});
            }
        });
    });

    // Helper para rellenar el formulario (separamos por claridad)
    function rellenarFormularioEdicion(data) {
        idSucursalInput.value = data.id || '';
        document.getElementById('direccion_sucursal').value = data.direccion || '';
        document.getElementById('horario_apertura').value = data.apertura || '';
        document.getElementById('hora_cierre').value = data.cierre || '';
        document.getElementById('capacidad_empleados').value = data.capacidad || 0;
        document.getElementById('telefono_sucursal').value = data.telefono || '';
        document.getElementById('correo_sucursal').value = data.correo || '';
        // Poner el valor formateado 'id - Nombre' si conocemos el nombre
        if (data.departamento) {
            if (typeof DEPARTAMENTOS_MAP !== 'undefined' && DEPARTAMENTOS_MAP.hasOwnProperty(data.departamento)) {
                document.getElementById('id_departamento').value = data.departamento + ' - ' + DEPARTAMENTOS_MAP[data.departamento];
            } else {
                document.getElementById('id_departamento').value = data.departamento;
            }
        } else {
            document.getElementById('id_departamento').value = '';
        }

        mostrarBotonesActualizar();
        document.getElementById('direccion_sucursal').focus();
    }

    function limpiarFormulario() {
        form.reset();
        idSucursalInput.value = '';
        operacionInput.value = 'crear';
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
        const direccion = document.getElementById('direccion_sucursal').value.trim();
        const apertura = document.getElementById('horario_apertura').value;
        const cierre = document.getElementById('hora_cierre').value;
        const capacidad = parseInt(document.getElementById('capacidad_empleados').value, 10);

        if (!direccion) { alert('La dirección es requerida'); return false; }
        if (!apertura) { alert('El horario de apertura es requerido'); return false; }
        if (!cierre) { alert('La hora de cierre es requerida'); return false; }
        if (isNaN(capacidad) || capacidad < 0) { alert('La capacidad debe ser un número 0 o mayor'); return false; }

        return true;
    }
});
