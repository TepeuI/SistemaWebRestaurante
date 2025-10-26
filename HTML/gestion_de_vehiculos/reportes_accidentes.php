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
        case 'crear_accidente':
            crearAccidente();
            break;
        case 'actualizar_accidente':
            actualizarAccidente();
            break;
        case 'eliminar_accidente':
            eliminarAccidente();
            break;
    }
}

function crearAccidente() {
    global $conn;
    $conn = conectar();
    
    $id_viaje = $_POST['id_viaje'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $descripcion_accidente = $_POST['descripcion_accidente'] ?? '';
    $fecha_hora = $_POST['fecha_hora'] ?? date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO reportes_accidentes (id_viaje, id_empleado, descripcion_accidente, fecha_hora) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $id_viaje, $id_empleado, $descripcion_accidente, $fecha_hora);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Reporte de accidente registrado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar accidente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: reportes_accidentes.php');
    exit();
}

function actualizarAccidente() {
    global $conn;
    $conn = conectar();
    
    $id_accidente = $_POST['id_accidente'] ?? '';
    $id_viaje = $_POST['id_viaje'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $descripcion_accidente = $_POST['descripcion_accidente'] ?? '';
    $fecha_hora = $_POST['fecha_hora'] ?? '';
    
    $sql = "UPDATE reportes_accidentes SET id_viaje = ?, id_empleado = ?, descripcion_accidente = ?, fecha_hora = ? 
            WHERE id_accidente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissi", $id_viaje, $id_empleado, $descripcion_accidente, $fecha_hora, $id_accidente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Reporte de accidente actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar accidente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: reportes_accidentes.php');
    exit();
}

function eliminarAccidente() {
    global $conn;
    $conn = conectar();
    
    $id_accidente = $_POST['id_accidente'] ?? '';
    
    $sql = "DELETE FROM reportes_accidentes WHERE id_accidente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_accidente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Reporte de accidente eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar accidente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: reportes_accidentes.php');
    exit();
}

// Obtener todos los accidentes para mostrar en la tabla
function obtenerAccidentes() {
    $conn = conectar();
    
    // CONSULTA CORREGIDA con nombres de columnas correctos
    $sql = "SELECT ra.*, 
                   v.descripcion_viaje,
                   v.fecha_hora_salida,
                   ve.no_placa,
                   e.nombre_empleado,
                   e.apellido_empleado
            FROM reportes_accidentes ra
            LEFT JOIN viajes v ON ra.id_viaje = v.id_viaje
            LEFT JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
            LEFT JOIN empleados e ON ra.id_empleado = e.id_empleado
            ORDER BY ra.fecha_hora DESC";
    
    $resultado = $conn->query($sql);
    $accidentes = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $accidentes[] = $fila;
        }
    }
    
    desconectar($conn);
    return $accidentes;
}

