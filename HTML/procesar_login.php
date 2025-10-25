=<?php
session_start();
require_once 'conexion.php';

function validarLogin($usuario, $contrasenia) {
    $conn = conectar();
    // Buscar el usuario por nombre de usuario
    $sql = "SELECT id_usuario, id_empleado, usuario, contrasenia_hash, activo FROM usuarios WHERE usuario = ? LIMIT 1";

    if (!($stmt = $conn->prepare($sql))) {
        // Preparación fallida
        desconectar($conn);
        return false;
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows !== 1) {
        // Usuario no encontrado
        $stmt->close();
        desconectar($conn);
        return false;
    }

    $usuario_data = $resultado->fetch_assoc();

    // El campo 'activo' debe ser 1
    if (empty($usuario_data['activo']) || $usuario_data['activo'] != 1) {
        $stmt->close();
        desconectar($conn);
        return false;
    }

    $hash = $usuario_data['contrasenia_hash'];
    // Verificar la contraseña usando password_verify (asume contrasenias hasheadas con password_hash)
    $password_ok = false;
    if (!empty($hash) && password_verify($contrasenia, $hash)) {
        $password_ok = true;
    } else {
        // Fallback para formatos legados: plaintext, md5, sha1, sha256
        // Comprobar igualdad directa (si la DB tenía contraseñas en texto plano)
        if (!empty($hash) && hash_equals($hash, $contrasenia)) {
            $password_ok = true;
        }
        // md5
        if (!$password_ok && !empty($hash) && hash_equals($hash, md5($contrasenia))) {
            $password_ok = true;
        }
        // sha1
        if (!$password_ok && !empty($hash) && hash_equals($hash, sha1($contrasenia))) {
            $password_ok = true;
        }
        // sha256
        if (!$password_ok && !empty($hash) && hash_equals($hash, hash('sha256', $contrasenia))) {
            $password_ok = true;
        }

        // Si entró por un método legado, migramos el hash a password_hash() para seguridad
        if ($password_ok) {
            $newHash = password_hash($contrasenia, PASSWORD_DEFAULT);
            $updSql = "UPDATE usuarios SET contrasenia_hash = ? WHERE id_usuario = ?";
            if ($updStmt = $conn->prepare($updSql)) {
                $updStmt->bind_param("si", $newHash, $usuario_data['id_usuario']);
                $updStmt->execute();
                $updStmt->close();
            }
        }
    }

    if (!$password_ok) {
        $stmt->close();
        desconectar($conn);
        return false;
    }

    // Si existe un empleado relacionado, verificar que esté ACTIVO
    $id_empleado = $usuario_data['id_empleado'];
    $nombre_completo = '';
    if (!empty($id_empleado)) {
        $sql2 = "SELECT nombre, apellido, estado FROM empleados WHERE id_empleado = ? LIMIT 1";
        if ($stmt2 = $conn->prepare($sql2)) {
            $stmt2->bind_param("i", $id_empleado);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            if ($res2 && $res2->num_rows === 1) {
                $emp = $res2->fetch_assoc();
                if ($emp['estado'] !== 'ACTIVO') {
                    // Empleado inactivo
                    $stmt2->close();
                    $stmt->close();
                    desconectar($conn);
                    return false;
                }
                $nombre_completo = trim($emp['nombre'] . ' ' . $emp['apellido']);
            }
            $stmt2->close();
        }
    }

    // Guardar datos en sesión de forma segura
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $usuario_data['id_usuario'];
    $_SESSION['id_empleado'] = $id_empleado;
    $_SESSION['usuario'] = $usuario_data['usuario'];
    if (!empty($nombre_completo)) {
        $_SESSION['nombre'] = $nombre_completo;
    }

    $stmt->close();
    desconectar($conn);
    return true;
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