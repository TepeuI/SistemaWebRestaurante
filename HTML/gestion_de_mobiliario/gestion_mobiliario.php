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
            crearMobiliario();
            break;
        case 'actualizar':
            actualizarMobiliario();
            break;
        case 'eliminar':
            eliminarMobiliario();
            break;
    }
}

function crearMobiliario() {
    global $conn;
    $conn = conectar();
    
    $nombre_mobiliario = $_POST['nombre_mobiliario'] ?? '';
    $id_tipo_mobiliario = $_POST['id_tipo_mobiliario'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $cantidad_en_stock = $_POST['cantidad_en_stock'] ?? 0;
    
    $sql = "INSERT INTO inventario_mobiliario (nombre_mobiliario, id_tipo_mobiliario, descripcion, cantidad_en_stock) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $nombre_mobiliario, $id_tipo_mobiliario, $descripcion, $cantidad_en_stock);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mobiliario creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear mobiliario: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_mobiliario.php');
    exit();
}

function actualizarMobiliario() {
    global $conn;
    $conn = conectar();
    
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    $nombre_mobiliario = $_POST['nombre_mobiliario'] ?? '';
    $id_tipo_mobiliario = $_POST['id_tipo_mobiliario'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $cantidad_en_stock = $_POST['cantidad_en_stock'] ?? 0;
    
    $sql = "UPDATE inventario_mobiliario SET nombre_mobiliario = ?, id_tipo_mobiliario = ?, descripcion = ?, cantidad_en_stock = ? 
            WHERE id_mobiliario = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisii", $nombre_mobiliario, $id_tipo_mobiliario, $descripcion, $cantidad_en_stock, $id_mobiliario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mobiliario actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar mobiliario: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_mobiliario.php');
    exit();
}

function eliminarMobiliario() {
    global $conn;
    $conn = conectar();
    
    $id_mobiliario = $_POST['id_mobiliario'] ?? '';
    
    $sql = "DELETE FROM inventario_mobiliario WHERE id_mobiliario = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mobiliario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Mobiliario eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar mobiliario: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_mobiliario.php');
    exit();
}

// Obtener tipos de mobiliario para el select
function obtenerTiposMobiliario() {
    $conn = conectar();
    $sql = "SELECT id_tipo_mobiliario, descripcion FROM tipos_mobiliario ORDER BY descripcion";
    $resultado = $conn->query($sql);
    $tipos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $tipos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $tipos;
}

// Obtener todos los mobiliarios del inventario
function obtenerMobiliarios() {
    $conn = conectar();
    $sql = "SELECT im.*, tm.descripcion as tipo_mobiliario 
            FROM inventario_mobiliario im 
            LEFT JOIN tipos_mobiliario tm ON im.id_tipo_mobiliario = tm.id_tipo_mobiliario 
            ORDER BY im.nombre_mobiliario";
    $resultado = $conn->query($sql);
    $mobiliarios = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $mobiliarios[] = $fila;
        }
    }
    
    desconectar($conn);
    return $mobiliarios;
}

$tipos_mobiliario = obtenerTiposMobiliario();
$mobiliarios = obtenerMobiliarios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Mobiliario - Marina Roja</title>
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
        .descripcion-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">GESTIÓN DE MOBILIARIO</h1>
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
            echo "Tipos Mobiliario: " . count($tipos_mobiliario) . " | ";
            echo "Mobiliarios (inventario): " . count($mobiliarios);
            ?>
        </div>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">FORMULARIO - CONTROL DE MOBILIARIO</h2>

            <form id="form-mobiliario" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_mobiliario" name="id_mobiliario" value="">
                
                <div class="col-md-4">
                    <label class="form-label" for="nombre_mobiliario">Nombre del Mobiliario:</label>
                    <input type="text" class="form-control" id="nombre_mobiliario" name="nombre_mobiliario" required placeholder="Ej. Silla ejecutiva">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="id_tipo_mobiliario">Tipo de Mobiliario:</label>
                    <select class="form-control" id="id_tipo_mobiliario" name="id_tipo_mobiliario" required>
                        <option value="">Seleccione un tipo</option>
                        <?php foreach($tipos_mobiliario as $tipo): ?>
                            <option value="<?php echo $tipo['id_tipo_mobiliario']; ?>">
                                <?php echo htmlspecialchars($tipo['descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="cantidad_en_stock">Cantidad en Stock:</label>
                    <input type="number" class="form-control" id="cantidad_en_stock" name="cantidad_en_stock" min="0" required value="0">
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion">Descripción:</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Descripción detallada del mobiliario..."></textarea>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">INVENTARIO DE MOBILIARIO</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-mobiliario">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mobiliarios as $mobiliario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mobiliario['id_mobiliario']); ?></td>
                            <td><?php echo htmlspecialchars($mobiliario['nombre_mobiliario']); ?></td>
                            <td><?php echo htmlspecialchars($mobiliario['tipo_mobiliario'] ?? 'N/A'); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($mobiliario['descripcion'] ?? ''); ?>">
                                <?php echo htmlspecialchars($mobiliario['descripcion'] ?? 'Sin descripción'); ?>
                            </td>
                            <td><?php echo htmlspecialchars($mobiliario['cantidad_en_stock']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $mobiliario['id_mobiliario']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($mobiliario['nombre_mobiliario']); ?>"
                                        data-tipo="<?php echo $mobiliario['id_tipo_mobiliario']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($mobiliario['descripcion'] ?? ''); ?>"
                                        data-cantidad="<?php echo $mobiliario['cantidad_en_stock']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este mobiliario?')">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_mobiliario" value="<?php echo $mobiliario['id_mobiliario']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($mobiliarios)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay mobiliarios registrados en el inventario</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-mobiliario');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idMobiliarioInput = document.getElementById('id_mobiliario');

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
                    const tipo = this.getAttribute('data-tipo');
                    const descripcion = this.getAttribute('data-descripcion');
                    const cantidad = this.getAttribute('data-cantidad');

                    // Llenar formulario
                    idMobiliarioInput.value = id;
                    document.getElementById('nombre_mobiliario').value = nombre;
                    document.getElementById('id_tipo_mobiliario').value = tipo;
                    document.getElementById('descripcion').value = descripcion;
                    document.getElementById('cantidad_en_stock').value = cantidad;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idMobiliarioInput.value = '';
                operacionInput.value = 'crear';
                document.getElementById('cantidad_en_stock').value = '0';
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
                const nombre = document.getElementById('nombre_mobiliario').value.trim();
                const tipo = document.getElementById('id_tipo_mobiliario').value;
                const cantidad = document.getElementById('cantidad_en_stock').value;

                if (!nombre) {
                    alert('El nombre del mobiliario es requerido');
                    return false;
                }
                if (!tipo) {
                    alert('El tipo de mobiliario es requerido');
                    return false;
                }
                if (!cantidad || cantidad < 0) {
                    alert('La cantidad debe ser mayor o igual a 0');
                    return false;
                }

                return true;
            }

            // Inicializar
            limpiarFormulario();
        });
    </script>
</body>
</html>