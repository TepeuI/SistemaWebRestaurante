<?php
// funciones_permisos.php (incluir este archivo)
function tienePermiso($id_usuario, $nombre_aplicacion, $tipo_permiso = 'consultar') {
    require_once 'conexion.php';
    $conn = conectar();
    
    $sql = "SELECT p.permiso_insertar, p.permiso_consultar, p.permiso_actualizar, p.permiso_eliminar
            FROM permisos_usuario_aplicacion p
            INNER JOIN aplicaciones a ON p.id_aplicacion = a.id_aplicacion
            WHERE p.id_usuario = ? AND a.Aplicacion = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_usuario, $nombre_aplicacion);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        desconectar($conn);
        return false;
    }
    
    $permiso = $result->fetch_assoc();
    $stmt->close();
    desconectar($conn);
    
    // Verificar el tipo de permiso solicitado
    switch($tipo_permiso) {
        case 'insertar': return (bool)$permiso['permiso_insertar'];
        case 'consultar': return (bool)$permiso['permiso_consultar'];
        case 'actualizar': return (bool)$permiso['permiso_actualizar'];
        case 'eliminar': return (bool)$permiso['permiso_eliminar'];
        default: return (bool)$permiso['permiso_consultar'];
    }
}

// Función para obtener todas las aplicaciones permitidas
function obtenerAplicacionesPermitidas($id_usuario) {
    require_once 'conexion.php';
    $conn = conectar();
    
    $sql = "SELECT a.Aplicacion, a.descripcion_aplicacion,
                   p.permiso_insertar, p.permiso_consultar, 
                   p.permiso_actualizar, p.permiso_eliminar
            FROM permisos_usuario_aplicacion p
            INNER JOIN aplicaciones a ON p.id_aplicacion = a.id_aplicacion
            WHERE p.id_usuario = ? AND p.permiso_consultar = 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $aplicaciones = [];
    while ($row = $result->fetch_assoc()) {
        $aplicaciones[$row['Aplicacion']] = $row;
    }
    
    $stmt->close();
    desconectar($conn);
    
    return $aplicaciones;
}

<?php
// Agregar esta función en funciones_permisos.php
function obtenerRutaAplicacion($nombre_aplicacion) {
    $rutas = [
        'Reservaciones' => 'Reservaciones.html',
        'Empleados' => 'gestion_empleados/Empleados.php',
        'Telefonos_Empleados' => 'gestion_empleados/Telefono_empleados.php',
        'Correos_Empleados' => 'gestion_empleados/Correo_empleados.php',
        'Sucursales' => 'Departamental/Sucursales.php',
        'Gestion_Mobiliario' => '../HTML/gestion_de_mobiliario/gestion_mobiliario.php',
        'Compras_Mobiliario' => '../HTML/gestion_de_mobiliario/compras_mobiliario.php',
        'Detalle_Compras_Mobiliario' => '../HTML/gestion_de_mobiliario/detalle_compras_mobiliario.php',
        'Mantenimiento_Muebles' => '../HTML/mantenimiento_de_Mobiliario/mantenimiento_muebles.php',
        'Mantenimiento_Electrodomesticos' => '../HTML/mantenimiento_de_Mobiliario/mantenimiento_electrodomesticos.php',
        'Gestion_Vehiculos' => '../HTML/gestion_de_vehiculos/gestion_vehiculos.php',
        'Mantenimiento_Vehiculos' => '../HTML/gestion_de_vehiculos/mantenimiento_vehiculos.php',
        'Viajes_Vehiculos' => '../HTML/gestion_de_vehiculos/viajes_vehiculos.php',
        'Rutas_Vehiculos' => '../HTML/gestion_de_vehiculos/rutas_vehiculos.php',
        'Reportes_Accidentes' => '../HTML/gestion_de_vehiculos/reportes_accidentes.php',
        'Taller_Vehiculos' => '../HTML/taller_de_vehiculos/taller_vehiculos.php',
        'Platos' => '../HTML/Recetas_platos_Bebida_Orden/platos.php',
        'Bebidas' => '../HTML/Recetas_platos_Bebida_Orden/bebidas.php',
        'Recetas' => '../HTML/Recetas_platos_Bebida_Orden/recetas.php',
        'Gestion_Inventario_Ingredientes' => '../HTML/inventario_ingredientes/Gestion_Inventario_Ingredientes.php',
        'Control_Ingrediente' => '../HTML/inventario_ingredientes/Control_Ingrediente.php',
        'Perdida_Ingrediente' => '../HTML/inventario_ingredientes/Perdida_Ingrediente.php',
        'Gestion_Compras_Ingredientes' => '../HTML/inventario_ingredientes/Gestion_Compras_Inventarios_Ingredientes.php',
        'Detalle_Compras_Ingredientes' => '../HTML/inventario_ingredientes/Detalle_Compras_Ingredientes.php',
        'Gestion_Proveedores' => '../HTML/proveedores/gestion_proveedores.php',
        'Facturacion_Ventas' => 'Facturacion_Ventas.html',
        'Crear_Usuario' => 'crear_usuario.php',
        'Aplicaciones' => '../HTML/Usuarios_Aplicaciones/Aplicaciones.php',
        'Asignacion_Usuario_Aplicaciones' => '../HTML/Usuarios_Aplicaciones/Asignación_Usuario_Aplicaciones.php'
    ];
    
    return $rutas[$nombre_aplicacion] ?? '#';
}
?>
?>