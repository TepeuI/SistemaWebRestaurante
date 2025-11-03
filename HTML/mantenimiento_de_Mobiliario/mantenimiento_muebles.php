<?php
session_start();
require_once '../conexion.php';
require_once '../funciones_globales.php'; // Añadido para bitácoras

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
    
    // Obtener información adicional para la bitácora
    $info_mobiliario = obtenerInfoMobiliario($id_mobiliario);
    $info_taller = $id_taller ? obtenerInfoTaller($id_taller) : null;
    
    $sql = "INSERT INTO mantenimiento_muebles (id_mobiliario, id_taller, descripcion_mantenimiento, fecha_mantenimiento, codigo_serie, costo_mantenimiento) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssd", $id_mobiliario, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $codigo_serie, $costo_mantenimiento);
    
    if ($stmt->execute()) {
        $id_mantenimiento_nuevo = $conn->insert_id;
        
        // REGISTRO DE BITÁCORA - CREAR MANTENIMIENTO
        $descripcion_bitacora = "Mantenimiento creado (ID: $id_mantenimiento_nuevo) - " .
                               "Mobiliario: '{$info_mobiliario['nombre_mobiliario']}' ({$info_mobiliario['tipo_mobiliario']}) - " .
                               ($info_taller ? "Taller: {$info_taller['nombre_taller']} - " : "Mantenimiento interno - ") .
                               "Código Serie: $codigo_serie - " .
                               "Descripción: " . (strlen($descripcion_mantenimiento) > 50 ? substr($descripcion_mantenimiento, 0, 50) . "..." : $descripcion_mantenimiento) . " - " .
                               "Fecha: $fecha_mantenimiento - " .
                               "Costo: Q " . number_format($costo_mantenimiento, 2);
        
        registrarBitacora(
            $conn,
            "Mantenimiento Muebles",
            "insertar",
            $descripcion_bitacora
        );
        
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
    
    // Obtener datos anteriores para la bitácora
    $sql_anterior = "SELECT mm.*, im.nombre_mobiliario, tm.descripcion as tipo_mobiliario, t.nombre_taller
                     FROM mantenimiento_muebles mm
                     LEFT JOIN inventario_mobiliario im ON mm.id_mobiliario = im.id_mobiliario
                     LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
                     LEFT JOIN talleres t ON mm.id_taller = t.id_taller
                     WHERE mm.id_mantenimiento_muebles = ?";
    $stmt_anterior = $conn->prepare($sql_anterior);
    $stmt_anterior->bind_param("i", $id_mantenimiento_muebles);
    $stmt_anterior->execute();
    $result_anterior = $stmt_anterior->get_result();
    $datos_anterior = $result_anterior->fetch_assoc();
    $stmt_anterior->close();
    
    // Obtener información nueva para la bitácora
    $info_mobiliario_nuevo = obtenerInfoMobiliario($id_mobiliario);
    $info_taller_nuevo = $id_taller ? obtenerInfoTaller($id_taller) : null;
    
    $sql = "UPDATE mantenimiento_muebles SET id_mobiliario = ?, id_taller = ?, descripcion_mantenimiento = ?, fecha_mantenimiento = ?, codigo_serie = ?, costo_mantenimiento = ? 
            WHERE id_mantenimiento_muebles = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssdi", $id_mobiliario, $id_taller, $descripcion_mantenimiento, $fecha_mantenimiento, $codigo_serie, $costo_mantenimiento, $id_mantenimiento_muebles);
    
    if ($stmt->execute()) {
        // REGISTRO DE BITÁCORA - ACTUALIZAR MANTENIMIENTO
        $cambios = [];
        
        // Comparar cambios en mobiliario
        if ($datos_anterior['id_mobiliario'] != $id_mobiliario) {
            $mobiliario_anterior = obtenerInfoMobiliario($datos_anterior['id_mobiliario']);
            $cambios[] = "Mobiliario: '{$mobiliario_anterior['nombre_mobiliario']}' → '{$info_mobiliario_nuevo['nombre_mobiliario']}'";
        }
        
        // Comparar cambios en taller
        $taller_anterior_id = $datos_anterior['id_taller'];
        if ($taller_anterior_id != $id_taller) {
            $taller_anterior = $taller_anterior_id ? obtenerInfoTaller($taller_anterior_id) : null;
            $taller_anterior_nombre = $taller_anterior ? $taller_anterior['nombre_taller'] : 'Interno';
            $taller_nuevo_nombre = $info_taller_nuevo ? $info_taller_nuevo['nombre_taller'] : 'Interno';
            $cambios[] = "Taller: $taller_anterior_nombre → $taller_nuevo_nombre";
        }
        
        // Comparar cambios en descripción
        if ($datos_anterior['descripcion_mantenimiento'] != $descripcion_mantenimiento) {
            $desc_anterior = strlen($datos_anterior['descripcion_mantenimiento']) > 30 ? 
                           substr($datos_anterior['descripcion_mantenimiento'], 0, 30) . "..." : 
                           $datos_anterior['descripcion_mantenimiento'];
            $desc_nueva = strlen($descripcion_mantenimiento) > 30 ? 
                         substr($descripcion_mantenimiento, 0, 30) . "..." : 
                         $descripcion_mantenimiento;
            $cambios[] = "Descripción: '$desc_anterior' → '$desc_nueva'";
        }
        
        // Comparar cambios en fecha
        if ($datos_anterior['fecha_mantenimiento'] != $fecha_mantenimiento) {
            $cambios[] = "Fecha: {$datos_anterior['fecha_mantenimiento']} → $fecha_mantenimiento";
        }
        
        // Comparar cambios en código de serie
        if ($datos_anterior['codigo_serie'] != $codigo_serie) {
            $cambios[] = "Código Serie: '{$datos_anterior['codigo_serie']}' → '$codigo_serie'";
        }
        
        // Comparar cambios en costo
        if ($datos_anterior['costo_mantenimiento'] != $costo_mantenimiento) {
            $costo_anterior = number_format($datos_anterior['costo_mantenimiento'], 2);
            $costo_nuevo = number_format($costo_mantenimiento, 2);
            $cambios[] = "Costo: Q $costo_anterior → Q $costo_nuevo";
        }
        
        if (!empty($cambios)) {
            registrarBitacora(
                $conn,
                "Mantenimiento Muebles",
                "Actualizar",
                "Mantenimiento actualizado (ID: $id_mantenimiento_muebles) - Cambios: " . implode(", ", $cambios)
            );
        }
        
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
    
    // Validar que el ID no esté vacío
    if (empty($id_mantenimiento_muebles)) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de mantenimiento válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: mantenimiento_muebles.php');
        exit();
    }
    
    try {
        // Obtener información del mantenimiento para la bitácora
        $sql_info = "SELECT mm.*, im.nombre_mobiliario, tm.descripcion as tipo_mobiliario, t.nombre_taller
                     FROM mantenimiento_muebles mm
                     LEFT JOIN inventario_mobiliario im ON mm.id_mobiliario = im.id_mobiliario
                     LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
                     LEFT JOIN talleres t ON mm.id_taller = t.id_taller
                     WHERE mm.id_mantenimiento_muebles = ?";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("i", $id_mantenimiento_muebles);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        
        if ($result_info->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El mantenimiento que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $stmt_info->close();
            desconectar($conn);
            header('Location: mantenimiento_muebles.php');
            exit();
        }
        
        $mantenimiento = $result_info->fetch_assoc();
        $stmt_info->close();
        
        // Proceder con la eliminación
        $sql = "DELETE FROM mantenimiento_muebles WHERE id_mantenimiento_muebles = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_mantenimiento_muebles);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // REGISTRO DE BITÁCORA - ELIMINAR MANTENIMIENTO
                $descripcion_bitacora = "Mantenimiento eliminado (ID: $id_mantenimiento_muebles) - " .
                                       "Mobiliario: '{$mantenimiento['nombre_mobiliario']}' ({$mantenimiento['tipo_mobiliario']}) - " .
                                       ($mantenimiento['nombre_taller'] ? "Taller: {$mantenimiento['nombre_taller']} - " : "Mantenimiento interno - ") .
                                       "Código Serie: {$mantenimiento['codigo_serie']} - " .
                                       "Descripción: " . (strlen($mantenimiento['descripcion_mantenimiento']) > 50 ? 
                                       substr($mantenimiento['descripcion_mantenimiento'], 0, 50) . "..." : $mantenimiento['descripcion_mantenimiento']) . " - " .
                                       "Fecha: {$mantenimiento['fecha_mantenimiento']} - " .
                                       "Costo: Q " . number_format($mantenimiento['costo_mantenimiento'], 2);
                
                registrarBitacora(
                    $conn,
                    "Mantenimiento Muebles",
                    "Eliminar",
                    $descripcion_bitacora
                );
                
                $_SESSION['mensaje'] = "Mantenimiento eliminado exitosamente";
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
    header('Location: mantenimiento_muebles.php');
    exit();
}

// Funciones auxiliares para bitácoras
function obtenerInfoMobiliario($id_mobiliario) {
    $conn = conectar();
    $sql = "SELECT im.nombre_mobiliario, tm.descripcion as tipo_mobiliario
            FROM inventario_mobiliario im
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            WHERE im.id_mobiliario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mobiliario);
    $stmt->execute();
    $result = $stmt->get_result();
    $mobiliario = $result->fetch_assoc();
    $stmt->close();
    desconectar($conn);
    return $mobiliario;
}

function obtenerInfoTaller($id_taller) {
    $conn = conectar();
    $sql = "SELECT nombre_taller, telefono FROM talleres WHERE id_taller = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_taller);
    $stmt->execute();
    $result = $stmt->get_result();
    $taller = $result->fetch_assoc();
    $stmt->close();
    desconectar($conn);
    return $taller;
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
                            <td class="text-end fw-bold">Q <?php echo number_format($mantenimiento['costo_mantenimiento'], 2); ?></td>
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
                                <form method="post" style="display:inline;" data-eliminar="true">
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/mantenimiento_muebles.js"></script>
</body>
</html>