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
    
    $no_placa = $_POST['no_placas'] ?? '';
    $marca_vehiculo = $_POST['marca'] ?? '';
    $modelo_vehiculo = $_POST['modelo'] ?? '';
    $anio_vehiculo = $_POST['anio_vehiculo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $estado = $_POST['estado'] ?? 'ACTIVO';
    $id_mobiliario = $_POST['id_mobiliario'] ?? NULL;
    
    // Si id_mobiliario está vacío, establecerlo como NULL
    if ($id_mobiliario === '') {
        $id_mobiliario = NULL;
    }
    
    $sql = "INSERT INTO vehiculos (no_placa, marca_vehiculo, modelo_vehiculo, anio_vehiculo, descripcion, estado, id_mobiliario) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssissi", $no_placa, $marca_vehiculo, $modelo_vehiculo, $anio_vehiculo, $descripcion, $estado, $id_mobiliario);
    
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
    
    $id_vehiculo = $_POST['id_placa'] ?? '';
    $no_placa = $_POST['no_placas'] ?? '';
    $marca_vehiculo = $_POST['marca'] ?? '';
    $modelo_vehiculo = $_POST['modelo'] ?? '';
    $anio_vehiculo = $_POST['anio_vehiculo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $estado = $_POST['estado'] ?? 'ACTIVO';
    $id_mobiliario = $_POST['id_mobiliario'] ?? NULL;
    
    // Si id_mobiliario está vacío, establecerlo como NULL
    if ($id_mobiliario === '') {
        $id_mobiliario = NULL;
    }
    
    $sql = "UPDATE vehiculos SET no_placa = ?, marca_vehiculo = ?, modelo_vehiculo = ?, anio_vehiculo = ?, descripcion = ?, estado = ?, id_mobiliario = ? 
            WHERE id_vehiculo = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssissii", $no_placa, $marca_vehiculo, $modelo_vehiculo, $anio_vehiculo, $descripcion, $estado, $id_mobiliario, $id_vehiculo);
    
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
    
    $id_vehiculo = $_POST['id_placa'] ?? '';
    
    try {
        // Verificar si el vehículo está siendo usado en otras tablas
        $check_viajes = $conn->prepare("SELECT COUNT(*) as count FROM viajes WHERE id_vehiculo = ?");
        $check_viajes->bind_param("i", $id_vehiculo);
        $check_viajes->execute();
        $result_viajes = $check_viajes->get_result();
        $row_viajes = $result_viajes->fetch_assoc();
        $check_viajes->close();
        
        if ($row_viajes['count'] > 0) {
            $_SESSION['mensaje'] = "No se puede eliminar el vehículo porque está siendo utilizado en viajes registrados.";
            $_SESSION['tipo_mensaje'] = "error";
            desconectar($conn);
            header('Location: gestion_vehiculos.php');
            exit();
        }
        
        // Puedes agregar más verificaciones para otras tablas aquí si es necesario
        // Por ejemplo:
        // $check_mantenimiento = $conn->prepare("SELECT COUNT(*) as count FROM mantenimientos WHERE id_vehiculo = ?");
        // ...
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM vehiculos WHERE id_vehiculo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_vehiculo);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Vehículo eliminado exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            // Capturar cualquier otro error que pueda ocurrir
            $_SESSION['mensaje'] = "Error al eliminar vehículo: " . $conn->error;
            $_SESSION['tipo_mensaje'] = "error";
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el vehículo porque está siendo utilizado en otros registros del sistema.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar vehículo: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "error";
        }
    } catch (Exception $e) {
        // Capturar cualquier otra excepción
        $_SESSION['mensaje'] = "Error al eliminar vehículo: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    desconectar($conn);
    header('Location: gestion_vehiculos.php');
    exit();
}

