<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function registrarBitacora($conexion, $tabla, $accion, $detalle = '')
{
    try {
        $id_usuario = $_SESSION['id_usuario'] ?? 0;

        // Obtener IP real
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'DESCONOCIDA';
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }

        //nombre de PC
        $pc = gethostname();

        // Hora local del sistema (ajusta tu zona horaria)
        date_default_timezone_set('America/Guatemala');
        $fecha_hora = date('Y-m-d H:i:s');

        //descripción de la acción
        $operacion = ucfirst($accion) . " en tabla '$tabla'";
        if ($detalle) {
            $operacion .= " = " . $detalle;
        }

        // Insertar con hora manual
        $stmt = $conexion->prepare("
            INSERT INTO bitacora (id_usuario, ip, pc, operacion_realizada, fecha_hora_accion)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $id_usuario, $ip, $pc, $operacion, $fecha_hora);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error al registrar bitácora: " . $e->getMessage());
    }
}
?>
