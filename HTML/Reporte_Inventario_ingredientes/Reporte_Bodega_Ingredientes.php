<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// RUTA CORREGIDA Y USANDO TU FUNCIÓN
include('../conexion.php');

// Crear conexión usando tu función
$conexion = conectar();

// Inicializar variables de filtro
$ingrediente_id = $_GET['ingrediente'] ?? '';
$stock_minimo = $_GET['stock_minimo'] ?? '';
$unidad_id = $_GET['unidad'] ?? '';

// Construir consulta para inventario de ingredientes
$where_conditions = [];
$params = [];
$types = '';

// Filtro por ingrediente
if (!empty($ingrediente_id)) {
    $where_conditions[] = "i.id_ingrediente = ?";
    $params[] = $ingrediente_id;
    $types .= 'i';
}

// Filtro por stock mínimo
if (!empty($stock_minimo)) {
    $where_conditions[] = "i.cantidad_stock <= ?";
    $params[] = $stock_minimo;
    $types .= 'd';
}

// Filtro por unidad de medida
if (!empty($unidad_id)) {
    $where_conditions[] = "i.id_unidad = ?";
    $params[] = $unidad_id;
    $types .= 'i';
}

// Consulta base para inventario de ingredientes
$query_inventario = "
    SELECT 
        i.id_ingrediente,
        i.nombre_ingrediente,
        i.descripcion,
        i.cantidad_stock,
        um.unidad as nombre_unidad,
        um.abreviatura,
        CASE 
            WHEN i.cantidad_stock = 0 THEN 'AGOTADO'
            WHEN i.cantidad_stock <= 10 THEN 'BAJO'
            ELSE 'NORMAL'
        END as estado_stock
    FROM ingredientes i
    INNER JOIN unidades_medida um ON i.id_unidad = um.id_unidad
";

// Agregar condiciones WHERE si existen
if (!empty($where_conditions)) {
    $query_inventario .= " WHERE " . implode(" AND ", $where_conditions);
}

$query_inventario .= " ORDER BY i.cantidad_stock ASC, i.nombre_ingrediente ASC";

// Preparar y ejecutar consulta de inventario
if (!empty($params)) {
    $stmt_inventario = $conexion->prepare($query_inventario);
    $stmt_inventario->bind_param($types, ...$params);
    $stmt_inventario->execute();
    $result_inventario = $stmt_inventario->get_result();
} else {
    $result_inventario = $conexion->query($query_inventario);
}

// Consultas para resumen estadístico - CORREGIDAS
$total_ingredientes = 0;
$total_stock_bajo = 0;
$total_agotados = 0;

// Total ingredientes
if (!empty($params)) {
    $query_total_ingredientes = "SELECT COUNT(*) as total FROM ingredientes i";
    if (!empty($where_conditions)) {
        $query_total_ingredientes .= " WHERE " . implode(" AND ", $where_conditions);
    }
    
    $stmt_total = $conexion->prepare($query_total_ingredientes);
    $stmt_total->bind_param($types, ...$params);
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    $total_ingredientes = $total_result->fetch_assoc()['total'] ?? 0;
    $stmt_total->close();
} else {
    $total_result = $conexion->query("SELECT COUNT(*) as total FROM ingredientes i");
    $total_ingredientes = $total_result->fetch_assoc()['total'] ?? 0;
}

// Ingredientes con stock bajo (≤10)
$query_stock_bajo = "SELECT COUNT(*) as total FROM ingredientes i WHERE i.cantidad_stock <= 10";
if (!empty($where_conditions)) {
    // Para stock bajo, necesitamos combinar con los filtros existentes
    $query_stock_bajo .= " AND " . implode(" AND ", $where_conditions);
    
    $stmt_stock_bajo = $conexion->prepare($query_stock_bajo);
    $stmt_stock_bajo->bind_param($types, ...$params);
    $stmt_stock_bajo->execute();
    $stock_bajo_result = $stmt_stock_bajo->get_result();
    $total_stock_bajo = $stock_bajo_result->fetch_assoc()['total'] ?? 0;
    $stmt_stock_bajo->close();
} else {
    $stock_bajo_result = $conexion->query($query_stock_bajo);
    $total_stock_bajo = $stock_bajo_result->fetch_assoc()['total'] ?? 0;
}

