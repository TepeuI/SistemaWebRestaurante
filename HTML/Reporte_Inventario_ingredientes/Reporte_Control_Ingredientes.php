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
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$estado_ingrediente = $_GET['estado'] ?? '';
$ingrediente_id = $_GET['ingrediente'] ?? '';

// Construir consulta para control de ingredientes
$where_conditions_control = [];
$params_control = [];
$types_control = '';

// Filtro por fecha desde (fecha_entrada)
if (!empty($fecha_desde)) {
    $where_conditions_control[] = "ci.fecha_entrada >= ?";
    $params_control[] = $fecha_desde;
    $types_control .= 's';
}

// Filtro por fecha hasta (fecha_entrada)
if (!empty($fecha_hasta)) {
    $where_conditions_control[] = "ci.fecha_entrada <= ?";
    $params_control[] = $fecha_hasta;
    $types_control .= 's';
}

// Filtro por estado
if (!empty($estado_ingrediente)) {
    $where_conditions_control[] = "ci.estado = ?";
    $params_control[] = $estado_ingrediente;
    $types_control .= 's';
}

// Filtro por ingrediente
if (!empty($ingrediente_id)) {
    $where_conditions_control[] = "ci.id_ingrediente = ?";
    $params_control[] = $ingrediente_id;
    $types_control .= 'i';
}

// Consulta base para control de ingredientes
$query_control = "
    SELECT 
        ci.id_control,
        ci.id_ingrediente,
        i.nombre_ingrediente,
        i.descripcion,
        ci.estado,
        ci.fecha_entrada,
        ci.fecha_caducidad,
        DATEDIFF(ci.fecha_caducidad, CURDATE()) as dias_restantes,
        i.cantidad_stock,
        um.unidad as nombre_unidad,
        um.abreviatura
    FROM control_ingredientes ci
    INNER JOIN ingredientes i ON ci.id_ingrediente = i.id_ingrediente
    INNER JOIN unidades_medida um ON i.id_unidad = um.id_unidad
";

// Agregar condiciones WHERE si existen
if (!empty($where_conditions_control)) {
    $query_control .= " WHERE " . implode(" AND ", $where_conditions_control);
}

$query_control .= " ORDER BY ci.fecha_caducidad ASC, ci.estado DESC";

// Preparar y ejecutar consulta de control
if (!empty($params_control)) {
    $stmt_control = $conexion->prepare($query_control);
    $stmt_control->bind_param($types_control, ...$params_control);
    $stmt_control->execute();
    $result_control = $stmt_control->get_result();
} else {
    $result_control = $conexion->query($query_control);
}

// CONSULTA CORREGIDA PARA PÉRDIDAS - SIN fecha_perdida
$query_perdidas = "
    SELECT 
        pi.id_perdida,
        pi.id_ingrediente,
        i.nombre_ingrediente,
        pi.descripcion,
        pi.cantidad_unitaria_perdida,
        pi.costo_perdida,
        um.unidad as nombre_unidad,
        um.abreviatura
    FROM perdidas_inventario pi
    INNER JOIN ingredientes i ON pi.id_ingrediente = i.id_ingrediente
    INNER JOIN unidades_medida um ON i.id_unidad = um.id_unidad
";

// Como no hay fecha en pérdidas, no aplicamos filtros de fecha
$query_perdidas .= " ORDER BY pi.costo_perdida DESC";

// Ejecutar consulta de pérdidas
$result_perdidas = $conexion->query($query_perdidas);

// Consultas para resumen estadístico
// Total ingredientes en control
$query_total_control = "SELECT COUNT(*) as total FROM control_ingredientes ci";
if (!empty($where_conditions_control)) {
    $query_total_control .= " WHERE " . implode(" AND ", $where_conditions_control);
}

// Total pérdidas (sin filtros de fecha)
$query_total_perdidas = "SELECT COUNT(*) as total, SUM(pi.costo_perdida) as total_costo FROM perdidas_inventario pi";

