<?php
include("conexion.php");

$conexion = conectar();

$accion = $_POST['accion'] ?? '';

switch ($accion) {

    case 'listar':
        $sql = "SELECT * FROM mesas";
        $resultado = $conexion->query($sql);
        $data = [];
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
        echo json_encode($data);
        break;

    case 'insertar':
        $descripcion = $_POST['descripcion'];
        $capacidad = $_POST['capacidad_personas'];
        $estado = $_POST['estado'];

        $sql = "INSERT INTO mesas (descripcion, capacidad_personas, estado)
                VALUES ('$descripcion', '$capacidad', '$estado')";
        echo ($conexion->query($sql)) ? "ok" : "error";
        break;

    case 'modificar':
        $id = $_POST['id_mesa'];
        $descripcion = $_POST['descripcion'];
        $capacidad = $_POST['capacidad_personas'];
        $estado = $_POST['estado'];

        $sql = "UPDATE mesas SET descripcion='$descripcion',
                capacidad_personas='$capacidad', estado='$estado'
                WHERE id_mesa=$id";
        echo ($conexion->query($sql)) ? "ok" : "error";
        break;

    case 'eliminar':
        $id = $_POST['id_mesa'];
        $sql = "DELETE FROM mesas WHERE id_mesa=$id";
        echo ($conexion->query($sql)) ? "ok" : "error";
        break;
}

desconectar($conexion);
?>
