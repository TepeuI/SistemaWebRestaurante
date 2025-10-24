document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-empleado');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idEmpleadoInput = document.getElementById('id_empleado');

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

    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const apellido = this.getAttribute('data-apellido');
            const departamento = this.getAttribute('data-departamento');
            const telefono = this.getAttribute('data-telefono');
            const correo = this.getAttribute('data-correo');
            const estado = this.getAttribute('data-estado');

            idEmpleadoInput.value = id;
            document.getElementById('nombre').value = nombre;
            document.getElementById('apellido').value = apellido;
            document.getElementById('departamento').value = departamento;
            document.getElementById('telefono').value = telefono;
            document.getElementById('correo').value = correo;
            document.getElementById('estado').value = estado;

            mostrarBotonesActualizar();
        });
    });

    function limpiarFormulario() {
        form.reset();
        idEmpleadoInput.value = '';
        operacionInput.value = 'crear';
    }

    function mostrarBotonesGuardar() {
        btnGuardar.style.display = 'inline-block';
        btnActualizar.style.display = 'none';
        btnCancelar.style.display = 'none';
    }

    function mostrarBotonesActualizar() {
        btnGuardar.style.display = 'none';
        btnActualizar.style.display = 'inline-block';
        btnCancelar.style.display = 'inline-block';
    }

    function validarFormulario() {
        const nombre = document.getElementById('nombre').value.trim();
        const apellido = document.getElementById('apellido').value.trim();
        const departamento = document.getElementById('departamento').value;

        if (!nombre) { alert('El nombre es requerido'); return false; }
        if (!apellido) { alert('El apellido es requerido'); return false; }
        if (!departamento) { alert('El departamento es requerido'); return false; }

        return true;
    }
});