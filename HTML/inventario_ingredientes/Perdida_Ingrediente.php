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
            crearPerdida();
            break;
        case 'actualizar':
            actualizarPerdida();
            break;
        case 'eliminar':
            eliminarPerdida();
            break;
    }
}

function crearPerdida() {
    global $conn;
    $conn = conectar();
    
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad_unidades = floatval($_POST['cantidad_unidades'] ?? 0);
    $costo_perdida_q = floatval($_POST['costo_perdida_q'] ?? 0);
    $fecha_perdida = $_POST['fecha_perdida'] ?? '';
    
    $sql = "INSERT INTO perdidas_inventario (id_ingrediente, descripcion, cantidad_unidades, costo_perdida_q, fecha_perdida) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isddss", $id_ingrediente, $descripcion, $cantidad_unidades, $costo_perdida_q, $fecha_perdida);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Pérdida registrada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al registrar pérdida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: perdidas_inventario.php');
    exit();
}

function actualizarPerdida() {
    global $conn;
    $conn = conectar();
    
    $id_perdida = intval($_POST['id_perdida'] ?? '');
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $cantidad_unidades = floatval($_POST['cantidad_unidades'] ?? 0);
    $costo_perdida_q = floatval($_POST['costo_perdida_q'] ?? 0);
    $fecha_perdida = $_POST['fecha_perdida'] ?? '';
    
    $sql = "UPDATE perdidas_inventario SET id_ingrediente = ?, descripcion = ?, cantidad_unidades = ?, costo_perdida_q = ?, fecha_perdida = ? 
            WHERE id_perdida = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isddssi", $id_ingrediente, $descripcion, $cantidad_unidades, $costo_perdida_q, $fecha_perdida, $id_perdida);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Pérdida actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar pérdida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: perdidas_inventario.php');
    exit();
}

function eliminarPerdida() {
    global $conn;
    $conn = conectar();
    
    $id_perdida = intval($_POST['id_perdida'] ?? '');
    
    $sql = "DELETE FROM perdidas_inventario WHERE id_perdida = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_perdida);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Pérdida eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar pérdida: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: perdidas_inventario.php');
    exit();
}

// Obtener todas las pérdidas para mostrar en la tabla
function obtenerPerdidas() {
    $conn = conectar();
    $sql = "SELECT p.*, i.nombre_ingrediente 
            FROM perdidas_inventario p 
            LEFT JOIN ingredientes i ON p.id_ingrediente = i.id_ingrediente 
            ORDER BY p.fecha_perdida DESC";
    $resultado = $conn->query($sql);
    $perdidas = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $perdidas[] = $fila;
        }
    }
    
    desconectar($conn);
    return $perdidas;
}

// Obtener ingredientes para el dropdown
function obtenerIngredientes() {
    $conn = conectar();
    $sql = "SELECT id_ingrediente, nombre_ingrediente FROM ingredientes ORDER BY nombre_ingrediente";
    $resultado = $conn->query($sql);
    $ingredientes = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $ingredientes[] = $fila;
        }
    }
    
    desconectar($conn);
    return $ingredientes;
}

