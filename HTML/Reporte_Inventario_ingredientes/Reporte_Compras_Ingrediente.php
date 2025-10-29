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
$proveedor_id = $_GET['proveedor'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$types = '';

// Filtro por fecha desde
if (!empty($fecha_desde)) {
    $where_conditions[] = "ci.fecha_de_compra >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}

// Filtro por fecha hasta
if (!empty($fecha_hasta)) {
    $where_conditions[] = "ci.fecha_de_compra <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}

// Filtro por proveedor
if (!empty($proveedor_id)) {
    $where_conditions[] = "ci.id_proveedor = ?";
    $params[] = $proveedor_id;
    $types .= 'i';
}

// Consulta base
$query = "
    SELECT 
        ci.id_compra_ingrediente,
        ci.fecha_de_compra,
        p.nombre_proveedor,
        p.telefono_proveedor,
        p.correo_proveedor,
        ci.monto_total_compra,
        i.nombre_ingrediente,
        i.id_ingrediente,
        dci.cantidad_compra,
        dci.costo_unitario,
        dci.costo_total,
        um.unidad as nombre_unidad,
        um.abreviatura
    FROM compras_ingrediente ci
    INNER JOIN proveedores p ON ci.id_proveedor = p.id_proveedor
    INNER JOIN detalle_compra_ingrediente dci ON ci.id_compra_ingrediente = dci.id_compra_ingrediente
    INNER JOIN ingredientes i ON dci.id_ingrediente = i.id_ingrediente
    INNER JOIN unidades_medida um ON dci.id_unidad = um.id_unidad
";

// Agregar condiciones WHERE si existen
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY ci.fecha_de_compra DESC, ci.id_compra_ingrediente DESC";

// Preparar y ejecutar consulta
if (!empty($params)) {
    $stmt = $conexion->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conexion->query($query);
}

// CONSULTAS PARA RESUMEN CORREGIDAS
$query_total = "SELECT COUNT(*) as total FROM compras_ingrediente ci";
$query_monto = "SELECT SUM(ci.monto_total_compra) as total FROM compras_ingrediente ci";

// Agregar condiciones WHERE si existen para las consultas del resumen
if (!empty($where_conditions)) {
    $query_total .= " WHERE " . implode(" AND ", $where_conditions);
    $query_monto .= " WHERE " . implode(" AND ", $where_conditions);
}

// Ejecutar consultas del resumen con parámetros si existen
if (!empty($params)) {
    // Total compras
    $stmt_total = $conexion->prepare($query_total);
    $stmt_total->bind_param($types, ...$params);
    $stmt_total->execute();
    $total_compras_result = $stmt_total->get_result();
    
    // Monto total
    $stmt_monto = $conexion->prepare($query_monto);
    $stmt_monto->bind_param($types, ...$params);
    $stmt_monto->execute();
    $monto_total_result = $stmt_monto->get_result();
} else {
    $total_compras_result = $conexion->query($query_total);
    $monto_total_result = $conexion->query($query_monto);
}

$total_compras = $total_compras_result->fetch_assoc()['total'] ?? 0;
$monto_total = $monto_total_result->fetch_assoc()['total'] ?? 0;
$promedio = $total_compras > 0 ? $monto_total / $total_compras : 0;

// Cerrar statements si existen
if (isset($stmt_total)) $stmt_total->close();
if (isset($stmt_monto)) $stmt_monto->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Compras de Ingredientes - Marea Roja</title>
    
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

        .tabla-compras {
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

        .compra-header {
            background-color: #f1f5f9 !important;
            font-weight: 700;
        }

        .compra-header td {
            border-top: 2px solid #cbd5e1;
            font-size: 14px;
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

        .compra-total {
            background: linear-gradient(135deg, #1e293b, #374151) !important;
            color: white;
            font-weight: 700;
        }

        .info-proveedor {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .monto-alto {
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
                <i class="bi bi-cart-check me-2"></i>Reporte de Compras de Ingredientes
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
                            <label for="proveedor"><i class="bi bi-truck me-1"></i>Proveedor:</label>
                            <select id="proveedor" name="proveedor">
                                <option value="">Todos los proveedores</option>
                                <?php
                                $proveedores = $conexion->query("SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor");
                                while ($prov = $proveedores->fetch_assoc()) {
                                    $selected = ($proveedor_id == $prov['id_proveedor']) ? 'selected' : '';
                                    echo "<option value='{$prov['id_proveedor']}' $selected>{$prov['nombre_proveedor']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filtro-item">
                            <button type="submit" class="btn-buscar">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                            <a href="Reporte_Compras_Ingrediente.php" class="btn-limpiar" style="margin-left: 10px;">
                                <i class="bi bi-arrow-clockwise me-1"></i>Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumen -->
        <div class="resumen">
            <h2 class="card-title text-primary mb-4">
                <i class="bi bi-graph-up me-2"></i>Resumen Estadístico
            </h2>
            <div class="resumen-grid">
                <div class="resumen-item">
                    <h3>Total Compras</h3>
                    <div class="valor"><?php echo number_format($total_compras); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Monto Total</h3>
                    <div class="valor">Q<?php echo number_format($monto_total, 2); ?></div>
                </div>
                <div class="resumen-item">
                    <h3>Promedio por Compra</h3>
                    <div class="valor">Q<?php echo number_format($promedio, 2); ?></div>
                </div>
            </div>
        </div>

        <!-- Botones de exportación -->
        <div class="export-buttons">
            <a href="#" class="btn-export btn-pdf" onclick="exportarPDF()">
                <i class="bi bi-file-earmark-pdf"></i>Exportar PDF
            </a>
            <a href="#" class="btn-export btn-excel" onclick="exportarExcel()">
                <i class="bi bi-file-earmark-excel"></i>Exportar Excel
            </a>
        </div>

        <!-- Tabla de compras y detalles -->
        <div class="tabla-compras">
            <div class="card-body">
                <h2 class="card-title text-primary mb-4">
                    <i class="bi bi-list-ul me-2"></i>Detalle de Compras
                </h2>
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID Compra</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Ingrediente</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Costo Unitario</th>
                                    <th class="text-right">Costo Total</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $current_compra = null;
                                $compra_total = 0;
                                
                                while ($row = $result->fetch_assoc()):
                                    if ($current_compra != $row['id_compra_ingrediente']):
                                        if ($current_compra !== null):
                                ?>
                                <tr class="compra-total">
                                    <td colspan="6" class="text-right"><strong>Total Compra:</strong></td>
                                    <td class="text-right"><strong>$<?php echo number_format($compra_total, 2); ?></strong></td>
                                    <td></td>
                                </tr>
                                <?php
                                        endif;
                                        $current_compra = $row['id_compra_ingrediente'];
                                        $compra_total = 0;
                                ?>
                                <tr class="compra-header">
                                    <td><strong>#<?php echo $row['id_compra_ingrediente']; ?></strong></td>
                                    <td><strong><?php echo date('d/m/Y', strtotime($row['fecha_de_compra'])); ?></strong></td>
                                    <td colspan="2">
                                        <strong><?php echo htmlspecialchars($row['nombre_proveedor']); ?></strong>
                                        <div class="info-proveedor">
                                            <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($row['telefono_proveedor']); ?> 
                                            | <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($row['correo_proveedor']); ?>
                                        </div>
                                    </td>
                                    <td class="text-right"></td>
                                    <td class="text-right"></td>
                                    <td class="text-right"><strong class="monto-alto">$<?php echo number_format($row['monto_total_compra'], 2); ?></strong></td>
                                    <td class="text-center">
                                        <button onclick="verDetalle(<?php echo $row['id_compra_ingrediente']; ?>)" class="btn-ver">
                                            <i class="bi bi-eye"></i>Ver
                                        </button>
                                    </td>
                                </tr>
                                <?php
                                    endif;
                                    $compra_total += $row['costo_total'];
                                ?>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><?php echo htmlspecialchars($row['nombre_ingrediente']); ?></td>
                                    <td class="text-right"><?php echo number_format($row['cantidad_compra'], 2) . ' ' . htmlspecialchars($row['abreviatura']); ?></td>
                                    <td class="text-right">Q<?php echo number_format($row['costo_unitario'], 4); ?></td>
                                    <td class="text-right monto-alto">Q<?php echo number_format($row['costo_total'], 2); ?></td>
                                    <td class="text-center">
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <!-- Último total -->
                                <tr class="compra-total">
                                    <td colspan="6" class="text-right"><strong>Total Compra:</strong></td>
                                    <td class="text-right"><strong>Q<?php echo number_format($compra_total, 2); ?></strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="bi bi-inbox"></i>
                        <h3>No se encontraron registros de compras</h3>
                        <p>No hay compras registradas en el sistema con los filtros aplicados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function verDetalle(idCompra) {
            alert('Ver detalle de compra: ' + idCompra);
            // window.location.href = 'detalle_compra.php?id=' + idCompra;
        }

        function editarItem(idCompra, idIngrediente) {
            if(confirm('¿Estás seguro de que quieres editar este item?')) {
                alert('Editar item - Compra: ' + idCompra + ', Ingrediente: ' + idIngrediente);
                // window.location.href = 'editar_detalle.php?compra=' + idCompra + '&ingrediente=' + idIngrediente;
            }
        }

        function exportarPDF() {
            alert('Funcionalidad de exportar PDF en desarrollo');
            // window.location.href = 'exportar_pdf.php?' + window.location.search;
        }

        function exportarExcel() {
            alert('Funcionalidad de exportar Excel en desarrollo');
            // window.location.href = 'exportar_excel.php?' + window.location.search;
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