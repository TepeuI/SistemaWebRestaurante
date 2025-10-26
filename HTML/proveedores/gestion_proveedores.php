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
        case 'crear_proveedor':
            crearProveedor();
            break;
        case 'actualizar_proveedor':
            actualizarProveedor();
            break;
        case 'eliminar_proveedor':
            eliminarProveedor();
            break;
    }
}

function crearProveedor() {
    global $conn;
    $conn = conectar();
    
    $nombre_proveedor = $_POST['nombre_proveedor'] ?? '';
    $correo_proveedor = $_POST['correo_proveedor'] ?? '';
    $telefono_proveedor = $_POST['telefono_proveedor'] ?? '';
    
    $sql = "INSERT INTO proveedores (nombre_proveedor, correo_proveedor, telefono_proveedor) 
            VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre_proveedor, $correo_proveedor, $telefono_proveedor);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Proveedor creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear proveedor: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_proveedores.php');
    exit();
}

function actualizarProveedor() {
    global $conn;
    $conn = conectar();
    
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    $nombre_proveedor = $_POST['nombre_proveedor'] ?? '';
    $correo_proveedor = $_POST['correo_proveedor'] ?? '';
    $telefono_proveedor = $_POST['telefono_proveedor'] ?? '';
    
    $sql = "UPDATE proveedores SET nombre_proveedor = ?, correo_proveedor = ?, telefono_proveedor = ? 
            WHERE id_proveedor = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nombre_proveedor, $correo_proveedor, $telefono_proveedor, $id_proveedor);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Proveedor actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar proveedor: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_proveedores.php');
    exit();
}

function eliminarProveedor() {
    global $conn;
    $conn = conectar();
    
    $id_proveedor = $_POST['id_proveedor'] ?? '';
    
    $sql = "DELETE FROM proveedores WHERE id_proveedor = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_proveedor);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Proveedor eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar proveedor: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_proveedores.php');
    exit();
}

// Obtener todos los proveedores para mostrar en la tabla
function obtenerProveedores() {
    $conn = conectar();
    
    $sql = "SELECT * FROM proveedores ORDER BY nombre_proveedor";
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

$proveedores = obtenerProveedores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - Marina Roja</title>
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
            max-height: 500px;
            overflow-y: auto;
        }
        .badge-proveedor {
            background-color: #6f42c1;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .email-cell {
            word-break: break-all;
        }
        .info-proveedor {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">GESTIÓN DE PROVEEDORES</h1>
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
            echo "Proveedores registrados: " . count($proveedores);
            ?>
        </div>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE PROVEEDORES</h2>

            <form id="form-proveedores" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_proveedor">
                <input type="hidden" id="id_proveedor" name="id_proveedor" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="nombre_proveedor">Nombre del Proveedor:</label>
                    <input type="text" class="form-control" id="nombre_proveedor" name="nombre_proveedor" 
                           required placeholder="Ej: Muebles y Diseños S.A.">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="telefono_proveedor">Teléfono:</label>
                    <input type="text" class="form-control" id="telefono_proveedor" name="telefono_proveedor" 
                           placeholder="Ej: 5555-1234">
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="correo_proveedor">Correo Electrónico:</label>
                    <input type="email" class="form-control" id="correo_proveedor" name="correo_proveedor" 
                           placeholder="Ej: contacto@proveedor.com">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">LISTA DE PROVEEDORES REGISTRADOS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-proveedores">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Correo Electrónico</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($proveedores as $proveedor): ?>
                        <tr>
                            <td>
                                <span class="badge-proveedor">#<?php echo htmlspecialchars($proveedor['id_proveedor']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?></td>
                            <td class="email-cell">
                                <?php if (!empty($proveedor['correo_proveedor'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($proveedor['correo_proveedor']); ?>">
                                        <?php echo htmlspecialchars($proveedor['correo_proveedor']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($proveedor['telefono_proveedor'])): ?>
                                    <?php echo htmlspecialchars($proveedor['telefono_proveedor']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No especificado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $proveedor['id_proveedor']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?>"
                                        data-correo="<?php echo htmlspecialchars($proveedor['correo_proveedor'] ?? ''); ?>"
                                        data-telefono="<?php echo htmlspecialchars($proveedor['telefono_proveedor'] ?? ''); ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este proveedor?')">
                                    <input type="hidden" name="operacion" value="eliminar_proveedor">
                                    <input type="hidden" name="id_proveedor" value="<?php echo $proveedor['id_proveedor']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay proveedores registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-proveedores');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idProveedorInput = document.getElementById('id_proveedor');

            // Botón Nuevo
            btnNuevo.addEventListener('click', function() {
                limpiarFormulario();
                mostrarBotonesGuardar();
            });

            // Botón Guardar (Crear)
            btnGuardar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'crear_proveedor';
                    form.submit();
                }
            });

            // Botón Actualizar
            btnActualizar.addEventListener('click', function() {
                if (validarFormulario()) {
                    operacionInput.value = 'actualizar_proveedor';
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
                    const correo = this.getAttribute('data-correo');
                    const telefono = this.getAttribute('data-telefono');

                    // Llenar formulario
                    idProveedorInput.value = id;
                    document.getElementById('nombre_proveedor').value = nombre;
                    document.getElementById('correo_proveedor').value = correo;
                    document.getElementById('telefono_proveedor').value = telefono;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idProveedorInput.value = '';
                operacionInput.value = 'crear_proveedor';
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
                const nombre = document.getElementById('nombre_proveedor').value.trim();
                const correo = document.getElementById('correo_proveedor').value.trim();

                if (!nombre) {
                    alert('El nombre del proveedor es requerido');
                    return false;
                }

                // Validación opcional de email
                if (correo && !isValidEmail(correo)) {
                    alert('Por favor ingrese un correo electrónico válido');
                    return false;
                }

                return true;
            }

            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Inicializar
            limpiarFormulario();
        });
    </script>
</body>
</html>