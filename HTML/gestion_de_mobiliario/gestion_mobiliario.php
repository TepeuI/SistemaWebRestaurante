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
    
    // Validar que el ID no esté vacío
    if (empty($id_mobiliario)) {
        $_SESSION['mensaje'] = "Error: No se proporcionó un ID de mobiliario válido.";
        $_SESSION['tipo_mensaje'] = "error";
        desconectar($conn);
        header('Location: gestion_mobiliario.php');
        exit();
    }
    
    try {
        // Primero verificar si el mobiliario existe
        $check_mobiliario = $conn->prepare("SELECT id_mobiliario FROM inventario_mobiliario WHERE id_mobiliario = ?");
        if (!$check_mobiliario) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $check_mobiliario->bind_param("i", $id_mobiliario);
        
        if (!$check_mobiliario->execute()) {
            throw new Exception("Error al ejecutar la consulta: " . $check_mobiliario->error);
        }
        
        $result_mobiliario = $check_mobiliario->get_result();
        
        if ($result_mobiliario->num_rows === 0) {
            $_SESSION['mensaje'] = "Error: El mobiliario que intenta eliminar no existe en el sistema.";
            $_SESSION['tipo_mensaje'] = "error";
            $check_mobiliario->close();
            desconectar($conn);
            header('Location: gestion_mobiliario.php');
            exit();
        }
        $check_mobiliario->close();
        
        // Verificar si existe la tabla detalle_compra_mobiliario y si tiene relación con inventario_mobiliario
        $check_tabla_detalle = $conn->query("SHOW TABLES LIKE 'detalle_compra_mobiliario'");
        if ($check_tabla_detalle && $check_tabla_detalle->num_rows > 0) {
            // La tabla existe, verificar si hay columnas que referencien inventario_mobiliario
            $check_columnas = $conn->query("SHOW COLUMNS FROM detalle_compra_mobiliario");
            $tiene_relacion = false;
            $columna_relacion = '';
            
            while ($columna = $check_columnas->fetch_assoc()) {
                if (strpos($columna['Field'], 'mobiliario') !== false || 
                    strpos($columna['Field'], 'id_mobiliario') !== false) {
                    $tiene_relacion = true;
                    $columna_relacion = $columna['Field'];
                    break;
                }
            }
            
            if ($tiene_relacion && !empty($columna_relacion)) {
                // Verificar si hay registros relacionados
                $check_relacion = $conn->prepare("SELECT COUNT(*) as count FROM detalle_compra_mobiliario WHERE {$columna_relacion} = ?");
                if ($check_relacion) {
                    $check_relacion->bind_param("i", $id_mobiliario);
                    $check_relacion->execute();
                    $result_relacion = $check_relacion->get_result();
                    $row_relacion = $result_relacion->fetch_assoc();
                    $check_relacion->close();
                    
                    if ($row_relacion['count'] > 0) {
                        $_SESSION['mensaje'] = "No se puede eliminar el mobiliario porque está siendo utilizado en detalles de compra (" . $row_relacion['count'] . " registros relacionados). Primero debe eliminar o modificar los registros relacionados en los detalles de compra.";
                        $_SESSION['tipo_mensaje'] = "error";
                        desconectar($conn);
                        header('Location: gestion_mobiliario.php');
                        exit();
                    }
                }
            }
        }
        
        // Si no hay referencias, proceder con la eliminación
        $sql = "DELETE FROM inventario_mobiliario WHERE id_mobiliario = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de eliminación: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id_mobiliario);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "Mobiliario eliminado exitosamente";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "No se pudo eliminar el mobiliario. Es posible que ya haya sido eliminado o no exista.";
                $_SESSION['tipo_mensaje'] = "error";
            }
        } else {
            $error = $stmt->error;
            if (strpos($error, 'foreign key constraint') !== false) {
                $_SESSION['mensaje'] = "No se puede eliminar el mobiliario porque está siendo utilizado en otros registros del sistema. Verifique que no existan registros relacionados en los detalles de compra.";
                $_SESSION['tipo_mensaje'] = "error";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar mobiliario: " . $error;
                $_SESSION['tipo_mensaje'] = "error";
            }
        }
        
        $stmt->close();
        
    } catch (mysqli_sql_exception $e) {
        // Capturar excepciones específicas de MySQL
        $error_message = $e->getMessage();
        
        if (strpos($error_message, 'foreign key constraint fails') !== false) {
            $_SESSION['mensaje'] = "No se puede eliminar el mobiliario porque está siendo utilizado en otros registros del sistema. Verifique que no existan registros relacionados en los detalles de compra.";
            $_SESSION['tipo_mensaje'] = "error";
        } else if (strpos($error_message, 'Unknown column') !== false) {
            $_SESSION['mensaje'] = "Error en la consulta a la base de datos. Por favor, contacte al administrador del sistema.";
            $_SESSION['tipo_mensaje'] = "error";
        } else {
            $_SESSION['mensaje'] = "Error de base de datos: " . $error_message;
            $_SESSION['tipo_mensaje'] = "error";
        }
    } catch (Exception $e) {
        // Capturar cualquier otra excepción
        $_SESSION['mensaje'] = "Error inesperado: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
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
                                <form method="post" style="display:inline;" data-eliminar="true">
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/gestion_mobiliario.js"></script>
</body>
</html>