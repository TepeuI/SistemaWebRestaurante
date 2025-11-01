<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("conexion.php");
require_once("funciones_globales.php");

if (session_status() === PHP_SESSION_NONE) session_start();
$conexion = conectar();
header('Content-Type: application/json; charset=utf-8');

// 🔒 Bloquear acceso sin login
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Acceso denegado. Inicie sesión.']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {

        // 🔹 Mostrar clientes
        case 'listar':
            $sql = "SELECT * FROM clientes ORDER BY id_cliente ASC";
            $resultado = $conexion->query($sql);

            if (!$resultado) {
                echo json_encode(['status' => 'error', 'msg' => 'Error en consulta SQL: ' . $conexion->error]);
                exit;
            }

            $data = [];
            while ($fila = $resultado->fetch_assoc()) $data[] = $fila;
            echo json_encode(['status' => 'ok', 'data' => $data]);
            break;

        // 🔹 Insertar cliente
        case 'insertar':
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $nit = trim($_POST['nit'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $correo = trim($_POST['correo'] ?? '');

            // validaciones
            if (!$nombre || !$nit) {
                echo json_encode(['status' => 'error', 'msg' => 'El nombre y NIT son obligatorios.']);
                exit;
            }

            if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'msg' => 'El correo no es válido.']);
                exit;
            }

            // Evitar NIT duplicado
            $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM clientes WHERE nit = ?");
            $stmt_check->bind_param("s", $nit);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($count > 0) {
                echo json_encode(['status' => 'error', 'msg' => 'El NIT ya está registrado.']);
                exit;
            }

            // Insertar cliente
            $stmt = $conexion->prepare("INSERT INTO clientes (nombre, apellido, nit, telefono, correo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nombre, $apellido, $nit, $telefono, $correo);
            $ok = $stmt->execute();

            if ($ok) registrarBitacora($conexion, "clientes", "insertar", "Cliente: $nombre $apellido (NIT $nit)");

            echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $ok ? '' : $stmt->error]);
            $stmt->close();
            break;

        // 🔹 Modificar cliente
        case 'modificar':
            $id = (int)($_POST['id_cliente'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $nit = trim($_POST['nit'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $correo = trim($_POST['correo'] ?? '');

            if (!$id || !$nombre || !$nit) {
                echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos.']);
                exit;
            }

            if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'msg' => 'El correo no es válido.']);
                exit;
            }

            // Verificar duplicado de NIT
            $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM clientes WHERE nit = ? AND id_cliente <> ?");
            $stmt_check->bind_param("si", $nit, $id);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($count > 0) {
                echo json_encode(['status' => 'error', 'msg' => 'El NIT ya pertenece a otro cliente.']);
                exit;
            }

            $stmt = $conexion->prepare("UPDATE clientes SET nombre=?, apellido=?, nit=?, telefono=?, correo=? WHERE id_cliente=?");
            $stmt->bind_param("sssssi", $nombre, $apellido, $nit, $telefono, $correo, $id);
            $ok = $stmt->execute();

            if ($ok) registrarBitacora($conexion, "clientes", "modificar", "Cliente #$id actualizado ($nombre $apellido, NIT $nit)");

            echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $ok ? '' : $stmt->error]);
            $stmt->close();
            break;

        // 🔹 Eliminar cliente
        case 'eliminar':
            $id = (int)($_POST['id_cliente'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'msg' => 'ID inválido.']);
                exit;
            }

            $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_cliente=?");
            $stmt->bind_param("i", $id);
            $ok = $stmt->execute();

            if ($ok) registrarBitacora($conexion, "clientes", "eliminar", "Cliente ID #$id");

            echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $ok ? '' : $stmt->error]);
            $stmt->close();
            break;

        // 🔹 Obtener siguiente ID
        case 'siguiente_id':
            $sql = "SELECT IFNULL(MAX(id_cliente), 0) + 1 AS siguiente FROM clientes";
            $resultado = $conexion->query($sql);
            $fila = $resultado->fetch_assoc();
            echo json_encode(['status' => 'ok', 'siguiente' => $fila['siguiente']]);
            break;

        default:
            echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Excepción: ' . $e->getMessage()]);
}

desconectar($conexion);
?>