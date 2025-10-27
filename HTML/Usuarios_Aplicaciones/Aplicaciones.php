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
            crearAplicacion();
            break;
        case 'actualizar':
            actualizarAplicacion();
            break;
        case 'eliminar':
            eliminarAplicacion();
            break;
    }
}

function crearAplicacion() {
    global $conn;
    $conn = conectar();
    
    $Aplicacion = trim($_POST['Aplicacion'] ?? '');
    $descripcion_aplicacion = trim($_POST['descripcion_aplicacion'] ?? '');
    
    $sql = "INSERT INTO aplicaciones (Aplicacion, descripcion_aplicacion) 
            VALUES (?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $Aplicacion, $descripcion_aplicacion);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Aplicación creada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear aplicación: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: aplicaciones.php');
    exit();
}

function actualizarAplicacion() {
    global $conn;
    $conn = conectar();
    
    $id_aplicacion = intval($_POST['id_aplicacion'] ?? '');
    $Aplicacion = trim($_POST['Aplicacion'] ?? '');
    $descripcion_aplicacion = trim($_POST['descripcion_aplicacion'] ?? '');
    
    $sql = "UPDATE aplicaciones SET Aplicacion = ?, descripcion_aplicacion = ? 
            WHERE id_aplicacion = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $Aplicacion, $descripcion_aplicacion, $id_aplicacion);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Aplicación actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar aplicación: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: aplicaciones.php');
    exit();
}

function eliminarAplicacion() {
    global $conn;
    $conn = conectar();
    
    $id_aplicacion = intval($_POST['id_aplicacion'] ?? '');
    
    // Verificar si la aplicación está en uso en permisos
    $sql_check = "SELECT id_permiso FROM permisos_usuario_aplicacion WHERE id_aplicacion = ? LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_aplicacion);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensaje'] = "No se puede eliminar la aplicación porque está siendo usada en permisos";
        $_SESSION['tipo_mensaje'] = "error";
        $stmt_check->close();
        desconectar($conn);
        header('Location: aplicaciones.php');
        exit();
    }
    $stmt_check->close();
    
    $sql = "DELETE FROM aplicaciones WHERE id_aplicacion = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_aplicacion);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Aplicación eliminada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar aplicación: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: aplicaciones.php');
    exit();
}

// Obtener todas las aplicaciones para mostrar en la tabla
function obtenerAplicaciones() {
    $conn = conectar();
    $sql = "SELECT * FROM aplicaciones ORDER BY Aplicacion";
    $resultado = $conn->query($sql);
    $aplicaciones = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $aplicaciones[] = $fila;
        }
    }
    
    desconectar($conn);
    return $aplicaciones;
}

$aplicaciones = obtenerAplicaciones();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Aplicaciones - Marea Roja</title>

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
        <h1 class="mb-0">GESTIÓN DE APLICACIONES - MAREA ROJA</h1>
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
            <i class="bi bi-window-stack me-2"></i>FORMULARIO DE APLICACIONES
        </h2>

        <form id="form-aplicacion" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_aplicacion" name="id_aplicacion" value="">
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="Aplicacion">
                    <i class="bi bi-window me-1"></i>Aplicación: *
                </label>
                <input type="text" class="form-control" id="Aplicacion" name="Aplicacion" 
                       required placeholder="Ej. Gestión de Inventario" maxlength="45">
            </div>
            
            <div class="col-md-8">
                <label class="form-label fw-semibold" for="descripcion_aplicacion">
                    <i class="bi bi-card-text me-1"></i>Descripción: *
                </label>
                <input type="text" class="form-control" id="descripcion_aplicacion" name="descripcion_aplicacion" 
                       required placeholder="Ej. Módulo para gestionar inventario de ingredientes" maxlength="120">
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
            <i class="bi bi-list-ul me-2"></i>LISTA DE APLICACIONES
        </h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-aplicaciones">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Aplicación</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($aplicaciones as $aplicacion): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($aplicacion['id_aplicacion']); ?></td>
                        <td><?php echo htmlspecialchars($aplicacion['Aplicacion']); ?></td>
                        <td class="descripcion-cell" title="<?php echo htmlspecialchars($aplicacion['descripcion_aplicacion']); ?>">
                            <?php echo htmlspecialchars($aplicacion['descripcion_aplicacion']); ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $aplicacion['id_aplicacion']; ?>"
                                    data-aplicacion="<?php echo htmlspecialchars($aplicacion['Aplicacion']); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($aplicacion['descripcion_aplicacion']); ?>">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta aplicación?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_aplicacion" value="<?php echo $aplicacion['id_aplicacion']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($aplicaciones)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No hay aplicaciones registradas</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-aplicacion');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idAplicacionInput = document.getElementById('id_aplicacion');

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
                const aplicacion = this.getAttribute('data-aplicacion');
                const descripcion = this.getAttribute('data-descripcion');

                // Llenar formulario
                idAplicacionInput.value = id;
                document.getElementById('Aplicacion').value = aplicacion;
                document.getElementById('descripcion_aplicacion').value = descripcion;

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idAplicacionInput.value = '';
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
            const aplicacion = document.getElementById('Aplicacion').value.trim();
            const descripcion = document.getElementById('descripcion_aplicacion').value.trim();

            if (!aplicacion) {
                alert('El nombre de la aplicación es requerido');
                return false;
            }
            if (!descripcion) {
                alert('La descripción es requerida');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>