$perdidas = obtenerPerdidas();
$ingredientes = obtenerIngredientes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pérdidas de Inventario - Marea Roja</title>

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
        
        .costo-alto {
            color: #dc3545;
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
        <h1 class="mb-0">PÉRDIDAS DE INVENTARIO - MAREA ROJA</h1>
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
            <i class="bi bi-exclamation-triangle me-2"></i>REGISTRO DE PÉRDIDAS
        </h2>

        <form id="form-perdida" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_perdida" name="id_perdida" value="">
            
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="id_ingrediente">
                    <i class="bi bi-box-seam me-1"></i>Ingrediente: *
                </label>
                <select class="form-control" id="id_ingrediente" name="id_ingrediente" required>
                    <option value="">Seleccione un ingrediente</option>
                    <?php foreach($ingredientes as $ingrediente): ?>
                        <option value="<?php echo $ingrediente['id_ingrediente']; ?>">
                            <?php echo htmlspecialchars($ingrediente['nombre_ingrediente']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="descripcion">
                    <i class="bi bi-card-text me-1"></i>Descripción: *
                </label>
                <input type="text" class="form-control" id="descripcion" name="descripcion" 
                       required placeholder="Ej: Pérdida por caducidad" maxlength="200">
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="cantidad_unidades">
                    <i class="bi bi-box-arrow-down me-1"></i>Cantidad: *
                </label>
                <input type="number" class="form-control" id="cantidad_unidades" name="cantidad_unidades" 
                       required placeholder="0.000" step="0.001" min="0">
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="costo_perdida_q">
                    <i class="bi bi-currency-dollar me-1"></i>Costo (Q): *
                </label>
                <input type="number" class="form-control" id="costo_perdida_q" name="costo_perdida_q" 
                       required placeholder="0.00" step="0.01" min="0">
            </div>
            
            <div class="col-md-2">
                <label class="form-label fw-semibold" for="fecha_perdida">
                    <i class="bi bi-calendar-date me-1"></i>Fecha: *
                </label>
                <input type="date" class="form-control" id="fecha_perdida" name="fecha_perdida" required>
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
            <i class="bi bi-list-ul me-2"></i>HISTORIAL DE PÉRDIDAS
        </h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-perdidas">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Ingrediente</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Costo (Q)</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($perdidas as $perdida): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($perdida['id_perdida']); ?></td>
                        <td><?php echo htmlspecialchars($perdida['nombre_ingrediente']); ?></td>
                        <td><?php echo htmlspecialchars($perdida['descripcion']); ?></td>
                        <td><?php echo htmlspecialchars($perdida['cantidad_unidades']); ?></td>
                        <td class="<?php echo $perdida['costo_perdida_q'] > 100 ? 'costo-alto' : ''; ?>">
                            Q <?php echo number_format($perdida['costo_perdida_q'], 2); ?>
                        </td>
                        <td><?php echo htmlspecialchars($perdida['fecha_perdida']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $perdida['id_perdida']; ?>"
                                    data-ingrediente="<?php echo $perdida['id_ingrediente']; ?>"
                                    data-descripcion="<?php echo htmlspecialchars($perdida['descripcion']); ?>"
                                    data-cantidad="<?php echo $perdida['cantidad_unidades']; ?>"
                                    data-costo="<?php echo $perdida['costo_perdida_q']; ?>"
                                    data-fecha="<?php echo $perdida['fecha_perdida']; ?>">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este registro de pérdida?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_perdida" value="<?php echo $perdida['id_perdida']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($perdidas)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay pérdidas registradas</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-perdida');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idPerdidaInput = document.getElementById('id_perdida');

        // Establecer fecha actual por defecto
        document.getElementById('fecha_perdida').valueAsDate = new Date();

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
                const ingrediente = this.getAttribute('data-ingrediente');
                const descripcion = this.getAttribute('data-descripcion');
                const cantidad = this.getAttribute('data-cantidad');
                const costo = this.getAttribute('data-costo');
                const fecha = this.getAttribute('data-fecha');

                // Llenar formulario
                idPerdidaInput.value = id;
                document.getElementById('id_ingrediente').value = ingrediente;
                document.getElementById('descripcion').value = descripcion;
                document.getElementById('cantidad_unidades').value = cantidad;
                document.getElementById('costo_perdida_q').value = costo;
                document.getElementById('fecha_perdida').value = fecha;

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idPerdidaInput.value = '';
            operacionInput.value = 'crear';
            // Restablecer fecha actual
            document.getElementById('fecha_perdida').valueAsDate = new Date();
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
            const ingrediente = document.getElementById('id_ingrediente').value;
            const descripcion = document.getElementById('descripcion').value.trim();
            const cantidad = document.getElementById('cantidad_unidades').value;
            const costo = document.getElementById('costo_perdida_q').value;
            const fecha = document.getElementById('fecha_perdida').value;

            if (!ingrediente) {
                alert('El ingrediente es requerido');
                return false;
            }
            if (!descripcion) {
                alert('La descripción es requerida');
                return false;
            }
            if (!cantidad || cantidad <= 0) {
                alert('La cantidad debe ser un número positivo');
                return false;
            }
            if (!costo || costo < 0) {
                alert('El costo debe ser un número positivo');
                return false;
            }
            if (!fecha) {
                alert('La fecha es requerida');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>