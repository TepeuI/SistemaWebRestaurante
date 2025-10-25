<?php
// test_conexion.php
// Archivo para probar la conexión definida en conexion.php

require_once 'conexion.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Prueba de Conexión</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:20px} .ok{color:green}.err{color:red}</style>
</head>
<body>
  <h1>Prueba de Conexión a la Base de Datos</h1>
  <?php
  try {
      // Usa la función conectar() definida en conexion.php
      $conexion = conectar();

      // Verifica conexión con ping (si mysqli está activo)
      if ($conexion && $conexion->ping()) {
          echo "<p class=\"ok\">Conexión exitosa a la base de datos.</p>";
          echo "<ul>";
          echo "<li>Información del host: " . htmlspecialchars($conexion->host_info) . "</li>";
          echo "<li>Cliente MySQL: " . htmlspecialchars(mysqli_get_client_info()) . "</li>";
          echo "</ul>";
      } else {
          echo "<p class=\"err\">Conexión fallida. Comprueba los datos en <code>conexion.php</code>.</p>";
      }

      // Cierra la conexión usando la función disponible
      desconectar($conexion);
  } catch (Throwable $e) {
      echo "<p class=\"err\">Excepción al intentar conectar: " . htmlspecialchars($e->getMessage()) . "</p>";
  }
  ?>
  <p><a href="../indexq.php">Volver</a></p>
</body>
</html>
