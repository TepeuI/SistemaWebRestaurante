<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados</title>

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
        <h1 class="mb-0">GESTIÓN DE EMPLEADOS</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="menu_empleados.html" class="nav-link">Regresar</a></li>
        </ul>
    </div>
</header>

<main class="container">
    <section class="card shadow mb-4 p-4">
        <h2 class="card__title mb-3">FORMULARIO GENERAL DE EMPLEADOS</h2>

        <form id="form-inventario-general" class="row g-3">

            <!-- ===== SECCIÓN 1: DATOS GENERALES DE EMPLEADOS ===== -->
            <h4 class="mt-4">Empleados</h4>
            <div class="col-md-3">
                <label class="form-label" for="empleado-id">ID Empleado:</label>
                <input type="number" class="form-control" id="empleado-id" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="empleado-nombre">Nombre:</label>
                <input type="text" class="form-control" id="empleado-nombre" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="empleado-apellido">Apellido:</label>
                <input type="text" class="form-control" id="empleado-apellido" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="empleado-departamento">ID Departamento:</label>
                <input type="number" class="form-control" id="empleado-departamento" required>
            </div>

          
            <!-- ===== SECCIÓN 2: TELEFONO DE EMPLEADOS ===== -->
            <h4 class="mt-4">Teléfono</h4>
            <div class="col-md-2">
                <label class="form-label" for="empleado-id-telefono">ID Teléfono:</label>
                <input type="number" class="form-control" id="empleado-id-telefono" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="empleado-telefono-numero">No. Teléfono:</label>
                <input type="number" class="form-control" id="empleado-telefono-numero" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="empleado-telefono-empleado">ID Empleado:</label>
                <input type="number" class="form-control" id="empleado-telefono-empleado" required>
            </div>


            <!-- ===== SECCIÓN 3: CORREO DE EMPLEADOS ===== -->
            <h4 class="mt-4">Correo de Empleados</h4>
            <div class="col-md-2">
                <label class="form-label" for="id-correo">ID Correo:</label>
                <input type="number" class="form-control" id="id-correo" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="empleado-correo-direccion">Dirección correo:</label>
                <input type="text" class="form-control" id="empleado-correo-direccion" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="correo-id-empleado">ID Empleado:</label>
                <input type="number" class="form-control" id="correo-id-empleado" required>
            </div>

            <!-- ===== SECCIÓN 4: CONTACTO DE EMERGENCIAS PARA EMPLEADOS ===== -->
            <h4 class="mt-4">Contactos de Emergencias</h4>
            <div class="col-md-2">
                <label class="form-label" for="id-contacto-empleado">ID Contacto:</label>
                <input type="number" class="form-control" id="id-contacto-empleado" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="nombre-empleado-contacto">Nombre:</label>
                <input type="text" class="form-control" id="nombre-empleado-contacto" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="contacto-telefono-numero">No. Teléfono:</label>
                <input type="number" class="form-control" id="contacto-telefono-numero" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="contacto-relacion">Relación:</label>
                <input type="text" class="form-control" id="contacto-relacion" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="contacto-id-empleado">ID Empleado:</label>
                <input type="number" class="form-control" id="contacto-id-empleado" required>
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

<script src="Empleados.js"></script>
</body>
</html>
