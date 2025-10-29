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
        case 'crear_ruta':
            crearRuta();
            break;
        case 'actualizar_ruta':
            actualizarRuta();
            break;
        case 'eliminar_ruta':
            eliminarRuta();
            break;
    }
}

function crearRuta() {
    global $conn;
    $conn = conectar();
    
    $descripcion_ruta = $_POST['descripcion_ruta'] ?? '';
    $inicio_ruta = $_POST['inicio_ruta'] ?? '';
    $fin_ruta = $_POST['fin_ruta'] ?? '';
    $gasolina_aproximada = $_POST['gasolina_aproximada'] ?? 0;
    
    $sql = "INSERT INTO rutas (descripcion_ruta, inicio_ruta, fin_ruta, gasolina_aproximada) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssd", $descripcion_ruta, $inicio_ruta, $fin_ruta, $gasolina_aproximada);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ruta creada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear ruta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: rutas_vehiculos.php');
    exit();
}

function actualizarRuta() {
    global $conn;
    $conn = conectar();
    
    $id_ruta = $_POST['id_ruta'] ?? '';
    $descripcion_ruta = $_POST['descripcion_ruta'] ?? '';
    $inicio_ruta = $_POST['inicio_ruta'] ?? '';
    $fin_ruta = $_POST['fin_ruta'] ?? '';
    $gasolina_aproximada = $_POST['gasolina_aproximada'] ?? 0;
    
    $sql = "UPDATE rutas SET descripcion_ruta = ?, inicio_ruta = ?, fin_ruta = ?, gasolina_aproximada = ? 
            WHERE id_ruta = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdi", $descripcion_ruta, $inicio_ruta, $fin_ruta, $gasolina_aproximada, $id_ruta);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Ruta actualizada exitosamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al actualizar ruta: " . $conn->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    $stmt->close();
    desconectar($conn);
    header('Location: rutas_vehiculos.php');
    exit();
}

function eliminarRuta() {
    global $conn;
    $conn = conectar();
    
    $id_ruta = $_POST['id_ruta'] ?? '';
    
    try {
        // Verificar si la ruta está siendo usada en la tabla viajes
        $check_viajes = $conn->prepare("SELECT COUNT(*) as count FROM viajes WHERE id_ruta = ?");
        $check_viajes->bind_param("i", $id_ruta);
        $check_viajes->execute();
        $result_viajes = $check_viajes->get_result();
        $row_viajes = $result_viajes->fetch_assoc();
        $check_viajes->close();
        
        if ($row_viajes['count'] > 0) {
            $_SESSION['mensaje'] = "No se puede eliminar la ruta porque está siendo utilizada en viajes registrados.";
            $_SESSION['tipo_mensaje'] = "error";
            desconectar($conn);
            header('Location: rutas_vehiculos.php');
            exit();
        }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM rutas WHERE id_ruta = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_ruta);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Ruta eliminada exitosamente";
            $_SESSION['tipo_mensaje'] = "success";
        } else {
            // Capturar cualquier otro error que pueda ocurrir
            $_SESSION['mensaje'] = "Error al eliminar ruta: " . $conn->error;
            $_SESSION['tipo_mensaje'] = "error";
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar la ruta porque está siendo utilizada en otros registros del sistema.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar ruta: " . $e->getMessage();
            $_SESSION['tipo_mensaje'] = "error";
        }
    } catch (Exception $e) {
        // Capturar cualquier otra excepción
        $_SESSION['mensaje'] = "Error al eliminar ruta: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
    desconectar($conn);
    header('Location: rutas_vehiculos.php');
    exit();
}

// Obtener todas las rutas para mostrar en la tabla
function obtenerRutas() {
    $conn = conectar();
    
    $sql = "SELECT * FROM rutas ORDER BY descripcion_ruta";
    $resultado = $conn->query($sql);
    $rutas = [];
    
    if ($resultado && $resultado->num_rows > 0) {
        while($fila = $resultado->fetch_assoc()) {
            $rutas[] = $fila;
        }
    }
    
    desconectar($conn);
    return $rutas;
}

$rutas = obtenerRutas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rutas - Marina Roja</title>
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Frameworks y librerías base -->
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/diseñoModulos.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">GESTIÓN DE RUTAS DE VEHÍCULOS</h1>
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
            <h2 class="card__title text-primary mb-4">FORMULARIO - REGISTRO DE RUTAS</h2>

            <form id="form-rutas" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear_ruta">
                <input type="hidden" id="id_ruta" name="id_ruta" value="">
                
                <div class="col-12">
                    <label class="form-label" for="descripcion_ruta">Descripción de la Ruta:</label>
                    <input type="text" class="form-control" id="descripcion_ruta" name="descripcion_ruta" 
                           required placeholder="Ej. Ruta de entregas zona centro">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="inicio_ruta">Punto de Inicio:</label>
                    <input type="text" class="form-control" id="inicio_ruta" name="inicio_ruta" 
                           placeholder="Ej. Bodega principal">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="fin_ruta">Punto Final:</label>
                    <input type="text" class="form-control" id="fin_ruta" name="fin_ruta" 
                           placeholder="Ej. Última entrega">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label" for="gasolina_aproximada">Gasolina Aproximada (Litros):</label>
                    <input type="number" step="0.01" class="form-control" id="gasolina_aproximada" name="gasolina_aproximada" 
                           min="0" placeholder="Ej. 15.50">
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">RUTAS REGISTRADAS</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-rutas">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Descripción</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Gasolina (L)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($rutas as $ruta): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ruta['id_ruta']); ?></td>
                            <td class="descripcion-cell" title="<?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>">
                                <?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($ruta['inicio_ruta'] ?? 'No especificado'); ?></td>
                            <td><?php echo htmlspecialchars($ruta['fin_ruta'] ?? 'No especificado'); ?></td>
                            <td class="text-end fw-bold">
                                <?php echo $ruta['gasolina_aproximada'] ? number_format($ruta['gasolina_aproximada'], 2) . ' L' : 'No especificado'; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn" 
                                        data-id="<?php echo $ruta['id_ruta']; ?>"
                                        data-descripcion="<?php echo htmlspecialchars($ruta['descripcion_ruta']); ?>"
                                        data-inicio="<?php echo htmlspecialchars($ruta['inicio_ruta'] ?? ''); ?>"
                                        data-fin="<?php echo htmlspecialchars($ruta['fin_ruta'] ?? ''); ?>"
                                        data-gasolina="<?php echo $ruta['gasolina_aproximada']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" data-eliminar="true">
                                    <input type="hidden" name="operacion" value="eliminar_ruta">
                                    <input type="hidden" name="id_ruta" value="<?php echo $ruta['id_ruta']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rutas)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay rutas registradas</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/rutas_vehiculos.js"></script>
</body>
</html>