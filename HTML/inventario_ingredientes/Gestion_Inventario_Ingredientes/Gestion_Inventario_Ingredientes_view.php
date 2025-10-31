<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Ingredientes - Marea Roja</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Bootstrap y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <link rel="stylesheet" href="Gestion_Inventario_Ingredientes.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">INVENTARIO DE INGREDIENTES - MAREA ROJA</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
        </ul>
    </div>
</header>

<main class="container my-4">
    <!-- Mostrar mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="mensaje <?php echo $_SESSION['tipo_mensaje']; ?>">
            <?php 
            echo htmlspecialchars($_SESSION['mensaje']); 
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
        </div>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card-title text-primary mb-4">
            <i class="bi bi-box-seam me-2"></i>FORMULARIO DE INGREDIENTES
        </h2>

        <form id="form-ingrediente" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_ingrediente" name="id_ingrediente" value="">
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="nombre_ingrediente">
                    <i class="bi bi-tag me-1"></i>Nombre del Ingrediente: *
                </label>
                <input type="text" class="form-control" id="nombre_ingrediente" name="nombre_ingrediente" 
                       required placeholder="Ej. Churrasco Premium" maxlength="120">
            </div>
            
            <!-- NUEVO CAMPO DESCRIPCIÓN -->
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="descripcion">
                    <i class="bi bi-card-text me-1"></i>Descripción:
                </label>
                <input type="text" class="form-control" id="descripcion" name="descripcion" 
                       placeholder="Ej. Carne de res premium cortada en tiras" maxlength="200">
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="id_unidad">
                    <i class="bi bi-rulers me-1"></i>Unidad de Medida: *
                </label>
                <select class="form-control" id="id_unidad" name="id_unidad" required>
                    <option value="">Seleccione una unidad</option>
                    <?php foreach($unidades as $unidad): ?>
                        <option value="<?php echo $unidad['id_unidad']; ?>">
                            <?php echo htmlspecialchars($unidad['unidad'] . ' (' . $unidad['abreviatura'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="cantidad_stock">
                    <i class="bi bi-box me-1"></i>Cantidad en Stock: *
                </label>
                <input type="number" class="form-control" id="cantidad_stock" name="cantidad_stock" 
                       required placeholder="Ej. 13.5" step="0.001" min="0">
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">
                <i class="bi bi-plus-circle me-1"></i>Nuevo
            </button>
            <button id="btn-guardar" type="button" class="btn btn-success">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">
                <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
            </button>
            <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">
                <i class="bi bi-x-circle me-1"></i>Cancelar
            </button>
        </div>

        <h2 class="card-title mb-3 mt-5">
            <i class="bi bi-list-ul me-2"></i>LISTA DE INGREDIENTES
        </h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-ingredientes">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ingredientes as $ingrediente): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ingrediente['id_ingrediente']); ?></td>
                        <td><?php echo htmlspecialchars($ingrediente['nombre_ingrediente']); ?></td>
                        <td><?php echo htmlspecialchars($ingrediente['descripcion'] ?? ''); ?></td>
                        <td>
                            <?php 
                            echo htmlspecialchars(
                                $ingrediente['unidad'] . ' (' . $ingrediente['abreviatura'] . ')'
                            ); 
                            ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $ingrediente['cantidad_stock'] < 10 ? 'bg-warning' : 'bg-success'; ?>">
                                <?php echo htmlspecialchars($ingrediente['cantidad_stock']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $ingrediente['id_ingrediente']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($ingrediente['nombre_ingrediente']); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($ingrediente['descripcion'] ?? ''); ?>"
                                    data-unidad="<?php echo $ingrediente['id_unidad']; ?>"
                                    data-stock="<?php echo $ingrediente['cantidad_stock']; ?>">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este ingrediente?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_ingrediente" value="<?php echo $ingrediente['id_ingrediente']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ingredientes)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay ingredientes registrados</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="Gestion_Inventario_Ingredientes.js"></script>
</body>
</html>