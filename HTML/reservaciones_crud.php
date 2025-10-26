<?php
include("conexion.php");
$conexion = conectar();

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    //un select con join para traer las meses en combo y listarlas
    case 'listar':
        $sql = "SELECT r.id_reservacion, r.id_cliente, c.nombre AS cliente_nombre,
                       r.id_mesa, m.descripcion AS mesa_desc, 
                       r.cantidad_personas, r.fecha_hora, r.estado
                FROM reservaciones r
                INNER JOIN clientes c ON c.id_cliente = r.id_cliente
                INNER JOIN mesas m ON m.id_mesa = r.id_mesa";
        $resultado = $conexion->query($sql);
        $data = [];
        while ($fila = $resultado->fetch_assoc()) {
            $data[] = $fila;
        }
        echo json_encode($data);
        break;

        //metodo para insertar con post
    case 'insertar':
        $id_cliente = $_POST['id_cliente'];
        $id_mesa = $_POST['id_mesa'];
        $cantidad_personas = $_POST['cantidad_personas'];
        $fecha_hora = $_POST['fecha_hora'];
        $estado = $_POST['estado'];

        $sql = "INSERT INTO reservaciones (id_cliente, id_mesa, cantidad_personas, fecha_hora, estado)
                VALUES ('$id_cliente', '$id_mesa', '$cantidad_personas', '$fecha_hora', '$estado')";
        echo ($conexion->query($sql)) ? "ok" : "error";
        break;

        //metodo para modificar o actualizar con post
    case 'modificar':
        $id = $_POST['id_reservacion'];
        $id_cliente = $_POST['id_cliente'];
        $id_mesa = $_POST['id_mesa'];
        $cantidad_personas = $_POST['cantidad_personas'];
        $fecha_hora = $_POST['fecha_hora'];
        $estado = $_POST['estado'];
        
        $sql = "UPDATE reservaciones SET     
                    id_cliente='$id_cliente', 
                    id_mesa='$id_mesa',
                    cantidad_personas='$cantidad_personas',
                    fecha_hora='$fecha_hora',
                    estado='$estado'
                WHERE id_reservacion=$id";
        echo ($conexion->query($sql)) ? "ok" : "error";
        break;
        
        //metodo para eliminar reservaciones
    case 'eliminar':
        $id = $_POST['id_reservacion'];
        $sql = "DELETE FROM reservaciones WHERE id_reservacion=$id";
        echo ($conexion->query($sql)) ? "ok" : "error";
        break;

    case 'clientes':
        $sql = "SELECT id_cliente, nombre FROM clientes";
        $resultado = $conexion->query($sql);
        $clientes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }
        echo json_encode($clientes);
        break;

    case 'mesas':
        $sql = "SELECT id_mesa, descripcion FROM mesas WHERE estado='DISPONIBLE'";
        $resultado = $conexion->query($sql);
        $mesas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $mesas[] = $fila;
        }
        echo json_encode($mesas);
        break;
}

desconectar($conexion);
?>