// Ingredientes agotados
$query_agotados = "SELECT COUNT(*) as total FROM ingredientes i WHERE i.cantidad_stock = 0";
if (!empty($where_conditions)) {
    $query_agotados .= " AND " . implode(" AND ", $where_conditions);
    
    $stmt_agotados = $conexion->prepare($query_agotados);
    $stmt_agotados->bind_param($types, ...$params);
    $stmt_agotados->execute();
    $agotados_result = $stmt_agotados->get_result();
    $total_agotados = $agotados_result->fetch_assoc()['total'] ?? 0;
    $stmt_agotados->close();
} else {
    $agotados_result = $conexion->query($query_agotados);
    $total_agotados = $agotados_result->fetch_assoc()['total'] ?? 0;
}

// Obtener ingredientes para el dropdown
$ingredientes = $conexion->query("SELECT id_ingrediente, nombre_ingrediente FROM ingredientes ORDER BY nombre_ingrediente");

// Obtener unidades de medida para el dropdown
$unidades = $conexion->query("SELECT id_unidad, unidad, abreviatura FROM unidades_medida ORDER BY unidad");

// Cerrar statements si existen
if (isset($stmt_inventario)) $stmt_inventario->close();

// FUNCIÓN PARA EXPORTAR A EXCEL
if (isset($_GET['exportar_excel'])) {
    exportarExcel($result_inventario, $ingrediente_id, $stock_minimo, $unidad_id);
}