// Obtener viajes para el select
function obtenerViajes() {
    $conn = conectar();
    
    // CONSULTA CORREGIDA - usando columnas correctas
    $sql = "SELECT v.id_viaje, v.descripcion_viaje, v.fecha_hora_salida, ve.no_placa, 
                   ep.nombre_empleado as nombre_piloto, ep.apellido_empleado as apellido_piloto
            FROM viajes v
            LEFT JOIN vehiculos ve ON v.id_vehiculo = ve.id_vehiculo
            LEFT JOIN empleados ep ON v.id_empleado_piloto = ep.id_empleado
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

// Obtener empleados para el select
function obtenerEmpleados() {
    $conn = conectar();
    
    // CONSULTA CORREGIDA - usando columnas correctas
    $sql = "SELECT id_empleado, nombre_empleado, apellido_empleado 
            FROM empleados 
            ORDER BY nombre_empleado, apellido_empleado";
    
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

$accidentes = obtenerAccidentes();
$viajes = obtenerViajes();
$empleados = obtenerEmpleados();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Accidentes - Marina Roja</title>
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
        .debug-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 12px;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .descripcion-cell {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .fecha-cell {
            white-space: nowrap;
        }
        .badge-accidente {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .info-accidente {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">REPORTES DE ACCIDENTES</h1>
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

        <!-- Debug info -->
        <div class="debug-info">
            <strong>Debug:</strong> 
            <?php 
            echo "Accidentes: " . count($accidentes) . " | ";
            echo "Viajes: " . count($viajes) . " | ";
            echo "Empleados: " . count($empleados);
            ?>
        </div>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - REPORTE DE ACCIDENTES</h2>

            <div class="info-accidente">
                <h5 class="text-warning">⚠️ Importante</h5>
                <p class="mb-0">Este formulario es para reportar accidentes ocurridos durante los viajes. 
                Asegúrese de proporcionar una descripción detallada del incidente.</p>
            </div>

            <form id="form-accidentes" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_accidente">
                <input type="hidden" id="id_accidente" name="id_accidente" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="id_viaje">Viaje Relacionado:</label>
                    <select class="form-control" id="id_viaje" name="id_viaje" required>
                        <option value="">Seleccione un viaje</option>
                        <?php foreach($viajes as $viaje): ?>
                            <option value="<?php echo $viaje['id_viaje']; ?>">
                                Viaje #<?php echo $viaje['id_viaje']; ?> - 
                                <?php echo htmlspecialchars($viaje['no_placa'] ?? 'Sin placa'); ?> - 
                                <?php echo htmlspecialchars($viaje['descripcion_viaje'] ?? 'Sin descripción'); ?> -
                                <?php echo htmlspecialchars($viaje['fecha_hora_salida']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_empleado">Empleado que Reporta:</label>
                    <select class="form-control" id="id_empleado" name="id_empleado" required>
                        <option value="">Seleccione un empleado</option>
                        <?php foreach($empleados as $empleado): ?>
                            <option value="<?php echo $empleado['id_empleado']; ?>">
                                <?php echo htmlspecialchars($empleado['nombre_empleado'] . ' ' . $empleado['apellido_empleado']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_accidente">Descripción del Accidente:</label>
                    <textarea class="form-control" id="descripcion_accidente" name="descripcion_accidente" 
                              rows="4" required placeholder="Describa detalladamente el accidente ocurrido, incluyendo lugar, hora, daños, personas involucradas, etc."></textarea>
                    <div class="form-text">Mínimo 50 caracteres. Sea lo más descriptivo posible.</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="fecha_hora">Fecha y Hora del Accidente:</label>
                    <input type="datetime-local" class="form-control" id="fecha_hora" name="fecha_hora" required>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">HISTORIAL DE ACCIDENTES REPORTADOS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-accidentes">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Viaje</th>
                            <th>Vehículo</th>
                            <th>Empleado</th>
                            <th>Descripción</th>
                            <th>Fecha/Hora</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($accidentes as $accidente): ?>
                        <tr>
                            <td>
                                <span class="badge-accidente">#<?php echo htmlspecialchars($accidente['id_accidente']); ?></span>
                            </td>
                            <td>
                                Viaje #<?php echo htmlspecialchars($accidente['id_viaje']); ?><br>
                                <small><?php echo htmlspecialchars($accidente['descripcion_viaje'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($accidente['no_placa'] ?? 'N/A'); ?></td>
                            <td>
                                <?php echo htmlspecialchars($accidente['nombre_empleado'] . ' ' . $accidente['apellido_empleado']); ?>
                            </td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($accidente['descripcion_accidente']); ?>">
                                <?php echo htmlspecialchars($accidente['descripcion_accidente']); ?>
                            </td>
                            <td class="fecha-cell">
                                <?php 
                                $fecha = new DateTime($accidente['fecha_hora']);
                                echo $fecha->format('d/m/Y H:i');
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $accidente['id_accidente']; ?>"
                                        data-viaje="<?php echo $accidente['id_viaje']; ?>"
                                        data-empleado="<?php echo $accidente['id_empleado']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($accidente['descripcion_accidente']); ?>"
                                        data-fecha="<?php echo $accidente['fecha_hora']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este reporte de accidente?')">
                                    <input type="hidden" name="operacion" value="eliminar_accidente">
                                    <input type="hidden" name="id_accidente" value="<?php echo $accidente['id_accidente']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($accidentes)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay accidentes reportados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-accidentes');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idAccidenteInput = document.getElementById('id_accidente');
            const descripcionInput = document.getElementById('descripcion_accidente');
            const fechaHoraInput = document.getElementById('fecha_hora');

            // Botón Nuevo
            btnNuevo.addEventListener('click', function() {
                limpiarFormulario();
                mostrarBotonesGuardar();
            });

            // Botón Guardar (Crear)
            btnGuardar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'crear_accidente';
                    form.submit();
                }
            });

            // Botón Actualizar
            btnActualizar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'actualizar_accidente';
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
                    const viaje = this.getAttribute('data-viaje');
                    const empleado = this.getAttribute('data-empleado');
                    const descripcion = this.getAttribute('data-descripcion');
                    const fecha = this.getAttribute('data-fecha');

                    // Llenar formulario
                    idAccidenteInput.value = id;
                    document.getElementById('id_viaje').value = viaje;
                    document.getElementById('id_empleado').value = empleado;
                    descripcionInput.value = descripcion;
                    
                    // Formatear fecha para el input datetime-local
                    const fechaObj = new Date(fecha);
                    const fechaFormateada = fechaObj.toISOString().slice(0, 16);
                    fechaHoraInput.value = fechaFormateada;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idAccidenteInput.value = '';
                operacionInput.value = 'crear_accidente';
                // Establecer fecha y hora actual por defecto
                fechaHoraInput.valueAsDate = new Date();
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
                const viaje = document.getElementById('id_viaje').value;
                const empleado = document.getElementById('id_empleado').value;
                const descripcion = descripcionInput.value.trim();
                const fecha = fechaHoraInput.value;

                if (!viaje) {
                    alert('El viaje relacionado es requerido');
                    return false;
                }
                if (!empleado) {
                    alert('El empleado que reporta es requerido');
                    return false;
                }
                if (!descripcion) {
                    alert('La descripción del accidente es requerida');
                    return false;
                }
                if (descripcion.length < 50) {
                    alert('La descripción debe tener al menos 50 caracteres');
                    return false;
                }
                if (!fecha) {
                    alert('La fecha y hora del accidente son requeridas');
                    return false;
                }

                return true;
            }

            // Inicializar - establecer fecha y hora actual
            limpiarFormulario();
        });
    </script>
</body>
</html>