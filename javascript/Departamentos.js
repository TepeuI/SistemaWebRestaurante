document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-departamento');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idDepartamentoInput = document.getElementById('id_departamento');
    const btnMostrarLista = document.getElementById('btn-mostrar-lista');
    const listaDepartamentos = document.getElementById('lista-departamentos');

    if (!form) return; // seguridad si la página no contiene el formulario
    // Asegurar que la lista esté oculta al cargar la página (por si no lo hizo el HTML)
    if (listaDepartamentos) listaDepartamentos.style.display = 'none';

    // Manejar botón mostrar/ocultar lista
    if (btnMostrarLista && listaDepartamentos) {
        btnMostrarLista.addEventListener('click', function() {
            const isHidden = listaDepartamentos.style.display === 'none' || listaDepartamentos.style.display === '';
            if (isHidden) {
                listaDepartamentos.style.display = 'block';
                btnMostrarLista.textContent = 'Ocultar lista';
                // opcional: desplazar hacia la lista
                listaDepartamentos.scrollIntoView({ behavior: 'smooth' });
            } else {
                listaDepartamentos.style.display = 'none';
                btnMostrarLista.textContent = 'Mostrar lista';
            }
        });
    }
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

            idDepartamentoInput.value = id || '';
            document.getElementById('nombre_departamento').value = nombre || '';

            mostrarBotonesActualizar();
            // Opcional: enfocar el campo
            document.getElementById('nombre_departamento').focus();
        });
    });

    function limpiarFormulario() {
        form.reset();
        idDepartamentoInput.value = '';
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
        const nombre = document.getElementById('nombre_departamento').value.trim();

        if (!nombre) { alert('El nombre del departamento es requerido'); return false; }

        return true;
    }
});
