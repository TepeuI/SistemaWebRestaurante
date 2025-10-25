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
    
    $sql = "INSERT INTO viajes (id_ruta, id_vehiculo, id_empleado_piloto, id_empleado_acompanante, fecha_hora_salida, tiempo_aproximado_min, descripcion_viaje) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisiss", $id_ruta, $id_vehiculo, $id_empleado_piloto, $id_empleado_acompanante, $fecha_hora_salida, $tiempo_aproximado_min, $descripcion_viaje);
    
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
    
    $sql = "UPDATE viajes 
            SET id_ruta = ?, id_vehiculo = ?, id_empleado_piloto = ?, id_empleado_acompanante = ?, 
                fecha_hora_salida = ?, tiempo_aproximado_min = ?, descripcion_viaje = ? 
            WHERE id_viaje = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisissi", $id_ruta, $id_vehiculo, $id_empleado_piloto, $id_empleado_acompanante, $fecha_hora_salida, $tiempo_aproximado_min, $descripcion_viaje, $id_viaje);
    
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
    
    $sql = "DELETE FROM viajes WHERE id_viaje = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_viaje);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Viaje eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar viaje: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: viajes_vehiculos.php');
    exit();
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

function obtenerVehiculos() {
    $conn = conectar();
    // CORREGIDO: Removido WHERE estado = 'ACTIVO' ya que no existe la columna estado
    $sql = "SELECT id_vehiculo, no_placa, marca_vehiculo, modelo_vehiculo FROM vehiculos ORDER BY no_placa";
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
    // CORREGIDO: Removido WHERE estado = 'ACTIVO' ya que no existe la columna estado
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
    <style>
        body, h1, h2, h3, h4, h5, h6, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }
        .mensaje {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .mensaje.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn-action {
            margin: 2px;
        }
    </style>
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
        <!-- Mostrar mensajes -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="mensaje <?php echo $_SESSION['tipo_mensaje']; ?>">
                <?php 
                echo htmlspecialchars($_SESSION['mensaje']); 
                unset($_SESSION['mensaje']);
                unset($_SESSION['tipo_mensaje']);
                ?>
            </div>
        <?php endif; ?>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - Viajes de Vehículos</h2>

            <form id="form-viaje" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_viaje" name="id_viaje" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="id_ruta">Ruta:</label>
                    <select class="form-control" id="id_ruta" name="id_ruta" required>
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
                    <select class="form-control" id="id_vehiculo" name="id_vehiculo" required>
                        <option value="">Seleccione un vehículo</option>
                        <?php foreach($vehiculos as $vehiculo): ?>
                            <option value="<?php echo $vehiculo['id_vehiculo']; ?>">
                                <?php echo htmlspecialchars($vehiculo['no_placa'] . ' - ' . $vehiculo['marca_vehiculo'] . ' ' . $vehiculo['modelo_vehiculo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_empleado_piloto">Empleado Piloto:</label>
                    <select class="form-control" id="id_empleado_piloto" name="id_empleado_piloto" required>
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
                    <select class="form-control" id="id_empleado_acompanante" name="id_empleado_acompanante">
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
                    <input type="datetime-local" class="form-control" id="fecha_hora_salida" name="fecha_hora_salida" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="tiempo_aproximado_min">Tiempo Aproximado (minutos):</label>
                    <input type="number" class="form-control" id="tiempo_aproximado_min" name="tiempo_aproximado_min" 
                           min="1" placeholder="Ej. 120">
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_viaje">Descripción del Viaje:</label>
                    <textarea class="form-control" id="descripcion_viaje" name="descripcion_viaje" 
                              rows="3" placeholder="Ej. Viaje para entrega de ingredientes, recolección de suministros, etc."></textarea>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
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
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este viaje?')">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-viaje');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idViajeInput = document.getElementById('id_viaje');

            // Botón Nuevo
            btnNuevo.addEventListener('click', function() {
                limpiarFormulario();
                mostrarBotonesGuardar();
            });

            // Botón Guardar (Crear)
            btnGuardar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'crear';
                    form.submit();
                }
            });

            // Botón Actualizar
            btnActualizar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'actualizar';
                    form.submit();
                }
            });

            // Botón Cancelar
            btnCancelar.addEventListener('click', function() {
                limpiarFormulario();
                mostrarBotonesGuardar();
            });

            // Eventos para botones Editar
            document.querySelectorAll('.editar-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const ruta = this.getAttribute('data-ruta');
                    const vehiculo = this.getAttribute('data-vehiculo');
                    const piloto = this.getAttribute('data-piloto');
                    const acompanante = this.getAttribute('data-acompanante');
                    const fecha = this.getAttribute('data-fecha');
                    const tiempo = this.getAttribute('data-tiempo');
                    const descripcion = this.getAttribute('data-descripcion');

                    // Llenar formulario
                    idViajeInput.value = id;
                    document.getElementById('id_ruta').value = ruta;
                    document.getElementById('id_vehiculo').value = vehiculo;
                    document.getElementById('id_empleado_piloto').value = piloto;
                    document.getElementById('id_empleado_acompanante').value = acompanante;
                    document.getElementById('fecha_hora_salida').value = fecha;
                    document.getElementById('tiempo_aproximado_min').value = tiempo;
                    document.getElementById('descripcion_viaje').value = descripcion;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idViajeInput.value = '';
                operacionInput.value = 'crear';
                // Establecer fecha y hora actual por defecto
                document.getElementById('fecha_hora_salida').valueAsDate = new Date();
            }

            function mostrarBotonesGuardar() {
                btnGuardar.style.display = 'inline-block';
                btnActualizar.style.display = 'none';
                btnCancelar.style.display = 'none';
            }

            function mostrarBotonesActualizar() {
                btnGuardar.style.display = 'none';
                btnActualizar.style.display = 'inline-block';
                btnCancelar.style.display = 'inline-block';
            }

            function validarFormulario() {
                const ruta = document.getElementById('id_ruta').value;
                const vehiculo = document.getElementById('id_vehiculo').value;
                const piloto = document.getElementById('id_empleado_piloto').value;
                const fecha = document.getElementById('fecha_hora_salida').value;

                if (!ruta) {
                    alert('Seleccione una ruta');
                    return false;
                }
                if (!vehiculo) {
                    alert('Seleccione un vehículo');
                    return false;
                }
                if (!piloto) {
                    alert('Seleccione un empleado piloto');
                    return false;
                }
                if (!fecha) {
                    alert('La fecha y hora de salida son requeridas');
                    return false;
                }

                return true;
            }

            // Establecer fecha y hora actual por defecto al cargar la página
            document.getElementById('fecha_hora_salida').valueAsDate = new Date();
        });
    </script>
</body>
</html>