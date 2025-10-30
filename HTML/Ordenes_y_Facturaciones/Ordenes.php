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
        'crear' => crearOrden(),
        'actualizar' => actualizarOrden(),
        'eliminar' => eliminarOrden(),
        default => null
    };
}

// --- Funciones CRUD ---
function crearOrden(): void {
    $conn = conectar();
    
    try {
        $conn->begin_transaction();
        
        $id_mesa = (int)($_POST['id_mesa'] ?? 0);
        $id_plato = (int)($_POST['id_plato'] ?? 0);
        $id_bebida = (int)($_POST['id_bebida'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cantidad = (int)($_POST['cantidad'] ?? 1);
        
        // Calcular total basado en el precio del plato o bebida
        $total = calcularTotalOrden($conn, $id_plato, $id_bebida, $cantidad);
        
        $sql = "INSERT INTO orden (id_mesa, id_plato, id_bebida, descripcion, cantidad, total, fecha_orden) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisid", $id_mesa, $id_plato, $id_bebida, $descripcion, $cantidad, $total);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['mensaje'] = "Orden creada exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            throw new Exception("Error al crear orden: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensaje'] = $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    } finally {
        desconectar($conn);
        header('Location: Ordenes.php');
        exit();
    }
}

function actualizarOrden(): void {
    $conn = conectar();
    
    try {
        $conn->begin_transaction();
        
        $id_orden = (int)($_POST['id_orden'] ?? 0);
        $id_mesa = (int)($_POST['id_mesa'] ?? 0);
        $id_plato = (int)($_POST['id_plato'] ?? 0);
        $id_bebida = (int)($_POST['id_bebida'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $cantidad = (int)($_POST['cantidad'] ?? 1);
        
        // Calcular total basado en el precio del plato o bebida
        $total = calcularTotalOrden($conn, $id_plato, $id_bebida, $cantidad);
        
        $sql = "UPDATE orden SET id_mesa = ?, id_plato = ?, id_bebida = ?, 
                descripcion = ?, cantidad = ?, total = ? WHERE id_orden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisidi", $id_mesa, $id_plato, $id_bebida, $descripcion, $cantidad, $total, $id_orden);
        
        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['mensaje'] = "Orden actualizada exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            throw new Exception("Error al actualizar orden: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensaje'] = $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    } finally {
        desconectar($conn);
        header('Location: Ordenes.php');
        exit();
    }
}

function eliminarOrden(): void {
    $conn = conectar();
    $id_orden = (int)($_POST['id_orden'] ?? 0);

    try {
        $sql = "DELETE FROM orden WHERE id_orden = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_orden);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Orden eliminada exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            throw new Exception("Error al eliminar orden: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() === 1451) {
            $_SESSION['mensaje'] = "No se puede eliminar: la orden tiene facturas relacionadas.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar orden: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "error";
        }
    } finally {
        desconectar($conn);
        header('Location: Ordenes.php');
        exit();
    }
}

function calcularTotalOrden($conn, $id_plato, $id_bebida, $cantidad): float {
    $total = 0.0;
    
    // Si hay plato, obtener su precio
    if ($id_plato > 0) {
        $sql_plato = "SELECT precio_unitario FROM platos WHERE id_plato = ?";
        $stmt_plato = $conn->prepare($sql_plato);
        $stmt_plato->bind_param("i", $id_plato);
        $stmt_plato->execute();
        $result_plato = $stmt_plato->get_result();
        $row_plato = $result_plato->fetch_assoc();
        $stmt_plato->close();
        
        if ($row_plato) {
            $total += (float)$row_plato['precio_unitario'] * $cantidad;
        }
    }
    
    // Si hay bebida, obtener su precio
    if ($id_bebida > 0) {
        $sql_bebida = "SELECT precio_unitario FROM bebidas WHERE id_bebida = ?";
        $stmt_bebida = $conn->prepare($sql_bebida);
        $stmt_bebida->bind_param("i", $id_bebida);
        $stmt_bebida->execute();
        $result_bebida = $stmt_bebida->get_result();
        $row_bebida = $result_bebida->fetch_assoc();
        $stmt_bebida->close();
        
        if ($row_bebida) {
            $total += (float)$row_bebida['precio_unitario'] * $cantidad;
        }
    }
    
    return $total;
}

// --- Funciones para obtener datos ---
function obtenerPlatos(): array {
    $conn = conectar();
    $sql = "SELECT id_plato, nombre_plato, descripcion, precio_unitario 
            FROM platos 
            ORDER BY nombre_plato ASC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

function obtenerBebidas(): array {
    $conn = conectar();
    $sql = "SELECT id_bebida, descripcion, precio_unitario 
            FROM bebidas 
            ORDER BY descripcion ASC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

function obtenerOrdenes(): array {
    $conn = conectar();
    $sql = "SELECT o.*, p.nombre_plato, b.descripcion as nombre_bebida,
                   (SELECT COUNT(*) FROM facturas f WHERE f.id_orden = o.id_orden) as tiene_factura
            FROM orden o
            LEFT JOIN platos p ON o.id_plato = p.id_plato
            LEFT JOIN bebidas b ON o.id_bebida = b.id_bebida
            ORDER BY o.fecha_orden DESC";
    $res = $conn->query($sql);
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    desconectar($conn);
    return $rows;
}

function obtenerMesas(): array {
    // Asumiendo que tienes una tabla de mesas, si no, puedes usar números fijos
    return [
        ['id' => 1, 'numero' => 'Mesa 1'],
        ['id' => 2, 'numero' => 'Mesa 2'],
        ['id' => 3, 'numero' => 'Mesa 3'],
        ['id' => 4, 'numero' => 'Mesa 4'],
        ['id' => 5, 'numero' => 'Mesa 5'],
        ['id' => 6, 'numero' => 'Mesa 6'],
    ];
}

$platos = obtenerPlatos();
$bebidas = obtenerBebidas();
$ordenes = obtenerOrdenes();
$mesas = obtenerMesas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Gestión de Órdenes - Marea Roja</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
    <style>
        body, input, button, table { font-family: 'Poppins', Arial, sans-serif; }
        .mensaje { padding:10px; margin:10px 0; border-radius:5px; }
        .mensaje.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .mensaje.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
        .table-responsive { max-height:500px; overflow-y:auto; }
        .monto-total { font-size: 1.2em; font-weight: bold; color: #28a745; }
        .orden-info { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .precio-info { color: #6c757d; font-size: 0.9em; }
        .facturada-badge { background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; }
    </style>
</head>
<body>
<header class="mb-4">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <h1 class="mb-0">GESTIÓN DE ÓRDENES</h1>
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
        <h2 class="card__title mb-3">Formulario de Órdenes</h2>

        <form id="form-ordenes" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_orden" id="id_orden" value="">

            <div class="col-md-3">
                <label for="id_mesa" class="form-label required">Mesa</label>
                <select id="id_mesa" name="id_mesa" class="form-select" required>
                    <option value="">Seleccionar mesa...</option>
                    <?php foreach($mesas as $mesa): ?>
                        <option value="<?= $mesa['id'] ?>">
                            <?= htmlspecialchars($mesa['numero']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="id_plato" class="form-label">Plato</label>
                <select id="id_plato" name="id_plato" class="form-select">
                    <option value="">Seleccionar plato...</option>
                    <?php foreach($platos as $plato): ?>
                        <option value="<?= $plato['id_plato'] ?>" 
                                data-precio="<?= $plato['precio_unitario'] ?>">
                            <?= htmlspecialchars($plato['nombre_plato']) ?> - Q<?= number_format($plato['precio_unitario'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="id_bebida" class="form-label">Bebida</label>
                <select id="id_bebida" name="id_bebida" class="form-select">
                    <option value="">Seleccionar bebida...</option>
                    <?php foreach($bebidas as $bebida): ?>
                        <option value="<?= $bebida['id_bebida'] ?>" 
                                data-precio="<?= $bebida['precio_unitario'] ?>">
                            <?= htmlspecialchars($bebida['descripcion']) ?> - Q<?= number_format($bebida['precio_unitario'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="2" 
                          placeholder="Observaciones o especificaciones de la orden..."></textarea>
            </div>

            <div class="col-md-3">
                <label for="cantidad" class="form-label required">Cantidad</label>
                <input type="number" id="cantidad" name="cantidad" class="form-control" 
                       min="1" value="1" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Total</label>
                <div class="orden-info">
                    <span id="total_display" class="monto-total">Q0.00</span>
                </div>
            </div>

            <div class="col-12">
                <div class="precio-info">
                    <small>Seleccione al menos un plato o una bebida</small>
                </div>
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
            <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
            <button id="btn-actualizar" type="button" class="btn btn-warning d-none">Actualizar</button>
            <button id="btn-cancelar" type="button" class="btn btn-danger d-none">Cancelar</button>
            <div class="ms-auto text-muted align-self-center">Fila activa: <span id="fila-activa">ninguna</span></div>
        </div>

        <h2 class="card__title mb-3 mt-5">Historial de Órdenes</h2>
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-ordenes">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Mesa</th>
                        <th>Plato</th>
                        <th>Bebida</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ordenes): ?>
                        <?php foreach($ordenes as $orden): ?>
                        <tr>
                            <td><?= htmlspecialchars($orden['id_orden']); ?></td>
                            <td><?= htmlspecialchars($orden['id_mesa']); ?></td>
                            <td><?= htmlspecialchars($orden['nombre_plato'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($orden['nombre_bebida'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($orden['descripcion'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($orden['cantidad']); ?></td>
                            <td>Q<?= number_format($orden['total'], 2); ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></td>
                            <td>
                                <?php if ($orden['tiene_factura'] > 0): ?>
                                    <span class="facturada-badge">Facturada</span>
                                <?php else: ?>
                                    <span class="text-muted">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($orden['tiene_factura'] == 0): ?>
                                    <button class="btn btn-sm btn-primary editar-btn"
                                        data-id="<?= $orden['id_orden']; ?>"
                                        data-mesa="<?= $orden['id_mesa']; ?>"
                                        data-plato="<?= $orden['id_plato']; ?>"
                                        data-bebida="<?= $orden['id_bebida']; ?>"
                                        data-descripcion="<?= htmlspecialchars($orden['descripcion'] ?? ''); ?>"
                                        data-cantidad="<?= $orden['cantidad']; ?>">
                                        Editar
                                    </button>
                                    <form method="post" class="d-inline" data-eliminar="true">
                                        <input type="hidden" name="operacion" value="eliminar">
                                        <input type="hidden" name="id_orden" value="<?= $orden['id_orden']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">No editable</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center">No hay órdenes registradas</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Datos disponibles para JS
    window.platos = <?= json_encode($platos); ?>;
    window.bebidas = <?= json_encode($bebidas); ?>;
</script>
<script src="../../javascript/Ordenes.js"></script>
</body>
</html>