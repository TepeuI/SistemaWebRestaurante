<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("conexion.php");
require_once("funciones_globales.php");

if (session_status() === PHP_SESSION_NONE) session_start();
$conexion = conectar();
header('Content-Type: application/json; charset=utf-8');

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {

        // 🔹 Mostrar mesas
        case 'listar':
            $sql = "SELECT * FROM mesas ORDER BY id_mesa ASC";
            $resultado = $conexion->query($sql);

            if (!$resultado) {
                echo json_encode(['status' => 'error', 'msg' => 'Error en la consulta: ' . $conexion->error]);
                exit;
            }

            $data = [];
            while ($fila = $resultado->fetch_assoc()) $data[] = $fila;
            echo json_encode(['status' => 'ok', 'data' => $data]);
            break;

        // 🔹 Insertar mesa
        case 'insertar':
            $descripcion = trim($_POST['descripcion'] ?? '');
            $capacidad = (int)($_POST['capacidad_personas'] ?? 0);
            $estado = trim($_POST['estado'] ?? 'DISPONIBLE');

            // Validaciones básicas
            if (!$descripcion || !$capacidad) {
                echo json_encode(['status' => 'error', 'msg' => 'Por favor completa todos los campos.']);
                exit;
            }

            if (!preg_match('/^[a-zA-Z0-9\s#-]+$/u', $descripcion)) {
                echo json_encode(['status' => 'error', 'msg' => 'La descripción contiene caracteres no permitidos.']);
                exit;
            }

            // Evitar duplicados
            $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM mesas WHERE descripcion = ?");
            $stmt_check->bind_param("s", $descripcion);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($count > 0) {
                echo json_encode(['status' => 'error', 'msg' => 'Ya existe una mesa con esa descripción.']);
                exit;
            }

            // Insertar mesa
            $stmt = $conexion->prepare("INSERT INTO mesas (descripcion, capacidad_personas, estado) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $descripcion, $capacidad, $estado);
            $ok = $stmt->execute();

            if ($ok) registrarBitacora($conexion, "mesas", "insertar", "Mesa '$descripcion' creada (Capacidad $capacidad, Estado $estado)");

            echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $ok ? '' : $stmt->error]);
            $stmt->close();
            break;

        // 🔹 Modificar mesa
        case 'modificar':
            $id = (int)($_POST['id_mesa'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $capacidad = (int)($_POST['capacidad_personas'] ?? 0);
            $estado = trim($_POST['estado'] ?? '');

            if (!$id || !$descripcion || !$capacidad || !$estado) {
                echo json_encode(['status' => 'error', 'msg' => 'Campos incompletos o inválidos.']);
                exit;
            }

            if (!preg_match('/^[a-zA-Z0-9\s#-]+$/u', $descripcion)) {
                echo json_encode(['status' => 'error', 'msg' => 'La descripción contiene caracteres no permitidos.']);
                exit;
            }

            // Evitar duplicado de descripción
            $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM mesas WHERE descripcion = ? AND id_mesa <> ?");
            $stmt_check->bind_param("si", $descripcion, $id);
            $stmt_check->execute();
            $stmt_check->bind_result($count);
            $stmt_check->fetch();
            $stmt_check->close();

            if ($count > 0) {
                echo json_encode(['status' => 'error', 'msg' => 'Ya existe otra mesa con esa descripción.']);
                exit;
            }

            // Actualizar
            $stmt = $conexion->prepare("UPDATE mesas SET descripcion=?, capacidad_personas=?, estado=? WHERE id_mesa=?");
            $stmt->bind_param("sisi", $descripcion, $capacidad, $estado, $id);
            $ok = $stmt->execute();

            if ($ok) registrarBitacora($conexion, "mesas", "modificar", "Mesa #$id actualizada ('$descripcion', capacidad $capacidad, estado $estado)");

            echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $ok ? '' : $stmt->error]);
            $stmt->close();
            break;

        // 🔹 Eliminar mesa
        case 'eliminar':
            $id = (int)($_POST['id_mesa'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'msg' => 'ID inválido.']);
                exit;
            }

            // Obtener descripción antes de eliminar
            $resDesc = $conexion->query("SELECT descripcion FROM mesas WHERE id_mesa = $id");
            $desc = $resDesc && $resDesc->num_rows > 0 ? $resDesc->fetch_assoc()['descripcion'] : 'Desconocida';

            $stmt = $conexion->prepare("DELETE FROM mesas WHERE id_mesa=?");
            $stmt->bind_param("i", $id);
            $ok = $stmt->execute();

            if ($ok) registrarBitacora($conexion, "mesas", "eliminar", "Mesa #$id ('$desc') eliminada.");

            echo json_encode(['status' => $ok ? 'ok' : 'error', 'msg' => $ok ? '' : $stmt->error]);
            $stmt->close();
            break;

        // 🔹 Siguiente ID
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
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Excepción: ' . $e->getMessage()]);
}

desconectar($conexion);
?>


