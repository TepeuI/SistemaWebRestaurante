<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function registrarBitacora($conexion, $tabla, $accion, $detalle = '')
{
    try {
        $id_usuario = $_SESSION['id_usuario'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
        $pc = gethostname();

        //descripcion de la tabla
        $operacion = ucfirst($accion) . " en tabla '$tabla'";
        if ($detalle) {
            $operacion .= " = " . $detalle;
        }

        $stmt = $conexion->prepare("
            INSERT INTO bitacora (id_usuario, ip, pc, operacion_realizada)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isss", $id_usuario, $ip, $pc, $operacion);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // No se interrumpe el flujo y muestra error
        error_log("Error al registrar bitácora: " . $e->getMessage());
    }
}
?>