function exportarExcel($result_inventario, $ingrediente_id, $stock_minimo, $unidad_id) {
    // Configurar headers para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="inventario_ingredientes_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Crear contenido Excel
    echo "<html>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<style>";
    echo "table { border-collapse: collapse; width: 100%; }";
    echo "th, td { border: 1px solid black; padding: 8px; text-align: left; }";
    echo "th { background-color: #f2f2f2; font-weight: bold; }";
    echo ".titulo { font-size: 18px; font-weight: bold; margin-bottom: 10px; }";
    echo ".subtitulo { font-size: 14px; margin-bottom: 5px; }";
    echo ".filtros { margin-bottom: 15px; font-size: 12px; }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    
    // Título y información
    echo "<div class='titulo'>Reporte de Inventario de Ingredientes</div>";
    echo "<div class='subtitulo'>Marea Roja - " . date('d/m/Y H:i:s') . "</div>";
    
    // Mostrar filtros aplicados
    echo "<div class='filtros'>";
    echo "<strong>Filtros aplicados:</strong><br>";
    if (!empty($ingrediente_id)) {
        // Obtener nombre del ingrediente para mostrar en lugar del ID
        include('../conexion.php');
        $conexion_temp = conectar();
        $stmt_ing = $conexion_temp->prepare("SELECT nombre_ingrediente FROM ingredientes WHERE id_ingrediente = ?");
        $stmt_ing->bind_param("i", $ingrediente_id);
        $stmt_ing->execute();
        $result_ing = $stmt_ing->get_result();
        $ingrediente_nombre = $result_ing->fetch_assoc()['nombre_ingrediente'] ?? $ingrediente_id;
        $stmt_ing->close();
        desconectar($conexion_temp);
        
        echo "Ingrediente: " . htmlspecialchars($ingrediente_nombre) . "<br>";
    }
    if (!empty($stock_minimo)) echo "Stock máximo: " . $stock_minimo . "<br>";
    if (!empty($unidad_id)) {
        // Obtener nombre de la unidad para mostrar en lugar del ID
        include('../conexion.php');
        $conexion_temp = conectar();
        $stmt_uni = $conexion_temp->prepare("SELECT unidad FROM unidades_medida WHERE id_unidad = ?");
        $stmt_uni->bind_param("i", $unidad_id);
        $stmt_uni->execute();
        $result_uni = $stmt_uni->get_result();
        $unidad_nombre = $result_uni->fetch_assoc()['unidad'] ?? $unidad_id;
        $stmt_uni->close();
        desconectar($conexion_temp);
        
        echo "Unidad: " . htmlspecialchars($unidad_nombre) . "<br>";
    }
    echo "</div>";
    
    // INVENTARIO DE INGREDIENTES
    echo "<div class='subtitulo'>INVENTARIO DE INGREDIENTES</div>";
    if ($result_inventario->num_rows > 0) {
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Ingrediente</th>";
        echo "<th>Descripción</th>";
        echo "<th>Stock Actual</th>";
        echo "<th>Unidad</th>";
        echo "<th>Estado</th>";
        echo "</tr>";
        
        // Reiniciar el puntero del resultado
        $result_inventario->data_seek(0);
        
        while ($row = $result_inventario->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id_ingrediente'] . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_ingrediente']) . "</td>";
            echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
            echo "<td>" . number_format($row['cantidad_stock'], 3) . "</td>";
            echo "<td>" . htmlspecialchars($row['abreviatura']) . "</td>";
            echo "<td>" . $row['estado_stock'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay datos de inventario</p>";
    }
    
    echo "</body>";
    echo "</html>";
    exit();
}
?>

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
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, h1, h2, h3, h4, h5, h6, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }

        body {
            background-color: #f8fafc;
            color: #334155;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* BARRA SUPERIOR COMPLETA */
        .header-full {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 20px 0;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            width: 100%;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        .nav-link {
            background: rgba(255, 255, 255, 0.2);
            color: white !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 25px;
            background: white;
        }

        .card-body {
            padding: 25px;
        }

        .filtros {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filtro-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filtro-item {
            flex: 1;
            min-width: 220px;
        }

        .filtro-item label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .filtro-item input, .filtro-item select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .filtro-item input:focus, .filtro-item select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .btn-buscar {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-buscar:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .btn-limpiar {
            background: #6b7280;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-limpiar:hover {
            background: #4b5563;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }

        .resumen {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .resumen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .resumen-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 10px;
            border-left: 4px solid #3b82f6;
            transition: transform 0.3s ease;
        }

        .resumen-item:hover {
            transform: translateY(-2px);
        }

        .resumen-item h3 {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .resumen-item .valor {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .tabla-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #64748b;
        }

        .no-data i {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            color: #cbd5e1;
        }

        .no-data h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #475569;
        }

        .export-buttons {
            margin-bottom: 25px;
            text-align: right;
        }

        .btn-export {
            background: #6b7280;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-left: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .btn-export:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }

        .btn-excel {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .btn-excel:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-normal {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-bajo {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-agotado {
            background: #fee2e2;
            color: #991b1b;
        }

        .text-danger {
            color: #dc2626;
            font-weight: 700;
        }

        .text-warning {
            color: #d97706;
            font-weight: 700;
        }

        .text-success {
            color: #059669;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-title {
                font-size: 22px;
            }
            
            .filtro-group {
                flex-direction: column;
            }
            
            .filtro-item {
                min-width: 100%;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 10px 8px;
            }
            
            .resumen-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- BARRA SUPERIOR COMPLETA -->
    <div class="header-full">
        <div class="header-content">
            <h1 class="header-title">
                <i class="bi bi-clipboard-data me-2"></i>Inventario de Ingredientes
            </h1>
            <a href="../menu_empleados_vista.php" class="nav-link">
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
                            <label for="ingrediente"><i class="bi bi-egg me-1"></i>Ingrediente:</label>
                            <select id="ingrediente" name="ingrediente">
                                <option value="">Todos los ingredientes</option>
                                <?php
                                while ($ing = $ingredientes->fetch_assoc()) {
                                    $selected = ($ingrediente_id == $ing['id_ingrediente']) ? 'selected' : '';
                                    echo "<option value='{$ing['id_ingrediente']}' $selected>{$ing['nombre_ingrediente']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filtro-item">
                            <label for="stock_minimo"><i class="bi bi-exclamation-triangle me-1"></i>Stock Máximo:</label>
                            <input type="number" id="stock_minimo" name="stock_minimo" value="<?php echo htmlspecialchars($stock_minimo); ?>" 
                                   step="0.001" min="0" placeholder="Mostrar stock menor o igual a...">
                        </div>
                        <div class="filtro-item">
                            <label for="unidad"><i class="bi bi-rulers me-1"></i>Unidad de Medida:</label>
                            <select id="unidad" name="unidad">
                                <option value="">Todas las unidades</option>
                                <?php
                                while ($unidad = $unidades->fetch_assoc()) {
                                    $selected = ($unidad_id == $unidad['id_unidad']) ? 'selected' : '';
                                    echo "<option value='{$unidad['id_unidad']}' $selected>{$unidad['unidad']} ({$unidad['abreviatura']})</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filtro-item">
                            <button type="submit" class="btn-buscar">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                            <a href="Reporte_Bodega_Ingredientes.php" class="btn-limpiar" style="margin-left: 10px;">
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
                <i class="bi bi-graph-up me-2"></i>Resumen Estadístico
            </h2>
            <div class="resumen-grid">
                <div class="resumen-item">
                    <h3>Total Ingredientes</h3>
                    <div class="valor"><?php echo number_format($total_ingredientes); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Stock Bajo (≤10)</h3>
                    <div class="valor text-warning"><?php echo number_format($total_stock_bajo); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Agotados</h3>
                    <div class="valor text-danger"><?php echo number_format($total_agotados); ?></div>
                </div>
            </div>
        </div>

        <!-- Botones de exportación -->
        <div class="export-buttons">
            <a href="?<?php 
                echo http_build_query(array_merge($_GET, ['exportar_excel' => '1']));
            ?>" class="btn-export btn-excel">
                <i class="bi bi-file-earmark-excel"></i>Exportar Excel
            </a>
        </div>

        <!-- Inventario de Ingredientes -->
        <div class="tabla-container">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">
                    <i class="bi bi-box-seam me-2"></i>Inventario de Ingredientes
                </h2>
                <?php if ($result_inventario->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ingrediente</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Stock Actual</th>
                                    <th>Unidad</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_inventario->fetch_assoc()): 
                                    $badge_class = [
                                        'NORMAL' => 'badge-normal',
                                        'BAJO' => 'badge-bajo',
                                        'AGOTADO' => 'badge-agotado'
                                    ][$row['estado_stock']] ?? 'badge-normal';
                                ?>
                                <tr>
                                    <td><?php echo $row['id_ingrediente']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nombre_ingrediente']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                    <td class="text-right <?php echo $row['estado_stock'] == 'AGOTADO' ? 'text-danger' : ($row['estado_stock'] == 'BAJO' ? 'text-warning' : 'text-success'); ?>">
                                        <?php echo number_format($row['cantidad_stock'], 3); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['abreviatura']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $row['estado_stock']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-inbox"></i>
                        <h3>No se encontraron ingredientes</h3>
                        <p>No hay ingredientes con los filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Establecer valores por defecto si no hay filtros aplicados
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('stock_minimo')) {
                // Puedes establecer un valor por defecto si lo deseas
                // document.getElementById('stock_minimo').value = '10';
            }
        }
    </script>
</body>
</html>

<?php
// Cerrar conexión al final
if (isset($conexion)) {
    desconectar($conexion);
}
?>