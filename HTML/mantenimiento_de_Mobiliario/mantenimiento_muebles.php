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
        case 'crear_mantenimiento':
            crearMantenimiento();
            break;
        case 'actualizar_mantenimiento':
            actualizarMantenimiento();
            break;
        case 'eliminar_mantenimiento':
            eliminarMantenimiento();
            break;
    }
}

function crearMantenimiento() {
    global $conn;
    $conn = conectar();
    
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    $id_taller = $_POST['id_taller'] ?? NULL;
    $descripcion_mantenimiento = $_POST['descripcion_mantenimiento'] ?? '';
    $fecha_mantenimiento = $_POST['fecha_mantenimiento'] ?? '';
    $codigo_serie = $_POST['codigo_serie'] ?? '';
    $costo_mantenimiento = $_POST['costo_mantenimiento'] ?? 0;
    
    // Si id_taller está vacío, establecerlo como NULL
    if ($id_taller === '') {
        $id_taller = NULL;
    }
    
    $sql = "INSERT INTO mantenimiento_muebles (id_mobiliario, id_taller, descripcion_mantenimiento, fecha_mantenimiento, codigo_serie, costo_mantenimiento) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssd", $id_mobiliario, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $codigo_serie, $costo_mantenimiento);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento registrado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_muebles.php');
    exit();
}

function actualizarMantenimiento() {
    global $conn;
    $conn = conectar();
    
    $id_mantenimiento_muebles = $_POST['id_mantenimiento_muebles'] ?? '';
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    $id_taller = $_POST['id_taller'] ?? NULL;
    $descripcion_mantenimiento = $_POST['descripcion_mantenimiento'] ?? '';
    $fecha_mantenimiento = $_POST['fecha_mantenimiento'] ?? '';
    $codigo_serie = $_POST['codigo_serie'] ?? '';
    $costo_mantenimiento = $_POST['costo_mantenimiento'] ?? 0;
    
    // Si id_taller está vacío, establecerlo como NULL
    if ($id_taller === '') {
        $id_taller = NULL;
    }
    
    $sql = "UPDATE mantenimiento_muebles SET id_mobiliario = ?, id_taller = ?, descripcion_mantenimiento = ?, fecha_mantenimiento = ?, codigo_serie = ?, costo_mantenimiento = ? 
            WHERE id_mantenimiento_muebles = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssdi", $id_mobiliario, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $codigo_serie, $costo_mantenimiento, $id_mantenimiento_muebles);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_muebles.php');
    exit();
}

function eliminarMantenimiento() {
    global $conn;
    $conn = conectar();
    
    $id_mantenimiento_muebles = $_POST['id_mantenimiento_muebles'] ?? '';
    
    $sql = "DELETE FROM mantenimiento_muebles WHERE id_mantenimiento_muebles = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mantenimiento_muebles);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_muebles.php');
    exit();
}

// Obtener todos los mantenimientos para mostrar en la tabla
function obtenerMantenimientos() {
    $conn = conectar();
    
    $sql = "SELECT mm.*, 
                   im.nombre_mobiliario,
                   im.descripcion as descripcion_mobiliario,
                   tm.descripcion as tipo_mobiliario,
                   t.nombre_taller,
                   t.telefono as telefono_taller
            FROM mantenimiento_muebles mm
            LEFT JOIN inventario_mobiliario im ON mm.id_mobiliario = im.id_mobiliario
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            LEFT JOIN talleres t ON mm.id_taller = t.id_taller
            WHERE tm.descripcion NOT LIKE '%vehículo%' AND tm.descripcion NOT LIKE '%vehiculo%'
            ORDER BY mm.fecha_mantenimiento DESC";
    
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

// Obtener mobiliario para el select (excluyendo vehículos)
function obtenerMobiliario() {
    $conn = conectar();
    
    $sql = "SELECT im.id_mobiliario, im.nombre_mobiliario, tm.descripcion as tipo_mobiliario
            FROM inventario_mobiliario im
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            WHERE tm.descripcion NOT LIKE '%vehículo%' AND tm.descripcion NOT LIKE '%vehiculo%'
            ORDER BY im.nombre_mobiliario";
    
    $resultado = $conn->query($sql);
    $mobiliario = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $mobiliario[] = $fila;
        }
    }
    
    desconectar($conn);
    return $mobiliario;
}

