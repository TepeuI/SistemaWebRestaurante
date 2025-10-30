<?php
session_start();
require_once '../conexion.php';

// --- Verificar sesión ---
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// --- Procesar operaciones CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';

    match ($operacion) {
        'crear' => crearFactura(),
        'actualizar' => actualizarFactura(),
        'eliminar' => eliminarFactura(),
        default => null
    };
}

// --- Funciones CRUD ---
function crearFactura(): void {
    $conn = conectar();
    
    try {
        $conn->begin_transaction();
        
        $codigo_serie = trim($_POST['codigo_serie'] ?? '');
        $fecha_emision = trim($_POST['fecha_emision'] ?? '');
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $id_orden = (int)($_POST['id_orden'] ?? 0);
        
        // Obtener NIT del cliente seleccionado
        $nit_cliente = obtenerNitCliente($conn, $id_cliente);
        
        if (!$nit_cliente) {
            throw new Exception("Cliente no encontrado");
        }
        
        // Calcular monto total de la orden
        $monto_total = calcularMontoOrden($conn, $id_orden);
        
        $sql = "INSERT INTO facturas (codigo_serie, fecha_emision, id_cliente, nit_cliente, monto_total_q, id_orden) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisdi", $codigo_serie, $fecha_emision, $id_cliente, $nit_cliente, $monto_total, $id_orden);
        
        if ($stmt->execute()) {
            $id_factura = $conn->insert_id;
            
            // Procesar detalles de cobro si existen
            if (isset($_POST['detalles_cobro']) && is_array($_POST['detalles_cobro'])) {
                foreach ($_POST['detalles_cobro'] as $detalle) {
                    $id_tipo_cobro = (int)($detalle['id_tipo_cobro'] ?? 0);
                    $monto_detalle = (float)($detalle['monto_detalle_q'] ?? 0);
                    
                    if ($id_tipo_cobro > 0 && $monto_detalle > 0) {
                        $sql_detalle = "INSERT INTO detalle_cobro (id_factura, id_tipo_cobro, monto_detalle_q) 
                                       VALUES (?, ?, ?)";
                        $stmt_detalle = $conn->prepare($sql_detalle);
                        $stmt_detalle->bind_param("iid", $id_factura, $id_tipo_cobro, $monto_detalle);
                        $stmt_detalle->execute();
                        $stmt_detalle->close();
                    }
                }
            }
            
            $conn->commit();
            $_SESSION['mensaje'] = "Factura creada exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            throw new Exception("Error al crear factura: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensaje'] = $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    } finally {
        desconectar($conn);
        header('Location: Facturacion_Ventas.php');
        exit();
    }
}

