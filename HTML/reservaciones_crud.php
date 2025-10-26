<?php
include("conexion.php");
$conexion = conectar();

$accion = $_POST['accion'] ?? '';

header('Content-Type: application/json; charset=utf-8');

switch ($accion) {
    // mostrar todas las reservaciones
    case 'listar':
        $sql = "SELECT 
                    r.id_reservacion, 
                    r.nit_cliente AS id_cliente, 
                    CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
                    r.id_mesa, 
                    CONCAT('Mesa #', m.id_mesa, ' (', m.capacidad_personas, ' pers.)') AS mesa_desc, 
                    r.cantidad_personas, 
                    r.fecha_hora, 
                    r.estado
                FROM reservaciones r
                INNER JOIN clientes c ON c.nit = r.nit_cliente
                INNER JOIN mesas m ON m.id_mesa = r.id_mesa";
        $resultado = $conexion->query($sql);
        $data = [];
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $data]);
        break;

    // insertar nueva reservación
    case 'insertar':
        $id_cliente = $_POST['id_cliente'] ?? '';
        $id_mesa = $_POST['id_mesa'] ?? '';
        $cantidad_personas = $_POST['cantidad_personas'] ?? '';
        $fecha_hora = $_POST['fecha_hora'] ?? '';
        $fecha_hora = str_replace("T", " ", $fecha_hora);
        $estado = $_POST['estado'] ?? '';

        if (!$id_cliente || !$id_mesa || !$cantidad_personas || !$fecha_hora || !$estado) {
            echo json_encode(['status' => 'error', 'msg' => 'Campos incompletos']);
            exit;
        }

        $stmt = $conexion->prepare("INSERT INTO reservaciones (nit_cliente, id_mesa, cantidad_personas, fecha_hora, estado)
                                    VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiss", $id_cliente, $id_mesa, $cantidad_personas, $fecha_hora, $estado);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'msg' => $stmt->error]);
        }
        $stmt->close();
        break;

    // modificar reservación
    case 'modificar':
        $id = $_POST['id_reservacion'] ?? '';
        $id_cliente = $_POST['id_cliente'] ?? '';
        $id_mesa = $_POST['id_mesa'] ?? '';
        $cantidad_personas = $_POST['cantidad_personas'] ?? '';
        $fecha_hora = $_POST['fecha_hora'] ?? '';
        $fecha_hora = str_replace("T", " ", $fecha_hora);
        $estado = $_POST['estado'] ?? '';

        if (!$id || !$id_cliente || !$id_mesa) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
            exit;
        }

        $stmt = $conexion->prepare("UPDATE reservaciones 
                                    SET nit_cliente=?, id_mesa=?, cantidad_personas=?, fecha_hora=?, estado=?
                                    WHERE id_reservacion=?");
        $stmt->bind_param("siissi", $id_cliente, $id_mesa, $cantidad_personas, $fecha_hora, $estado, $id);

        echo json_encode(['status' => $stmt->execute() ? 'ok' : 'error']);
        $stmt->close();
        break;

    //eliminar reservación
    case 'eliminar':
        $id = $_POST['id_reservacion'] ?? '';
        if (!is_numeric($id)) {
            echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
            exit;
        }

        $stmt = $conexion->prepare("DELETE FROM reservaciones WHERE id_reservacion = ?");
        $stmt->bind_param("i", $id);

        echo json_encode(['status' => $stmt->execute() ? 'ok' : 'error']);
        $stmt->close();
        break;

    // Listar clientes, usa nit como id_cliente
    case 'clientes':
        $sql = "SELECT nit AS id_cliente, CONCAT(nombre, ' ', apellido) AS nombre FROM clientes";
        $resultado = $conexion->query($sql);
        $clientes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $clientes]);
        break;

    // Listar mesas (todas o las disponibles)
    case 'mesas':
        $sql = "SELECT id_mesa, CONCAT('Mesa #', id_mesa, ' (', capacidad_personas, ' pers.)') AS descripcion FROM mesas";
        $resultado = $conexion->query($sql);
        $mesas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $mesas[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $mesas]);
        break;

    // obtener el siguiente id de reservaciones
    case 'siguiente_id':
        $sql = "SELECT IFNULL(MAX(id_reservacion), 0) + 1 AS siguiente FROM reservaciones";
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
