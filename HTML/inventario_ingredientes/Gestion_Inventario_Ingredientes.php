<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inventario</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body, h1, h2, h3, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }
    </style>

    <!-- Bootstrap y librerías base -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/animate.min.css">
    <link rel="stylesheet" href="../css/boxicons.min.css">
    <link rel="stylesheet" href="../css/swiper-bundle.min.css">
    <link rel="stylesheet" href="../css/glightbox.min.css">
    <link rel="stylesheet" href="../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">SISTEMA DE INVENTARIO INGREDIENTES MAREA ROJA</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="menu_empleados.html" class="nav-link">Regresar</a></li>
        </ul>
    </div>
</header>

<main class="container">
    <section class="card shadow mb-4 p-4">
        <h2 class="card__title mb-3">FORMULARIO GENERAL DE INVENTARIO</h2>

        <form id="form-inventario-general" class="row g-3">

            <!-- ===== SECCIÓN 1: INVENTARIO INGREDIENTES ===== -->
            <h4 class="mt-4">Inventario de Ingredientes</h4>
            <div class="col-md-3">
                <label class="form-label" for="inv-id">ID Ingrediente:</label>
                <input type="number" class="form-control" id="inv-id" required placeholder="Ej. 1">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="inv-nombre">Nombre Ingrediente:</label>
                <input type="text" class="form-control" id="inv-nombre" required placeholder="Ej. Churrasco">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="inv-descripcion">Descripción:</label>
                <input type="text" class="form-control" id="inv-descripcion" required placeholder="Ej. Carne Premium">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="inv-cantidad">Cantidad en Stock:</label>
                <input type="number" class="form-control" id="inv-cantidad" required placeholder="Ej. 13">
            </div>

          
            <!-- ===== SECCIÓN 4: CONTROL INVENTARIO ===== -->
            <h4 class="mt-4">Control de Inventario</h4>
            <div class="col-md-2">
                <label class="form-label" for="control-id">ID Control:</label>
                <input type="number" class="form-control" id="control-id" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="control-ingrediente">ID Ingrediente:</label>
                <input type="number" class="form-control" id="control-ingrediente" required>
            </div>
                <div class="col-md-2 d-flex align-items-center">
                <label class="form-label me-2" for="control-estado">Expirado:</label>
                <input type="checkbox" class="form-check-input" id="control-estado">
                </div>
            <div class="col-md-3">
                <label class="form-label" for="control-entrada">Fecha de Entrada:</label>
                <input type="date" class="form-control" id="control-entrada" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="control-caducidad">Fecha de Caducidad:</label>
                <input type="date" class="form-control" id="control-caducidad" required>
            </div>

            <!-- ===== SECCIÓN 5: PÉRDIDAS ===== -->
            <h4 class="mt-4">Pérdidas de Inventario</h4>
            <div class="col-md-2">
                <label class="form-label" for="perdida-id">ID Pérdida:</label>
                <input type="number" class="form-control" id="perdida-id" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="perdida-descripcion">Descripción:</label>
                <input type="text" class="form-control" id="perdida-descripcion" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="perdida-ing-id">ID Ingrediente:</label>
                <input type="number" class="form-control" id="perdida-ing-id" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="perdida-cantidad">Cantidad Perdida:</label>
                <input type="number" class="form-control" id="perdida-cantidad" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="perdida-costo">Costo de Pérdida:</label>
                <input type="number" class="form-control" id="perdida-costo" required>
            </div>

            <!-- BOTONES GLOBALES -->
            <div class="col-12 d-flex gap-2 mt-4">
                <button type="button" class="btn btn-danger">Nuevo</button>
                <button type="submit" class="btn btn-success">Guardar</button>
                <button type="button" class="btn btn-warning">Actualizar</button>
                <button type="button" class="btn btn-secondary">Eliminar</button>
            </div>
        </form>
    </section>
</main>

<script src="inventario.js"></script>
</body>
</html>
