document.addEventListener('DOMContentLoaded', function() {
    // Mostrar mensajes con SweetAlert
    if (window.__mensaje) {
        Swal.fire({
            icon: window.__mensaje.tipo === 'success' ? 'success' : 'error',
            title: window.__mensaje.tipo === 'success' ? 'Éxito' : 'Error',
            text: window.__mensaje.text,
            timer: 3000,
            showConfirmButton: false
        });
    }

    // Funcionalidad del formulario de factura
    const form = document.getElementById('form-factura');
    const btnNuevo = document.getElementById('btn-nuevo');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnActualizar = document.getElementById('btn-actualizar');
    const btnCancelar = document.getElementById('btn-cancelar');
    const operacion = document.getElementById('operacion');
    const idFactura = document.getElementById('id_factura');

    // Botón Nuevo
    btnNuevo.addEventListener('click', function() {
        form.reset();
        operacion.value = 'crear';
        btnGuardar.style.display = 'inline-block';
        btnActualizar.style.display = 'none';
        btnCancelar.style.display = 'none';
        idFactura.value = '';
    });

    // Botón Guardar
    btnGuardar.addEventListener('click', function() {
        if (form.checkValidity()) {
            form.submit();
        } else {
            form.reportValidity();
        }
    });

    // Botón Actualizar
    btnActualizar.addEventListener('click', function() {
        if (form.checkValidity()) {
            operacion.value = 'actualizar';
            form.submit();
        } else {
            form.reportValidity();
        }
    });

    // Botón Cancelar
    btnCancelar.addEventListener('click', function() {
        form.reset();
        operacion.value = 'crear';
        btnGuardar.style.display = 'inline-block';
        btnActualizar.style.display = 'none';
        btnCancelar.style.display = 'none';
        idFactura.value = '';
    });

    // Botones Editar
    document.querySelectorAll('.editar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            idFactura.value = this.dataset.id;
            document.getElementById('codigo_serie').value = this.dataset.codigo;
            document.getElementById('fecha_emision').value = this.dataset.fecha;
            document.getElementById('nit_cliente').value = this.dataset.nit;
            document.getElementById('monto_total').value = this.dataset.monto;
            
            operacion.value = 'actualizar';
            btnGuardar.style.display = 'none';
            btnActualizar.style.display = 'inline-block';
            btnCancelar.style.display = 'inline-block';
            
            // Scroll al formulario
            form.scrollIntoView({ behavior: 'smooth' });
        });
    });

    // Confirmación para eliminar
    document.querySelectorAll('form[data-eliminar="true"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});