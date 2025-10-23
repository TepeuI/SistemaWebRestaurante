<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Mobiliario - Plantilla</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body, h1, h2, h3, h4, h5, h6, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }
    </style>
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/animate.min.css">
    <link rel="stylesheet" href="../css/boxicons.min.css">
    <link rel="stylesheet" href="../css/swiper-bundle.min.css">
    <link rel="stylesheet" href="../css/glightbox.min.css">
    <!-- Estilos personalizados globales -->
    <link rel="stylesheet" href="../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">INVENTARIO MOBILIARIO</h1>
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar</a></li>
            </ul>
        </div>
    </header>

    <main class="container my-4">
        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - COMPRAS DE MOBILIARIO</h2>
            <!-- Compras de Mobiliario -->
            <h2 class="card__title mb-3 mt-4">Compras de Mobiliario</h2>
            <form id="form-compras" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label" for="compra-id">ID Compra:</label>
                    <input type="number" class="form-control" id="compra-id" required placeholder="Ej. 1">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="compra-proveedor">ID Proveedor:</label>
                    <input type="number" class="form-control" id="compra-proveedor" required placeholder="Ej. 1">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="detalle-cantidad">Cantidad Compra:</label>
                    <input type="number" class="form-control" id="detalle-cantidad" required placeholder="Ej. 5">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="compra-fecha">Fecha Compra:</label>
                    <input type="date" class="form-control" id="compra-fecha" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="detalle-mobiliario">ID Mobiliario:</label>
                    <input type="number" class="form-control" id="detalle-mobiliario" required placeholder="Ej. 1">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="detalle-unitario">Costo Unitario:</label>
                    <input type="number" step="0.01" class="form-control" id="detalle-unitario" required placeholder="Ej. 250.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="compra-monto">Monto Total:</label>
                    <input type="number" step="0.01" class="form-control" id="compra-monto" required placeholder="Ej. 2500.00">
                </div>
            </form>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-compras">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Compra</th>
                            <th>ID Proveedor</th>
                            <th>Cantidad Compra</th>
                            <th>Fecha Compra</th>\
                            <th>ID mobiliario</th>
                            <th>costo unitario</th>
                            <th>Monto Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>2</td>
                            <td>5</td>
                            <td>2024-06-15</td>
                            <td>3</td>
                            <td>300.00</td>
                            <td>1500.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- toolbar unificado para acciones (actúa sobre la tabla activa) -->
            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-danger">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning">Actualizar</button>
                <button id="btn-eliminar" type="button" class="btn btn-secondary">Eliminar</button>
                <div class="ms-auto text-muted align-self-center">Tabla activa: <span id="tabla-activa">inventario</span></div>
            </div>

        </section>
    </main>

    <script>
        (function(){
            const tablaInvent = document.getElementById('tabla-inventario');
            const tablaControl = document.getElementById('tabla-control');
            const tablaCompras = document.getElementById('tabla-compras');
            const tablaDetalle = document.getElementById('tabla-detalle-compra');
            const spanActiva = document.getElementById('tabla-activa');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnEliminar = document.getElementById('btn-eliminar');

            let tablaActiva = 'inventario';
            let filaSeleccionada = null;

            function setActiva(name){
                tablaActiva = name;
                spanActiva.textContent = name;
                filaSeleccionada = null;
                [tablaInvent, tablaControl, tablaCompras, tablaDetalle].forEach(t => {
                    if(!t) return;
                    Array.from(t.querySelectorAll('tbody tr')).forEach(r => r.classList.remove('table-primary'));
                });
            }

            function attachRowClicks(tabla, name){
                tabla.addEventListener('click', function(e){
                    const tr = e.target.closest('tr');
                    if(!tr) return;
                    setActiva(name);
                    filaSeleccionada = tr;
                    Array.from(tabla.querySelectorAll('tbody tr')).forEach(r => r.classList.remove('table-primary'));
                    tr.classList.add('table-primary');
                });
            }

            if(tablaInvent) attachRowClicks(tablaInvent, 'inventario');
            if(tablaControl) attachRowClicks(tablaControl, 'control');
            if(tablaCompras) attachRowClicks(tablaCompras, 'compras');
            if(tablaDetalle) attachRowClicks(tablaDetalle, 'detalle');

            function limpiarForm(){
                document.getElementById('form-inventario').reset();
                const fc = document.getElementById('form-control'); if(fc) fc.reset();
                const fcp = document.getElementById('form-compras'); if(fcp) fcp.reset();
                const fd = document.getElementById('form-detalle-compra'); if(fd) fd.reset();
            }

            btnNuevo.addEventListener('click', ()=>{ limpiarForm(); filaSeleccionada = null; });

            btnGuardar.addEventListener('click', ()=>{
                if(tablaActiva === 'inventario'){
                    const id = document.getElementById('inv-id').value;
                    const nombre = document.getElementById('inv-nombre').value;
                    const desc = document.getElementById('inv-descrip').value;
                    const cant = document.getElementById('inv-cantidad_stock').value;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${id}</td><td>${nombre}</td><td>${desc}</td><td>${cant}</td>`;
                    tablaInvent.querySelector('tbody').appendChild(tr);
                } else if(tablaActiva === 'control'){
                    const id = document.getElementById('control-id').value;
                    const inv = document.getElementById('control-inv').value;
                    const estado = document.getElementById('control-estado').value;
                    const entrada = document.getElementById('control-entrada').value;
                    const cad = document.getElementById('control-caducidad').value;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${id}</td><td>${inv}</td><td>${estado}</td><td>${entrada}</td><td>${cad}</td>`;
                    tablaControl.querySelector('tbody').appendChild(tr);
                } else if(tablaActiva === 'compras'){
                    const id = document.getElementById('compra-id').value;
                    const prov = document.getElementById('compra-proveedor').value;
                    const fecha = document.getElementById('compra-fecha').value;
                    const monto = document.getElementById('compra-monto').value;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${id}</td><td>${prov}</td><td>${fecha}</td><td>${monto}</td>`;
                    tablaCompras.querySelector('tbody').appendChild(tr);
                } else if(tablaActiva === 'detalle'){
                    const id = document.getElementById('detalle-compra').value;
                    const mob = document.getElementById('detalle-mobiliario').value;
                    const cantidad = document.getElementById('detalle-cantidad').value;
                    const unit = document.getElementById('detalle-unitario').value;
                    const total = document.getElementById('detalle-total').value;
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${id}</td><td>${mob}</td><td>${cantidad}</td><td>${unit}</td><td>${total}</td>`;
                    tablaDetalle.querySelector('tbody').appendChild(tr);
                }
                limpiarForm();
            });

            btnActualizar.addEventListener('click', ()=>{
                if(!filaSeleccionada) return alert('Selecciona primero una fila de la tabla activa');
                if(tablaActiva === 'inventario'){
                    filaSeleccionada.cells[0].textContent = document.getElementById('inv-id').value;
                    filaSeleccionada.cells[1].textContent = document.getElementById('inv-nombre').value;
                    filaSeleccionada.cells[2].textContent = document.getElementById('inv-descrip').value;
                    filaSeleccionada.cells[3].textContent = document.getElementById('inv-cantidad_stock').value;
                } else if(tablaActiva === 'control'){
                    filaSeleccionada.cells[0].textContent = document.getElementById('control-id').value;
                    filaSeleccionada.cells[1].textContent = document.getElementById('control-inv').value;
                    filaSeleccionada.cells[2].textContent = document.getElementById('control-estado').value;
                    filaSeleccionada.cells[3].textContent = document.getElementById('control-entrada').value;
                    filaSeleccionada.cells[4].textContent = document.getElementById('control-caducidad').value;
                } else if(tablaActiva === 'compras'){
                    filaSeleccionada.cells[0].textContent = document.getElementById('compra-id').value;
                    filaSeleccionada.cells[1].textContent = document.getElementById('compra-proveedor').value;
                    filaSeleccionada.cells[2].textContent = document.getElementById('compra-fecha').value;
                    filaSeleccionada.cells[3].textContent = document.getElementById('compra-monto').value;
                } else if(tablaActiva === 'detalle'){
                    filaSeleccionada.cells[0].textContent = document.getElementById('detalle-compra').value;
                    filaSeleccionada.cells[1].textContent = document.getElementById('detalle-mobiliario').value;
                    filaSeleccionada.cells[2].textContent = document.getElementById('detalle-cantidad').value;
                    filaSeleccionada.cells[3].textContent = document.getElementById('detalle-unitario').value;
                    filaSeleccionada.cells[4].textContent = document.getElementById('detalle-total').value;
                }
                limpiarForm();
            });

            btnEliminar.addEventListener('click', ()=>{
                if(!filaSeleccionada) return alert('Selecciona primero una fila de la tabla activa');
                filaSeleccionada.remove();
                filaSeleccionada = null;
            });

            function attachRowDbl(tabla, name){
                tabla.addEventListener('dblclick', function(e){
                    const tr = e.target.closest('tr');
                    if(!tr) return;
                    setActiva(name);
                    filaSeleccionada = tr;
                    if(name === 'inventario'){
                        document.getElementById('inv-id').value = tr.cells[0].textContent;
                        document.getElementById('inv-nombre').value = tr.cells[1].textContent;
                        document.getElementById('inv-descrip').value = tr.cells[2].textContent;
                        document.getElementById('inv-cantidad_stock').value = tr.cells[3].textContent;
                    } else if(name === 'control'){
                        document.getElementById('control-id').value = tr.cells[0].textContent;
                        document.getElementById('control-inv').value = tr.cells[1].textContent;
                        document.getElementById('control-estado').value = tr.cells[2].textContent;
                        document.getElementById('control-entrada').value = tr.cells[3].textContent;
                        document.getElementById('control-caducidad').value = tr.cells[4].textContent;
                    } else if(name === 'compras'){
                        document.getElementById('compra-id').value = tr.cells[0].textContent;
                        document.getElementById('compra-proveedor').value = tr.cells[1].textContent;
                        document.getElementById('compra-fecha').value = tr.cells[2].textContent;
                        document.getElementById('compra-monto').value = tr.cells[3].textContent;
                    } else if(name === 'detalle'){
                        document.getElementById('detalle-compra').value = tr.cells[0].textContent;
                        document.getElementById('detalle-mobiliario').value = tr.cells[1].textContent;
                        document.getElementById('detalle-cantidad').value = tr.cells[2].textContent;
                        document.getElementById('detalle-unitario').value = tr.cells[3].textContent;
                        document.getElementById('detalle-total').value = tr.cells[4].textContent;
                    }
                });
            }

            if(tablaInvent) attachRowDbl(tablaInvent,'inventario');
            if(tablaControl) attachRowDbl(tablaControl,'control');
            if(tablaCompras) attachRowDbl(tablaCompras,'compras');
            if(tablaDetalle) attachRowDbl(tablaDetalle,'detalle');

            setActiva('inventario');
        })();
    </script>
</body>
</html>