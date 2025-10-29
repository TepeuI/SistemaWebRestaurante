<?php
include("conexion.php");
include("funciones_globales.php");
session_start();
$conexion = conectar();

$accion = $_POST['accion'] ?? '';

header('Content-Type: application/json; charset=utf-8');

switch ($accion) {

    //mostrar clientes
    case 'listar':
        $sql = "SELECT * FROM clientes ORDER BY id_cliente ASC";
        $resultado = $conexion->query($sql);
        $data = [];
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $data]);
        break;

    //insertar clientes
    case 'insertar':
        $nombre = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $nit = trim($_POST['nit'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $correo = trim($_POST['correo'] ?? '');

        // validaciones básicas
        if (!$nombre || !$nit) {
            echo json_encode(['status' => 'error', 'msg' => 'El nombre y NIT son obligatorios.']);
            exit;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) && $correo != '') {
            echo json_encode(['status' => 'error', 'msg' => 'El correo no es válido.']);
            exit;
        }

        //evitar nit duplicados
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

        // insertar seguro
        $stmt = $conexion->prepare("INSERT INTO clientes (nombre, apellido, nit, telefono, correo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $apellido, $nit, $telefono, $correo);
        $ok = $stmt->execute();
        if ($ok) {
            registrarBitacora($conexion, "clientes", "insertar", "Cliente: $nombre $apellido (NIT $nit)");
        }
        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;


    //modificar clientes
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

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) && $correo != '') {
            echo json_encode(['status' => 'error', 'msg' => 'El correo no es válido.']);
            exit;
        }

        //verificar nit duplicado
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
        if ($ok) {
            registrarBitacora($conexion, "clientes", "modificar", "Cliente ID #$id ($nombre $apellido, NIT $nit)");
        }
        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;


    // eliminar cliente
    case 'eliminar':
        $id = (int)($_POST['id_cliente'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'msg' => 'ID inválido.']);
            exit;
        }

        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_cliente=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        if ($ok) {
            registrarBitacora($conexion, "clientes", "eliminar", "Cliente ID #$id");
        }
        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;


    //obtener siguiente ID
    case 'siguiente_id':
        $sql = "SELECT IFNULL(MAX(id_cliente), 0) + 1 AS siguiente FROM clientes";
        $resultado = $conexion->query($sql);
        $fila = $resultado->fetch_assoc();
        echo json_encode(['status' => 'ok', 'siguiente' => $fila['siguiente']]);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida.']);
        break;
}

desconectar($conexion);
?>
