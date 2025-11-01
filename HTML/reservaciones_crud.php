<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("conexion.php");
require_once("funciones_globales.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conexion = conectar();
header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['accion'] ?? '';

switch ($accion) {

    //mostrar reservaciones
    case 'listar':
        $sql = "SELECT 
                    r.id_reservacion,
                    r.id_cliente,
                    CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
                    r.id_mesa,
                    CONCAT('Mesa #', m.id_mesa, ' (', m.capacidad_personas, ' pers.)') AS mesa_desc,
                    r.cantidad_personas,
                    r.fecha_hora,
                    r.estado
                FROM reservaciones r
                INNER JOIN clientes c ON c.id_cliente = r.id_cliente
                INNER JOIN mesas m ON m.id_mesa = r.id_mesa";
        $resultado = $conexion->query($sql);
        $data = [];
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $data]);
        break;

    //insertar reservación
    case 'insertar':
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $id_mesa = (int)($_POST['id_mesa'] ?? 0);
        $cantidad_personas = (int)($_POST['cantidad_personas'] ?? 0);
        $fecha_hora = str_replace("T", " ", $_POST['fecha_hora'] ?? '');
        $estado = $_POST['estado'] ?? 'PROGRAMADA';

        if (!$id_cliente || !$id_mesa || !$cantidad_personas || !$fecha_hora) {
            echo json_encode(['status' => 'error', 'msg' => 'Campos incompletos']);
            exit;
        }

        // Verificar capacidad y disponibilidad
        $verificar = $conexion->query("SELECT capacidad_personas, estado FROM mesas WHERE id_mesa = $id_mesa");
        if (!$verificar || $verificar->num_rows === 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Mesa no encontrada']);
            exit;
        }
        $mesa = $verificar->fetch_assoc();
        if ($mesa['estado'] !== 'DISPONIBLE') {
            echo json_encode(['status' => 'error', 'msg' => 'La mesa no está disponible.']);
            exit;
        }
        if ($cantidad_personas > (int)$mesa['capacidad_personas']) {
            echo json_encode(['status' => 'error', 'msg' => 'La cantidad de personas supera la capacidad de la mesa.']);
            exit;
        }

        $stmt = $conexion->prepare(
            "INSERT INTO reservaciones (id_cliente, id_mesa, cantidad_personas, fecha_hora, estado)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iiiss", $id_cliente, $id_mesa, $cantidad_personas, $fecha_hora, $estado);
        $ok = $stmt->execute();
        if ($ok) {
            registrarBitacora($conexion, 'reservaciones', 'insertar', "Reservación creada (Cliente #$id_cliente, Mesa #$id_mesa, $cantidad_personas personas)");
    }


        if ($ok) {
        //cambiar el estado de la mesa a reservada solo si la reservación esta programada
         if ($estado === 'PROGRAMADA') {
        $conexion->query("UPDATE mesas SET estado = 'RESERVADA' WHERE id_mesa = $id_mesa");
    }
}


        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;

    //modificar reservación
    case 'modificar':
        $id = (int)($_POST['id_reservacion'] ?? 0);
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $id_mesa_nueva = (int)($_POST['id_mesa'] ?? 0);
        $cantidad_personas = (int)($_POST['cantidad_personas'] ?? 0);
        $fecha_hora = str_replace("T", " ", $_POST['fecha_hora'] ?? '');
        $estado = $_POST['estado'] ?? 'PROGRAMADA';

        if (!$id || !$id_cliente || !$id_mesa_nueva) {
            echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
            exit;
        }

        // Obtener datos anteriores para saber si cambia la mesa o estado
        $prevQ = $conexion->query("SELECT id_mesa, estado FROM reservaciones WHERE id_reservacion = $id");
        if (!$prevQ || $prevQ->num_rows === 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Reservación no encontrada']);
            exit;
        }
        $prev = $prevQ->fetch_assoc();
        $id_mesa_anterior = (int)$prev['id_mesa'];
        $estado_anterior = $prev['estado'];

        // Verificar capacidad de la MESA NUEVA
        $v2 = $conexion->query("SELECT capacidad_personas, estado FROM mesas WHERE id_mesa = $id_mesa_nueva");
        if (!$v2 || $v2->num_rows === 0) {
            echo json_encode(['status' => 'error', 'msg' => 'Mesa no encontrada']);
            exit;
        }
        $mesaNueva = $v2->fetch_assoc();
        if ($cantidad_personas > (int)$mesaNueva['capacidad_personas']) {
            echo json_encode(['status' => 'error', 'msg' => 'La cantidad de personas supera la capacidad de la nueva mesa.']);
            exit;
        }

        // Si cambia de mesa y la nueva no es la misma, exigir que la nueva esté DISPONIBLE
        if ($id_mesa_nueva !== $id_mesa_anterior && $mesaNueva['estado'] !== 'DISPONIBLE') {
            echo json_encode(['status' => 'error', 'msg' => 'La nueva mesa seleccionada no está disponible.']);
            exit;
        }

        $stmt = $conexion->prepare(
            "UPDATE reservaciones 
             SET id_cliente=?, id_mesa=?, cantidad_personas=?, fecha_hora=?, estado=?
             WHERE id_reservacion=?"
        );
        $stmt->bind_param("iiissi", $id_cliente, $id_mesa_nueva, $cantidad_personas, $fecha_hora, $estado, $id);
        $ok = $stmt->execute();

        if ($ok) {
        registrarBitacora($conexion, 'reservaciones', 'modificar', "Reservación #$id actualizada (Cliente #$id_cliente, Mesa #$id_mesa_nueva, Estado: $estado)");
        }

        if ($ok) {
            // Si cambió de mesa:
            if ($id_mesa_nueva !== $id_mesa_anterior) {
                // Si la reservación queda programada reserva una nueva mesa
                if ($estado === 'PROGRAMADA') {
                    $conexion->query("UPDATE mesas SET estado = 'RESERVADA' WHERE id_mesa = $id_mesa_nueva");
                } else { // si esta cancelada o cumplida libera la mesa
                    $existeNueva = $conexion->query("SELECT COUNT(*) AS total FROM reservaciones WHERE id_mesa = $id_mesa_nueva AND estado = 'PROGRAMADA'");
                    $totalNueva = (int)$existeNueva->fetch_assoc()['total'];
                    if ($totalNueva === 0) {
                        $conexion->query("UPDATE mesas SET estado = 'DISPONIBLE' WHERE id_mesa = $id_mesa_nueva");
                    }
                }

                //liberar la mesa anterior si ya no tiene algo programado
                $existeAnt = $conexion->query("SELECT COUNT(*) AS total 
                                            FROM reservaciones 
                                            WHERE id_mesa = $id_mesa_anterior AND estado = 'PROGRAMADA'");
                $totalAnt = (int)$existeAnt->fetch_assoc()['total'];
                if ($totalAnt === 0) {
                    $conexion->query("UPDATE mesas SET estado = 'DISPONIBLE' WHERE id_mesa = $id_mesa_anterior");
                }

            } else {
                // no hay cambio de mesa
                if ($estado === 'CANCELADA' || $estado === 'CUMPLIDA') {
                    // Si ya no quedan reservaciones para esa mesa se libera
                    $existe = $conexion->query("SELECT COUNT(*) AS total FROM reservaciones WHERE id_mesa = $id_mesa_nueva AND estado = 'PROGRAMADA'");
                    $total = (int)$existe->fetch_assoc()['total'];
                    if ($total === 0) {
                        $conexion->query("UPDATE mesas SET estado = 'DISPONIBLE' WHERE id_mesa = $id_mesa_nueva");
                    }
                } elseif ($estado === 'PROGRAMADA') {
                    // Si queda programada se asegura que este reservada
                    $conexion->query("UPDATE mesas SET estado = 'RESERVADA' WHERE id_mesa = $id_mesa_nueva");
                }
            }
        }

        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;

    // eliminar reservación
    case 'eliminar':
        $id = (int)($_POST['id_reservacion'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'msg' => 'ID inválido']);
            exit;
        }

        // Obtener mesa antes de eliminar
        $res = $conexion->query("SELECT id_mesa FROM reservaciones WHERE id_reservacion = $id");
        $fila = $res->fetch_assoc();
        $id_mesa = (int)($fila['id_mesa'] ?? 0);

        $stmt = $conexion->prepare("DELETE FROM reservaciones WHERE id_reservacion = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();

        if ($ok) {
            registrarBitacora($conexion, 'reservaciones', 'eliminar', "Reservación eliminada (ID #$id, Mesa #$id_mesa)");
        }


        if ($ok && $id_mesa) {
            // Si ya no hay reservaciones programadas para esa mesa, liberarla
            $existe = $conexion->query("SELECT COUNT(*) AS total FROM reservaciones WHERE id_mesa = $id_mesa AND estado = 'PROGRAMADA'");
            $total = (int)$existe->fetch_assoc()['total'];
            if ($total === 0) {
                $conexion->query("UPDATE mesas SET estado = 'DISPONIBLE' WHERE id_mesa = $id_mesa");
            }
        }

        echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
        $stmt->close();
        break;

    //listar clientes
    case 'clientes':
        $sql = "SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS nombre FROM clientes";
        $resultado = $conexion->query($sql);
        $clientes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $clientes]);
        break;

    //listar mesas 
    case 'mesas':
        $includeId = (int)($_POST['include_id'] ?? 0);
        if ($includeId > 0) {
            $sql = "SELECT id_mesa, capacidad_personas, estado,
                           CONCAT('Mesa #', id_mesa, ' (', capacidad_personas, ' pers.)') AS descripcion
                    FROM mesas
                    WHERE estado = 'DISPONIBLE' OR id_mesa = $includeId";
        } else {
            $sql = "SELECT id_mesa, capacidad_personas, estado,
                           CONCAT('Mesa #', id_mesa, ' (', capacidad_personas, ' pers.)') AS descripcion
                    FROM mesas
                    WHERE estado = 'DISPONIBLE'";
        }
        $resultado = $conexion->query($sql);
        $mesas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $mesas[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $mesas]);
        break;

    // siguiente ID
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
