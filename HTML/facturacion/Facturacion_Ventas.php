<?php
// Facturación - Sistema Web Restaurante
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// ---------------------- CRUD PRINCIPAL ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    switch ($operacion) {
        case 'crear':
            crearFactura();
            break;
        case 'actualizar':
            actualizarFactura();
            break;
        case 'eliminar':
            eliminarFactura();
            break;
        case 'agregar_detalle':
            agregarDetalleFactura();
            break;
        case 'eliminar_detalle':
            eliminarDetalleFactura();
            break;
        case 'agregar_detalle_cobro':
            agregarDetalleCobro();
            break;
        case 'eliminar_detalle_cobro':
            eliminarDetalleCobro();
            break;
    }
}

function crearFactura() {
    $conn = conectar();
    $codigo_serie = $_POST['codigo_serie'] ?? '';
    $fecha_emision = $_POST['fecha_emision'] ?? '';
    $nit = $_POST['nit'] ?? '';
    $monto_total = $_POST['monto_total'] ?? 0;

    $sql = "INSERT INTO facturas (codigo_serie, fecha_emision, nit, monto_total)
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssd', $codigo_serie, $fecha_emision, $nit, $monto_total);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Factura creada exitosamente' : 'Error al crear factura: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: ../Facturacion_Ventas.php');
    exit();
}

function actualizarFactura() {
    $conn = conectar();
    $id_factura = $_POST['id_factura'] ?? '';
    $codigo_serie = $_POST['codigo_serie'] ?? '';
    $fecha_emision = $_POST['fecha_emision'] ?? '';
    $nit = $_POST['nit'] ?? '';
    $monto_total = $_POST['monto_total'] ?? 0;

    $sql = "UPDATE facturas SET codigo_serie=?, fecha_emision=?, nit=?, monto_total=? WHERE id_factura=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssdi', $codigo_serie, $fecha_emision, $nit, $monto_total, $id_factura);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Factura actualizada exitosamente' : 'Error al actualizar factura: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: ../Facturacion_Ventas.php');
    exit();
}

function eliminarFactura() {
    $conn = conectar();
    $id_factura = $_POST['id_factura'] ?? '';
    $sql = "DELETE FROM facturas WHERE id_factura = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_factura);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Factura eliminada exitosamente' : 'Error al eliminar factura: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: ../Facturacion_Ventas.php');
    exit();
}

function agregarDetalleFactura() {
    $conn = conectar();
    $id_factura = $_POST['id_factura'] ?? '';
    $id_plato = $_POST['id_plato'] ?? null;
    $id_bebida = $_POST['id_bebida'] ?? null;
    $cantidad_platos = $_POST['cantidad_platos'] ?? 0;
    $cantidad_bebidas = $_POST['cantidad_bebidas'] ?? 0;
    $precio_total = $_POST['precio_total'] ?? 0;

    $sql = "INSERT INTO detalle_factura (id_factura, id_plato, id_bebida, cantidad_platos, cantidad_bebidas, precio_total)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiiidd', $id_factura, $id_plato, $id_bebida, $cantidad_platos, $cantidad_bebidas, $precio_total);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Detalle agregado exitosamente' : 'Error al agregar detalle: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: ../Facturacion_Ventas.php?id_factura=' . $id_factura);
    exit();
}

function eliminarDetalleFactura() {
    $conn = conectar();
    $id_factura = $_POST['id_factura'] ?? '';
    $id_plato = $_POST['id_plato'] ?? '';
    $id_bebida = $_POST['id_bebida'] ?? '';

    $sql = "DELETE FROM detalle_factura WHERE id_factura = ? AND id_plato = ? AND id_bebida = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $id_factura, $id_plato, $id_bebida);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Detalle eliminado exitosamente' : 'Error al eliminar detalle: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: ../Facturacion_Ventas.php?id_factura=' . $id_factura);
    exit();
}

function agregarDetalleCobro() {
    $conn = conectar();
    $id_factura = $_POST['id_factura'] ?? '';
    $id_tipo_cobro = $_POST['id_tipo_cobro'] ?? '';
    $monto_detalle = $_POST['monto_detalle'] ?? 0;

    $sql = "INSERT INTO detalle_cobro (id_factura, id_tipo_cobro, monto_detalle)
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iid', $id_factura, $id_tipo_cobro, $monto_detalle);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Detalle de cobro agregado exitosamente' : 'Error al agregar detalle de cobro: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: ../Facturacion_Ventas.php?id_factura=' . $id_factura);
    exit();
}

function eliminarDetalleCobro() {
    $conn = conectar();
    $id_factura = $_POST['id_factura'] ?? '';
    $id_tipo_cobro = $_POST['id_tipo_cobro'] ?? '';

    $sql = "DELETE FROM detalle_cobro WHERE id_factura = ? AND id_tipo_cobro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $id_factura, $id_tipo_cobro);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Detalle de cobro eliminado exitosamente' : 'Error al eliminar detalle de cobro: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: ../Facturacion_Ventas.php?id_factura=' . $id_factura);
    exit();
}

