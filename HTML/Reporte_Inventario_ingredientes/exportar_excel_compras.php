<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

include('../conexion.php');

// Crear conexión usando tu función
$conexion = conectar();

// Obtener parámetros de filtro
$fecha_desde = $_GET['fecha_desde'] ?? '';
$fecha_hasta = $_GET['fecha_hasta'] ?? '';
$proveedor_id = $_GET['proveedor'] ?? '';

// Configurar headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_compras_Ingredientes' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Construir consulta con filtros (misma lógica que en el reporte principal)
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

// Generar Excel
echo "<html>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<style>
    table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
    th { background-color: #2E86C1; color: white; padding: 10px; text-align: left; font-weight: bold; }
    td { border: 1px solid #ddd; padding: 8px; }
    .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #1e40af; }
    .subtitle { font-size: 14px; color: #666; margin-bottom: 15px; }
    .compra-header { background-color: #f1f5f9; font-weight: bold; }
    .compra-total { background-color: #1e293b; color: white; font-weight: bold; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
</style>";
echo "</head>";
echo "<body>";

echo "<div class='title'>REPORTE DE COMPRAS DE INGREDIENTES - MAREA ROJA</div>";
echo "<div class='subtitle'>Generado: " . date('d/m/Y H:i:s') . "</div>";

// Mostrar filtros aplicados
if (!empty($fecha_desde) || !empty($fecha_hasta) || !empty($proveedor_id)) {
    echo "<div class='subtitle'>Filtros aplicados: ";
    $filtros = [];
    if (!empty($fecha_desde)) $filtros[] = "Desde: " . $fecha_desde;
    if (!empty($fecha_hasta)) $filtros[] = "Hasta: " . $fecha_hasta;
    if (!empty($proveedor_id)) {
        $prov_result = $conexion->query("SELECT nombre_proveedor FROM proveedores WHERE id_proveedor = $proveedor_id");
        $proveedor_nombre = $prov_result->fetch_assoc()['nombre_proveedor'] ?? '';
        $filtros[] = "Proveedor: " . $proveedor_nombre;
    }
    echo implode(" | ", $filtros);
    echo "</div>";
}

echo "<table border='1'>";
echo "<tr>";
echo "<th>ID Compra</th>";
echo "<th>Fecha</th>";
echo "<th>Proveedor</th>";
echo "<th>Ingrediente</th>";
echo "<th>Cantidad</th>";
echo "<th>Costo Unitario</th>";
echo "<th>Costo Total</th>";
echo "</tr>";

if ($result && $result->num_rows > 0) {
    $current_compra = null;
    $compra_total = 0;
    $total_general = 0;
    
    while ($row = $result->fetch_assoc()) {
        if ($current_compra != $row['id_compra_ingrediente']) {
            if ($current_compra !== null) {
                // Mostrar total de la compra anterior
                echo "<tr class='compra-total'>";
                echo "<td colspan='6' class='text-right'><strong>Total Compra:</strong></td>";
                echo "<td class='text-right'><strong>Q" . number_format($compra_total, 2) . "</strong></td>";
                echo "</tr>";
                $total_general += $compra_total;
            }
            
            $current_compra = $row['id_compra_ingrediente'];
            $compra_total = 0;
            
            // Encabezado de la compra
            echo "<tr class='compra-header'>";
            echo "<td><strong>#" . $row['id_compra_ingrediente'] . "</strong></td>";
            echo "<td><strong>" . date('d/m/Y', strtotime($row['fecha_de_compra'])) . "</strong></td>";
            echo "<td colspan='2'><strong>" . htmlspecialchars($row['nombre_proveedor']) . "</strong><br>";
            echo "<small>Tel: " . htmlspecialchars($row['telefono_proveedor']) . " | Email: " . htmlspecialchars($row['correo_proveedor']) . "</small></td>";
            echo "<td></td>";
            echo "<td></td>";
            echo "<td class='text-right'><strong>Q" . number_format($row['monto_total_compra'], 2) . "</strong></td>";
            echo "</tr>";
        }
        
        $compra_total += $row['costo_total'];
        
        // Detalle del item
        echo "<tr>";
        echo "<td></td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "<td>" . htmlspecialchars($row['nombre_ingrediente']) . "</td>";
        echo "<td class='text-right'>" . number_format($row['cantidad_compra'], 2) . " " . htmlspecialchars($row['abreviatura']) . "</td>";
        echo "<td class='text-right'>Q" . number_format($row['costo_unitario'], 4) . "</td>";
        echo "<td class='text-right'>Q" . number_format($row['costo_total'], 2) . "</td>";
        echo "</tr>";
    }
    
    // Último total de compra
    if ($current_compra !== null) {
        echo "<tr class='compra-total'>";
        echo "<td colspan='6' class='text-right'><strong>Total Compra:</strong></td>";
        echo "<td class='text-right'><strong>Q" . number_format($compra_total, 2) . "</strong></td>";
        echo "</tr>";
        $total_general += $compra_total;
    }
    
    // Total general
    echo "<tr style='background-color: #059669; color: white; font-weight: bold;'>";
    echo "<td colspan='6' class='text-right'><strong>TOTAL GENERAL:</strong></td>";
    echo "<td class='text-right'><strong>Q" . number_format($total_general, 2) . "</strong></td>";
    echo "</tr>";
    
} else {
    echo "<tr><td colspan='7' style='text-align: center;'>No se encontraron registros de compras</td></tr>";
}

echo "</table>";

// Resumen estadístico
echo "<br><br>";
echo "<div class='title'>RESUMEN ESTADÍSTICO</div>";
echo "<table border='1' style='width: 50%;'>";

// Consultas para resumen
$query_total = "SELECT COUNT(*) as total FROM compras_ingrediente ci";
$query_monto = "SELECT SUM(ci.monto_total_compra) as total FROM compras_ingrediente ci";

if (!empty($where_conditions)) {
    $query_total .= " WHERE " . implode(" AND ", $where_conditions);
    $query_monto .= " WHERE " . implode(" AND ", $where_conditions);
}

if (!empty($params)) {
    $stmt_total = $conexion->prepare($query_total);
    $stmt_total->bind_param($types, ...$params);
    $stmt_total->execute();
    $total_compras_result = $stmt_total->get_result();
    
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

echo "<tr><th>Concepto</th><th>Valor</th></tr>";
echo "<tr><td>Total de Compras</td><td>" . number_format($total_compras) . "</td></tr>";
echo "<tr><td>Monto Total</td><td>Q" . number_format($monto_total, 2) . "</td></tr>";
echo "<tr><td>Promedio por Compra</td><td>Q" . number_format($promedio, 2) . "</td></tr>";
echo "</table>";

echo "</body>";
echo "</html>";

// Cerrar conexión
desconectar($conexion);
exit;
?>