<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

include('../../conexion.php');

// Crear conexión
$conexion = conectar();

// Obtener parámetros de filtro
$tipo_consulta = $_GET['tipo_consulta'] ?? 'platos';
$precio_min = $_GET['precio_min'] ?? '';
$precio_max = $_GET['precio_max'] ?? '';
$busqueda_nombre = $_GET['busqueda_nombre'] ?? '';

// Configurar headers para Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_' . $tipo_consulta . '_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Construir consulta según el tipo
switch($tipo_consulta) {
    case 'platos':
        $query = "
            SELECT 
                id_plato as id,
                nombre_plato as nombre,
                descripcion,
                precio_unitario,
                CASE 
                    WHEN precio_unitario < 10 THEN 'ECONÓMICO'
                    WHEN precio_unitario < 25 THEN 'MEDIO'
                    ELSE 'PREMIUM'
                END as categoria_precio
            FROM platos WHERE 1=1
        ";
        $titulo = "REPORTE DE PLATOS - MAREA ROJA";
        break;
        
    case 'bebidas':
        $query = "
            SELECT 
                id_bebida as id,
                descripcion as nombre,
                precio_unitario,
                CASE 
                    WHEN precio_unitario < 5 THEN 'ECONÓMICO'
                    WHEN precio_unitario < 15 THEN 'MEDIO'
                    ELSE 'PREMIUM'
                END as categoria_precio
            FROM bebidas WHERE 1=1
        ";
        $titulo = "REPORTE DE BEBIDAS - MAREA ROJA";
        break;
        
    case 'ingredientes':
        $query = "
            SELECT 
                i.id_ingrediente as id,
                i.nombre_ingrediente as nombre,
                i.descripcion,
                i.cantidad_stock as stock,
                um.unidad,
                CASE 
                    WHEN i.cantidad_stock < 10 THEN 'BAJO'
                    WHEN i.cantidad_stock < 50 THEN 'MEDIO'
                    ELSE 'ALTO'
                END as nivel_stock
            FROM ingredientes i
            LEFT JOIN unidades_medida um ON i.id_unidad = um.id_unidad
            WHERE 1=1
        ";
        $titulo = "REPORTE DE INGREDIENTES - MAREA ROJA";
        break;
        
    case 'recetas':
        $query = "
            SELECT 
                r.id_registro_receta as id,
                p.nombre_plato as plato,
                i.nombre_ingrediente as ingrediente,
                r.cantidad,
                um.unidad,
                um.abreviatura
            FROM receta r
            INNER JOIN platos p ON r.id_plato = p.id_plato
            INNER JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente
            LEFT JOIN unidades_medida um ON r.id_unidad = um.id_unidad
            WHERE 1=1
        ";
        $titulo = "REPORTE DE RECETAS - MAREA ROJA";
        break;
}

// Aplicar filtros
$conditions = [];
$params = [];
$types = '';

if (!empty($precio_min) && in_array($tipo_consulta, ['platos', 'bebidas', 'ingredientes'])) {
    $field = ($tipo_consulta == 'ingredientes') ? 'i.cantidad_stock' : 'precio_unitario';
    $conditions[] = "$field >= ?";
    $params[] = $precio_min;
    $types .= 'd';
}

if (!empty($precio_max) && in_array($tipo_consulta, ['platos', 'bebidas', 'ingredientes'])) {
    $field = ($tipo_consulta == 'ingredientes') ? 'i.cantidad_stock' : 'precio_unitario';
    $conditions[] = "$field <= ?";
    $params[] = $precio_max;
    $types .= 'd';
}

