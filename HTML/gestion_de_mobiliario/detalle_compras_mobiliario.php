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
        case 'crear_detalle':
            crearDetalleCompra();
            break;
        case 'actualizar_detalle':
            actualizarDetalleCompra();
            break;
        case 'eliminar_detalle':
            eliminarDetalleCompra();
            break;
    }
}

function crearDetalleCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_mobiliario = $_POST['id_compra_mobiliario'] ?? '';
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    $cantidad_de_compra = $_POST['cantidad_de_compra'] ?? 0;
    $costo_unitario = $_POST['costo_unitario'] ?? 0;
    $monto_total_de_mobiliario = $cantidad_de_compra * $costo_unitario;
    
    $sql = "INSERT INTO detalle_compra_mobiliario (id_compra_mobiliario, id_mobiliario, cantidad_de_compra, costo_unitario, monto_total_de_mobiliario) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiidd", $id_compra_mobiliario, $id_mobiliario, $cantidad_de_compra, $costo_unitario, $monto_total_de_mobiliario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Detalle de compra registrado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar detalle de compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: detalle_compras_mobiliario.php');
    exit();
}

function actualizarDetalleCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_mobiliario = $_POST['id_compra_mobiliario'] ?? '';
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    $cantidad_de_compra = $_POST['cantidad_de_compra'] ?? 0;
    $costo_unitario = $_POST['costo_unitario'] ?? 0;
    $monto_total_de_mobiliario = $cantidad_de_compra * $costo_unitario;
    
    $sql = "UPDATE detalle_compra_mobiliario SET cantidad_de_compra = ?, costo_unitario = ?, monto_total_de_mobiliario = ? 
            WHERE id_compra_mobiliario = ? AND id_mobiliario = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iddii", $cantidad_de_compra, $costo_unitario, $monto_total_de_mobiliario, $id_compra_mobiliario, $id_mobiliario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Detalle de compra actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar detalle de compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: detalle_compras_mobiliario.php');
    exit();
}

function eliminarDetalleCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_mobiliario = $_POST['id_compra_mobiliario'] ?? '';
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    
    // Validar que los IDs no estén vacíos
    if (empty($id_compra_mobiliario) || empty($id_mobiliario)) {
        $_SESSION['mensaje'] = "Error: No se proporcionaron IDs válidos para eliminar el detalle.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: detalle_compras_mobiliario.php');
        exit();
    }
    
    try {
        // Primero verificar si el detalle existe
        $check_detalle = $conn->prepare("SELECT * FROM detalle_compra_mobiliario WHERE id_compra_mobiliario = ? AND id_mobiliario = ?");
        if (!$check_detalle) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_detalle->bind_param("ii", $id_compra_mobiliario, $id_mobiliario);
        
        if (!$check_detalle->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_detalle->error);
        }
        
        $result_detalle = $check_detalle->get_result();
        
        if ($result_detalle->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El detalle de compra que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_detalle->close();
            desconectar($conn);
            header('Location: detalle_compras_mobiliario.php');
            exit();
        }
        $check_detalle->close();
        
        // Proceder con la eliminación
        $sql = "DELETE FROM detalle_compra_mobiliario WHERE id_compra_mobiliario = ? AND id_mobiliario = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("ii", $id_compra_mobiliario, $id_mobiliario);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Detalle de compra eliminado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar el detalle de compra. Es posible que ya haya sido eliminado o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar el detalle de compra porque está siendo utilizado en otros registros del sistema.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar detalle de compra: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el detalle de compra porque está siendo utilizado en otros registros del sistema.";
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
    header('Location: detalle_compras_mobiliario.php');
    exit();
}

// Obtener todos los detalles de compra para mostrar en la tabla
function obtenerDetallesCompra() {
    $conn = conectar();
    
    $sql = "SELECT dcm.*, 
                   cm.fecha_de_compra,
                   p.nombre_proveedor,
                   im.nombre_mobiliario,
                   tm.descripcion as tipo_mobiliario
            FROM detalle_compra_mobiliario dcm
            LEFT JOIN compras_mobiliario cm ON dcm.id_compra_mobiliario = cm.id_compra_mobiliario
            LEFT JOIN proveedores p ON cm.id_proveedor = p.id_proveedor
            LEFT JOIN inventario_mobiliario im ON dcm.id_mobiliario = im.id_mobiliario
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
            ORDER BY cm.fecha_de_compra DESC, dcm.id_compra_mobiliario";
    
    $resultado = $conn->query($sql);
    $detalles = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $detalles[] = $fila;
        }
    }
    
    desconectar($conn);
    return $detalles;
}

