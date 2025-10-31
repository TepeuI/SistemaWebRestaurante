<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Procesar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    
    switch($operacion) {
        case 'crear':
            crearIngrediente();
            break;
        case 'actualizar':
            actualizarIngrediente();
            break;
        case 'eliminar':
            eliminarIngrediente();
            break;
    }
}

function crearIngrediente() {
    global $conn;
    $conn = conectar();
    
    $nombre_ingrediente = trim($_POST['nombre_ingrediente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_unidad = intval($_POST['id_unidad'] ?? '');
    $cantidad_stock = floatval($_POST['cantidad_stock'] ?? 0);
    
    $sql = "INSERT INTO ingredientes (nombre_ingrediente, descripcion, id_unidad, cantidad_stock) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssid", $nombre_ingrediente, $descripcion, $id_unidad, $cantidad_stock);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ingrediente creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear ingrediente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Gestion_Inventario_Ingredientes.php');
    exit();
}

function actualizarIngrediente() {
    global $conn;
    $conn = conectar();
    
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $nombre_ingrediente = trim($_POST['nombre_ingrediente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_unidad = intval($_POST['id_unidad'] ?? '');
    $cantidad_stock = floatval($_POST['cantidad_stock'] ?? 0);
    
    $sql = "UPDATE ingredientes SET nombre_ingrediente = ?, descripcion = ?, id_unidad = ?, cantidad_stock = ? 
            WHERE id_ingrediente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssidi", $nombre_ingrediente, $descripcion, $id_unidad, $cantidad_stock, $id_ingrediente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ingrediente actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar ingrediente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Gestion_Inventario_Ingredientes.php');
    exit();
}

function eliminarIngrediente() {
    global $conn;
    $conn = conectar();
    
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    
    // Verificar si el ingrediente está en uso en recetas
    $sql_check = "SELECT id_registro_receta FROM receta WHERE id_ingrediente = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_ingrediente);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "No se puede eliminar el ingrediente porque está siendo usado en recetas";
        $_SESSION['tipo_mensaje'] = "error";
        $stmt_check->close();
        desconectar($conn);
        header('Location: Gestion_Inventario_Ingredientes.php');
        exit();
    }
    $stmt_check->close();
    
    $sql = "DELETE FROM ingredientes WHERE id_ingrediente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_ingrediente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ingrediente eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar ingrediente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Gestion_Inventario_Ingredientes.php');
    exit();
}

// Obtener todos los ingredientes para mostrar en la tabla
function obtenerIngredientes() {
    $conn = conectar();
    $sql = "SELECT i.*, um.unidad, um.abreviatura 
            FROM ingredientes i 
            LEFT JOIN unidades_medida um ON i.id_unidad = um.id_unidad 
            ORDER BY i.nombre_ingrediente";
    $resultado = $conn->query($sql);
    $ingredientes = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $ingredientes[] = $fila;
        }
    }
    
    desconectar($conn);
    return $ingredientes;
}

// Obtener unidades de medida para el dropdown
function obtenerUnidadesMedida() {
    $conn = conectar();
    $sql = "SELECT * FROM unidades_medida ORDER BY unidad";
    $resultado = $conn->query($sql);
    $unidades = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $unidades[] = $fila;
        }
    }
    
    desconectar($conn);
    return $unidades;
}

$ingredientes = obtenerIngredientes();
$unidades = obtenerUnidadesMedida();

// Incluir la vista HTML
include 'Gestion_Inventario_Ingredientes_view.php';
?>