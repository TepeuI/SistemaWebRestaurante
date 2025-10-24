<?php
/**
 * Abre una conexión MySQLi a la base de datos marea_roja_db
 * - Host/usuario/BD/puerto se definen aquí.
 * - Aplica charset utf8mb4 para soportar emojis y caracteres multi-byte.
 */
function conectar() {
    $host = "localhost";      // servidor MySQL
    $user = "pedro";           // usuario
    $pass = "123";               // contraseña
    $bd   = "marea_roja_db";  // base de datos
    $port = 3306;             // puerto MySQL

    // Crea objeto de conexión
    $conexion = new mysqli($host, $user, $pass, $bd, $port);

    // Si falla, detiene la ejecución con el mensaje de error
    if ($conexion->connect_error) {
        die("Error de conexión: " . $conexion->connect_error);
    }

    // Charset recomendado para textos en español y símbolos
    $conexion->set_charset("utf8mb4");

    // Devuelve la conexión abierta
    return $conexion;
}

/**
 * Cierra la conexión MySQLi
 */
function desconectar($conexion) {
    $conexion->close();
}
?>