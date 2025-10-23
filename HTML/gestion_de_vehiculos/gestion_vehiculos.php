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
            crearVehiculo();
            break;
        case 'actualizar':
            actualizarVehiculo();
            break;
        case 'eliminar':
            eliminarVehiculo();
            break;
    }
}

function crearVehiculo() {
    global $conn;
    $conn = conectar();
    
    $no_placas = $_POST['no_placas'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $anio_vehiculo = $_POST['anio_vehiculo'] ?? '';
    $estado = $_POST['estado'] ?? 'ACTIVO';
    
    $sql = "INSERT INTO vehiculos (no_placas, marca, modelo, anio_vehiculo, estado) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $no_placas, $marca, $modelo, $anio_vehiculo, $estado);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Vehículo creado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear vehículo: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_vehiculos.php');
    exit();
}

function actualizarVehiculo() {
    global $conn;
    $conn = conectar();
    
    $id_placa = $_POST['id_placa'] ?? '';
    $no_placas = $_POST['no_placas'] ?? '';
    $marca = $_POST['marca'] ?? '';
    $modelo = $_POST['modelo'] ?? '';
    $anio_vehiculo = $_POST['anio_vehiculo'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    $sql = "UPDATE vehiculos SET no_placas = ?, marca = ?, modelo = ?, anio_vehiculo = ?, estado = ? 
            WHERE id_placa = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisi", $no_placas, $marca, $modelo, $anio_vehiculo, $estado, $id_placa);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Vehículo actualizado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar vehículo: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_vehiculos.php');
    exit();
}

function eliminarVehiculo() {
    global $conn;
    $conn = conectar();
    
    $id_placa = $_POST['id_placa'] ?? '';
    
    $sql = "DELETE FROM vehiculos WHERE id_placa = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_placa);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Vehículo eliminado exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al eliminar vehículo: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: gestion_vehiculos.php');
    exit();
}

// Obtener todos los vehículos para mostrar en la tabla
function obtenerVehiculos() {
    $conn = conectar();
    $sql = "SELECT * FROM vehiculos ORDER BY id_placa";
    $resultado = $conn->query($sql);
    $vehiculos = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $vehiculos[] = $fila;
        }
    }
    
    desconectar($conn);
    return $vehiculos;
}

$vehiculos = obtenerVehiculos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vehículos - Marea Roja</title>
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
            <h1 class="mb-0">Gestión de Vehículos</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - Vehículos</h2>

            <form id="form-vehiculo" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_placa" name="id_placa" value="">
                
                <div class="col-md-3">
                    <label class="form-label" for="no_placas">Placa:</label>
                    <input type="text" class="form-control" id="no_placas" name="no_placas" required placeholder="Ej. P812HYN">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="marca">Marca:</label>
                    <input type="text" class="form-control" id="marca" name="marca" required placeholder="Ej. Ford">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="modelo">Modelo:</label>
                    <input type="text" class="form-control" id="modelo" name="modelo" required placeholder="Ej. Ranger">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="anio_vehiculo">Año:</label>
                    <input type="number" class="form-control" id="anio_vehiculo" name="anio_vehiculo" required placeholder="Ej. 2014" min="1900" max="2030">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="estado">Estado:</label>
                    <select class="form-control" id="estado" name="estado" required>
                        <option value="ACTIVO">ACTIVO</option>
                        <option value="EN_TALLER">EN TALLER</option>
                        <option value="BAJA">BAJA</option>
                    </select>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">Lista de Vehículos</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-vehiculos">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Placa</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Año</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vehiculos as $vehiculo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vehiculo['id_placa']); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['no_placas']); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['marca']); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['modelo']); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['anio_vehiculo']); ?></td>
                            <td>
                                <span class="badge 
                                    <?php echo $vehiculo['estado'] == 'ACTIVO' ? 'bg-success' : 
                                           ($vehiculo['estado'] == 'EN_TALLER' ? 'bg-warning' : 'bg-danger'); ?>">
                                    <?php echo htmlspecialchars($vehiculo['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $vehiculo['id_placa']; ?>"
                                        data-placa="<?php echo htmlspecialchars($vehiculo['no_placas']); ?>"
                                        data-marca="<?php echo htmlspecialchars($vehiculo['marca']); ?>"
                                        data-modelo="<?php echo htmlspecialchars($vehiculo['modelo']); ?>"
                                        data-anio="<?php echo $vehiculo['anio_vehiculo']; ?>"
                                        data-estado="<?php echo $vehiculo['estado']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este vehículo?')">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_placa" value="<?php echo $vehiculo['id_placa']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vehiculos)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay vehículos registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-vehiculo');
            const btnNuevo = document.getElementById('btn-nuevo');
            const btnGuardar = document.getElementById('btn-guardar');
            const btnActualizar = document.getElementById('btn-actualizar');
            const btnCancelar = document.getElementById('btn-cancelar');
            const operacionInput = document.getElementById('operacion');
            const idPlacaInput = document.getElementById('id_placa');

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
                    const placa = this.getAttribute('data-placa');
                    const marca = this.getAttribute('data-marca');
                    const modelo = this.getAttribute('data-modelo');
                    const anio = this.getAttribute('data-anio');
                    const estado = this.getAttribute('data-estado');

                    // Llenar formulario
                    idPlacaInput.value = id;
                    document.getElementById('no_placas').value = placa;
                    document.getElementById('marca').value = marca;
                    document.getElementById('modelo').value = modelo;
                    document.getElementById('anio_vehiculo').value = anio;
                    document.getElementById('estado').value = estado;

                    mostrarBotonesActualizar();
                });
            });

            function limpiarFormulario() {
                form.reset();
                idPlacaInput.value = '';
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
                const placa = document.getElementById('no_placas').value.trim();
                const marca = document.getElementById('marca').value.trim();
                const modelo = document.getElementById('modelo').value.trim();
                const anio = document.getElementById('anio_vehiculo').value;

                if (!placa) {
                    alert('La placa es requerida');
                    return false;
                }
                if (!marca) {
                    alert('La marca es requerida');
                    return false;
                }
                if (!modelo) {
                    alert('El modelo es requerido');
                    return false;
                }
                if (!anio) {
                    alert('El año es requerido');
                    return false;
                }

                return true;
            }
        });
    </script>
</body>
</html>