function obtenerFacturas() {
    $conn = conectar();
    $sql = "SELECT * FROM facturas ORDER BY id_factura";
    $resultado = $conn->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    desconectar($conn);
    return $data;
}

function obtenerDetallesFactura($id_factura) {
    $conn = conectar();
    $sql = "SELECT df.*, 
                   p.nombre_plato,
                   b.descripcion as descripcion_bebida,
                   (df.cantidad_platos + df.cantidad_bebidas) as cantidad_total
            FROM detalle_factura df
            LEFT JOIN platos p ON df.id_plato = p.id_plato
            LEFT JOIN bebidas b ON df.id_bebida = b.id_bebida
            WHERE df.id_factura = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_factura);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    $stmt->close();
    desconectar($conn);
    return $data;
}

function obtenerDetallesCobro($id_factura) {
    $conn = conectar();
    $sql = "SELECT dc.*, tc.tipo_cobro 
            FROM detalle_cobro dc
            LEFT JOIN tipos_cobro tc ON dc.id_tipo_cobro = tc.id_tipo_cobro
            WHERE dc.id_factura = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_factura);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    $stmt->close();
    desconectar($conn);
    return $data;
}

// ---------------------- MAPEOS ----------------------
$facturas = obtenerFacturas();
$conn = conectar();

// Platos
$platos_map = [];
$res = $conn->query("SELECT id_plato, nombre_plato, precio_unitario FROM platos");
while ($row = $res->fetch_assoc()) {
    $platos_map[$row['id_plato']] = $row['nombre_plato'] . ' - Q' . $row['precio_unitario'];
}

// Bebidas
$bebidas_map = [];
$res = $conn->query("SELECT id_bebida, descripcion, precio_unitario FROM bebidas");
while ($row = $res->fetch_assoc()) {
    $bebidas_map[$row['id_bebida']] = $row['descripcion'] . ' - Q' . $row['precio_unitario'];
}

// Clientes
$clientes_map = [];
$res = $conn->query("SELECT NIT, nombre FROM clientes");
while ($row = $res->fetch_assoc()) {
    $clientes_map[$row['NIT']] = $row['nombre'];
}

// Tipos de Cobro
$tipos_cobro_map = [];
$res = $conn->query("SELECT id_tipo_cobro, tipo_cobro FROM tipos_cobro");
while ($row = $res->fetch_assoc()) {
    $tipos_cobro_map[$row['id_tipo_cobro']] = $row['tipo_cobro'];
}

desconectar($conn);

