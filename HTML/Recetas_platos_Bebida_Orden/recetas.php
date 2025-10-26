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
            crearReceta();
            break;
        case 'actualizar':
            actualizarReceta();
            break;
        case 'eliminar':
            eliminarReceta();
            break;
    }
}

function crearReceta() {
    global $conn;
    $conn = conectar();
    
    $id_plato = intval($_POST['id_plato'] ?? '');
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $id_unidad = intval($_POST['id_unidad'] ?? '');
    
    $sql = "INSERT INTO receta (id_plato, id_ingrediente, id_unidad) 
            VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $id_plato, $id_ingrediente, $id_unidad);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Receta creada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear receta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: recetas.php');
    exit();
}

function actualizarReceta() {
    global $conn;
    $conn = conectar();
    
    $id_registro_receta = intval($_POST['id_registro_receta'] ?? '');
    $id_plato = intval($_POST['id_plato'] ?? '');
    $id_ingrediente = intval($_POST['id_ingrediente'] ?? '');
    $id_unidad = intval($_POST['id_unidad'] ?? '');
    
    $sql = "UPDATE receta SET id_plato = ?, id_ingrediente = ?, id_unidad = ? 
            WHERE id_registro_receta = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $id_plato, $id_ingrediente, $id_unidad, $id_registro_receta);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Receta actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar receta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: recetas.php');
    exit();
}

function eliminarReceta() {
    global $conn;
    $conn = conectar();
    
    $id_registro_receta = intval($_POST['id_registro_receta'] ?? '');
    
    $sql = "DELETE FROM receta WHERE id_registro_receta = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_registro_receta);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Receta eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar receta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: recetas.php');
    exit();
}

// Obtener todas las recetas para mostrar en la tabla
function obtenerRecetas() {
    $conn = conectar();
    $sql = "SELECT r.*, p.nombre_plato, i.nombre_ingrediente, um.unidad, um.abreviatura 
            FROM receta r 
            LEFT JOIN platos p ON r.id_plato = p.id_plato 
            LEFT JOIN ingredientes i ON r.id_ingrediente = i.id_ingrediente 
            LEFT JOIN unidades_medida um ON r.id_unidad = um.id_unidad 
            ORDER BY p.nombre_plato, i.nombre_ingrediente";
    $resultado = $conn->query($sql);
    $recetas = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $recetas[] = $fila;
        }
    }
    
    desconectar($conn);
    return $recetas;
}

// Obtener platos para el dropdown
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

// Obtener ingredientes para el dropdown
function obtenerIngredientes() {
    $conn = conectar();
    $sql = "SELECT * FROM ingredientes ORDER BY nombre_ingrediente";
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

$recetas = obtenerRecetas();
$platos = obtenerPlatos();
$ingredientes = obtenerIngredientes();
$unidades = obtenerUnidadesMedida();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Recetas - Marea Roja</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
  <style>
    /* ESTILOS INLINE QUE USA VEHICULOS - Agregar en recetas */
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
    
    .badge-info {
        background-color: #0dcaf0;
        color: black;
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 6px;
    }
</style>

    <!-- Bootstrap y librerías base -->
 <link rel="stylesheet" href="../../css/bootstrap.min.css">
 <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">GESTIÓN DE RECETAS - MAREA ROJA</h1>
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
            <i class="bi bi-journal-text me-2"></i>FORMULARIO DE RECETAS
        </h2>

        <form id="form-receta" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_registro_receta" name="id_registro_receta" value="">
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="id_plato">
                    <i class="bi bi-egg-fried me-1"></i>Plato: *
                </label>
                <select class="form-control" id="id_plato" name="id_plato" required>
                    <option value="">Seleccione un plato</option>
                    <?php foreach($platos as $plato): ?>
                        <option value="<?php echo $plato['id_plato']; ?>">
                            <?php echo htmlspecialchars($plato['nombre_plato']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
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
            <i class="bi bi-list-ul me-2"></i>LISTA DE RECETAS
        </h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-recetas">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Plato</th>
                        <th>Ingrediente</th>
                        <th>Unidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recetas as $receta): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($receta['id_registro_receta']); ?></td>
                        <td><?php echo htmlspecialchars($receta['nombre_plato']); ?></td>
                        <td><?php echo htmlspecialchars($receta['nombre_ingrediente']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars($receta['unidad'] . ' (' . $receta['abreviatura'] . ')'); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $receta['id_registro_receta']; ?>"
                                    data-plato="<?php echo $receta['id_plato']; ?>"
                                    data-ingrediente="<?php echo $receta['id_ingrediente']; ?>"
                                    data-unidad="<?php echo $receta['id_unidad']; ?>">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta receta?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_registro_receta" value="<?php echo $receta['id_registro_receta']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recetas)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay recetas registradas</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-receta');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idRecetaInput = document.getElementById('id_registro_receta');

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
                const plato = this.getAttribute('data-plato');
                const ingrediente = this.getAttribute('data-ingrediente');
                const unidad = this.getAttribute('data-unidad');

                // Llenar formulario
                idRecetaInput.value = id;
                document.getElementById('id_plato').value = plato;
                document.getElementById('id_ingrediente').value = ingrediente;
                document.getElementById('id_unidad').value = unidad;

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idRecetaInput.value = '';
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
            const plato = document.getElementById('id_plato').value;
            const ingrediente = document.getElementById('id_ingrediente').value;
            const unidad = document.getElementById('id_unidad').value;

            if (!plato) {
                alert('El plato es requerido');
                return false;
            }
            if (!ingrediente) {
                alert('El ingrediente es requerido');
                return false;
            }
            if (!unidad) {
                alert('La unidad de medida es requerida');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>