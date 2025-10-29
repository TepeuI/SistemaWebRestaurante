<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Procesar operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    
    switch($operacion) {
        case 'crear':
            crearViaje();
            break;
        case 'actualizar':
            actualizarViaje();
            break;
        case 'eliminar':
            eliminarViaje();
            break;
    }
}

function crearViaje() {
    global $conn;
    $conn = conectar();
    
    $id_ruta = $_POST['id_ruta'] ?? '';
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_empleado_piloto = $_POST['id_empleado_piloto'] ?? '';
    $id_empleado_acompanante = $_POST['id_empleado_acompanante'] ?? null;
    $fecha_hora_salida = $_POST['fecha_hora_salida'] ?? '';
    $tiempo_aproximado_min = $_POST['tiempo_aproximado_min'] ?? null;
    $descripcion_viaje = $_POST['descripcion_viaje'] ?? '';
    
    // DEBUG: Ver qué está llegando
    error_log("DEBUG - Fecha recibida: " . $fecha_hora_salida);
    
    // CORREGIDO: Validación más robusta de la fecha
    if (empty($fecha_hora_salida) || !validarFechaHora($fecha_hora_salida)) {
        $_SESSION['mensaje'] = "Error: La fecha y hora de salida no son válidas";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    // CORREGIDO: Convertir formato de fecha de HTML a MySQL
    $fecha_hora_salida_mysql = convertirFechaHoraMySQL($fecha_hora_salida);
    
    // DEBUG: Ver la conversión
    error_log("DEBUG - Fecha convertida: " . $fecha_hora_salida_mysql);
    
    // Validar que los datos existen
    if (!validarRuta($id_ruta)) {
        $_SESSION['mensaje'] = "Error: La ruta seleccionada no existe";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    if (!validarVehiculo($id_vehiculo)) {
        $_SESSION['mensaje'] = "Error: El vehículo seleccionado no existe";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    if (!validarEmpleado($id_empleado_piloto)) {
        $_SESSION['mensaje'] = "Error: El empleado piloto seleccionado no existe";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    if ($id_empleado_acompanante && !validarEmpleado($id_empleado_acompanante)) {
        $_SESSION['mensaje'] = "Error: El empleado acompañante seleccionado no existe";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    $sql = "INSERT INTO viajes (id_ruta, id_vehiculo, id_empleado_piloto, id_empleado_acompanante, fecha_hora_salida, tiempo_aproximado_min, descripcion_viaje) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissss", $id_ruta, $id_vehiculo, $id_empleado_piloto, $id_empleado_acompanante, $fecha_hora_salida_mysql, $tiempo_aproximado_min, $descripcion_viaje);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Viaje creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear viaje: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: viajes_vehiculos.php');
    exit();
}

function actualizarViaje() {
    global $conn;
    $conn = conectar();
    
    $id_viaje = $_POST['id_viaje'] ?? '';
    $id_ruta = $_POST['id_ruta'] ?? '';
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_empleado_piloto = $_POST['id_empleado_piloto'] ?? '';
    $id_empleado_acompanante = $_POST['id_empleado_acompanante'] ?? null;
    $fecha_hora_salida = $_POST['fecha_hora_salida'] ?? '';
    $tiempo_aproximado_min = $_POST['tiempo_aproximado_min'] ?? null;
    $descripcion_viaje = $_POST['descripcion_viaje'] ?? '';
    
    // CORREGIDO: Validación más robusta de la fecha
    if (empty($fecha_hora_salida) || !validarFechaHora($fecha_hora_salida)) {
        $_SESSION['mensaje'] = "Error: La fecha y hora de salida no son válidas";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    // CORREGIDO: Convertir formato de fecha de HTML a MySQL
    $fecha_hora_salida_mysql = convertirFechaHoraMySQL($fecha_hora_salida);
    
    // Validar que los datos existen
    if (!validarRuta($id_ruta)) {
        $_SESSION['mensaje'] = "Error: La ruta seleccionada no existe";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    if (!validarVehiculo($id_vehiculo)) {
        $_SESSION['mensaje'] = "Error: El vehículo seleccionado no existe";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    if (!validarEmpleado($id_empleado_piloto)) {
        $_SESSION['mensaje'] = "Error: El empleado piloto seleccionado no existe";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    if ($id_empleado_acompanante && !validarEmpleado($id_empleado_acompanante)) {
        $_SESSION['mensaje'] = "Error: El empleado acompañante seleccionado no existe";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: viajes_vehiculos.php');
        exit();
    }
    
    $sql = "UPDATE viajes 
            SET id_ruta = ?, id_vehiculo = ?, id_empleado_piloto = ?, id_empleado_acompanante = ?, 
                fecha_hora_salida = ?, tiempo_aproximado_min = ?, descripcion_viaje = ? 
            WHERE id_viaje = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiissssi", $id_ruta, $id_vehiculo, $id_empleado_piloto, $id_empleado_acompanante, $fecha_hora_salida_mysql, $tiempo_aproximado_min, $descripcion_viaje, $id_viaje);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Viaje actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar viaje: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: viajes_vehiculos.php');
    exit();
}

function eliminarViaje() {
    global $conn;
    $conn = conectar();
    
    $id_viaje = $_POST['id_viaje'] ?? '';
    
    try {
        // Verificar si el viaje está siendo referenciado en otras tablas
        // Por ejemplo, si hay una tabla de facturas o reportes que referencien viajes
        // $check_facturas = $conn->prepare("SELECT COUNT(*) as count FROM facturas WHERE id_viaje = ?");
        // $check_facturas->bind_param("i", $id_viaje);
        // $check_facturas->execute();
        // $result_facturas = $check_facturas->get_result();
        // $row_facturas = $result_facturas->fetch_assoc();
        // $check_facturas->close();
        
        // if ($row_facturas['count'] > 0) {
        //     $_SESSION['mensaje'] = "No se puede eliminar el viaje porque está siendo utilizado en facturas registradas.";
        //     $_SESSION['tipo_mensaje'] = "error";
        //     desconectar($conn);
        //     header('Location: viajes_vehiculos.php');
        //     exit();
        // }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM viajes WHERE id_viaje = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_viaje);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Viaje eliminado exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            // Capturar cualquier otro error que pueda ocurrir
            $_SESSION['mensaje'] = "Error al eliminar viaje: " . $conn->error;
            $_SESSION['tipo_mensaje'] = "error";
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el viaje porque está siendo utilizado en otros registros del sistema.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar viaje: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "error";
        }
    } catch (Exception $e) {
        // Capturar cualquier otra excepción
        $_SESSION['mensaje'] = "Error al eliminar viaje: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    desconectar($conn);
    header('Location: viajes_vehiculos.php');
    exit();
}

// CORREGIDO: Función mejorada para validar fecha/hora
function validarFechaHora($fechaHora) {
    if (empty($fechaHora)) {
        return false;
    }
    
    // Verificar formato básico (debe contener T)
    if (strpos($fechaHora, 'T') === false) {
        return false;
    }
    
    // Intentar crear DateTime object
    try {
        $dt = DateTime::createFromFormat('Y-m-d\TH:i', $fechaHora);
        return $dt && $dt->format('Y-m-d\TH:i') === $fechaHora;
    } catch (Exception $e) {
        return false;
    }
}

// CORREGIDO: Función mejorada para convertir fecha de HTML a formato MySQL
function convertirFechaHoraMySQL($fechaHtml) {
    // Si está vacío, retornar null
    if (empty($fechaHtml)) {
        return null;
    }
    
    // Verificar que tenga el formato correcto
    if (strpos($fechaHtml, 'T') === false) {
        // Si no tiene 'T', intentar formatear de otra manera
        $timestamp = strtotime($fechaHtml);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }
    
    // Formato normal: "2024-01-15T14:30" a "2024-01-15 14:30:00"
    $fechaHora = str_replace('T', ' ', $fechaHtml);
    
    // Si no tiene segundos, agregarlos
    if (strlen($fechaHora) == 16) { // YYYY-MM-DD HH:MM
        $fechaHora .= ':00';
    }
    
    return $fechaHora;
}

// Funciones de validación
function validarRuta($id_ruta) {
    $conn = conectar();
    $sql = "SELECT id_ruta FROM rutas WHERE id_ruta = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_ruta);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $exists;
}

function validarVehiculo($id_vehiculo) {
    $conn = conectar();
    $sql = "SELECT id_vehiculo FROM vehiculos WHERE id_vehiculo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $exists;
}

function validarEmpleado($id_empleado) {
    $conn = conectar();
    $sql = "SELECT id_empleado FROM empleados WHERE id_empleado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_empleado);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    desconectar($conn);
    return $exists;
}

// Obtener datos para los selectores
function obtenerRutas() {
    $conn = conectar();
    $sql = "SELECT id_ruta, descripcion_ruta, inicio_ruta, fin_ruta FROM rutas ORDER BY descripcion_ruta";
    $resultado = $conn->query($sql);
    $rutas = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $rutas[] = $fila;
        }
    }
    
    desconectar($conn);
    return $rutas;
}

// MODIFICADO: Solo vehículos con estado ACTIVO
function obtenerVehiculos() {
    $conn = conectar();
    $sql = "SELECT id_vehiculo, no_placa, marca_vehiculo, modelo_vehiculo 
            FROM vehiculos 
            WHERE estado = 'ACTIVO' 
            ORDER BY no_placa";
    $resultado = $conn->query($sql);
    $vehiculos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $vehiculos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $vehiculos;
}

function obtenerEmpleados() {
    $conn = conectar();
    $sql = "SELECT id_empleado, nombre_empleado, apellido_empleado FROM empleados ORDER BY nombre_empleado, apellido_empleado";
    $resultado = $conn->query($sql);
    $empleados = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $empleados[] = $fila;
        }
    }
    
    desconectar($conn);
    return $empleados;
}

// Obtener todos los viajes para mostrar en la tabla
function obtenerViajes() {
    $conn = conectar();
    $sql = "SELECT v.*, r.descripcion_ruta, ve.no_placa, ve.marca_vehiculo, ve.modelo_vehiculo, 
                   ep.nombre_empleado as piloto_nombre, ep.apellido_empleado as piloto_apellido,
                   ea.nombre_empleado as acompanante_nombre, ea.apellido_empleado as acompanante_apellido
            FROM viajes v
            LEFT JOIN rutas r ON v.id_ruta = r.id_ruta
            LEFT JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
            LEFT JOIN empleados ep ON v.id_empleado_piloto = ep.id_empleado
            LEFT JOIN empleados ea ON v.id_empleado_acompanante = ea.id_empleado
            ORDER BY v.fecha_hora_salida DESC";
    $resultado = $conn->query($sql);
    $viajes = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $viajes[] = $fila;
        }
    }
    
    desconectar($conn);
    return $viajes;
}

$rutas = obtenerRutas();
$vehiculos = obtenerVehiculos();
$empleados = obtenerEmpleados();
$viajes = obtenerViajes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Viajes - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">Gestión de Viajes</h1>
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
            </ul>
        </div>
    </header>

    <main class="container my-4">
        <!-- Mostrar mensajes con SweetAlert2 -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <script>
                window.__mensaje = {
                    text: <?php echo json_encode($_SESSION['mensaje']); ?>,
                    tipo: <?php echo json_encode($_SESSION['tipo_mensaje'] ?? 'error'); ?>
                };
            </script>
            <noscript>
                <div class="alert alert-<?php echo ($_SESSION['tipo_mensaje'] ?? '') === 'success' ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
                </div>
            </noscript>
            <?php 
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
        <?php endif; ?>

        <!-- Mensaje si no hay vehículos activos -->
        <?php if (empty($vehiculos)): ?>
            <div class="alert alert-warning">
                <strong>⚠️ No hay vehículos disponibles</strong>
                <p class="mb-0">No hay vehículos activos disponibles para asignar viajes. Active un vehículo en el módulo de Gestión de Vehículos.</p>
            </div>
        <?php endif; ?>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - Viajes de Vehículos</h2>

            <form id="form-viaje" method="post" class="row g-3" <?php echo empty($vehiculos) ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_viaje" name="id_viaje" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="id_ruta">Ruta:</label>
                    <select class="form-control" id="id_ruta" name="id_ruta" required <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                        <option value="">Seleccione una ruta</option>
                        <?php foreach($rutas as $ruta): ?>
                            <option value="<?php echo $ruta['id_ruta']; ?>">
                                <?php echo htmlspecialchars($ruta['descripcion_ruta'] . ' (' . $ruta['inicio_ruta'] . ' - ' . $ruta['fin_ruta'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_vehiculo">Vehículo:</label>
                    <select class="form-control" id="id_vehiculo" name="id_vehiculo" required <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                        <option value="">Seleccione un vehículo</option>
                        <?php foreach($vehiculos as $vehiculo): ?>
                            <option value="<?php echo $vehiculo['id_vehiculo']; ?>">
                                <?php echo htmlspecialchars($vehiculo['no_placa'] . ' - ' . $vehiculo['marca_vehiculo'] . ' ' . $vehiculo['modelo_vehiculo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($vehiculos)): ?>
                        <small class="text-danger">No hay vehículos activos disponibles</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_empleado_piloto">Empleado Piloto:</label>
                    <select class="form-control" id="id_empleado_piloto" name="id_empleado_piloto" required <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                        <option value="">Seleccione un piloto</option>
                        <?php foreach($empleados as $empleado): ?>
                            <option value="<?php echo $empleado['id_empleado']; ?>">
                                <?php echo htmlspecialchars($empleado['nombre_empleado'] . ' ' . $empleado['apellido_empleado']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_empleado_acompanante">Empleado Acompañante:</label>
                    <select class="form-control" id="id_empleado_acompanante" name="id_empleado_acompanante" <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                        <option value="">Sin acompañante</option>
                        <?php foreach($empleados as $empleado): ?>
                            <option value="<?php echo $empleado['id_empleado']; ?>">
                                <?php echo htmlspecialchars($empleado['nombre_empleado'] . ' ' . $empleado['apellido_empleado']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="fecha_hora_salida">Fecha y Hora de Salida:</label>
                    <input type="datetime-local" class="form-control" id="fecha_hora_salida" name="fecha_hora_salida" required <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="tiempo_aproximado_min">Tiempo Aproximado (minutos):</label>
                    <input type="number" class="form-control" id="tiempo_aproximado_min" name="tiempo_aproximado_min" 
                           min="1" placeholder="Ej. 120" <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_viaje">Descripción del Viaje:</label>
                    <textarea class="form-control" id="descripcion_viaje" name="descripcion_viaje" 
                              rows="3" placeholder="Ej. Viaje para entrega de ingredientes, recolección de suministros, etc." <?php echo empty($vehiculos) ? 'disabled' : ''; ?>></textarea>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary" <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success" <?php echo empty($vehiculos) ? 'disabled' : ''; ?>>Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">Historial de Viajes</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-viajes">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Viaje</th>
                            <th>Ruta</th>
                            <th>Vehículo</th>
                            <th>Piloto</th>
                            <th>Acompañante</th>
                            <th>Fecha/Hora Salida</th>
                            <th>Tiempo (min)</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($viajes as $viaje): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($viaje['id_viaje']); ?></td>
                            <td><?php echo htmlspecialchars($viaje['descripcion_ruta'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($viaje['no_placa'] . ' - ' . $viaje['marca_vehiculo']); ?></td>
                            <td><?php echo htmlspecialchars($viaje['piloto_nombre'] . ' ' . $viaje['piloto_apellido']); ?></td>
                            <td><?php echo htmlspecialchars(($viaje['acompanante_nombre'] ?? '') . ' ' . ($viaje['acompanante_apellido'] ?? '') ?: 'Ninguno'); ?></td>
                            <td><?php echo htmlspecialchars($viaje['fecha_hora_salida']); ?></td>
                            <td><?php echo htmlspecialchars($viaje['tiempo_aproximado_min'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($viaje['descripcion_viaje'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $viaje['id_viaje']; ?>"
                                        data-ruta="<?php echo $viaje['id_ruta']; ?>"
                                        data-vehiculo="<?php echo $viaje['id_vehiculo']; ?>"
                                        data-piloto="<?php echo $viaje['id_empleado_piloto']; ?>"
                                        data-acompanante="<?php echo $viaje['id_empleado_acompanante']; ?>"
                                        data-fecha="<?php echo str_replace(' ', 'T', substr($viaje['fecha_hora_salida'], 0, 16)); ?>"
                                        data-tiempo="<?php echo $viaje['tiempo_aproximado_min']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($viaje['descripcion_viaje']); ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_viaje" value="<?php echo $viaje['id_viaje']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($viajes)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay viajes registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/viajes_vehiculos.js"></script>
</body>
</html>