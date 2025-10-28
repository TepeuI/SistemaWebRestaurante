document.addEventListener('DOMContentLoaded', function () {

    // Mostrar una alerta (usa Swal si está disponible, sino alert)
    function showAlert(type, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info'),
                title: title || (type === 'success' ? 'Éxito' : 'Atención'),
                text: text || '',
                confirmButtonText: 'Aceptar'
            });
        } else {
            alert((title ? title + '\n' : '') + (text || ''));
        }
    }

    // Confirmación (devuelve Promise<boolean>)
    function confirmAction(opts) {
        opts = opts || {};
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                title: opts.title || '¿Confirmar acción?',
                text: opts.text || '',
                icon: opts.icon || 'question',
                showCancelButton: true,
                confirmButtonText: opts.confirmText || 'Sí',
                cancelButtonText: opts.cancelText || 'Cancelar'
            }).then(r => !!r.isConfirmed);
        } else {
            return Promise.resolve(confirm((opts.title ? opts.title + '\n' : '') + (opts.text || '')));
        }
    }

    // Hooks para formularios que declaran data-eliminar="true"
    document.querySelectorAll('form[data-eliminar="true"]').forEach(f => {
        f.addEventListener('submit', function (evt) {
            evt.preventDefault();
            const frm = this;
            confirmAction({
                title: '¿Eliminar elemento?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                confirmText: 'Eliminar',
                cancelText: 'Cancelar'
            }).then(confirmed => {
                if (confirmed) frm.submit();
            });
        });
    });

    // Mostrar mensaje enviado desde servidor (window.__mensaje = { tipo:'success'|'error'|'info', text:'...' })
    try {
        if (window.__mensaje && typeof window.__mensaje === 'object') {
            const m = window.__mensaje;
            const tipo = (m.tipo === 'success' || m.tipo === 'ok') ? 'success' : (m.tipo === 'error' ? 'error' : 'info');
            showAlert(tipo, tipo === 'success' ? 'Éxito' : 'Atención', m.text || '');
            try { delete window.__mensaje; } catch (e) { window.__mensaje = null; }
        }
    } catch (e) { /* ignorar errores */ }

    // Exponer API global simple
    window.APP_ALERTS = {
        showSuccess: (t, txt) => showAlert('success', t, txt),
        showError: (t, txt) => showAlert('error', t, txt),
        showInfo: (t, txt) => showAlert('info', t, txt),
        confirm: (opts) => confirmAction(opts)
    };

    // ---- Lógica específica de gestion_insumos ----
    (function () {
        const form = document.getElementById('form-insumos');
        if (!form) return;

        const operacion = document.getElementById('operacion');
        const idInput = document.getElementById('id_insumo');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const filaActivaSpan = document.getElementById('fila-activa');
        const stockInput = document.getElementById('stock');

        function limpiar() {
            form.reset();
            idInput.value = '';
            operacion.value = 'crear';
            if (btnGuardar) btnGuardar.classList.remove('d-none');
            if (btnActualizar) btnActualizar.classList.add('d-none');
            if (btnCancelar) btnCancelar.classList.add('d-none');
            if (filaActivaSpan) filaActivaSpan.textContent = 'ninguna';
            if (stockInput) stockInput.value = 0;
            document.querySelectorAll('#tabla-insumos tbody tr').forEach(r => r.classList.remove('table-primary'));
        }

        function validar() {
            const insumo = (form.insumo.value || '').trim();
            const stock = parseInt(form.stock.value, 10);
            if (!insumo) {
                showAlert('error', 'Validación', 'El nombre del insumo es requerido');
                return false;
            }
            if (isNaN(stock) || stock < 0) {
                showAlert('error', 'Validación', 'El stock debe ser un número entero mayor o igual a 0');
                return false;
            }
            return true;
        }

        // Editar
        document.querySelectorAll('.editar-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const insumo = btn.dataset.insumo || '';
                const descripcion = btn.dataset.descripcion || '';
                const stock = btn.dataset.stock || '0';

                const doFill = () => {
                    idInput.value = id || '';
                    form.insumo.value = insumo;
                    form.descripcion.value = descripcion;
                    form.stock.value = stock;
                    operacion.value = 'actualizar';

                    if (btnGuardar) btnGuardar.classList.add('d-none');
                    if (btnActualizar) btnActualizar.classList.remove('d-none');
                    if (btnCancelar) btnCancelar.classList.remove('d-none');

                    document.querySelectorAll('#tabla-insumos tbody tr').forEach(r => r.classList.remove('table-primary'));
                    const tr = btn.closest('tr');
                    if (tr) { tr.classList.add('table-primary'); if (filaActivaSpan) filaActivaSpan.textContent = tr.cells[0].textContent; }
                };

                // Confirmación antes de entrar en modo edición (opcional)
                confirmAction({
                    title: 'Editar insumo',
                    text: '¿Deseas editar este insumo?',
                    icon: 'question',
                    confirmText: 'Sí',
                    cancelText: 'Cancelar'
                }).then(ok => { if (ok) doFill(); });
            });
        });

        if (btnNuevo) btnNuevo.addEventListener('click', limpiar);
        if (btnCancelar) btnCancelar.addEventListener('click', limpiar);

        if (btnGuardar) btnGuardar.addEventListener('click', function () {
            if (!validar()) return;
            confirmAction({
                title: 'Guardar insumo',
                text: '¿Deseas guardar este insumo?',
                icon: 'question',
                confirmText: 'Guardar',
                cancelText: 'Cancelar'
            }).then(ok => { if (ok) { operacion.value = 'crear'; form.submit(); }});
        });

        if (btnActualizar) btnActualizar.addEventListener('click', function () {
            if (!validar()) return;
            confirmAction({
                title: 'Actualizar insumo',
                text: '¿Deseas guardar los cambios?',
                icon: 'question',
                confirmText: 'Actualizar',
                cancelText: 'Cancelar'
            }).then(ok => { if (ok) { operacion.value = 'actualizar'; form.submit(); }});
        });

        // Inicializar
        limpiar();
    })();

});
