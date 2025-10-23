<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marea Roja | Reservaciones</title>

    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

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
            <h1 class="mb-0">🍽️ Marea Roja | Reservaciones</h1>
            
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="menu_empleados.html" class="nav-link">Regresar</a></li>
            </ul>
        </div>
    </header>

    <main class="container">
        <!-- CRUD Reservaciones -->
        <section class="card shadow mb-4 p-4">
            <h2 class="card__title mb-3">Gestión de Reservaciones</h2>
            <form id="form-reservaciones" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label" for="res-id">ID Reservación:</label>
                    <input type="number" class="form-control" id="res-id" required placeholder="Ej. 1">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="res-cliente">ID Cliente:</label>
                    <input type="number" class="form-control" id="res-cliente" required placeholder="Ej. 12">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="res-telefono">Teléfono:</label>
                    <input type="tel" class="form-control" id="res-telefono" required placeholder="555-123-456">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="res-personas">Cantidad Personas:</label>
                    <input type="number" class="form-control" id="res-personas" min="1" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="res-mesa">ID Mesa:</label>
                    <input type="number" class="form-control" id="res-mesa" required placeholder="Ej. 5">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="res-estado">Estado:</label>
                    <select class="form-control" id="res-estado" required>
                        <option value="">Seleccionar...</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Confirmada">Confirmada</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2 mt-3">
                    <button type="button" class="btn btn-danger">Actualizar Tabla</button>
                    <button type="submit" class="btn btn-success">Insertar</button>
                    <button type="button" class="btn btn-warning">Modificar</button>
                    <button type="button" class="btn btn-secondary">Eliminar</button>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-reservaciones">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Reservación</th>
                            <th>ID Cliente</th>
                            <th>Teléfono</th>
                            <th>Cantidad Personas</th>
                            <th>ID Mesa</th>
                            <th>Estado</th>
                        </tr>
                </table>
            </div>
        </section>
    </main>

    <footer class="text-center py-3">
        &copy; 2025 Marea Roja - Sistema de Reservaciones
    </footer>
</body>
</html>

