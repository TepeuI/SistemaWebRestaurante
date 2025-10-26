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
        case 'crear_compra':
            crearCompra();
            break;
        case 'actualizar_compra':
            actualizarCompra();
            break;
        case 'eliminar_compra':
            eliminarCompra();
            break;
    }
}

function crearCompra() {
    global $conn;
    $conn = conectar();
    
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    $fecha_de_compra = $_POST['fecha_de_compra'] ?? '';
    $monto_total_compra_q = $_POST['monto_total_compra_q'] ?? 0;
    
    $sql = "INSERT INTO compras_mobiliario (id_proveedor, fecha_de_compra, monto_total_compra_q) 
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
    header('Location: compras_mobiliario.php');
    exit();
}

function actualizarCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_mobiliario = $_POST['id_compra_mobiliario'] ?? '';
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    $fecha_de_compra = $_POST['fecha_de_compra'] ?? '';
    $monto_total_compra_q = $_POST['monto_total_compra_q'] ?? 0;
    
    $sql = "UPDATE compras_mobiliario SET id_proveedor = ?, fecha_de_compra = ?, monto_total_compra_q = ? 
            WHERE id_compra_mobiliario = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdi", $id_proveedor, $fecha_de_compra, $monto_total_compra_q, $id_compra_mobiliario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Compra actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: compras_mobiliario.php');
    exit();
}

function eliminarCompra() {
    global $conn;
    $conn = conectar();
    
    $id_compra_mobiliario = $_POST['id_compra_mobiliario'] ?? '';
    
    $sql = "DELETE FROM compras_mobiliario WHERE id_compra_mobiliario = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_compra_mobiliario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Compra eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar compra: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: compras_mobiliario.php');
    exit();
}

// Obtener todas las compras para mostrar en la tabla
function obtenerCompras() {
    $conn = conectar();
    
    $sql = "SELECT cm.*, p.nombre_proveedor 
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

// Obtener proveedores para el select
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
    <title>Compras de Mobiliario - Marina Roja</title>
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
            max-height: 400px;
            overflow-y: auto;
        }
        .monto {
            text-align: right;
            font-weight: bold;
        }
    </style>
    <!-- Frameworks y librerías base - RUTAS CORREGIDAS -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">COMPRAS DE MOBILIARIO</h1>
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
            echo "Compras: " . count($compras) . " | ";
            echo "Proveedores: " . count($proveedores);
            ?>
        </div>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE COMPRAS DE MOBILIARIO</h2>

            <form id="form-compras" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_compra">
                <input type="hidden" id="id_compra_mobiliario" name="id_compra_mobiliario" value="">
                
                <div class="col-md-4">
                    <label class="form-label" for="id_proveedor">Proveedor:</label>
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
                    <label class="form-label" for="fecha_de_compra">Fecha de Compra:</label>
                    <input type="date" class="form-control" id="fecha_de_compra" name="fecha_de_compra" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="monto_total_compra_q">Monto Total (Q):</label>
                    <input type="number" step="0.01" class="form-control" id="monto_total_compra_q" name="monto_total_compra_q" 
                           min="0" required placeholder="0.00">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">HISTORIAL DE COMPRAS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-compras">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Compra</th>
                            <th>Proveedor</th>
                            <th>Fecha de Compra</th>
                            <th>Monto Total (Q)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($compras as $compra): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($compra['id_compra_mobiliario']); ?></td>
                            <td><?php echo htmlspecialchars($compra['nombre_proveedor'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($compra['fecha_de_compra']); ?></td>
                            <td class="monto">Q <?php echo number_format($compra['monto_total_compra_q'], 2); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $compra['id_compra_mobiliario']; ?>"
                                        data-proveedor="<?php echo $compra['id_proveedor']; ?>"
                                        data-fecha="<?php echo $compra['fecha_de_compra']; ?>"
                                        data-monto="<?php echo $compra['monto_total_compra_q']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta compra?')">
                                    <input type="hidden" name="operacion" value="eliminar_compra">
                                    <input type="hidden" name="id_compra_mobiliario" value="<?php echo $compra['id_compra_mobiliario']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
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
            const form = document.getElementById('form-compras');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idCompraInput = document.getElementById('id_compra_mobiliario');

            // Botón Nuevo
            btnNuevo.addEventListener('click', function() {
                limpiarFormulario();
                mostrarBotonesGuardar();
            });

            // Botón Guardar (Crear)
            btnGuardar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'crear_compra';
                    form.submit();
                }
            });

            // Botón Actualizar
            btnActualizar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'actualizar_compra';
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
                    document.getElementById('monto_total_compra_q').value = monto;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idCompraInput.value = '';
                operacionInput.value = 'crear_compra';
                // Establecer fecha actual por defecto
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
                const monto = document.getElementById('monto_total_compra_q').value;

                if (!proveedor) {
                    alert('El proveedor es requerido');
                    return false;
                }
                if (!fecha) {
                    alert('La fecha de compra es requerida');
                    return false;
                }
                if (!monto || monto <= 0) {
                    alert('El monto total debe ser mayor a 0');
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