// Obtener detalles si estamos viendo una factura específica
$detalles_factura = [];
$detalles_cobro = [];
$factura_actual = null;
if (isset($_GET['id_factura']) && !empty($_GET['id_factura'])) {
    $id_factura_actual = $_GET['id_factura'];
    $detalles_factura = obtenerDetallesFactura($id_factura_actual);
    $detalles_cobro = obtenerDetallesCobro($id_factura_actual);
    
    // Obtener datos de la factura actual
    $conn = conectar();
    $sql = "SELECT * FROM facturas WHERE id_factura = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_factura_actual);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $factura_actual = $resultado->fetch_assoc();
    $stmt->close();
    desconectar($conn);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Facturación</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Gestión de Facturación</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
        </ul>
    </div>
</header>

<main class="container my-4">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <script>
            // Mensaje generado en servidor — lo mostramos con SweetAlert en el cliente si está disponible
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
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card__title text-primary mb-4">Formulario de Factura</h2>

        <form id="form-factura" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_factura" id="id_factura">

            <div class="col-md-3">
                <label class="form-label">Código de Serie</label>
                <input type="text" class="form-control" name="codigo_serie" id="codigo_serie" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Fecha de Emisión</label>
                <input type="datetime-local" class="form-control" name="fecha_emision" id="fecha_emision" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">NIT Cliente</label>
                <select class="form-select" name="nit" id="nit" required>
                    <option value="">-- Seleccionar cliente --</option>
                    <?php foreach ($clientes_map as $nit => $nombre): ?>
                        <option value="<?= $nit; ?>"><?= $nit . ' - ' . htmlspecialchars($nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Monto Total</label>
                <input type="number" step="0.01" class="form-control" name="monto_total" id="monto_total" required>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <!-- Sección para agregar detalles de factura -->
        <?php if (isset($_GET['id_factura']) && !empty($_GET['id_factura'])): ?>
        <div class="mt-5">
            <h3 class="mb-3">Detalles de Factura</h3>
            
            <form method="post" class="row g-3 mb-4">
                <input type="hidden" name="operacion" value="agregar_detalle">
                <input type="hidden" name="id_factura" value="<?= $_GET['id_factura']; ?>">
                
                <div class="col-md-3">
                    <label class="form-label">Plato</label>
                    <select class="form-select" name="id_plato">
                        <option value="">-- Seleccionar plato --</option>
                        <?php foreach ($platos_map as $id => $desc): ?>
                            <option value="<?= $id; ?>"><?= $id . ' - ' . htmlspecialchars($desc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Bebida</label>
                    <select class="form-select" name="id_bebida">
                        <option value="">-- Seleccionar bebida --</option>
                        <?php foreach ($bebidas_map as $id => $desc): ?>
                            <option value="<?= $id; ?>"><?= $id . ' - ' . htmlspecialchars($desc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Cant. Platos</label>
                    <input type="number" class="form-control" name="cantidad_platos" value="0" min="0">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Cant. Bebidas</label>
                    <input type="number" class="form-control" name="cantidad_bebidas" value="0" min="0">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Precio Total</label>
                    <input type="number" step="0.01" class="form-control" name="precio_total" value="0" min="0">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Agregar Detalle</button>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Plato</th>
                            <th>Bebida</th>
                            <th>Cant. Platos</th>
                            <th>Cant. Bebidas</th>
                            <th>Precio Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detalles_factura as $detalle): ?>
                        <tr>
                            <td><?= htmlspecialchars($detalle['nombre_plato'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($detalle['descripcion_bebida'] ?? '-'); ?></td>
                            <td><?= $detalle['cantidad_platos']; ?></td>
                            <td><?= $detalle['cantidad_bebidas']; ?></td>
                            <td>Q<?= number_format($detalle['precio_total'], 2); ?></td>
                            <td class="text-center">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="operacion" value="eliminar_detalle">
                                    <input type="hidden" name="id_factura" value="<?= $_GET['id_factura']; ?>">
                                    <input type="hidden" name="id_plato" value="<?= $detalle['id_plato']; ?>">
                                    <input type="hidden" name="id_bebida" value="<?= $detalle['id_bebida']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($detalles_factura)): ?>
                        <tr><td colspan="6" class="text-center">No hay detalles de factura</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Sección para agregar detalles de cobro -->
        <div class="mt-5">
            <h3 class="mb-3">Detalles de Cobro</h3>
            
            <form method="post" class="row g-3 mb-4">
                <input type="hidden" name="operacion" value="agregar_detalle_cobro">
                <input type="hidden" name="id_factura" value="<?= $_GET['id_factura']; ?>">
                
                <div class="col-md-4">
                    <label class="form-label">Tipo de Cobro</label>
                    <select class="form-select" name="id_tipo_cobro" required>
                        <option value="">-- Seleccionar tipo --</option>
                        <?php foreach ($tipos_cobro_map as $id => $tipo): ?>
                            <option value="<?= $id; ?>"><?= htmlspecialchars($tipo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Monto</label>
                    <input type="number" step="0.01" class="form-control" name="monto_detalle" required min="0">
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Agregar Pago</button>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Tipo de Cobro</th>
                            <th>Monto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($detalles_cobro as $detalle): ?>
                        <tr>
                            <td><?= htmlspecialchars($detalle['tipo_cobro']); ?></td>
                            <td>Q<?= number_format($detalle['monto_detalle'], 2); ?></td>
                            <td class="text-center">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="operacion" value="eliminar_detalle_cobro">
                                    <input type="hidden" name="id_factura" value="<?= $_GET['id_factura']; ?>">
                                    <input type="hidden" name="id_tipo_cobro" value="<?= $detalle['id_tipo_cobro']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($detalles_cobro)): ?>
                        <tr><td colspan="3" class="text-center">No hay detalles de cobro</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Facturas</h3>
        </div>

        <div id="lista-facturas" class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Código Serie</th>
                        <th>Fecha Emisión</th>
                        <th>NIT Cliente</th>
                        <th>Monto Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($facturas as $factura): ?>
                    <tr>
                        <td><?= $factura['id_factura']; ?></td>
                        <td><?= htmlspecialchars($factura['codigo_serie']); ?></td>
                        <td><?= $factura['fecha_emision']; ?></td>
                        <td><?= $factura['nit'] . ' - ' . ($clientes_map[$factura['nit']] ?? ''); ?></td>
                        <td>Q<?= number_format($factura['monto_total'], 2); ?></td>
                        <td class="text-center">
                            <a href="../Facturacion_Ventas.php?id_factura=<?= $factura['id_factura']; ?>" class="btn btn-info btn-sm">Ver Detalles</a>
                            <button type="button" class="btn btn-primary btn-sm editar-btn"
                                data-id="<?= $factura['id_factura']; ?>"
                                data-codigo="<?= htmlspecialchars($factura['codigo_serie']); ?>"
                                data-fecha="<?= $factura['fecha_emision']; ?>"
                                data-nit="<?= $factura['nit']; ?>"
                                data-monto="<?= $factura['monto_total']; ?>">Editar</button>
                            <form method="post" style="display:inline;margin-left:6px;" data-eliminar="true">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_factura" value="<?= $factura['id_factura']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($facturas)): ?>
                    <tr><td colspan="6" class="text-center">No hay facturas registradas</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Facturacion.js"></script>
</body>
</html>