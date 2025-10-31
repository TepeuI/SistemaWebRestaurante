<?php
include("conexion.php");
session_start();
$conexion = conectar();

$accion = $_POST['accion'] ?? '';

header('Content-Type: application/json; charset=utf-8');

switch ($accion) {

    //registrar accion desde cualquier crud
    case 'insertar':
        registrarBitacora($_POST['operacion_realizada'] ?? '');
        break;

    //listar registros de bitacora
    case 'listar':
        $usuario = $_POST['usuario'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? '';

        // Construimos filtro dinámico
        $filtros = [];
        if (!empty($usuario)) {
            $filtros[] = "u.nombre LIKE '%" . $conexion->real_escape_string($usuario) . "%'";
        }
        if (!empty($fecha_inicio) && !empty($fecha_fin)) {
            $filtros[] = "DATE(b.fecha_hora_accion) BETWEEN '" . $conexion->real_escape_string($fecha_inicio) . "' 
                        AND '" . $conexion->real_escape_string($fecha_fin) . "'";
        } elseif (!empty($fecha_inicio)) {
            $filtros[] = "DATE(b.fecha_hora_accion) >= '" . $conexion->real_escape_string($fecha_inicio) . "'";
        } elseif (!empty($fecha_fin)) {
            $filtros[] = "DATE(b.fecha_hora_accion) <= '" . $conexion->real_escape_string($fecha_fin) . "'";
        }

        $where = count($filtros) ? "WHERE " . implode(" AND ", $filtros) : "";

        $sql = "SELECT b.id_bitacora, u.nombre AS usuario, b.ip, b.pc, 
                    b.operacion_realizada, b.fecha_hora_accion
                FROM bitacora b
                LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
                $where
                ORDER BY b.id_bitacora DESC";

        $resultado = $conexion->query($sql);
        $data = [];
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
        echo json_encode(['status' => 'ok', 'data' => $data]);
    break;
}

//funcion global registrar bitacora
function registrarBitacora($operacion)
{
    global $conexion;

    $id_usuario = $_SESSION['id_usuario'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
    $pc = gethostname();
    $operacion = trim($operacion);

    if (!$id_usuario || !$operacion) {
        echo json_encode(['status' => 'error', 'msg' => 'Faltan datos para registrar la bitácora']);
        return;
    }

    $stmt = $conexion->prepare(
        "INSERT INTO bitacora (id_usuario, ip, pc, operacion_realizada) 
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $id_usuario, $ip, $pc, $operacion);
    $ok = $stmt->execute();

    echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $stmt->error]);
    $stmt->close();
}

desconectar($conexion);
?>
