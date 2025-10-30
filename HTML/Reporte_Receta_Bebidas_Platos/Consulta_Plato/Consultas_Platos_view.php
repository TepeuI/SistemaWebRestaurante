<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas Integradas - Marea Roja</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="Consultas_Platos.css">
</head>

<body>
    <!-- BARRA SUPERIOR COMPLETA -->
    <div class="header-full">
        <div class="header-content">
            <h1 class="header-title">
                Consultas Integradas
            </h1>
            <a href="../../menu_empleados_vista.php" class="nav-link">
                <i class="bi bi-arrow-left me-1"></i>Regresar al Menú
            </a>
        </div>
    </div>

    <div class="container">
        <!-- Filtros -->
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">
                    <i class="bi bi-funnel me-2"></i>Filtros de Búsqueda
                </h2>
                <form method="GET" action="">
                    <div class="filtro-group">
                        <div class="filtro-item">
                            <label for="tipo_consulta"><i class="bi bi-grid me-1"></i>Tipo de Consulta:</label>
                            <select id="tipo_consulta" name="tipo_consulta" onchange="this.form.submit()">
                                <option value="platos" <?php echo ($tipo_consulta == 'platos') ? 'selected' : ''; ?>>Platos</option>
                                <option value="bebidas" <?php echo ($tipo_consulta == 'bebidas') ? 'selected' : ''; ?>>Bebidas</option>
                                <option value="ingredientes" <?php echo ($tipo_consulta == 'ingredientes') ? 'selected' : ''; ?>>Ingredientes</option>
                                <option value="recetas" <?php echo ($tipo_consulta == 'recetas') ? 'selected' : ''; ?>>Recetas</option>
                            </select>
                        </div>
                        
                        <?php if (in_array($tipo_consulta, ['platos', 'bebidas', 'ingredientes'])): ?>
                        <div class="filtro-item">
                            <label for="precio_min">
                                <i class="bi bi-currency-dollar me-1"></i>
                                <?php echo $tipo_consulta == 'ingredientes' ? 'Stock Mínimo:' : 'Precio Mínimo:'; ?>
                            </label>
                            <input type="number" id="precio_min" name="precio_min" value="<?php echo htmlspecialchars($precio_min); ?>" 
                                   step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="filtro-item">
                            <label for="precio_max">
                                <i class="bi bi-currency-dollar me-1"></i>
                                <?php echo $tipo_consulta == 'ingredientes' ? 'Stock Máximo:' : 'Precio Máximo:'; ?>
                            </label>
                            <input type="number" id="precio_max" name="precio_max" value="<?php echo htmlspecialchars($precio_max); ?>" 
                                   step="0.01" min="0" placeholder="<?php echo $tipo_consulta == 'ingredientes' ? '1000.00' : '100.00'; ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="filtro-item">
                            <label for="busqueda_nombre"><i class="bi bi-search me-1"></i>Buscar por Nombre:</label>
                            <input type="text" id="busqueda_nombre" name="busqueda_nombre" 
                                   value="<?php echo htmlspecialchars($busqueda_nombre); ?>" 
                                   placeholder="Ej: Pasta, Refresco, Harina...">
                        </div>
                        
                        <div class="filtro-item">
                            <button type="submit" class="btn-buscar">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                            <a href="Consultas_Platos.php" class="btn-limpiar" style="margin-left: 10px;">
                                <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen Estadístico -->
        <div class="resumen">
            <h2 class="card-title text-primary mb-4">
                <i class="bi bi-graph-up me-2"></i>Resumen de <?php echo ucfirst($tipo_consulta); ?>
            </h2>
            <div class="resumen-grid">
                <div class="resumen-item">
                    <h3>Total Registros</h3>
                    <div class="valor"><?php echo number_format($total_registros); ?></div>
                </div>
                <?php if (in_array($tipo_consulta, ['platos', 'bebidas', 'ingredientes'])): ?>
                <div class="resumen-item">
                    <h3><?php echo $tipo_consulta == 'ingredientes' ? 'Stock Promedio' : 'Precio Promedio'; ?></h3>
                    <div class="valor text-success">
                        <?php echo $tipo_consulta == 'ingredientes' ? number_format($promedio, 2) : 'Q' . number_format($promedio, 2); ?>
                    </div>
                </div>
                <div class="resumen-item">
                    <h3><?php echo $tipo_consulta == 'ingredientes' ? 'Stock Mínimo' : 'Precio Mínimo'; ?></h3>
                    <div class="valor text-info">
                        <?php echo $tipo_consulta == 'ingredientes' ? number_format($minimo, 2) : 'Q' . number_format($minimo, 2); ?>
                    </div>
                </div>
                <div class="resumen-item">
                    <h3><?php echo $tipo_consulta == 'ingredientes' ? 'Stock Máximo' : 'Precio Máximo'; ?></h3>
                    <div class="valor text-warning">
                        <?php echo $tipo_consulta == 'ingredientes' ? number_format($maximo, 2) : 'Q' . number_format($maximo, 2); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

       <!-- Botones de exportación -->