// Obtener compras para el select
function obtenerCompras() {
    $conn = conectar();
    
    $sql = "SELECT cm.id_compra_mobiliario, cm.fecha_de_compra, p.nombre_proveedor
            FROM compras_mobiliario cm
            LEFT JOIN proveedores p ON cm.id_proveedor = p.id_proveedor
            ORDER BY cm.fecha_de_compra DESC";
    
    $resultado = $conn->query($sql);
    $compras = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $compras[] = $fila;
        }
    }
    
    desconectar($conn);
    return $compras;
}

// Obtener mobiliario para el select
function obtenerMobiliario() {
    $conn = conectar();
    
    $sql = "SELECT im.id_mobiliario, im.nombre_mobiliario, tm.descripcion as tipo_mobiliario
            FROM inventario_mobiliario im
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario
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

$detalles = obtenerDetallesCompra();
$compras = obtenerCompras();
$mobiliarios = obtenerMobiliario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Compras de Mobiliario - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">DETALLE DE COMPRAS DE MOBILIARIO</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - DETALLE DE COMPRAS DE MOBILIARIO</h2>

            <form id="form-detalle" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_detalle">
                <input type="hidden" id="id_compra_mobiliario_original" name="id_compra_mobiliario_original" value="">
                <input type="hidden" id="id_mobiliario_original" name="id_mobiliario_original" value="">
                
                <div class="col-md-4">
                    <label class="form-label" for="id_compra_mobiliario">Compra:</label>
                    <select class="form-control" id="id_compra_mobiliario" name="id_compra_mobiliario" required>
                        <option value="">Seleccione una compra</option>
                        <?php foreach($compras as $compra): ?>
                            <option value="<?php echo $compra['id_compra_mobiliario']; ?>">
                                Compra #<?php echo $compra['id_compra_mobiliario']; ?> - 
                                <?php echo htmlspecialchars($compra['nombre_proveedor']); ?> - 
                                <?php echo htmlspecialchars($compra['fecha_de_compra']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="id_mobiliario">Mobiliario:</label>
                    <select class="form-control" id="id_mobiliario" name="id_mobiliario" required>
                        <option value="">Seleccione mobiliario</option>
                        <?php foreach($mobiliarios as $mob): ?>
                            <option value="<?php echo $mob['id_mobiliario']; ?>">
                                <?php echo htmlspecialchars($mob['nombre_mobiliario']); ?> - 
                                <?php echo htmlspecialchars($mob['tipo_mobiliario']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label" for="cantidad_de_compra">Cantidad:</label>
                    <input type="number" class="form-control" id="cantidad_de_compra" name="cantidad_de_compra" 
                           min="1" required value="1">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label" for="costo_unitario">Costo Unitario (Q):</label>
                    <input type="number" step="0.01" class="form-control" id="costo_unitario" name="costo_unitario" 
                           min="0" required placeholder="0.00">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Monto Total (Q):</label>
                    <input type="text" class="form-control" id="monto_total_display" readonly 
                           style="background-color: #e9ecef; font-weight: bold;" value="Q 0.00">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">DETALLES DE COMPRAS REGISTRADAS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-detalles">
                    <thead class="table-dark">
                        <tr>
                            <th>Compra ID</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Mobiliario</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Costo Unitario</th>
                            <th>Monto Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($detalles as $detalle): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detalle['id_compra_mobiliario']); ?></td>
                            <td><?php echo htmlspecialchars($detalle['nombre_proveedor'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($detalle['fecha_de_compra']); ?></td>
                            <td><?php echo htmlspecialchars($detalle['nombre_mobiliario'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($detalle['tipo_mobiliario'] ?? 'N/A'); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($detalle['cantidad_de_compra']); ?></td>
                            <td class="text-end fw-bold">Q <?php echo number_format($detalle['costo_unitario'], 2); ?></td>
                            <td class="text-end fw-bold">Q <?php echo number_format($detalle['monto_total_de_mobiliario'], 2); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-compra="<?php echo $detalle['id_compra_mobiliario']; ?>"
                                        data-mobiliario="<?php echo $detalle['id_mobiliario']; ?>"
                                        data-cantidad="<?php echo $detalle['cantidad_de_compra']; ?>"
                                        data-costo="<?php echo $detalle['costo_unitario']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar_detalle">
                                    <input type="hidden" name="id_compra_mobiliario" value="<?php echo $detalle['id_compra_mobiliario']; ?>">
                                    <input type="hidden" name="id_mobiliario" value="<?php echo $detalle['id_mobiliario']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($detalles)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay detalles de compra registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/detalle_compras_mobiliario.js"></script>
</body>
</html>