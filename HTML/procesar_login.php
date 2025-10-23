<?php
session_start();
require_once 'conexion.php';

function validarLogin($usuario, $contrasenia) {
    $conn = conectar();
    
    // Consulta preparada para mayor seguridad (sin hash)
    $sql = "SELECT u.id_usuario, u.id_empleado, u.usuario, u.contrasenia_hash, u.activo,
                   e.nombre, e.apellido, e.estado as estado_empleado
            FROM usuarios u 
            INNER JOIN empleados e ON u.id_empleado = e.id_empleado 
            WHERE u.usuario = ? AND u.contrasenia_hash = ? AND u.activo = 1 AND e.estado = 'ACTIVO'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $usuario, $contrasenia);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $usuario_data = $resultado->fetch_assoc();
        
        // Guardar datos en sesión
        $_SESSION['id_usuario'] = $usuario_data['id_usuario'];
        $_SESSION['id_empleado'] = $usuario_data['id_empleado'];
        $_SESSION['usuario'] = $usuario_data['usuario'];
        $_SESSION['nombre'] = $usuario_data['nombre'] . ' ' . $usuario_data['apellido'];
        
        $stmt->close();
        desconectar($conn);
        return true;
    }
    
    $stmt->close();
    desconectar($conn);
    return false;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $contrasenia = $_POST['clave'];
    
    if (validarLogin($usuario, $contrasenia)) {
        // Login exitoso - redirigir al menú principal (PHP)
        header('Location: menu_empleados.php');  // ← CORREGIDO
        exit();
    } else {
        // Login fallido - redirigir de vuelta al login con error (PHP)
        $error = "Usuario o contraseña incorrectos";
        header('Location: login.php?error=' . urlencode($error));  // ← CORREGIDO
        exit();
    }
} else {
    // Si alguien intenta acceder directamente sin POST
    header('Location: login.php');  // ← CORREGIDO
    exit();
}
?>