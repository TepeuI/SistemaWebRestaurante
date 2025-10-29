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
        case 'crear_mantenimiento_elect':
            crearMantenimientoElectrodomestico();
            break;
        case 'actualizar_mantenimiento_elect':
            actualizarMantenimientoElectrodomestico();
            break;
        case 'eliminar_mantenimiento_elect':
            eliminarMantenimientoElectrodomestico();
            break;
    }
}

function crearMantenimientoElectrodomestico() {
    global $conn;
    $conn = conectar();
    
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    $id_taller = $_POST['id_taller'] ?? NULL;
    $descripcion_mantenimiento = $_POST['descripcion_mantenimiento'] ?? '';
    $fecha_mantenimiento = $_POST['fecha_mantenimiento'] ?? '';
    $costo_mantenimiento_q = $_POST['costo_mantenimiento_q'] ?? 0;
    
    // Si id_taller está vacío, establecerlo como NULL
    if ($id_taller === '') {
        $id_taller = NULL;
    }
    
    $sql = "INSERT INTO mantenimiento_electrodomesticos (id_mobiliario, id_taller, descripcion_mantenimiento, fecha_mantenimiento, costo_mantenimiento_q) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissd", $id_mobiliario, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $costo_mantenimiento_q);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento de electrodoméstico registrado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_electrodomesticos.php');
    exit();
}

function actualizarMantenimientoElectrodomestico() {
    global $conn;
    $conn = conectar();
    
    $id_mantenimiento_elect = $_POST['id_mantenimiento_elect'] ?? '';
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    $id_taller = $_POST['id_taller'] ?? NULL;
    $descripcion_mantenimiento = $_POST['descripcion_mantenimiento'] ?? '';
    $fecha_mantenimiento = $_POST['fecha_mantenimiento'] ?? '';
    $costo_mantenimiento_q = $_POST['costo_mantenimiento_q'] ?? 0;
    
    // Si id_taller está vacío, establecerlo como NULL
    if ($id_taller === '') {
        $id_taller = NULL;
    }
    
    $sql = "UPDATE mantenimiento_electrodomesticos SET id_mobiliario = ?, id_taller = ?, descripcion_mantenimiento = ?, fecha_mantenimiento = ?, costo_mantenimiento_q = ? 
            WHERE id_mantenimiento_elect = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissdi", $id_mobiliario, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $costo_mantenimiento_q, $id_mantenimiento_elect);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mantenimiento de electrodoméstico actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar mantenimiento: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: mantenimiento_electrodomesticos.php');
    exit();
}

function eliminarMantenimientoElectrodomestico() {
    global $conn;
    $conn = conectar();
    
    $id_mantenimiento_elect = $_POST['id_mantenimiento_elect'] ?? '';
    
    // Validar que el ID no esté vacío
    if (empty($id_mantenimiento_elect)) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de mantenimiento válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: mantenimiento_electrodomesticos.php');
        exit();
    }
    
    try {
        // Primero verificar si el mantenimiento existe
        $check_mantenimiento = $conn->prepare("SELECT id_mantenimiento_elect FROM mantenimiento_electrodomesticos WHERE id_mantenimiento_elect = ?");
        if (!$check_mantenimiento) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_mantenimiento->bind_param("i", $id_mantenimiento_elect);
        
        if (!$check_mantenimiento->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_mantenimiento->error);
        }
        
        $result_mantenimiento = $check_mantenimiento->get_result();
        
        if ($result_mantenimiento->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El mantenimiento que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_mantenimiento->close();
            desconectar($conn);
            header('Location: mantenimiento_electrodomesticos.php');
            exit();
        }
        $check_mantenimiento->close();
        
        // Proceder con la eliminación
        $sql = "DELETE FROM mantenimiento_electrodomesticos WHERE id_mantenimiento_elect = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_mantenimiento_elect);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Mantenimiento de electrodoméstico eliminado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar el mantenimiento. Es posible que ya haya sido eliminado o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar el mantenimiento porque está siendo utilizado en otros registros del sistema.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar mantenimiento: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el mantenimiento porque está siendo utilizado en otros registros del sistema.";
            $_SESSION['tipo_mensaje'] = "error";
        } else if (strpos($error_message, 'Unknown column') !== false) {
            $_SESSION['mensaje'] = "Error en la consulta a la base de datos. Por favor, contacte al administrador del sistema.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "Error de base de datos: " . $error_message;
            $_SESSION['tipo_mensaje'] = "error";
        }
    } catch (Exception $e) {
        // Capturar cualquier otra excepción
        $_SESSION['mensaje'] = "Error inesperado: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    desconectar($conn);
    header('Location: mantenimiento_electrodomesticos.php');
    exit();
}

