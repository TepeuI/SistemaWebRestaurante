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
            crearPlato();
            break;
        case 'actualizar':
            actualizarPlato();
            break;
        case 'eliminar':
            eliminarPlato();
            break;
    }
}

function crearPlato() {
    global $conn;
    $conn = conectar();
    
    $nombre_plato = trim($_POST['nombre_plato'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
    
    $sql = "INSERT INTO platos (nombre_plato, descripcion, precio_unitario) 
            VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $nombre_plato, $descripcion, $precio_unitario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Plato creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear plato: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: platos.php');
    exit();
}

function actualizarPlato() {
    global $conn;
    $conn = conectar();
    
    $id_plato = intval($_POST['id_plato'] ?? '');
    $nombre_plato = trim($_POST['nombre_plato'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
    
    $sql = "UPDATE platos SET nombre_plato = ?, descripcion = ?, precio_unitario = ? 
            WHERE id_plato = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $nombre_plato, $descripcion, $precio_unitario, $id_plato);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Plato actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar plato: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: platos.php');
    exit();
}

function eliminarPlato() {
    global $conn;
    $conn = conectar();
    
    $id_plato = intval($_POST['id_plato'] ?? '');
    
    // Verificar si el plato está en uso en recetas
    $sql_check = "SELECT id_registro_receta FROM receta WHERE id_plato = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_plato);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "No se puede eliminar el plato porque está siendo usado en recetas";
        $_SESSION['tipo_mensaje'] = "error";
        $stmt_check->close();
        desconectar($conn);
        header('Location: platos.php');
        exit();
    }
    $stmt_check->close();
    
    $sql = "DELETE FROM platos WHERE id_plato = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_plato);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Plato eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar plato: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: platos.php');
    exit();
}

// Obtener todos los platos para mostrar en la tabla
function obtenerPlatos() {
    $conn = conectar();
    $sql = "SELECT * FROM platos ORDER BY nombre_plato";
    $resultado = $conn->query($sql);
    $platos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $platos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $platos;
}

$platos = obtenerPlatos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Platos - Marea Roja</title>

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
        
        .precio-alto {
            color: #059669;
            font-weight: bold;
        }
        
        .descripcion-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>

    <!-- Bootstrap y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">GESTIÓN DE PLATOS - MAREA ROJA</h1>
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
            <i class="bi bi-egg-fried me-2"></i>FORMULARIO DE PLATOS
        </h2>

        <form id="form-plato" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_plato" name="id_plato" value="">
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="nombre_plato">
                    <i class="bi bi-tag me-1"></i>Nombre del Plato: *
                </label>
                <input type="text" class="form-control" id="nombre_plato" name="nombre_plato" 
                       required placeholder="Ej. Churrasco a la Parrilla" maxlength="120">
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="precio_unitario">
                    <i class="bi bi-currency-dollar me-1"></i>Precio Unitario (Q): *
                </label>
                <input type="number" class="form-control" id="precio_unitario" name="precio_unitario" 
                       required placeholder="0.00" step="0.01" min="0">
            </div>
            
            <div class="col-md-12">
                <label class="form-label fw-semibold" for="descripcion">
                    <i class="bi bi-card-text me-1"></i>Descripción:
                </label>
                <textarea class="form-control" id="descripcion" name="descripcion" 
                          rows="3" placeholder="Descripción del plato, ingredientes principales, etc." maxlength="200"></textarea>
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
            <i class="bi bi-list-ul me-2"></i>LISTA DE PLATOS
        </h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-platos">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio (Q)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($platos as $plato): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($plato['id_plato']); ?></td>
                        <td><?php echo htmlspecialchars($plato['nombre_plato']); ?></td>
                        <td class="descripcion-cell" title="<?php echo htmlspecialchars($plato['descripcion']); ?>">
                            <?php echo htmlspecialchars($plato['descripcion']); ?>
                        </td>
                        <td class="precio-alto">
                            Q <?php echo number_format($plato['precio_unitario'], 2); ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $plato['id_plato']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($plato['nombre_plato']); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($plato['descripcion']); ?>"
                                    data-precio="<?php echo $plato['precio_unitario']; ?>">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este plato?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_plato" value="<?php echo $plato['id_plato']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($platos)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay platos registrados</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-plato');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idPlatoInput = document.getElementById('id_plato');

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
                const precio = this.getAttribute('data-precio');

                // Llenar formulario
                idPlatoInput.value = id;
                document.getElementById('nombre_plato').value = nombre;
                document.getElementById('descripcion').value = descripcion;
                document.getElementById('precio_unitario').value = precio;

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idPlatoInput.value = '';
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
            const nombre = document.getElementById('nombre_plato').value.trim();
            const precio = document.getElementById('precio_unitario').value;

            if (!nombre) {
                alert('El nombre del plato es requerido');
                return false;
            }
            if (!precio || precio <= 0) {
                alert('El precio unitario debe ser un número positivo');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>