function actualizarFactura(): void {
    $conn = conectar();
    
    try {
        $conn->begin_transaction();
        
        $id_factura = (int)($_POST['id_factura'] ?? 0);
        $codigo_serie = trim($_POST['codigo_serie'] ?? '');
        $fecha_emision = trim($_POST['fecha_emision'] ?? '');
        $id_cliente = (int)($_POST['id_cliente'] ?? 0);
        $id_orden = (int)($_POST['id_orden'] ?? 0);
        
        // Obtener NIT del cliente seleccionado
        $nit_cliente = obtenerNitCliente($conn, $id_cliente);
        
        if (!$nit_cliente) {
            throw new Exception("Cliente no encontrado");
        }
        
        $monto_total = calcularMontoOrden($conn, $id_orden);
        
        $sql = "UPDATE facturas SET codigo_serie = ?, fecha_emision = ?, id_cliente = ?, 
                nit_cliente = ?, monto_total_q = ?, id_orden = ? WHERE id_factura = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisdii", $codigo_serie, $fecha_emision, $id_cliente, $nit_cliente, $monto_total, $id_orden, $id_factura);
        
        if ($stmt->execute()) {
            // Eliminar detalles de cobro existentes y agregar nuevos
            $sql_delete = "DELETE FROM detalle_cobro WHERE id_factura = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $id_factura);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            // Procesar nuevos detalles de cobro
            if (isset($_POST['detalles_cobro']) && is_array($_POST['detalles_cobro'])) {
                foreach ($_POST['detalles_cobro'] as $detalle) {
                    $id_tipo_cobro = (int)($detalle['id_tipo_cobro'] ?? 0);
                    $monto_detalle = (float)($detalle['monto_detalle_q'] ?? 0);
                    
                    if ($id_tipo_cobro > 0 && $monto_detalle > 0) {
                        $sql_detalle = "INSERT INTO detalle_cobro (id_factura, id_tipo_cobro, monto_detalle_q) 
                                       VALUES (?, ?, ?)";
                        $stmt_detalle = $conn->prepare($sql_detalle);
                        $stmt_detalle->bind_param("iid", $id_factura, $id_tipo_cobro, $monto_detalle);
                        $stmt_detalle->execute();
                        $stmt_detalle->close();
                    }
                }
            }
            
            $conn->commit();
            $_SESSION['mensaje'] = "Factura actualizada exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            throw new Exception("Error al actualizar factura: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensaje'] = $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    } finally {
        desconectar($conn);
        header('Location: Facturacion_Ventas.php');
        exit();
    }
}

function eliminarFactura(): void {
    $conn = conectar();
    $id_factura = (int)($_POST['id_factura'] ?? 0);

    try {
        $conn->begin_transaction();
        
        // Primero eliminar detalles de cobro
        $sql_detalles = "DELETE FROM detalle_cobro WHERE id_factura = ?";
        $stmt_detalles = $conn->prepare($sql_detalles);
        $stmt_detalles->bind_param("i", $id_factura);
        $stmt_detalles->execute();
        $stmt_detalles->close();
        
        // Luego eliminar la factura
        $sql = "DELETE FROM facturas WHERE id_factura = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_factura);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['mensaje'] = "Factura eliminada exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            throw new Exception("Error al eliminar factura: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        if ((int)$e->getCode() === 1451) {
            $_SESSION['mensaje'] = "No se puede eliminar: la factura tiene registros relacionados.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar factura: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "error";
        }
    } finally {
        desconectar($conn);
        header('Location: Facturacion_Ventas.php');
        exit();
    }
}

function obtenerNitCliente($conn, $id_cliente): string {
    $sql = "SELECT nit FROM clientes WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['nit'] ?? '';
}

function calcularMontoOrden($conn, $id_orden): float {
    $sql = "SELECT SUM(total) as total_orden FROM orden WHERE id_orden = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_orden);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (float)($row['total_orden'] ?? 0);
}

// --- Funciones para obtener datos ---
function obtenerClientes(): array {
    $conn = conectar();
    $sql = "SELECT id_cliente, nombre, apellido, nit, telefono, correo 
            FROM clientes 
            ORDER BY nombre, apellido ASC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

function obtenerFacturas(): array {
    $conn = conectar();
    $sql = "SELECT f.*, o.id_mesa, c.nombre, c.apellido,
                   GROUP_CONCAT(DISTINCT p.nombre_plato SEPARATOR ', ') as platos,
                   GROUP_CONCAT(DISTINCT b.descripcion SEPARATOR ', ') as bebidas
            FROM facturas f
            LEFT JOIN orden o ON f.id_orden = o.id_orden
            LEFT JOIN clientes c ON f.id_cliente = c.id_cliente
            LEFT JOIN platos p ON o.id_plato = p.id_plato
            LEFT JOIN bebidas b ON o.id_bebida = b.id_bebida
            GROUP BY f.id_factura
            ORDER BY f.fecha_emision DESC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

function obtenerTiposCobro(): array {
    $conn = conectar();
    $sql = "SELECT id_tipo_cobro, tipo_cobro FROM tipos_cobro ORDER BY tipo_cobro ASC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

function obtenerOrdenesDisponibles(): array {
    $conn = conectar();
    $sql = "SELECT o.id_orden, o.id_mesa, o.fecha_orden, 
                   p.nombre_plato, b.descripcion as bebida, o.total,
                   (SELECT COUNT(*) FROM facturas f WHERE f.id_orden = o.id_orden) as tiene_factura
            FROM orden o
            LEFT JOIN platos p ON o.id_plato = p.id_plato
            LEFT JOIN bebidas b ON o.id_bebida = b.id_bebida
            HAVING tiene_factura = 0
            ORDER BY o.fecha_orden DESC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

function obtenerDetallesCobro($id_factura): array {
    $conn = conectar();
    $sql = "SELECT dc.*, tc.tipo_cobro 
            FROM detalle_cobro dc
            JOIN tipos_cobro tc ON dc.id_tipo_cobro = tc.id_tipo_cobro
            WHERE dc.id_factura = ?
            ORDER BY dc.id_detalle_cobro ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_factura);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    desconectar($conn);
    return $rows;
}

$clientes = obtenerClientes();
$facturas = obtenerFacturas();
$tipos_cobro = obtenerTiposCobro();
$ordenes_disponibles = obtenerOrdenesDisponibles();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Facturación y Ventas - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <style>
        body, input, button, table { font-family: 'Poppins', Arial, sans-serif; }
        .mensaje { padding:10px; margin:10px 0; border-radius:5px; }
        .mensaje.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .mensaje.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .table-responsive { max-height:500px; overflow-y:auto; }
        .detalle-cobro-item { border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .monto-total { font-size: 1.2em; font-weight: bold; color: #28a745; }
        .orden-info { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .cliente-info { background: #e9ecef; padding: 8px; border-radius: 5px; margin-top: 5px; }
    </style>
</head>
<body>
<header class="mb-4">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <h1 class="mb-0">FACTURACIÓN Y VENTAS</h1>
        <a href="../menu_empleados.php" class="btn btn-outline-dark">Regresar</a>
    </div>
</header>

<main class="container my-4">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <script>
            window.__mensaje = {
                tipo: <?= json_encode($_SESSION['tipo_mensaje'] ?? 'info'); ?>,
                text: <?= json_encode($_SESSION['mensaje'] ?? ''); ?>
            };
        </script>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card__title mb-3">Formulario de Facturación</h2>

        <form id="form-facturacion" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_factura" id="id_factura" value="">

            <div class="col-md-3">
                <label for="codigo_serie" class="form-label required">Código Serie</label>
                <input type="text" id="codigo_serie" name="codigo_serie" class="form-control" placeholder="Ej: F001-001" required>
            </div>

            <div class="col-md-3">
                <label for="fecha_emision" class="form-label required">Fecha Emisión</label>
                <input type="datetime-local" id="fecha_emision" name="fecha_emision" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label for="id_cliente" class="form-label required">Cliente</label>
                <select id="id_cliente" name="id_cliente" class="form-select" required>
                    <option value="">Seleccionar cliente...</option>
                    <?php foreach($clientes as $cliente): ?>
                        <option value="<?= $cliente['id_cliente'] ?>" 
                                data-nit="<?= htmlspecialchars($cliente['nit']) ?>"
                                data-info="<?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?>">
                            <?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?> - NIT: <?= htmlspecialchars($cliente['nit']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="cliente-info-display" class="cliente-info d-none">
                    <small>NIT: <span id="nit-display"></span></small>
                </div>
            </div>

            <div class="col-md-6">
                <label for="id_orden" class="form-label required">Orden</label>
                <select id="id_orden" name="id_orden" class="form-select" required>
                    <option value="">Seleccionar orden...</option>
                    <?php foreach($ordenes_disponibles as $orden): ?>
                        <option value="<?= $orden['id_orden'] ?>" 
                                data-total="<?= $orden['total'] ?>"
                                data-info="Mesa: <?= $orden['id_mesa'] ?> - <?= $orden['nombre_plato'] ? $orden['nombre_plato'] : $orden['bebida'] ?>">
                            Orden #<?= $orden['id_orden'] ?> - Mesa <?= $orden['id_mesa'] ?> - Q<?= number_format($orden['total'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Monto Total de la Orden</label>
                <div class="orden-info">
                    <span id="monto_orden_display" class="monto-total">Q0.00</span>
                </div>
            </div>

            <!-- Sección para Detalles de Cobro -->
            <div class="col-12">
                <h4 class="mt-4 mb-3">Detalles de Cobro</h4>
                <div id="detalles-cobro-container">
                    <!-- Los detalles de cobro se agregarán dinámicamente aquí -->
                </div>
                <button type="button" id="btn-agregar-cobro" class="btn btn-outline-primary btn-sm mt-2">
                    + Agregar Método de Cobro
                </button>
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning d-none">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger d-none">Cancelar</button>
            <div class="ms-auto text-muted align-self-center">Fila activa: <span id="fila-activa">ninguna</span></div>
        </div>

        <h2 class="card__title mb-3 mt-5">Historial de Facturas</h2>
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-facturas">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Código Serie</th>
                        <th>Fecha Emisión</th>
                        <th>Cliente</th>
                        <th>NIT</th>
                        <th>Monto Total</th>
                        <th>Orden</th>
                        <th>Detalles</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($facturas): ?>
                        <?php foreach($facturas as $factura): 
                            $detalles_cobro = obtenerDetallesCobro($factura['id_factura']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($factura['id_factura']); ?></td>
                            <td><?= htmlspecialchars($factura['codigo_serie']); ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($factura['fecha_emision'])); ?></td>
                            <td><?= htmlspecialchars($factura['nombre'] . ' ' . $factura['apellido']); ?></td>
                            <td><?= htmlspecialchars($factura['nit_cliente']); ?></td>
                            <td>Q<?= number_format($factura['monto_total_q'], 2); ?></td>
                            <td>#<?= htmlspecialchars($factura['id_orden']); ?></td>
                            <td>
                                <small>
                                    <?= $factura['platos'] ? 'Platos: ' . htmlspecialchars($factura['platos']) : ''; ?>
                                    <?= $factura['bebidas'] ? 'Bebidas: ' . htmlspecialchars($factura['bebidas']) : ''; ?>
                                </small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary editar-btn"
                                    data-id="<?= $factura['id_factura']; ?>"
                                    data-codigo-serie="<?= htmlspecialchars($factura['codigo_serie']); ?>"
                                    data-fecha-emision="<?= date('Y-m-d\TH:i', strtotime($factura['fecha_emision'])); ?>"
                                    data-id-cliente="<?= $factura['id_cliente']; ?>"
                                    data-id-orden="<?= $factura['id_orden']; ?>"
                                    data-detalles-cobro='<?= json_encode($detalles_cobro); ?>'>
                                    Editar
                                </button>
                                <form method="post" class="d-inline" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_factura" value="<?= $factura['id_factura']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">No hay facturas registradas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<!-- Incluir SweetAlert2 y el JS de facturación -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Datos disponibles para JS
    window.tiposCobro = <?= json_encode($tipos_cobro); ?>;
    window.clientes = <?= json_encode($clientes); ?>;
</script>
<script src="../../javascript/facturacion.js"></script>
</body>
</html>