// Obtener todos los mantenimientos para mostrar en la tabla (solo electrodomésticos)
function obtenerMantenimientosElectrodomesticos() {
    $conn = conectar();
    
    $sql = "SELECT me.*, 
                   im.nombre_mobiliario,
                   im.descripcion as descripcion_mobiliario,
                   tm.descripcion as tipo_mobiliario,
                   t.nombre_taller,
                   t.telefono as telefono_taller
            FROM mantenimiento_electrodomesticos me
            LEFT JOIN inventario_mobiliario im ON me.id_mobiliario = im.id_mobiliario
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            LEFT JOIN talleres t ON me.id_taller = t.id_taller
            WHERE (tm.descripcion LIKE '%electrodoméstico%' OR tm.descripcion LIKE '%electrodomestico%' 
                   OR im.nombre_mobiliario LIKE '%refrigerador%' OR im.nombre_mobiliario LIKE '%cocina%' 
                   OR im.nombre_mobiliario LIKE '%microondas%' OR im.nombre_mobiliario LIKE '%licuadora%'
                   OR im.nombre_mobiliario LIKE '%lavadora%' OR im.nombre_mobiliario LIKE '%secadora%'
                   OR im.nombre_mobiliario LIKE '%horno%' OR im.nombre_mobiliario LIKE '%televisor%'
                   OR im.nombre_mobiliario LIKE '%aire%' OR im.nombre_mobiliario LIKE '%ventilador%')
            ORDER BY me.fecha_mantenimiento DESC";
    
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

// Obtener mobiliario para el select (solo electrodomésticos)
function obtenerElectrodomesticos() {
    $conn = conectar();
    
    $sql = "SELECT im.id_mobiliario, im.nombre_mobiliario, tm.descripcion as tipo_mobiliario
            FROM inventario_mobiliario im
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            WHERE tm.descripcion LIKE '%electrodoméstico%' OR tm.descripcion LIKE '%electrodomestico%' 
                   OR im.nombre_mobiliario LIKE '%refrigerador%' OR im.nombre_mobiliario LIKE '%cocina%' 
                   OR im.nombre_mobiliario LIKE '%microondas%' OR im.nombre_mobiliario LIKE '%licuadora%'
                   OR im.nombre_mobiliario LIKE '%lavadora%' OR im.nombre_mobiliario LIKE '%secadora%'
                   OR im.nombre_mobiliario LIKE '%horno%' OR im.nombre_mobiliario LIKE '%televisor%'
                   OR im.nombre_mobiliario LIKE '%aire%' OR im.nombre_mobiliario LIKE '%ventilador%'
            ORDER BY im.nombre_mobiliario";
    
    $resultado = $conn->query($sql);
    $electrodomesticos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $electrodomesticos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $electrodomesticos;
}

// Obtener talleres para el select (solo talleres de electrodomésticos)
function obtenerTalleresElectrodomesticos() {
    $conn = conectar();
    
    $sql = "SELECT id_taller, nombre_taller, telefono 
            FROM talleres 
            WHERE (nombre_taller LIKE '%electrodoméstico%' OR nombre_taller LIKE '%electrodomestico%'
                   OR nombre_taller LIKE '%línea blanca%' OR nombre_taller LIKE '%linea blanca%'
                   OR nombre_taller LIKE '%electrónica%' OR nombre_taller LIKE '%electronica%'
                   OR nombre_taller LIKE '%refrigeración%' OR nombre_taller LIKE '%refrigeracion%'
                   OR nombre_taller LIKE '%reparación%' OR nombre_taller LIKE '%reparacion%'
                   OR nombre_taller LIKE '%servicio técnico%' OR nombre_taller LIKE '%tecnico%')
                   AND nombre_taller NOT LIKE '%vehículo%' AND nombre_taller NOT LIKE '%vehiculo%'
                   AND nombre_taller NOT LIKE '%auto%' AND nombre_taller NOT LIKE '%carro%'
                   AND nombre_taller NOT LIKE '%mecánico%' AND nombre_taller NOT LIKE '%mecanico%'
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

$mantenimientos = obtenerMantenimientosElectrodomesticos();
$electrodomesticos = obtenerElectrodomesticos();
$talleres = obtenerTalleresElectrodomesticos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento de Electrodomésticos - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">MANTENIMIENTO DE ELECTRODOMÉSTICOS</h1>
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

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE MANTENIMIENTO</h2>

            <form id="form-mantenimiento" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_mantenimiento_elect">
                <input type="hidden" id="id_mantenimiento_elect" name="id_mantenimiento_elect" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="id_mobiliario">Electrodoméstico:</label>
                    <select class="form-control" id="id_mobiliario" name="id_mobiliario" required>
                        <option value="">Seleccione un electrodoméstico</option>
                        <?php foreach($electrodomesticos as $electro): ?>
                            <option value="<?php echo $electro['id_mobiliario']; ?>">
                                <?php echo htmlspecialchars($electro['nombre_mobiliario']); ?> - 
                                <?php echo htmlspecialchars($electro['tipo_mobiliario']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($electrodomesticos)): ?>
                        <small class="text-danger">No se encontraron electrodomésticos en el inventario</small>
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
                    <?php if (empty($talleres)): ?>
                        <small class="text-muted">No hay talleres especializados en electrodomésticos registrados</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_mantenimiento">Descripción del Mantenimiento:</label>
                    <textarea class="form-control" id="descripcion_mantenimiento" name="descripcion_mantenimiento" 
                              rows="3" required placeholder="Describa el trabajo de mantenimiento realizado (reparación, limpieza, cambio de piezas, etc.)..."></textarea>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="fecha_mantenimiento">Fecha de Mantenimiento:</label>
                    <input type="date" class="form-control" id="fecha_mantenimiento" name="fecha_mantenimiento" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="costo_mantenimiento_q">Costo (Q):</label>
                    <input type="number" step="0.01" class="form-control" id="costo_mantenimiento_q" name="costo_mantenimiento_q" 
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
                            <th>Electrodoméstico</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Taller</th>
                            <th>Fecha</th>
                            <th>Costo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mantenimientos as $mantenimiento): ?>
                        <tr>
                            <td>
                                <span class="badge-electrodomestico">#<?php echo htmlspecialchars($mantenimiento['id_mantenimiento_elect']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($mantenimiento['nombre_mobiliario'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($mantenimiento['tipo_mobiliario'] ?? 'N/A'); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>">
                                <?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($mantenimiento['nombre_taller'] ?? 'Interno'); ?></td>
                            <td class="fecha-cell"><?php echo htmlspecialchars($mantenimiento['fecha_mantenimiento']); ?></td>
                            <td class="text-end fw-bold">Q <?php echo number_format($mantenimiento['costo_mantenimiento_q'], 2); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $mantenimiento['id_mantenimiento_elect']; ?>"
                                        data-mobiliario="<?php echo $mantenimiento['id_mobiliario']; ?>"
                                        data-taller="<?php echo $mantenimiento['id_taller'] ?? ''; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($mantenimiento['descripcion_mantenimiento']); ?>"
                                        data-fecha="<?php echo $mantenimiento['fecha_mantenimiento']; ?>"
                                        data-costo="<?php echo $mantenimiento['costo_mantenimiento_q']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar_mantenimiento_elect">
                                    <input type="hidden" name="id_mantenimiento_elect" value="<?php echo $mantenimiento['id_mantenimiento_elect']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mantenimientos)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay mantenimientos de electrodomésticos registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/mantenimiento_electrodomesticos.js"></script>
</body>
</html>