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
            crearMantenimiento();
            break;
        case 'actualizar':
            actualizarMantenimiento();
            break;
        case 'eliminar':
            eliminarMantenimiento();
            break;
    }
}

function crearMantenimiento() {
    global $conn;
    $conn = conectar();
    
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_taller = $_POST['id_taller'] ?? '';
    $descripcion_mantenimiento = $_POST['descripcion_mantenimiento'] ?? '';
    $fecha_mantenimiento = $_POST['fecha_mantenimiento'] ?? '';
    $costo_mantenimiento = $_POST['costo_mantenimiento'] ?? '';
    
    $sql = "INSERT INTO mantenimiento_vehiculo (id_vehiculo, id_taller, descripcion_mantenimiento, fecha_mantenimiento, costo_mantenimiento) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissd", $id_vehiculo, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $costo_mantenimiento);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_vehiculos.php');
    exit();
}

function actualizarMantenimiento() {
    global $conn;
    $conn = conectar();
    
    $id_mantenimiento = $_POST['id_mantenimiento'] ?? '';
    $id_vehiculo = $_POST['id_vehiculo'] ?? '';
    $id_taller = $_POST['id_taller'] ?? '';
    $descripcion_mantenimiento = $_POST['descripcion_mantenimiento'] ?? '';
    $fecha_mantenimiento = $_POST['fecha_mantenimiento'] ?? '';
    $costo_mantenimiento = $_POST['costo_mantenimiento'] ?? '';
    
    $sql = "UPDATE mantenimiento_vehiculo 
            SET id_vehiculo = ?, id_taller = ?, descripcion_mantenimiento = ?, 
                fecha_mantenimiento = ?, costo_mantenimiento = ? 
            WHERE id_mantenimiento = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissdi", $id_vehiculo, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $costo_mantenimiento, $id_mantenimiento);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_vehiculos.php');
    exit();
}

function eliminarMantenimiento() {
    global $conn;
    $conn = conectar();
    
    $id_mantenimiento = $_POST['id_mantenimiento'] ?? '';
    
    $sql = "DELETE FROM mantenimiento_vehiculo WHERE id_mantenimiento = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mantenimiento);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_vehiculos.php');
    exit();
}

// Obtener datos para los selectores
function obtenerVehiculos() {
    $conn = conectar();
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

function obtenerTalleres() {
    $conn = conectar();
    $sql = "SELECT id_taller, nombre_taller FROM talleres ORDER BY nombre_taller";
    $resultado = $conn->query($sql);
    $talleres = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $talleres[] = $fila;
        }
    }
    
    desconectar($conn);
    return $talleres;
}

// Obtener todos los mantenimientos para mostrar en la tabla
function obtenerMantenimientos() {
    $conn = conectar();
    $sql = "SELECT mv.*, v.no_placa, v.marca_vehiculo, v.modelo_vehiculo, t.nombre_taller 
            FROM mantenimiento_vehiculo mv
            LEFT JOIN vehiculos v ON mv.id_vehiculo = v.id_vehiculo
            LEFT JOIN talleres t ON mv.id_taller = t.id_taller
            ORDER BY mv.fecha_mantenimiento DESC";
    $resultado = $conn->query($sql);
    $mantenimientos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $mantenimientos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $mantenimientos;
}

$vehiculos = obtenerVehiculos();
$talleres = obtenerTalleres();
$mantenimientos = obtenerMantenimientos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Vehículos - Marina Roja</title>
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
            <h1 class="mb-0">Mantenimiento de Vehículos</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - Mantenimiento de Vehículos</h2>

            <form id="form-mantenimiento" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_mantenimiento" name="id_mantenimiento" value="">
                
                <div class="col-md-4">
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
                
                <div class="col-md-4">
                    <label class="form-label" for="id_taller">Taller:</label>
                    <select class="form-control" id="id_taller" name="id_taller" required>
                        <option value="">Seleccione un taller</option>
                        <?php foreach($talleres as $taller): ?>
                            <option value="<?php echo $taller['id_taller']; ?>">
                                <?php echo htmlspecialchars($taller['nombre_taller']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="fecha_mantenimiento">Fecha de Mantenimiento:</label>
                    <input type="date" class="form-control" id="fecha_mantenimiento" name="fecha_mantenimiento" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="costo_mantenimiento">Costo (Q):</label>
                    <input type="number" class="form-control" id="costo_mantenimiento" name="costo_mantenimiento" 
                           step="0.01" min="0" required placeholder="Ej. 1250.00">
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_mantenimiento">Descripción del Mantenimiento:</label>
                    <textarea class="form-control" id="descripcion_mantenimiento" name="descripcion_mantenimiento" 
                              rows="3" required placeholder="Ej. Arreglo de suspensiones, cambio de aceite, etc."></textarea>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">Historial de Mantenimientos</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-mantenimientos">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Vehículo</th>
                            <th>Taller</th>
                            <th>Fecha</th>
                            <th>Costo (Q)</th>
                            <th>Descripción</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mantenimientos as $mantenimiento): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mantenimiento['id_mantenimiento']); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['no_placa'] . ' - ' . $mantenimiento['marca_vehiculo']); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['nombre_taller'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['fecha_mantenimiento']); ?></td>
                            <td>Q<?php echo number_format($mantenimiento['costo_mantenimiento'], 2); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $mantenimiento['id_mantenimiento']; ?>"
                                        data-vehiculo="<?php echo $mantenimiento['id_vehiculo']; ?>"
                                        data-taller="<?php echo $mantenimiento['id_taller']; ?>"
                                        data-fecha="<?php echo $mantenimiento['fecha_mantenimiento']; ?>"
                                        data-costo="<?php echo $mantenimiento['costo_mantenimiento']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este mantenimiento?')">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_mantenimiento" value="<?php echo $mantenimiento['id_mantenimiento']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mantenimientos)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay mantenimientos registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-mantenimiento');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idMantenimientoInput = document.getElementById('id_mantenimiento');

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
                    const vehiculo = this.getAttribute('data-vehiculo');
                    const taller = this.getAttribute('data-taller');
                    const fecha = this.getAttribute('data-fecha');
                    const costo = this.getAttribute('data-costo');
                    const descripcion = this.getAttribute('data-descripcion');

                    // Llenar formulario
                    idMantenimientoInput.value = id;
                    document.getElementById('id_vehiculo').value = vehiculo;
                    document.getElementById('id_taller').value = taller;
                    document.getElementById('fecha_mantenimiento').value = fecha;
                    document.getElementById('costo_mantenimiento').value = costo;
                    document.getElementById('descripcion_mantenimiento').value = descripcion;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idMantenimientoInput.value = '';
                operacionInput.value = 'crear';
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
                const vehiculo = document.getElementById('id_vehiculo').value;
                const taller = document.getElementById('id_taller').value;
                const fecha = document.getElementById('fecha_mantenimiento').value;
                const costo = document.getElementById('costo_mantenimiento').value;
                const descripcion = document.getElementById('descripcion_mantenimiento').value.trim();

                if (!vehiculo) {
                    alert('Seleccione un vehículo');
                    return false;
                }
                if (!taller) {
                    alert('Seleccione un taller');
                    return false;
                }
                if (!fecha) {
                    alert('La fecha es requerida');
                    return false;
                }
                if (!costo || costo <= 0) {
                    alert('El costo debe ser mayor a 0');
                    return false;
                }
                if (!descripcion) {
                    alert('La descripción es requerida');
                    return false;
                }

                return true;
            }

            // Establecer fecha actual por defecto
            document.getElementById('fecha_mantenimiento').valueAsDate = new Date();
        });
    </script>
</body>
</html>