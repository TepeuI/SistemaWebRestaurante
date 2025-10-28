<?php
// compra_insumos.php — Página para gestionar la compra de insumos

// Incluir configuración y funciones necesarias
require_once '../../config.php';
require_once '../../functions.php';

// Verificar sesión y permisos
session_start();
checkSession();
checkPermissions('compras_insumos');

// Conexión a la base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Inicializar variables
$id_compra = 0;
$fecha_compra = date('Y-m-d');
$id_proveedor = 0;
$monto_total = 0;
$detalles = [];

// Procesar formulario de compra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir y sanitizar datos
    $id_proveedor = isset($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : 0;
    $fecha_compra = isset($_POST['fecha_compra']) ? $_POST['fecha_compra'] : date('Y-m-d');
    $monto_total = isset($_POST['monto_total']) ? (float)$_POST['monto_total'] : 0;
    $detalles = isset($_POST['detalles']) ? json_decode($_POST['detalles'], true) : [];

    // Validar datos
    if ($id_proveedor <= 0 || $monto_total <= 0 || empty($detalles)) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    // Iniciar transacción
    $conn->begin_transaction();
    try {
        // Insertar compra
        $stmt = $conn->prepare("INSERT INTO compras_insumos (id_proveedor, fecha_compra, monto_total) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $id_proveedor, $fecha_compra, $monto_total);
        $stmt->execute();
        $id_compra = $conn->insert_id;
        $stmt->close();

        // Insertar detalles y actualizar stock
        $stmtDet = $conn->prepare("INSERT INTO detalle_compra_insumo (id_compra_insumo, id_insumo, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmtUpd = $conn->prepare("UPDATE inventario_insumos SET stock = stock + ? WHERE id_insumo = ?");
        foreach ($detalles as $d) {
            $stmtDet->bind_param("iisdd", $id_compra, $d['id_insumo'], $d['cantidad'], $d['precio_unitario'], $d['subtotal']);
            // Note: bind types: i (int), i (int), s (string) etc. cantidad puede ser decimal -> use 'd' for double; adjust params
            // To be safe, rebind with correct types:
            $stmtDet->close();
            $stmtDet = $conn->prepare("INSERT INTO detalle_compra_insumo (id_compra_insumo, id_insumo, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmtDet->bind_param("iiddd", $id_compra, $d['id_insumo'], $d['cantidad'], $d['precio_unitario'], $d['subtotal']);
            // The above types may vary by your schema; to avoid mismatch, use following safe execute:
            $stmtDet->execute();
            // Actualizar stock (asumiendo cantidad es decimal)
            $stmtUpd->bind_param("di", $d['cantidad'], $d['id_insumo']);
            $stmtUpd->execute();
        }

        // Confirmar transacción
        $conn->commit();
        echo json_encode(['success' => true, 'id_compra' => $id_compra]);
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error al procesar la compra: ' . $e->getMessage()]);
    }
    exit;
}

// Obtener lista de proveedores
$proveedores = [];
$stmt = $conn->prepare("SELECT id_proveedor, nombre FROM proveedores ORDER BY nombre");
$stmt->execute();
$stmt->bind_result($id_proveedor, $nombre);
while ($stmt->fetch()) {
    $proveedores[] = ['id' => $id_proveedor, 'nombre' => $nombre];
}
$stmt->close();

// Cerrar conexión
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra de Insumos</title>
    <!-- Incluir estilos y scripts necesarios -->
    <link rel="stylesheet" href="../../css/styles.css">
    <script src="../../js/scripts.js" defer></script>
</head>
<body>
    <div class="container">
        <h1>Compra de Insumos</h1>
        <form id="form-compra" method="post">
            <input type="hidden" name="id_compra" id="id_compra" value="<?php echo $id_compra; ?>">
            <div class="form-group">
                <label for="id_proveedor">Proveedor</label>
                <select name="id_proveedor" id="id_proveedor" required>
                    <option value="">Seleccione un proveedor</option>
                    <?php foreach ($proveedores as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($id_proveedor == $p['id']) ? 'selected' : ''; ?>><?php echo $p['nombre']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fecha_compra">Fecha de Compra</label>
                <input type="date" name="fecha_compra" id="fecha_compra" value="<?php echo $fecha_compra; ?>" required>
            </div>
            <div class="form-group">
                <label for="monto_total">Monto Total</label>
                <input type="number" name="monto_total" id="monto_total" step="0.01" value="<?php echo $monto_total; ?>" required>
            </div>
            <div class="form-group">
                <label for="detalles">Detalles</label>
                <textarea name="detalles" id="detalles" required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" id="btn-guardar">Guardar</button>
                <button type="button" id="btn-cancelar">Cancelar</button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-compra');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnCancelar = document.getElementById('btn-cancelar');

        // Cancelar: limpiar formulario y volver a la lista
        btnCancelar.addEventListener('click', function () {
            if (confirm('¿Cancelar la compra?')) {
                form.reset();
                window.location.href = 'lista_compras.php';
            }
        });

        // Guardar: enviar formulario por AJAX
        form.addEventListener('submit', function (evt) {
            evt.preventDefault();
            if (confirm('¿Deseas guardar esta compra?')) {
                const formData = new FormData(form);
                fetch('compra_insumos.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Compra registrada con éxito');
                        window.location.href = 'detalle_compra.php?id_compra=' + data.id_compra;
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error en la solicitud:', error);
                    alert('Ocurrió un error al procesar la compra');
                });
            }
        });
    });
    </script>
</body>
</html>