// Obtener talleres para el select (excluyendo talleres de vehículos)
function obtenerTalleres() {
    $conn = conectar();
    
    $sql = "SELECT id_taller, nombre_taller, telefono 
            FROM talleres 
            WHERE nombre_taller NOT LIKE '%vehículo%' 
               AND nombre_taller NOT LIKE '%vehiculo%'
               AND nombre_taller NOT LIKE '%auto%'
               AND nombre_taller NOT LIKE '%carro%'
               AND nombre_taller NOT LIKE '%mecánico%'
               AND nombre_taller NOT LIKE '%mecanico%'
            ORDER BY nombre_taller";
    
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

$mantenimientos = obtenerMantenimientos();
$mobiliarios = obtenerMobiliario();
$talleres = obtenerTalleres();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Muebles - Marina Roja</title>
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
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .fecha-cell {
            white-space: nowrap;
        }
        .costo {
            text-align: right;
            font-weight: bold;
        }
        .info-mantenimiento {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .codigo-serie {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #dee2e6;
        }
        .badge-mantenimiento {
            background-color: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">MANTENIMIENTO DE MUEBLES</h1>
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
            echo "Mantenimientos: " . count($mantenimientos) . " | ";
            echo "Mobiliarios (sin vehículos): " . count($mobiliarios) . " | ";
            echo "Talleres (sin vehículos): " . count($talleres);
            ?>
        </div>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE MANTENIMIENTO</h2>

            <form id="form-mantenimiento" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_mantenimiento">
                <input type="hidden" id="id_mantenimiento_muebles" name="id_mantenimiento_muebles" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="id_mobiliario">Mobiliario:</label>
                    <select class="form-control" id="id_mobiliario" name="id_mobiliario" required>
                        <option value="">Seleccione un mobiliario</option>
                        <?php foreach($mobiliarios as $mob): ?>
                            <option value="<?php echo $mob['id_mobiliario']; ?>">
                                <?php echo htmlspecialchars($mob['nombre_mobiliario']); ?> - 
                                <?php echo htmlspecialchars($mob['tipo_mobiliario']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($mobiliarios)): ?>
                        <small class="text-danger">No se encontraron muebles en el inventario</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_taller">Taller (Opcional):</label>
                    <select class="form-control" id="id_taller" name="id_taller">
                        <option value="">-- Sin taller específico --</option>
                        <?php foreach($talleres as $taller): ?>
                            <option value="<?php echo $taller['id_taller']; ?>">
                                <?php echo htmlspecialchars($taller['nombre_taller']); ?> - 
                                <?php echo htmlspecialchars($taller['telefono'] ?? 'Sin teléfono'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_mantenimiento">Descripción del Mantenimiento:</label>
                    <textarea class="form-control" id="descripcion_mantenimiento" name="descripcion_mantenimiento" 
                              rows="3" required placeholder="Describa el trabajo de mantenimiento realizado..."></textarea>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="fecha_mantenimiento">Fecha de Mantenimiento:</label>
                    <input type="date" class="form-control" id="fecha_mantenimiento" name="fecha_mantenimiento" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="codigo_serie">Código de Serie:</label>
                    <input type="text" class="form-control" id="codigo_serie" name="codigo_serie" 
                           required placeholder="Ej: MTN-001-2024">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="costo_mantenimiento">Costo (Q):</label>
                    <input type="number" step="0.01" class="form-control" id="costo_mantenimiento" name="costo_mantenimiento" 
                           min="0" required placeholder="0.00">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">HISTORIAL DE MANTENIMIENTOS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-mantenimientos">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Mobiliario</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Taller</th>
                            <th>Fecha</th>
                            <th>Código Serie</th>
                            <th>Costo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mantenimientos as $mantenimiento): ?>
                        <tr>
                            <td>
                                <span class="badge-mantenimiento">#<?php echo htmlspecialchars($mantenimiento['id_mantenimiento_muebles']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($mantenimiento['nombre_mobiliario'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['tipo_mobiliario'] ?? 'N/A'); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>">
                                <?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($mantenimiento['nombre_taller'] ?? 'Interno'); ?></td>
                            <td class="fecha-cell"><?php echo htmlspecialchars($mantenimiento['fecha_mantenimiento']); ?></td>
                            <td>
                                <span class="codigo-serie"><?php echo htmlspecialchars($mantenimiento['codigo_serie']); ?></span>
                            </td>
                            <td class="costo">Q <?php echo number_format($mantenimiento['costo_mantenimiento'], 2); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $mantenimiento['id_mantenimiento_muebles']; ?>"
                                        data-mobiliario="<?php echo $mantenimiento['id_mobiliario']; ?>"
                                        data-taller="<?php echo $mantenimiento['id_taller'] ?? ''; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>"
                                        data-fecha="<?php echo $mantenimiento['fecha_mantenimiento']; ?>"
                                        data-codigo="<?php echo htmlspecialchars($mantenimiento['codigo_serie']); ?>"
                                        data-costo="<?php echo $mantenimiento['costo_mantenimiento']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este mantenimiento?')">
                                    <input type="hidden" name="operacion" value="eliminar_mantenimiento">
                                    <input type="hidden" name="id_mantenimiento_muebles" value="<?php echo $mantenimiento['id_mantenimiento_muebles']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mantenimientos)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay mantenimientos registrados</td>
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
            const idMantenimientoInput = document.getElementById('id_mantenimiento_muebles');
            const fechaInput = document.getElementById('fecha_mantenimiento');

            // Botón Nuevo
            btnNuevo.addEventListener('click', function() {
                limpiarFormulario();
                mostrarBotonesGuardar();
            });

            // Botón Guardar (Crear)
            btnGuardar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'crear_mantenimiento';
                    form.submit();
                }
            });

            // Botón Actualizar
            btnActualizar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'actualizar_mantenimiento';
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
                    const mobiliario = this.getAttribute('data-mobiliario');
                    const taller = this.getAttribute('data-taller');
                    const descripcion = this.getAttribute('data-descripcion');
                    const fecha = this.getAttribute('data-fecha');
                    const codigo = this.getAttribute('data-codigo');
                    const costo = this.getAttribute('data-costo');

                    // Llenar formulario
                    idMantenimientoInput.value = id;
                    document.getElementById('id_mobiliario').value = mobiliario;
                    document.getElementById('id_taller').value = taller;
                    document.getElementById('descripcion_mantenimiento').value = descripcion;
                    document.getElementById('fecha_mantenimiento').value = fecha;
                    document.getElementById('codigo_serie').value = codigo;
                    document.getElementById('costo_mantenimiento').value = costo;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idMantenimientoInput.value = '';
                operacionInput.value = 'crear_mantenimiento';
                // Establecer fecha actual por defecto
                fechaInput.valueAsDate = new Date();
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
                const mobiliario = document.getElementById('id_mobiliario').value;
                const descripcion = document.getElementById('descripcion_mantenimiento').value.trim();
                const fecha = fechaInput.value;
                const codigo = document.getElementById('codigo_serie').value.trim();
                const costo = document.getElementById('costo_mantenimiento').value;

                if (!mobiliario) {
                    alert('El mobiliario es requerido');
                    return false;
                }
                if (!descripcion) {
                    alert('La descripción del mantenimiento es requerida');
                    return false;
                }
                if (!fecha) {
                    alert('La fecha de mantenimiento es requerida');
                    return false;
                }
                if (!codigo) {
                    alert('El código de serie es requerido');
                    return false;
                }
                if (!costo || costo < 0) {
                    alert('El costo debe ser mayor o igual a 0');
                    return false;
                }

                return true;
            }

            // Inicializar - establecer fecha actual
            limpiarFormulario();
        });
    </script>
</body>
</html>