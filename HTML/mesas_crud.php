<?php
include("conexion.php");
$conexion = conectar();

$accion = $_POST['accion'] ?? '';

header('Content-Type: application/json; charset=utf-8');

switch ($accion) {

    // mostrar mesas
    case 'listar':
        $sql = "SELECT * FROM mesas";
        $resultado = $conexion->query($sql);
        $data = [];
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $data]);
        break;

    // insertar mesas
    case 'insertar':
        $descripcion = $_POST['descripcion'] ?? '';
        $capacidad = (int)($_POST['capacidad_personas'] ?? 0);
        $estado = $_POST['estado'] ?? 'DISPONIBLE';

        if (!$descripcion || !$capacidad) {
            echo json_encode(['status' => 'error', 'msg' => 'Campos incompletos']);
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO mesas (descripcion, capacidad_personas, estado) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $descripcion, $capacidad, $estado);
        $ok = $stmt->execute();

        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;

    // para modificar
    case 'modificar':
        $id = (int)($_POST['id_mesa'] ?? 0);
        $descripcion = $_POST['descripcion'] ?? '';
        $capacidad = (int)($_POST['capacidad_personas'] ?? 0);
        $estado = $_POST['estado'] ?? '';

        $stmt = $conexion->prepare("UPDATE mesas SET descripcion=?, capacidad_personas=?, estado=? WHERE id_mesa=?");
        $stmt->bind_param("sisi", $descripcion, $capacidad, $estado, $id);
        $ok = $stmt->execute();

        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;

    //metodo de eliminar
    case 'eliminar':
        $id = (int)($_POST['id_mesa'] ?? 0);
        $stmt = $conexion->prepare("DELETE FROM mesas WHERE id_mesa=?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();

        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;

    // buscar id siguiente
    case 'siguiente_id':
        $sql = "SELECT IFNULL(MAX(id_mesa), 0) + 1 AS siguiente FROM mesas";
        $resultado = $conexion->query($sql);
        $fila = $resultado->fetch_assoc();
        echo json_encode(['status' => 'ok', 'siguiente' => $fila['siguiente']]);
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => 'Acción no válida']);
        break;
}

desconectar($conexion);
?>

