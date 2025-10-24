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
            crearIngrediente();
            break;
        case 'actualizar':
            actualizarIngrediente();
            break;
        case 'eliminar':
            eliminarIngrediente();
            break;
    }
}

function crearIngrediente() {
    global $conn;
    $conn = conectar();
    
    $nombre_ingrediente = trim($_POST['nombre_ingrediente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_unidad = intval($_POST['id_unidad'] ?? '');
    $cantidad_stock = floatval($_POST['cantidad_stock'] ?? 0);
    
    $sql = "INSERT INTO ingredientes (nombre_ingrediente, descripcion, id_unidad, cantidad_stock) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssid", $nombre_ingrediente, $descripcion, $id_unidad, $cantidad_stock);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ingrediente creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear ingrediente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Gestion_Inventario_Ingredientes.php');
    exit();
}

function actualizarIngrediente() {
    global $conn;
    $conn = conectar();
    
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $nombre_ingrediente = trim($_POST['nombre_ingrediente'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_unidad = intval($_POST['id_unidad'] ?? '');
    $cantidad_stock = floatval($_POST['cantidad_stock'] ?? 0);
    
    $sql = "UPDATE ingredientes SET nombre_ingrediente = ?, descripcion = ?, id_unidad = ?, cantidad_stock = ? 
            WHERE id_ingrediente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssidi", $nombre_ingrediente, $descripcion, $id_unidad, $cantidad_stock, $id_ingrediente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ingrediente actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar ingrediente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Gestion_Inventario_Ingredientes.php');
    exit();
}

function eliminarIngrediente() {
    global $conn;
    $conn = conectar();
    
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    
    // Verificar si el ingrediente está en uso en recetas
    $sql_check = "SELECT id_registro_receta FROM receta WHERE id_ingrediente = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_ingrediente);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "No se puede eliminar el ingrediente porque está siendo usado en recetas";
        $_SESSION['tipo_mensaje'] = "error";
        $stmt_check->close();
        desconectar($conn);
        header('Location: Gestion_Inventario_Ingredientes.php');
        exit();
    }
    $stmt_check->close();
    
    $sql = "DELETE FROM ingredientes WHERE id_ingrediente = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_ingrediente);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ingrediente eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar ingrediente: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Gestion_Inventario_Ingredientes.php');
    exit();
}

// Obtener todos los ingredientes para mostrar en la tabla
function obtenerIngredientes() {
    $conn = conectar();
    $sql = "SELECT i.*, um.unidad, um.abreviatura 
            FROM ingredientes i 
            LEFT JOIN unidades_medida um ON i.id_unidad = um.id_unidad 
            ORDER BY i.nombre_ingrediente";
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

// Obtener unidades de medida para el dropdown
function obtenerUnidadesMedida() {
    $conn = conectar();
    $sql = "SELECT * FROM unidades_medida ORDER BY unidad";
    $resultado = $conn->query($sql);
    $unidades = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $unidades[] = $fila;
        }
    }
    
    desconectar($conn);
    return $unidades;
}

$ingredientes = obtenerIngredientes();
$unidades = obtenerUnidadesMedida();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Ingredientes - Marea Roja</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
  <style>
    /* ESTILOS INLINE QUE USA VEHICULOS - Agregar en ingredientes */
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
</style>

    <!-- Bootstrap y librerías base -->
 <link rel="stylesheet" href="../../css/bootstrap.min.css">
 <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">INVENTARIO DE INGREDIENTES - MAREA ROJA</h1>
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
            <i class="bi bi-box-seam me-2"></i>FORMULARIO DE INGREDIENTES
        </h2>

        <form id="form-ingrediente" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_ingrediente" name="id_ingrediente" value="">
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="nombre_ingrediente">
                    <i class="bi bi-tag me-1"></i>Nombre del Ingrediente: *
                </label>
                <input type="text" class="form-control" id="nombre_ingrediente" name="nombre_ingrediente" 
                       required placeholder="Ej. Churrasco Premium" maxlength="120">
            </div>
            
            <!-- NUEVO CAMPO DESCRIPCIÓN -->
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="descripcion">
                    <i class="bi bi-card-text me-1"></i>Descripción:
                </label>
                <input type="text" class="form-control" id="descripcion" name="descripcion" 
                       placeholder="Ej. Carne de res premium cortada en tiras" maxlength="200">
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="id_unidad">
                    <i class="bi bi-rulers me-1"></i>Unidad de Medida: *
                </label>
                <select class="form-control" id="id_unidad" name="id_unidad" required>
                    <option value="">Seleccione una unidad</option>
                    <?php foreach($unidades as $unidad): ?>
                        <option value="<?php echo $unidad['id_unidad']; ?>">
                            <?php echo htmlspecialchars($unidad['unidad'] . ' (' . $unidad['abreviatura'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="cantidad_stock">
                    <i class="bi bi-box me-1"></i>Cantidad en Stock: *
                </label>
                <input type="number" class="form-control" id="cantidad_stock" name="cantidad_stock" 
                       required placeholder="Ej. 13.5" step="0.001" min="0">
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
            <i class="bi bi-list-ul me-2"></i>LISTA DE INGREDIENTES
        </h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-ingredientes">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Unidad</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ingredientes as $ingrediente): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ingrediente['id_ingrediente']); ?></td>
                        <td><?php echo htmlspecialchars($ingrediente['nombre_ingrediente']); ?></td>
                        <td><?php echo htmlspecialchars($ingrediente['descripcion'] ?? ''); ?></td>
                        <td>
                            <?php 
                            echo htmlspecialchars(
                                $ingrediente['unidad'] . ' (' . $ingrediente['abreviatura'] . ')'
                            ); 
                            ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $ingrediente['cantidad_stock'] < 10 ? 'bg-warning' : 'bg-success'; ?>">
                                <?php echo htmlspecialchars($ingrediente['cantidad_stock']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $ingrediente['id_ingrediente']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($ingrediente['nombre_ingrediente']); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($ingrediente['descripcion'] ?? ''); ?>"
                                    data-unidad="<?php echo $ingrediente['id_unidad']; ?>"
                                    data-stock="<?php echo $ingrediente['cantidad_stock']; ?>">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este ingrediente?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_ingrediente" value="<?php echo $ingrediente['id_ingrediente']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ingredientes)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay ingredientes registrados</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-ingrediente');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idIngredienteInput = document.getElementById('id_ingrediente');

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
                const nombre = this.getAttribute('data-nombre');
                const descripcion = this.getAttribute('data-descripcion');
                const unidad = this.getAttribute('data-unidad');
                const stock = this.getAttribute('data-stock');

                // Llenar formulario
                idIngredienteInput.value = id;
                document.getElementById('nombre_ingrediente').value = nombre;
                document.getElementById('descripcion').value = descripcion;
                document.getElementById('id_unidad').value = unidad;
                document.getElementById('cantidad_stock').value = stock;

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idIngredienteInput.value = '';
            operacionInput.value = 'crear';
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
            const nombre = document.getElementById('nombre_ingrediente').value.trim();
            const unidad = document.getElementById('id_unidad').value;
            const stock = document.getElementById('cantidad_stock').value;

            if (!nombre) {
                alert('El nombre del ingrediente es requerido');
                return false;
            }
            if (!unidad) {
                alert('La unidad de medida es requerida');
                return false;
            }
            if (!stock || stock < 0) {
                alert('La cantidad en stock debe ser un número positivo');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>