// Obtener todos los vehículos para mostrar en la tabla
function obtenerVehiculos() {
    $conn = conectar();
    
    // CORREGIDO: Usar inventario_mobiliario en lugar de mobiliario
    $sql = "SELECT v.*, im.nombre_mobiliario 
            FROM vehiculos v 
            LEFT JOIN inventario_mobiliario im ON v.id_mobiliario = im.id_mobiliario 
            ORDER BY v.id_vehiculo";
    
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

// Obtener mobiliario para el select - CORREGIDO: usar inventario_mobiliario
function obtenerMobiliario() {
    $conn = conectar();
    
    try {
        // Solo mobiliario de tipo vehículos (id_tipo_mobiliario = 1) de inventario_mobiliario
        $sql = "SELECT id_mobiliario, nombre_mobiliario 
                FROM inventario_mobiliario 
                WHERE id_tipo_mobiliario = 1 
                ORDER BY nombre_mobiliario";
        
        $resultado = $conn->query($sql);
        $mobiliario = [];
        
        if ($resultado && $resultado->num_rows > 0) {
            while($fila = $resultado->fetch_assoc()) {
                $mobiliario[] = $fila;
            }
        }
        
        desconectar($conn);
        return $mobiliario;
        
    } catch (Exception $e) {
        // Si hay error, retornar array vacío
        desconectar($conn);
        return [];
    }
}

$vehiculos = obtenerVehiculos();
$mobiliarios = obtenerMobiliario();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Vehículos - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
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
        <!-- Mostrar mensajes con SweetAlert2 -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <script>
                window.__mensaje = {
                    text: <?php echo json_encode($_SESSION['mensaje']); ?>,
                    tipo: <?php echo json_encode($_SESSION['tipo_mensaje'] ?? 'error'); ?>
                };
            </script>
            <noscript>
                <div class="alert alert-<?php echo ($_SESSION['tipo_mensaje'] ?? '') === 'success' ? 'success' : 'danger'; ?>">
                    <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
                </div>
            </noscript>
            <?php 
            unset($_SESSION['mensaje']);
            unset($_SESSION['tipo_mensaje']);
            ?>
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
                
                <div class="col-md-6">
                    <label class="form-label" for="id_mobiliario">Mobiliario Asociado:</label>
                    <select class="form-control" id="id_mobiliario" name="id_mobiliario">
                        <option value="">-- Sin mobiliario --</option>
                        <?php if (!empty($mobiliarios)): ?>
                            <?php foreach($mobiliarios as $mob): ?>
                                <option value="<?php echo $mob['id_mobiliario']; ?>">
                                    <?php echo htmlspecialchars($mob['nombre_mobiliario']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">-- No hay mobiliario en inventario --</option>
                        <?php endif; ?>
                    </select>
                    <?php if (empty($mobiliarios)): ?>
                        <small class="text-muted">No se encontraron registros de mobiliario en el inventario</small>
                    <?php endif; ?>
                </div>
                
                <div class="col-12">
                    <label class="form-label" for="descripcion">Descripción:</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" 
                              rows="2" placeholder="Ej. Vehículo para entregas a domicilio, color blanco, etc."></textarea>
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
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Mobiliario (Inventario)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($vehiculos as $vehiculo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vehiculo['id_vehiculo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['no_placa'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['marca_vehiculo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['modelo_vehiculo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($vehiculo['anio_vehiculo'] ?? 'N/A'); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($vehiculo['descripcion'] ?? ''); ?>">
                                <?php echo htmlspecialchars($vehiculo['descripcion'] ?? 'Sin descripción'); ?>
                            </td>
                            <td>
                                <?php 
                                $estado = $vehiculo['estado'] ?? 'ACTIVO';
                                $badge_class = '';
                                switch($estado) {
                                    case 'ACTIVO':
                                        $badge_class = 'badge bg-success';
                                        break;
                                    case 'EN_TALLER':
                                        $badge_class = 'badge bg-warning text-dark';
                                        break;
                                    case 'BAJA':
                                        $badge_class = 'badge bg-danger';
                                        break;
                                    default:
                                        $badge_class = 'badge bg-success';
                                }
                                ?>
                                <span class="<?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($estado); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($vehiculo['nombre_mobiliario'] ?? 'Ninguno'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $vehiculo['id_vehiculo'] ?? ''; ?>"
                                        data-placas="<?php echo htmlspecialchars($vehiculo['no_placa'] ?? ''); ?>"
                                        data-marca="<?php echo htmlspecialchars($vehiculo['marca_vehiculo'] ?? ''); ?>"
                                        data-modelo="<?php echo htmlspecialchars($vehiculo['modelo_vehiculo'] ?? ''); ?>"
                                        data-anio="<?php echo $vehiculo['anio_vehiculo'] ?? ''; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($vehiculo['descripcion'] ?? ''); ?>"
                                        data-estado="<?php echo $vehiculo['estado'] ?? 'ACTIVO'; ?>"
                                        data-mobiliario="<?php echo $vehiculo['id_mobiliario'] ?? ''; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_placa" value="<?php echo $vehiculo['id_vehiculo'] ?? ''; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($vehiculos)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No hay vehículos registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/gestion_vehiculos.js"></script>
</body>
</html>