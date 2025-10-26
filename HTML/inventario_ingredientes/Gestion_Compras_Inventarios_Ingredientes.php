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
            crearCompra();
            break;
        case 'actualizar':
            actualizarCompra();
            break;
        case 'eliminar':
            eliminarCompra();
            break;
    }
}

function crearCompra() {
    global $conn;
    $conn = conectar();
    
    $id_proveedor = intval($_POST['id_proveedor'] ?? '');
    $fecha_de_compra = $_POST['fecha_de_compra'] ?? '';
    $monto_total_compra_q = floatval($_POST['monto_total_compra_q'] ?? 0);
    
    $sql = "INSERT INTO compras_ingrediente (id_proveedor, fecha_de_compra, monto_total_compra_q) 
            VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isd", $id_proveedor, $fecha_de_compra, $monto_total_compra_q);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Compra registrada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: compras_ingredientes.php');
    exit();
}

function actualizarCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_ingrediente = intval($_POST['id_compra_ingrediente'] ?? '');
    $id_proveedor = intval($_POST['id_proveedor'] ?? '');
    $fecha_de_compra = $_POST['fecha_de_compra'] ?? '';
    $monto_total_compra_q = floatval($_POST['monto_total_compra_q'] ?? 0);
    
    $sql = "UPDATE compras_ingrediente SET id_proveedor = ?, fecha_de_compra = ?, monto_total_compra_q = ? 
            WHERE id_compra_ingrediente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdi", $id_proveedor, $fecha_de_compra, $monto_total_compra_q, $id_compra_ingrediente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Compra actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: compras_ingredientes.php');
    exit();
}

function eliminarCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_ingrediente = intval($_POST['id_compra_ingrediente'] ?? '');
    
    // Verificar si la compra tiene detalles asociados
    $sql_check = "SELECT id_detalle FROM detalle_compra_ingrediente WHERE id_compra_ingrediente = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_compra_ingrediente);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "No se puede eliminar la compra porque tiene detalles asociados";
        $_SESSION['tipo_mensaje'] = "error";
        $stmt_check->close();
        desconectar($conn);
        header('Location: compras_ingredientes.php');
        exit();
    }
    $stmt_check->close();
    
    $sql = "DELETE FROM compras_ingrediente WHERE id_compra_ingrediente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_compra_ingrediente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Compra eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: compras_ingredientes.php');
    exit();
}

// Obtener todas las compras para mostrar en la tabla
function obtenerCompras() {
    $conn = conectar();
    $sql = "SELECT ci.*, p.nombre_proveedor 
            FROM compras_ingrediente ci 
            LEFT JOIN proveedores p ON ci.id_proveedor = p.id_proveedor 
            ORDER BY ci.fecha_de_compra DESC";
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

// Obtener proveedores para el dropdown
function obtenerProveedores() {
    $conn = conectar();
    $sql = "SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor";
    $resultado = $conn->query($sql);
    $proveedores = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $proveedores[] = $fila;
        }
    }
    
    desconectar($conn);
    return $proveedores;
}

$compras = obtenerCompras();
$proveedores = obtenerProveedores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras de Ingredientes - Marea Roja</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body, h1, h2, h3, h4, h5, h6, label, input, button, table, th, td {
            font-family: 'Poppins', Arial, Helvetica, sans-serif !important;
        }
        
        .mensaje {
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .mensaje.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .mensaje.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .btn-action {
            margin: 1px;
            font-size: 0.875rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .table th {
            background-color: #1e40af;
            color: white;
            font-weight: 600;
        }
        
        .badge-estado {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 6px;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        
        .monto-alto {
            color: #059669;
            font-weight: bold;
        }
    </style>

    <!-- Bootstrap y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">COMPRAS DE INGREDIENTES - MAREA ROJA</h1>
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
        <h2 class="card-title text-primary mb-4">
            <i class="bi bi-cart-plus me-2"></i>GESTIÓN DE COMPRAS DE INGREDIENTES
        </h2>

        <form id="form-compra" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_compra_ingrediente" name="id_compra_ingrediente" value="">
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="id_proveedor">
                    <i class="bi bi-truck me-1"></i>Proveedor: *
                </label>
                <select class="form-control" id="id_proveedor" name="id_proveedor" required>
                    <option value="">Seleccione un proveedor</option>
                    <?php foreach($proveedores as $proveedor): ?>
                        <option value="<?php echo $proveedor['id_proveedor']; ?>">
                            <?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="fecha_de_compra">
                    <i class="bi bi-calendar-date me-1"></i>Fecha de Compra: *
                </label>
                <input type="date" class="form-control" id="fecha_de_compra" name="fecha_de_compra" required>
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="monto_total_compra_q">
                    <i class="bi bi-currency-dollar me-1"></i>Monto Total (Q): *
                </label>
                <input type="number" class="form-control" id="monto_total_compra_q" name="monto_total_compra_q" 
                       required placeholder="0.00" step="0.01" min="0">
            </div>
        </form>

        <div class="d-flex gap-2 mt-4">
            <button id="btn-nuevo" type="button" class="btn btn-secondary">
                <i class="bi bi-plus-circle me-1"></i>Nuevo
            </button>
            <button id="btn-guardar" type="button" class="btn btn-success">
                <i class="bi bi-check-lg me-1"></i>Guardar
            </button>
            <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">
                <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
            </button>
            <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">
                <i class="bi bi-x-circle me-1"></i>Cancelar
            </button>
        </div>

        <h2 class="card-title mb-3 mt-5">
            <i class="bi bi-list-ul me-2"></i>HISTORIAL DE COMPRAS
        </h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-compras">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Monto Total (Q)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($compras as $compra): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($compra['id_compra_ingrediente']); ?></td>
                        <td><?php echo htmlspecialchars($compra['nombre_proveedor']); ?></td>
                        <td><?php echo htmlspecialchars($compra['fecha_de_compra']); ?></td>
                        <td class="monto-alto">
                        <?php echo number_format($compra['monto_total_compra'], 2); ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $compra['id_compra_ingrediente']; ?>"
                                    data-proveedor="<?php echo $compra['id_proveedor']; ?>"
                                    data-fecha="<?php echo $compra['fecha_de_compra']; ?>"
                                    data-monto="<?php echo $compra['monto_total_compra']; ?>">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta compra?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_compra_ingrediente" value="<?php echo $compra['id_compra_ingrediente']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($compras)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay compras registradas</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-compra');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idCompraInput = document.getElementById('id_compra_ingrediente');

        // Establecer fecha actual por defecto
        document.getElementById('fecha_de_compra').valueAsDate = new Date();

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
                const proveedor = this.getAttribute('data-proveedor');
                const fecha = this.getAttribute('data-fecha');
                const monto = this.getAttribute('data-monto');

                // Llenar formulario
                idCompraInput.value = id;
                document.getElementById('id_proveedor').value = proveedor;
                document.getElementById('fecha_de_compra').value = fecha;
                document.getElementById('monto_total_compra').value = monto;

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idCompraInput.value = '';
            operacionInput.value = 'crear';
            // Restablecer fecha actual
            document.getElementById('fecha_de_compra').valueAsDate = new Date();
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
            const proveedor = document.getElementById('id_proveedor').value;
            const fecha = document.getElementById('fecha_de_compra').value;
            const monto = document.getElementById('monto_total_compra').value;

            if (!proveedor) {
                alert('El proveedor es requerido');
                return false;
            }
            if (!fecha) {
                alert('La fecha de compra es requerida');
                return false;
            }
            if (!monto || monto <= 0) {
                alert('El monto total debe ser un número positivo');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>