// Ejecutar consultas del resumen con parámetros si existen
if (!empty($params_control)) {
    $stmt_total_control = $conexion->prepare($query_total_control);
    $stmt_total_control->bind_param($types_control, ...$params_control);
    $stmt_total_control->execute();
    $total_control_result = $stmt_total_control->get_result();
} else {
    $total_control_result = $conexion->query($query_total_control);
}

$total_perdidas_result = $conexion->query($query_total_perdidas);

$total_control = $total_control_result->fetch_assoc()['total'] ?? 0;
$total_perdidas_data = $total_perdidas_result->fetch_assoc();
$total_perdidas = $total_perdidas_data['total'] ?? 0;
$total_costo_perdidas = $total_perdidas_data['total_costo'] ?? 0;

// Obtener ingredientes para el dropdown
$ingredientes = $conexion->query("SELECT id_ingrediente, nombre_ingrediente FROM ingredientes ORDER BY nombre_ingrediente");

// Cerrar statements si existen
if (isset($stmt_total_control)) $stmt_total_control->close();

// FUNCIÓN PARA EXPORTAR A EXCEL
if (isset($_GET['exportar_excel'])) {
    exportarExcel($result_control, $result_perdidas, $fecha_desde, $fecha_hasta, $estado_ingrediente, $ingrediente_id);
}

