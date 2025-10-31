document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-ingrediente');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacionInput = document.getElementById('operacion');
    const idIngredienteInput = document.getElementById('id_ingrediente');

    // Botón Nuevo
    btnNuevo.addEventListener('click', function() {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    // Botón Guardar (Crear)
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

    // Botón Cancelar
    btnCancelar.addEventListener('click', function() {
        limpiarFormulario();
        mostrarBotonesGuardar();
    });

    // Eventos para botones Editar
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            const descripcion = this.getAttribute('data-descripcion');
            const unidad = this.getAttribute('data-unidad');
            const stock = this.getAttribute('data-stock');

            // Llenar formulario
            idIngredienteInput.value = id;
            document.getElementById('nombre_ingrediente').value = nombre;
            document.getElementById('descripcion').value = descripcion;
            document.getElementById('id_unidad').value = unidad;
            document.getElementById('cantidad_stock').value = stock;

            mostrarBotonesActualizar();
        });
    });

    function limpiarFormulario() {
        form.reset();
        idIngredienteInput.value = '';
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
        const nombre = document.getElementById('nombre_ingrediente').value.trim();
        const unidad = document.getElementById('id_unidad').value;
        const stock = document.getElementById('cantidad_stock').value;

        if (!nombre) {
            alert('El nombre del ingrediente es requerido');
            return false;
        }
        if (!unidad) {
            alert('La unidad de medida es requerida');
            return false;
        }
        if (!stock || stock < 0) {
            alert('La cantidad en stock debe ser un número positivo');
            return false;
        }

        return true;
    }
});