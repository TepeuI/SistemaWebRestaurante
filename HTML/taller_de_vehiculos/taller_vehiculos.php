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
            crearTaller();
            break;
        case 'actualizar':
            actualizarTaller();
            break;
        case 'eliminar':
            eliminarTaller();
            break;
    }
}

function crearTaller() {
    global $conn;
    $conn = conectar();
    
    $nombre_taller = $_POST['nombre_taller'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $id_especialidad = $_POST['id_especialidad'] ?? null;
    $id_sucursal = $_POST['id_sucursal'] ?? null;
    
    $sql = "INSERT INTO talleres (nombre_taller, correo, telefono, id_especialidad, id_sucursal) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $nombre_taller, $correo, $telefono, $id_especialidad, $id_sucursal);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Taller creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear taller: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: taller_vehiculos.php');
    exit();
}

function actualizarTaller() {
    global $conn;
    $conn = conectar();
    
    $id_taller = $_POST['id_taller'] ?? '';
    $nombre_taller = $_POST['nombre_taller'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $id_especialidad = $_POST['id_especialidad'] ?? null;
    $id_sucursal = $_POST['id_sucursal'] ?? null;
    
    $sql = "UPDATE talleres 
            SET nombre_taller = ?, correo = ?, telefono = ?, id_especialidad = ?, id_sucursal = ? 
            WHERE id_taller = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiii", $nombre_taller, $correo, $telefono, $id_especialidad, $id_sucursal, $id_taller);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Taller actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar taller: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: taller_vehiculos.php');
    exit();
}

function eliminarTaller() {
    global $conn;
    $conn = conectar();
    
    $id_taller = $_POST['id_taller'] ?? '';
    
    $sql = "DELETE FROM talleres WHERE id_taller = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_taller);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Taller eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar taller: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: taller_vehiculos.php');
    exit();
}

// Obtener especialidades para el selector
function obtenerEspecialidades() {
    $conn = conectar();
    $sql = "SELECT id_especialidad, descripcion FROM especialidades_reparacion ORDER BY descripcion";
    $resultado = $conn->query($sql);
    $especialidades = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $especialidades[] = $fila;
        }
    }
    
    desconectar($conn);
    return $especialidades;
}

// Obtener sucursales para el selector
function obtenerSucursales() {
    $conn = conectar();
    $sql = "SELECT id_sucursal, direccion FROM sucursales ORDER BY direccion";
    $resultado = $conn->query($sql);
    $sucursales = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $sucursales[] = $fila;
        }
    }
    
    desconectar($conn);
    return $sucursales;
}

// Obtener todos los talleres para mostrar en la tabla
function obtenerTalleres() {
    $conn = conectar();
    $sql = "SELECT t.*, e.descripcion as especialidad, s.direccion as sucursal
            FROM talleres t
            LEFT JOIN especialidades_reparacion e ON t.id_especialidad = e.id_especialidad
            LEFT JOIN sucursales s ON t.id_sucursal = s.id_sucursal
            ORDER BY t.nombre_taller";
    $resultado = $conn->query($sql);
    $talleres = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $talleres[] = $fila;
        }
    }
    
    desconectar($conn);
    return $talleres;
}

$especialidades = obtenerEspecialidades();
$sucursales = obtenerSucursales();
$talleres = obtenerTalleres();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Talleres - Marea Roja</title>
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
    </style>
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">Gestión de Talleres</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - Taller de Vehículos</h2>

            <form id="form-taller" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_taller" name="id_taller" value="">
                
                <div class="col-md-6">
                    <label class="form-label" for="nombre_taller">Nombre del Taller:</label>
                    <input type="text" class="form-control" id="nombre_taller" name="nombre_taller" 
                           required placeholder="Ej. Taller Los Doritos">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_especialidad">Especialidad:</label>
                    <select class="form-control" id="id_especialidad" name="id_especialidad">
                        <option value="">Seleccione una especialidad</option>
                        <?php foreach($especialidades as $especialidad): ?>
                            <option value="<?php echo $especialidad['id_especialidad']; ?>">
                                <?php echo htmlspecialchars($especialidad['descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="id_sucursal">Sucursal:</label>
                    <select class="form-control" id="id_sucursal" name="id_sucursal">
                        <option value="">Seleccione una sucursal</option>
                        <?php foreach($sucursales as $sucursal): ?>
                            <option value="<?php echo $sucursal['id_sucursal']; ?>">
                                <?php echo htmlspecialchars($sucursal['direccion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="telefono">Teléfono:</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" 
                           placeholder="Ej. 1234-5678">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label" for="correo">Correo Electrónico:</label>
                    <input type="email" class="form-control" id="correo" name="correo" 
                           placeholder="Ej. taller@correo.com">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">Lista de Talleres</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-talleres">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Taller</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Especialidad</th>
                            <th>Sucursal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($talleres as $taller): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($taller['id_taller']); ?></td>
                            <td><?php echo htmlspecialchars($taller['nombre_taller']); ?></td>
                            <td><?php echo htmlspecialchars($taller['telefono'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($taller['correo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($taller['especialidad'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($taller['sucursal'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $taller['id_taller']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($taller['nombre_taller']); ?>"
                                        data-telefono="<?php echo htmlspecialchars($taller['telefono']); ?>"
                                        data-correo="<?php echo htmlspecialchars($taller['correo']); ?>"
                                        data-especialidad="<?php echo $taller['id_especialidad']; ?>"
                                        data-sucursal="<?php echo $taller['id_sucursal']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este taller?')">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_taller" value="<?php echo $taller['id_taller']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($talleres)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay talleres registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-taller');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idTallerInput = document.getElementById('id_taller');

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
                    const telefono = this.getAttribute('data-telefono');
                    const correo = this.getAttribute('data-correo');
                    const especialidad = this.getAttribute('data-especialidad');
                    const sucursal = this.getAttribute('data-sucursal');

                    // Llenar formulario
                    idTallerInput.value = id;
                    document.getElementById('nombre_taller').value = nombre;
                    document.getElementById('telefono').value = telefono;
                    document.getElementById('correo').value = correo;
                    document.getElementById('id_especialidad').value = especialidad;
                    document.getElementById('id_sucursal').value = sucursal;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idTallerInput.value = '';
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
                const nombre = document.getElementById('nombre_taller').value.trim();

                if (!nombre) {
                    alert('El nombre del taller es requerido');
                    return false;
                }

                return true;
            }
        });
    </script>
</body>
</html>