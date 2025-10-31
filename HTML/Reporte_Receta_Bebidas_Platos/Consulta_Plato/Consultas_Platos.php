<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

include('../../conexion.php');

// Crear conexión
$conexion = conectar();

// Inicializar variables de filtro
$tipo_consulta = $_GET['tipo_consulta'] ?? 'platos';
$precio_min = $_GET['precio_min'] ?? '';
$precio_max = $_GET['precio_max'] ?? '';
$busqueda_nombre = $_GET['busqueda_nombre'] ?? '';
$categoria_id = $_GET['categoria'] ?? '';

// Construir consultas según el tipo (BASE SIN WHERE)
switch($tipo_consulta) {
    case 'platos':
        $query_base = "
            SELECT 
                id_plato as id,
                nombre_plato as nombre,
                descripcion,
                precio_unitario,
                'plato' as tipo,
                CASE 
                    WHEN precio_unitario < 10 THEN 'ECONÓMICO'
                    WHEN precio_unitario < 25 THEN 'MEDIO'
                    ELSE 'PREMIUM'
                END as categoria_precio
            FROM platos
        ";
        $campo_nombre = "nombre_plato";
        $orden = "ORDER BY nombre_plato ASC";
        break;
        
    case 'bebidas':
        $query_base = "
            SELECT 
                id_bebida as id,
                descripcion as nombre,
                '' as descripcion,
                precio_unitario,
                'bebida' as tipo,
                CASE 
                    WHEN precio_unitario < 5 THEN 'ECONÓMICO'
                    WHEN precio_unitario < 15 THEN 'MEDIO'
                    ELSE 'PREMIUM'
                END as categoria_precio
            FROM bebidas
        ";
        $campo_nombre = "descripcion";
        $orden = "ORDER BY descripcion ASC";
        break;
        
    case 'ingredientes':
        $query_base = "
            SELECT 
                i.id_ingrediente as id,
                i.nombre_ingrediente as nombre,
                i.descripcion,
                i.cantidad_stock as precio_unitario,
                'ingrediente' as tipo,
                CASE 
                    WHEN i.cantidad_stock < 10 THEN 'BAJO'
                    WHEN i.cantidad_stock < 50 THEN 'MEDIO'
                    ELSE 'ALTO'
                END as categoria_precio,
                um.unidad,
                um.abreviatura
            FROM ingredientes i
            LEFT JOIN unidades_medida um ON i.id_unidad = um.id_unidad
        ";
        $campo_nombre = "i.nombre_ingrediente";
        $orden = "ORDER BY i.nombre_ingrediente ASC";
        break;
        
    case 'recetas':
        $query_base = "
            SELECT 
                r.id_registro_receta as id,
                p.nombre_plato as nombre,
                i.nombre_ingrediente as descripcion,
                r.cantidad as precio_unitario,
                'receta' as tipo,
                um.unidad,
                um.abreviatura
            FROM receta r
            INNER JOIN platos p ON r.id_plato = p.id_plato
            INNER JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente
            LEFT JOIN unidades_medida um ON r.id_unidad = um.id_unidad
        ";
        $campo_nombre = "p.nombre_plato";
        $orden = "ORDER BY p.nombre_plato ASC, i.nombre_ingrediente ASC";
        break;
        
    default:
        $tipo_consulta = 'platos';
        $query_base = "
            SELECT 
                id_plato as id,
                nombre_plato as nombre,
                descripcion,
                precio_unitario,
                'plato' as tipo,
                CASE 
                    WHEN precio_unitario < 10 THEN 'ECONÓMICO'
                    WHEN precio_unitario < 25 THEN 'MEDIO'
                    ELSE 'PREMIUM'
                END as categoria_precio
            FROM platos
        ";
        $campo_nombre = "nombre_plato";
        $orden = "ORDER BY nombre_plato ASC";
        break;
}

// Construir condiciones WHERE
$where_conditions = [];
$params = [];
$types = '';

// Filtros comunes - CORREGIDOS
if (!empty($precio_min)) {
    $where_conditions[] = "precio_unitario >= ?";
    $params[] = $precio_min;
    $types .= 'd';
}

if (!empty($precio_max)) {
    $where_conditions[] = "precio_unitario <= ?";
    $params[] = $precio_max;
    $types .= 'd';
}

if (!empty($busqueda_nombre)) {
    $where_conditions[] = "$campo_nombre LIKE ?";
    $params[] = "%" . $busqueda_nombre . "%";
    $types .= 's';
}

// Consulta principal CON filtros
$query_principal = $query_base;
if (!empty($where_conditions)) {
    $query_principal .= " WHERE " . implode(" AND ", $where_conditions);
}
$query_principal .= " " . $orden;

// DEBUG: Ver la consulta (eliminar en producción)
// echo "<!-- Consulta: " . htmlspecialchars($query_principal) . " -->";
// echo "<!-- Parámetros: " . print_r($params, true) . " -->";

// EJECUTAR CONSULTA PRINCIPAL
if (!empty($params)) {
    $stmt = $conexion->prepare($query_principal);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $resultados = [];
        if ($result) {
            while ($fila = $result->fetch_assoc()) {
                $resultados[] = $fila;
            }
        }
    } else {
        // Error en la preparación
        die("Error en la consulta: " . $conexion->error);
    }
} else {
    $result = $conexion->query($query_principal);
    $resultados = [];
    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $resultados[] = $fila;
        }
    }
}

// Calcular estadísticas desde PHP (más simple)
$total_registros = count($resultados);

if (in_array($tipo_consulta, ['platos', 'bebidas', 'ingredientes']) && !empty($resultados)) {
    $precios = array_column($resultados, 'precio_unitario');
    $promedio = $total_registros > 0 ? array_sum($precios) / $total_registros : 0;
    $minimo = $total_registros > 0 ? min($precios) : 0;
    $maximo = $total_registros > 0 ? max($precios) : 0;
    $suma = array_sum($precios);
} else {
    $promedio = $minimo = $maximo = $suma = 0;
}

// Preparar datos para la vista
$datos_vista = [
    'tipo_consulta' => $tipo_consulta,
    'resultados' => $resultados,
    'total_registros' => $total_registros,
    'promedio' => $promedio,
    'minimo' => $minimo,
    'maximo' => $maximo,
    'suma' => $suma,
    'filtros_aplicados' => [
        'precio_min' => $precio_min,
        'precio_max' => $precio_max,
        'busqueda_nombre' => $busqueda_nombre
    ]
];

// Incluir la vista
include 'Consultas_Platos_view.php';

// Cerrar conexión
if (isset($conexion)) {
    desconectar($conexion);
}
?>