function exportarExcel($result_control, $result_perdidas, $fecha_desde, $fecha_hasta, $estado_ingrediente, $ingrediente_id) {
    // Configurar headers para descarga de Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="control_inventarios_' . date('Y-m-d') . '.xls"');
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
    echo "<div class='titulo'>Reporte de Control de Inventarios y Pérdidas</div>";
    echo "<div class='subtitulo'>Marea Roja - " . date('d/m/Y H:i:s') . "</div>";
    
    // Mostrar filtros aplicados
    echo "<div class='filtros'>";
    echo "<strong>Filtros aplicados:</strong><br>";
    if (!empty($fecha_desde)) echo "Fecha desde: " . $fecha_desde . "<br>";
    if (!empty($fecha_hasta)) echo "Fecha hasta: " . $fecha_hasta . "<br>";
    if (!empty($estado_ingrediente)) echo "Estado: " . $estado_ingrediente . "<br>";
    if (!empty($ingrediente_id)) echo "ID Ingrediente: " . $ingrediente_id . "<br>";
    echo "</div>";
    
    // CONTROL DE INGREDIENTES
    echo "<div class='subtitulo'>CONTROL DE INGREDIENTES</div>";
    if ($result_control->num_rows > 0) {
        echo "<table>";
        echo "<tr>";
        echo "<th>ID Control</th>";
        echo "<th>ID Ingrediente</th>";
        echo "<th>Ingrediente</th>";
        echo "<th>Descripción</th>";
        echo "<th>Estado</th>";
        echo "<th>Fecha Entrada</th>";
        echo "<th>Fecha Caducidad</th>";
        echo "<th>Días Restantes</th>";
        echo "<th>Stock Actual</th>";
        echo "<th>Unidad</th>";
        echo "</tr>";
        
        // Reiniciar el puntero del resultado
        $result_control->data_seek(0);
        
        while ($row = $result_control->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id_control'] . "</td>";
            echo "<td>" . $row['id_ingrediente'] . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_ingrediente']) . "</td>";
            echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
            echo "<td>" . $row['estado'] . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_entrada'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_caducidad'])) . "</td>";
            echo "<td>" . $row['dias_restantes'] . " días</td>";
            echo "<td>" . number_format($row['cantidad_stock'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['abreviatura']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay datos de control de ingredientes</p>";
    }
    
    echo "<br><br>";
    
    // PÉRDIDAS DE INVENTARIO
    echo "<div class='subtitulo'>PÉRDIDAS DE INVENTARIO</div>";
    if ($result_perdidas->num_rows > 0) {
        echo "<table>";
        echo "<tr>";
        echo "<th>ID Pérdida</th>";
        echo "<th>ID Ingrediente</th>";
        echo "<th>Ingrediente</th>";
        echo "<th>Descripción</th>";
        echo "<th>Cantidad Perdida</th>";
        echo "<th>Costo Pérdida</th>";
        echo "<th>Unidad</th>";
        echo "</tr>";
        
        // Reiniciar el puntero del resultado
        $result_perdidas->data_seek(0);
        
        while ($row = $result_perdidas->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id_perdida'] . "</td>";
            echo "<td>" . $row['id_ingrediente'] . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_ingrediente']) . "</td>";
            echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
            echo "<td>" . number_format($row['cantidad_unitaria_perdida'], 2) . "</td>";
            echo "<td>Q " . number_format($row['costo_perdida'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['abreviatura']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay datos de pérdidas de inventario</p>";
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
    <title>Control de Inventarios y Pérdidas - Marea Roja</title>
    
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

        .btn-pdf {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .btn-pdf:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .btn-excel {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .btn-excel:hover {
            background: linear-gradient(135deg, #059669, #047857);
        }

        .btn-ver, .btn-editar {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin: 2px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-editar {
            background: #f59e0b;
        }

        .btn-ver:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-editar:hover {
            background: #d97706;
            transform: translateY(-1px);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-ok {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-por-vencer {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-vencido {
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
            
            .btn-ver, .btn-editar {
                padding: 6px 10px;
                font-size: 11px;
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
                <i class="bi bi-clipboard-data me-2"></i>Control de Inventarios y Pérdidas
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
                            <label for="fecha_desde"><i class="bi bi-calendar-date me-1"></i>Fecha Desde:</label>
                            <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                        </div>
                        <div class="filtro-item">
                            <label for="fecha_hasta"><i class="bi bi-calendar-date me-1"></i>Fecha Hasta:</label>
                            <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                        </div>
                        <div class="filtro-item">
                            <label for="estado"><i class="bi bi-tags me-1"></i>Estado Ingrediente:</label>
                            <select id="estado" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="OK" <?php echo ($estado_ingrediente == 'OK') ? 'selected' : ''; ?>>OK</option>
                                <option value="POR_VENCER" <?php echo ($estado_ingrediente == 'POR_VENCER') ? 'selected' : ''; ?>>Por Vencer</option>
                                <option value="VENCIDO" <?php echo ($estado_ingrediente == 'VENCIDO') ? 'selected' : ''; ?>>Vencido</option>
                            </select>
                        </div>
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
                            <button type="submit" class="btn-buscar">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                            <a href="Reporte_Control_Ingredientes.php" class="btn-limpiar" style="margin-left: 10px;">
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
                    <h3>Ingredientes en Control</h3>
                    <div class="valor"><?php echo number_format($total_control); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Total Pérdidas</h3>
                    <div class="valor"><?php echo number_format($total_perdidas); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Costo Total Pérdidas</h3>
                    <div class="valor text-danger">Q<?php echo number_format($total_costo_perdidas, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Botones de exportación -->
        <div class="export-buttons">
            <!-- <a href="#" class="btn-export btn-pdf" onclick="exportarPDF()">
                <i class="bi bi-file-earmark-pdf"></i>Exportar PDF
            </a> -->
            <a href="?<?php 
                echo http_build_query(array_merge($_GET, ['exportar_excel' => '1']));
            ?>" class="btn-export btn-excel">
                <i class="bi bi-file-earmark-excel"></i>Exportar Excel
            </a>
        </div>

        <!-- Control de Ingredientes -->
        <div class="tabla-container">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">
                    <i class="bi bi-clipboard-check me-2"></i>Control de Ingredientes
                </h2>
                <?php if ($result_control->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Control</th>
                                    <th>Ingrediente</th>
                                    <th>Estado</th>
                                    <th>Fecha Entrada</th>
                                    <th>Fecha Caducidad</th>
                                    <th>Días Restantes</th>
                                    <th>Stock Actual</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_control->fetch_assoc()): 
                                    $dias_restantes = $row['dias_restantes'];
                                    $badge_class = '';
                                    if ($row['estado'] == 'VENCIDO') {
                                        $badge_class = 'badge-vencido';
                                    } elseif ($row['estado'] == 'POR_VENCER') {
                                        $badge_class = 'badge-por-vencer';
                                    } else {
                                        $badge_class = 'badge-ok';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $row['id_control']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nombre_ingrediente']); ?></strong>
                                        <div class="info-proveedor" style="font-size: 12px; color: #6b7280;">
                                            <?php echo htmlspecialchars($row['descripcion']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $row['estado']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($row['fecha_entrada'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['fecha_caducidad'])); ?></td>
                                    <td class="<?php echo $dias_restantes < 0 ? 'text-danger' : ($dias_restantes < 7 ? 'text-warning' : 'text-success'); ?>">
                                        <?php echo $dias_restantes; ?> días
                                    </td>
                                    <td class="text-right">
                                        <?php echo number_format($row['cantidad_stock'], 2) . ' ' . htmlspecialchars($row['abreviatura']); ?>
                                    </td>
                                    <td class="text-center">
                                        <button onclick="verDetalleControl(<?php echo $row['id_control']; ?>)" class="btn-ver">
                                            <i class="bi bi-eye"></i>Ver
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-inbox"></i>
                        <h3>No se encontraron registros de control</h3>
                        <p>No hay ingredientes en control con los filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pérdidas de Inventario -->
        <div class="tabla-container">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>Pérdidas de Inventario
                </h2>
                <?php if ($result_perdidas->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Pérdida</th>
                                    <th>Ingrediente</th>
                                    <th>Descripción</th>
                                    <th class="text-right">Cantidad Perdida</th>
                                    <th class="text-right">Costo Pérdida</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result_perdidas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id_perdida']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nombre_ingrediente']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                    <td class="text-right">
                                        <?php echo number_format($row['cantidad_unitaria_perdida'], 2) . ' ' . htmlspecialchars($row['abreviatura']); ?>
                                    </td>
                                    <td class="text-right text-danger">
                                        Q<?php echo number_format($row['costo_perdida'], 2); ?>
                                    </td>
                                    <td class="text-center">
                                        <button onclick="verDetallePerdida(<?php echo $row['id_perdida']; ?>)" class="btn-ver">
                                            <i class="bi bi-eye"></i>Ver
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-inbox"></i>
                        <h3>No se encontraron registros de pérdidas</h3>
                        <p>No hay pérdidas registradas con los filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function verDetalleControl(idControl) {
            alert('Ver detalle de control: ' + idControl);
            // window.location.href = 'detalle_control.php?id=' + idControl;
        }

        function editarControl(idControl) {
            if(confirm('¿Estás seguro de que quieres editar este control?')) {
                alert('Editar control: ' + idControl);
                // window.location.href = 'editar_control.php?id=' + idControl;
            }
        }

        function verDetallePerdida(idPerdida) {
            alert('Ver detalle de pérdida: ' + idPerdida);
            // window.location.href = 'detalle_perdida.php?id=' + idPerdida;
        }

        function editarPerdida(idPerdida) {
            if(confirm('¿Estás seguro de que quieres editar esta pérdida?')) {
                alert('Editar pérdida: ' + idPerdida);
                // window.location.href = 'editar_perdida.php?id=' + idPerdida;
            }
        }

        function exportarPDF() {
            alert('Funcionalidad de exportar PDF en desarrollo');
            // window.location.href = 'exportar_pdf.php?' + window.location.search;
        }

        // Establecer fechas por defecto (últimos 30 días) si no hay filtros aplicados
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('fecha_desde') && !urlParams.has('fecha_hasta')) {
                const fechaHasta = new Date().toISOString().split('T')[0];
                const fechaDesde = new Date();
                fechaDesde.setDate(fechaDesde.getDate() - 30);
                
                document.getElementById('fecha_hasta').value = fechaHasta;
                document.getElementById('fecha_desde').value = fechaDesde.toISOString().split('T')[0];
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