<div class="export-buttons">
    <a href="exportar_excel.php?tipo_consulta=<?php echo $tipo_consulta; ?>&precio_min=<?php echo $precio_min; ?>&precio_max=<?php echo $precio_max; ?>&busqueda_nombre=<?php echo urlencode($busqueda_nombre); ?>" 
       class="btn-export btn-excel">
        <i class="bi bi-file-earmark-excel"></i>Exportar Excel
    </a>
</div>

        <!-- Resultados de la Consulta -->
        <div class="tabla-container">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">
                    <i class="bi bi-list-ul me-2"></i>
                    <?php 
                    $titulos = [
                        'platos' => 'Lista de Platos',
                        'bebidas' => 'Lista de Bebidas', 
                        'ingredientes' => 'Lista de Ingredientes',
                        'recetas' => 'Composición de Recetas'
                    ];
                    echo $titulos[$tipo_consulta];
                    ?>
                    <span class="badge bg-secondary ms-2"><?php echo number_format($total_registros); ?> registros</span>
                </h2>
                
                <?php if (!empty($resultados)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <?php if (in_array($tipo_consulta, ['platos', 'bebidas'])): ?>
                                        <th class="text-right">Precio Unitario</th>
                                        <th>Categoría Precio</th>
                                    <?php elseif ($tipo_consulta == 'ingredientes'): ?>
                                        <th class="text-right">Stock</th>
                                        <th>Unidad</th>
                                        <th>Nivel Stock</th>
                                    <?php elseif ($tipo_consulta == 'recetas'): ?>
                                        <th>Ingrediente</th>
                                        <th>Unidad</th>
                                    <?php endif; ?>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultados as $row): 
                                    $badge_class = '';
                                    if (in_array($tipo_consulta, ['platos', 'bebidas'])) {
                                        if ($row['categoria_precio'] == 'ECONÓMICO') {
                                            $badge_class = 'badge-economico';
                                        } elseif ($row['categoria_precio'] == 'MEDIO') {
                                            $badge_class = 'badge-medio';
                                        } else {
                                            $badge_class = 'badge-premium';
                                        }
                                    } elseif ($tipo_consulta == 'ingredientes') {
                                        if ($row['categoria_precio'] == 'BAJO') {
                                            $badge_class = 'badge-bajo';
                                        } elseif ($row['categoria_precio'] == 'MEDIO') {
                                            $badge_class = 'badge-medio';
                                        } else {
                                            $badge_class = 'badge-alto';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nombre']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['descripcion'] ?? ''); ?></td>
                                    
                                    <?php if (in_array($tipo_consulta, ['platos', 'bebidas'])): ?>
                                        <td class="text-right text-success fw-bold">
                                            Q<?php echo number_format($row['precio_unitario'], 2); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $row['categoria_precio']; ?>
                                            </span>
                                        </td>
                                    <?php elseif ($tipo_consulta == 'ingredientes'): ?>
                                        <td class="text-right fw-bold">
                                            <?php echo number_format($row['precio_unitario'], 2); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['unidad'] ?? $row['abreviatura'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo $row['categoria_precio']; ?>
                                            </span>
                                        </td>
                                    <?php elseif ($tipo_consulta == 'recetas'): ?>
                                        <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                        <td><?php echo htmlspecialchars($row['unidad'] ?? $row['abreviatura'] ?? ''); ?></td>
                                    <?php endif; ?>
                                    
                                    <td class="text-center">
                                        <button onclick="verDetalle(<?php echo $row['id']; ?>, '<?php echo $tipo_consulta; ?>')" class="btn-ver">
                                            <i class="bi bi-eye"></i>Ver
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-database"></i>
                        <h3>No se encontraron registros</h3>
                        <p>No hay <?php echo $tipo_consulta; ?> registrados con los filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="Js_Consultas_Platos.js"></script>
</body>
</html>