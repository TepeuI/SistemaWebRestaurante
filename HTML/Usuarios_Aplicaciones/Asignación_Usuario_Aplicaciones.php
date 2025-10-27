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
            crearPermiso();
            break;
        case 'actualizar':
            actualizarPermiso();
            break;
        case 'eliminar':
            eliminarPermiso();
            break;
    }
}

function crearPermiso() {
    global $conn;
    $conn = conectar();
    
    $id_usuario = intval($_POST['id_usuario'] ?? '');
    $id_aplicacion = intval($_POST['id_aplicacion'] ?? '');
    $permiso_insertar = isset($_POST['permiso_insertar']) ? 1 : 0;
    $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
    $permiso_actualizar = isset($_POST['permiso_actualizar']) ? 1 : 0;
    $permiso_eliminar = isset($_POST['permiso_eliminar']) ? 1 : 0;
    
    $sql = "INSERT INTO permisos_usuario_aplicacion (id_usuario, id_aplicacion, permiso_insertar, permiso_consultar, permiso_actualizar, permiso_eliminar) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiii", $id_usuario, $id_aplicacion, $permiso_insertar, $permiso_consultar, $permiso_actualizar, $permiso_eliminar);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Permiso asignado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al asignar permiso: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Asignación_Usuario_Aplicaciones.php');
    exit();
}

function actualizarPermiso() {
    global $conn;
    $conn = conectar();
    
    $id_permiso = intval($_POST['id_permiso'] ?? '');
    $id_usuario = intval($_POST['id_usuario'] ?? '');
    $id_aplicacion = intval($_POST['id_aplicacion'] ?? '');
    $permiso_insertar = isset($_POST['permiso_insertar']) ? 1 : 0;
    $permiso_consultar = isset($_POST['permiso_consultar']) ? 1 : 0;
    $permiso_actualizar = isset($_POST['permiso_actualizar']) ? 1 : 0;
    $permiso_eliminar = isset($_POST['permiso_eliminar']) ? 1 : 0;
    
    $sql = "UPDATE permisos_usuario_aplicacion SET id_usuario = ?, id_aplicacion = ?, permiso_insertar = ?, permiso_consultar = ?, permiso_actualizar = ?, permiso_eliminar = ? 
            WHERE id_permiso = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiiii", $id_usuario, $id_aplicacion, $permiso_insertar, $permiso_consultar, $permiso_actualizar, $permiso_eliminar, $id_permiso);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Permiso actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar permiso: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Asignación_Usuario_Aplicaciones.php');
    exit();
}

function eliminarPermiso() {
    global $conn;
    $conn = conectar();
    
    $id_permiso = intval($_POST['id_permiso'] ?? '');
    
    $sql = "DELETE FROM permisos_usuario_aplicacion WHERE id_permiso = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_permiso);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Permiso eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar permiso: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: Asignación_Usuario_Aplicaciones.php');
    exit();
}

// Obtener todos los permisos para mostrar en la tabla
function obtenerPermisos() {
    $conn = conectar();
    $sql = "SELECT p.*, 
                   u.usuario as nombre_usuario,
                   a.Aplicacion as nombre_aplicacion
            FROM permisos_usuario_aplicacion p 
            LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario 
            LEFT JOIN aplicaciones a ON p.id_aplicacion = a.id_aplicacion 
            ORDER BY u.usuario, a.Aplicacion";
    $resultado = $conn->query($sql);
    $permisos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $permisos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $permisos;
}

// Obtener usuarios para el dropdown
function obtenerUsuarios() {
    $conn = conectar();
    $sql = "SELECT id_usuario, usuario FROM usuarios ORDER BY usuario";
    $resultado = $conn->query($sql);
    $usuarios = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $usuarios[] = $fila;
        }
    }
    
    desconectar($conn);
    return $usuarios;
}