if (!empty($busqueda_nombre)) {
    switch($tipo_consulta) {
        case 'platos':
            $conditions[] = "nombre_plato LIKE ?";
            break;
        case 'bebidas':
            $conditions[] = "descripcion LIKE ?";
            break;
        case 'ingredientes':
            $conditions[] = "i.nombre_ingrediente LIKE ?";
            break;
        case 'recetas':
            $conditions[] = "p.nombre_plato LIKE ?";
            break;
    }
    $params[] = "%" . $busqueda_nombre . "%";
    $types .= 's';
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Ordenar
switch($tipo_consulta) {
    case 'platos':
        $query .= " ORDER BY nombre_plato ASC";
        break;
    case 'bebidas':
        $query .= " ORDER BY descripcion ASC";
        break;
    case 'ingredientes':
        $query .= " ORDER BY i.nombre_ingrediente ASC";
        break;
    case 'recetas':
        $query .= " ORDER BY p.nombre_plato ASC, i.nombre_ingrediente ASC";
        break;
}

// Ejecutar consulta
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
    table { border-collapse: collapse; width: 100%; }
    th { background-color: #2E86C1; color: white; padding: 8px; text-align: left; }
    td { border: 1px solid #ddd; padding: 6px; }
    .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
    .subtitle { font-size: 12px; color: #666; margin-bottom: 15px; }
</style>";
echo "</head>";
echo "<body>";

echo "<div class='title'>" . $titulo . "</div>";
echo "<div class='subtitle'>Generado: " . date('d/m/Y H:i:s') . "</div>";

echo "<table border='1'>";

// Encabezados según el tipo de consulta
echo "<tr>";
switch($tipo_consulta) {
    case 'platos':
        echo "<th>ID</th><th>Nombre del Plato</th><th>Descripción</th><th>Precio (Q)</th><th>Categoría Precio</th>";
        break;
    case 'bebidas':
        echo "<th>ID</th><th>Nombre de la Bebida</th><th>Precio (Q)</th><th>Categoría Precio</th>";
        break;
    case 'ingredientes':
        echo "<th>ID</th><th>Nombre del Ingrediente</th><th>Descripción</th><th>Stock</th><th>Unidad</th><th>Nivel Stock</th>";
        break;
    case 'recetas':
        echo "<th>ID</th><th>Plato</th><th>Ingrediente</th><th>Cantidad</th><th>Unidad</th>";
        break;
}
echo "</tr>";

// Datos
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        
        switch($tipo_consulta) {
            case 'platos':
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
                echo "<td>Q" . number_format($row['precio_unitario'], 2) . "</td>";
                echo "<td>" . $row['categoria_precio'] . "</td>";
                break;
                
            case 'bebidas':
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                echo "<td>Q" . number_format($row['precio_unitario'], 2) . "</td>";
                echo "<td>" . $row['categoria_precio'] . "</td>";
                break;
                
            case 'ingredientes':
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                echo "<td>" . htmlspecialchars($row['descripcion']) . "</td>";
                echo "<td>" . number_format($row['stock'], 2) . "</td>";
                echo "<td>" . htmlspecialchars($row['unidad'] ?? '') . "</td>";
                echo "<td>" . $row['nivel_stock'] . "</td>";
                break;
                
            case 'recetas':
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['plato']) . "</td>";
                echo "<td>" . htmlspecialchars($row['ingrediente']) . "</td>";
                echo "<td>" . number_format($row['cantidad'], 2) . "</td>";
                echo "<td>" . htmlspecialchars($row['unidad'] ?? $row['abreviatura'] ?? '') . "</td>";
                break;
        }
        
        echo "</tr>";
    }
    
    // Total de registros
    echo "<tr style='background-color: #f2f2f2;'>";
    echo "<td colspan='";
    switch($tipo_consulta) {
        case 'platos': echo "5"; break;
        case 'bebidas': echo "4"; break;
        case 'ingredientes': echo "6"; break;
        case 'recetas': echo "5"; break;
    }
    echo "' style='text-align: center; font-weight: bold;'>";
    echo "Total de registros: " . $result->num_rows;
    echo "</td></tr>";
    
} else {
    echo "<tr><td colspan='";
    switch($tipo_consulta) {
        case 'platos': echo "5"; break;
        case 'bebidas': echo "4"; break;
        case 'ingredientes': echo "6"; break;
        case 'recetas': echo "5"; break;
    }
    echo "' style='text-align: center;'>No se encontraron registros</td></tr>";
}

echo "</table>";
echo "</body>";
echo "</html>";

// Cerrar conexión
desconectar($conexion);
exit;
?>