// Obtener aplicaciones para el dropdown
function obtenerAplicaciones() {
    $conn = conectar();
    $sql = "SELECT id_aplicacion, Aplicacion FROM aplicaciones ORDER BY Aplicacion";
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

$permisos = obtenerPermisos();
$usuarios = obtenerUsuarios();
$aplicaciones = obtenerAplicaciones();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Permisos - Marea Roja</title>

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
        
        .form-check-input:checked {
            background-color: #1e40af;
            border-color: #1e40af;
        }
        
        .permiso-si {
            color: #10b981;
            font-weight: bold;
        }
        
        .permiso-no {
            color: #ef4444;
        }
        
        .checkboxes-container {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e2e8f0;
        }
    </style>

    <!-- Bootstrap y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>

<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">GESTIÓN DE PERMISOS - MAREA ROJA</h1>
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
            <i class="bi bi-shield-check me-2"></i>ASIGNACIÓN DE PERMISOS
        </h2>

        <form id="form-permiso" method="post" class="row g-3">
            <input type="hidden" id="operacion" name="operacion" value="crear">
            <input type="hidden" id="id_permiso" name="id_permiso" value="">
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="id_usuario">
                    <i class="bi bi-person me-1"></i>Usuario: *
                </label>
                <select class="form-control" id="id_usuario" name="id_usuario" required>
                    <option value="">Seleccione un usuario</option>
                    <?php foreach($usuarios as $usuario): ?>
                        <option value="<?php echo $usuario['id_usuario']; ?>">
                            <?php echo htmlspecialchars($usuario['usuario']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold" for="id_aplicacion">
                    <i class="bi bi-window me-1"></i>Aplicación: *
                </label>
                <select class="form-control" id="id_aplicacion" name="id_aplicacion" required>
                    <option value="">Seleccione una aplicación</option>
                    <?php foreach($aplicaciones as $aplicacion): ?>
                        <option value="<?php echo $aplicacion['id_aplicacion']; ?>">
                            <?php echo htmlspecialchars($aplicacion['Aplicacion']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-12">
                <div class="checkboxes-container">
                    <label class="form-label fw-semibold mb-3">
                        <i class="bi bi-key me-1"></i>Permisos:
                    </label>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permiso_consultar" name="permiso_consultar" value="1" checked>
                                <label class="form-check-label" for="permiso_consultar">
                                    Consultar
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permiso_insertar" name="permiso_insertar" value="1">
                                <label class="form-check-label" for="permiso_insertar">
                                    Insertar
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permiso_actualizar" name="permiso_actualizar" value="1">
                                <label class="form-check-label" for="permiso_actualizar">
                                    Actualizar
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permiso_eliminar" name="permiso_eliminar" value="1">
                                <label class="form-check-label" for="permiso_eliminar">
                                    Eliminar
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
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
            <i class="bi bi-list-ul me-2"></i>PERMISOS ASIGNADOS
        </h2>
        
        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered" id="tabla-permisos">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Aplicación</th>
                        <th>Consultar</th>
                        <th>Insertar</th>
                        <th>Actualizar</th>
                        <th>Eliminar</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($permisos as $permiso): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($permiso['id_permiso']); ?></td>
                        <td><?php echo htmlspecialchars($permiso['nombre_usuario']); ?></td>
                        <td><?php echo htmlspecialchars($permiso['nombre_aplicacion']); ?></td>
                        <td class="<?php echo $permiso['permiso_consultar'] ? 'permiso-si' : 'permiso-no'; ?>">
                            <?php echo $permiso['permiso_consultar'] ? '✓ SI' : '✗ NO'; ?>
                        </td>
                        <td class="<?php echo $permiso['permiso_insertar'] ? 'permiso-si' : 'permiso-no'; ?>">
                            <?php echo $permiso['permiso_insertar'] ? '✓ SI' : '✗ NO'; ?>
                        </td>
                        <td class="<?php echo $permiso['permiso_actualizar'] ? 'permiso-si' : 'permiso-no'; ?>">
                            <?php echo $permiso['permiso_actualizar'] ? '✓ SI' : '✗ NO'; ?>
                        </td>
                        <td class="<?php echo $permiso['permiso_eliminar'] ? 'permiso-si' : 'permiso-no'; ?>">
                            <?php echo $permiso['permiso_eliminar'] ? '✓ SI' : '✗ NO'; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                    data-id="<?php echo $permiso['id_permiso']; ?>"
                                    data-usuario="<?php echo $permiso['id_usuario']; ?>"
                                    data-aplicacion="<?php echo $permiso['id_aplicacion']; ?>"
                                    data-consultar="<?php echo $permiso['permiso_consultar']; ?>"
                                    data-insertar="<?php echo $permiso['permiso_insertar']; ?>"
                                    data-actualizar="<?php echo $permiso['permiso_actualizar']; ?>"
                                    data-eliminar="<?php echo $permiso['permiso_eliminar']; ?>">
                                <i class="bi bi-pencil me-1"></i>Editar
                            </button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este permiso?')">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_permiso" value="<?php echo $permiso['id_permiso']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-action">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($permisos)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No hay permisos asignados</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-permiso');
        const btnNuevo = document.getElementById('btn-nuevo');
        const btnGuardar = document.getElementById('btn-guardar');
        const btnActualizar = document.getElementById('btn-actualizar');
        const btnCancelar = document.getElementById('btn-cancelar');
        const operacionInput = document.getElementById('operacion');
        const idPermisoInput = document.getElementById('id_permiso');

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
                const usuario = this.getAttribute('data-usuario');
                const aplicacion = this.getAttribute('data-aplicacion');
                const consultar = this.getAttribute('data-consultar');
                const insertar = this.getAttribute('data-insertar');
                const actualizar = this.getAttribute('data-actualizar');
                const eliminar = this.getAttribute('data-eliminar');

                // Llenar formulario
                idPermisoInput.value = id;
                document.getElementById('id_usuario').value = usuario;
                document.getElementById('id_aplicacion').value = aplicacion;
                document.getElementById('permiso_consultar').checked = (consultar === '1');
                document.getElementById('permiso_insertar').checked = (insertar === '1');
                document.getElementById('permiso_actualizar').checked = (actualizar === '1');
                document.getElementById('permiso_eliminar').checked = (eliminar === '1');

                mostrarBotonesActualizar();
            });
        });

        function limpiarFormulario() {
            form.reset();
            idPermisoInput.value = '';
            operacionInput.value = 'crear';
            // Establecer consultar como checked por defecto
            document.getElementById('permiso_consultar').checked = true;
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
            const usuario = document.getElementById('id_usuario').value;
            const aplicacion = document.getElementById('id_aplicacion').value;

            if (!usuario) {
                alert('El usuario es requerido');
                return false;
            }
            if (!aplicacion) {
                alert('La aplicación es requerida');
                return false;
            }

            return true;
        }
    });
</script>
</body>
</html>