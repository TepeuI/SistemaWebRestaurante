<!-- Ernesto David Samayoa Jocol 0901-22-3415 -->
<?php
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Manejo de operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    switch ($operacion) {
        case 'crear':
            crearSucursal();
            break;
        case 'actualizar':
            actualizarSucursal();
            break;
        case 'eliminar':
            eliminarSucursal();
            break;
    }
}

function crearSucursal() {
    $conn = conectar();
    $direccion = $_POST['direccion_sucursal'] ?? '';
    $horario_apertura = $_POST['horario_apertura'] ?? '';
    $hora_cierre = $_POST['hora_cierre'] ?? '';
    $capacidad = $_POST['capacidad_empleados'] ?? 0;
    $telefono = $_POST['telefono_sucursal'] ?? '';
    $correo = $_POST['correo_sucursal'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? null;
    // Si el input viene como "3 - Guatemala", extraer el id numérico
    if ($id_departamento !== null && $id_departamento !== '') {
        if (preg_match('/^\s*(\d+)/', $id_departamento, $m)) {
            $id_departamento = intval($m[1]);
        } else {
            $id_departamento = null;
        }
    } else {
        $id_departamento = null;
    }

    $sql = "INSERT INTO sucursales (direccion_sucursal, horario_apertura, hora_cierre, capacidad_empleados, telefono_sucursal, correo_sucursal, id_departamento)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssissi', $direccion, $horario_apertura, $hora_cierre, $capacidad, $telefono, $correo, $id_departamento);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Sucursal creada exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al crear la sucursal: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Sucursales.php');
    exit();
}

function actualizarSucursal() {
    $conn = conectar();
    $id_sucursal = $_POST['id_sucursal'] ?? '';
    $direccion = $_POST['direccion_sucursal'] ?? '';
    $horario_apertura = $_POST['horario_apertura'] ?? '';
    $hora_cierre = $_POST['hora_cierre'] ?? '';
    $capacidad = $_POST['capacidad_empleados'] ?? 0;
    $telefono = $_POST['telefono_sucursal'] ?? '';
    $correo = $_POST['correo_sucursal'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? null;
    // Si el input viene como "3 - Guatemala", extraer el id numérico
    if ($id_departamento !== null && $id_departamento !== '') {
        if (preg_match('/^\s*(\d+)/', $id_departamento, $m)) {
            $id_departamento = intval($m[1]);
        } else {
            $id_departamento = null;
        }
    } else {
        $id_departamento = null;
    }

    $sql = "UPDATE sucursales 
            SET direccion_sucursal = ?, horario_apertura = ?, hora_cierre = ?, capacidad_empleados = ?, telefono_sucursal = ?, correo_sucursal = ?, id_departamento = ?
            WHERE id_sucursal = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssissii', $direccion, $horario_apertura, $hora_cierre, $capacidad, $telefono, $correo, $id_departamento, $id_sucursal);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Sucursal actualizada exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al actualizar la sucursal: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Sucursales.php');
    exit();
}

function eliminarSucursal() {
    $conn = conectar();
    $id_sucursal = $_POST['id_sucursal'] ?? '';

    $sql = "DELETE FROM sucursales WHERE id_sucursal = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_sucursal);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Sucursal eliminada exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar la sucursal: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Sucursales.php');
    exit();
}

function obtenerSucursales() {
    $conn = conectar();
    $sql = "SELECT * FROM sucursales ORDER BY id_sucursal";
    $resultado = $conn->query($sql);
    $sucursales = [];

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $sucursales[] = $fila;
        }
    }

    desconectar($conn);
    return $sucursales;
}

$sucursales = obtenerSucursales();

// Obtener mapa de departamentos (id => nombre) para mostrar el nombre junto al campo de ID
$conn = conectar();
$departamentos_map = [];
$sql = "SELECT id_departamento, nombre_departamento FROM departamentos";
$resultado = $conn->query($sql);
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $departamentos_map[$fila['id_departamento']] = $fila['nombre_departamento'];
    }
}
desconectar($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Sucursales</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Gestión de Sucursales</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
        </ul>
    </div>
</header>

<main class="container my-4">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] === 'success' ? 'success' : 'danger'; ?>">
            <?php
            echo htmlspecialchars($_SESSION['mensaje']);
            unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
            ?>
        </div>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card__title text-primary mb-4">Formulario de Sucursales</h2>

        <form id="form-sucursal" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_sucursal" id="id_sucursal" value="">

            <div class="col-md-4">
                <label class="form-label">Dirección</label>
                <input type="text" class="form-control" name="direccion_sucursal" id="direccion_sucursal" required disabled>
            </div>

            <div class="col-md-2">
                <label class="form-label">Horario Apertura</label>
                <input type="time" class="form-control" name="horario_apertura" id="horario_apertura" required disabled>
            </div>

            <div class="col-md-2">
                <label class="form-label">Hora Cierre</label>
                <input type="time" class="form-control" name="hora_cierre" id="hora_cierre" required disabled>
            </div>

            <div class="col-md-2">
                <label class="form-label">Capacidad Empleados</label>
                <input type="number" class="form-control" name="capacidad_empleados" id="capacidad_empleados" min="0" required disabled>
            </div>

            <div class="col-md-2">
                <label class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="telefono_sucursal" id="telefono_sucursal" disabled>
            </div>

            <div class="col-md-4">
                <label class="form-label">Correo</label>
                <input type="email" class="form-control" name="correo_sucursal" id="correo_sucursal" disabled>
            </div>

            <div class="col-md-3">
                <label class="form-label">ID Departamento</label>
                <!-- Usamos input text + datalist para que dentro del textbox aparezcan las IDs existentes (1,2,34,...) -->
                <input type="text" class="form-control" name="id_departamento" id="id_departamento" list="departamentos-list" inputmode="numeric" disabled>
                <datalist id="departamentos-list">
                    <?php foreach ($departamentos_map as $dep_id => $dep_name): ?>
                        <!-- Valor mostrado en el textbox: 'id - Nombre' -->
                        <option value="<?php echo htmlspecialchars($dep_id . ' - ' . $dep_name, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <!-- departamento-nombre removed to avoid showing extra text under the input -->
            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Sucursales</h3>
            <button id="btn-mostrar-lista" type="button" class="btn btn-info btn-sm">Mostrar lista</button>
        </div>
        <div id="lista-sucursales" class="table-responsive" style="display:none;">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Dirección</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Capacidad</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>ID Departamento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sucursales as $sucursal): ?>
                    <tr>
                        <td><?php echo $sucursal['id_sucursal']; ?></td>
                        <td><?php echo htmlspecialchars($sucursal['direccion_sucursal']); ?></td>
                        <td><?php echo $sucursal['horario_apertura']; ?></td>
                        <td><?php echo $sucursal['hora_cierre']; ?></td>
                        <td><?php echo $sucursal['capacidad_empleados']; ?></td>
                        <td><?php echo htmlspecialchars($sucursal['telefono_sucursal']); ?></td>
                        <td><?php echo htmlspecialchars($sucursal['correo_sucursal']); ?></td>
                        <td><?php echo $sucursal['id_departamento']; ?></td>
                        <td class="text-center">
                <button type="button" class="btn btn-primary btn-sm editar-btn" disabled
                                    data-id="<?php echo $sucursal['id_sucursal']; ?>"
                                    data-direccion="<?php echo htmlspecialchars($sucursal['direccion_sucursal']); ?>"
                                    data-apertura="<?php echo $sucursal['horario_apertura']; ?>"
                                    data-cierre="<?php echo $sucursal['hora_cierre']; ?>"
                                    data-capacidad="<?php echo $sucursal['capacidad_empleados']; ?>"
                                    data-telefono="<?php echo htmlspecialchars($sucursal['telefono_sucursal']); ?>"
                                    data-correo="<?php echo htmlspecialchars($sucursal['correo_sucursal']); ?>"
                                    data-departamento="<?php echo $sucursal['id_departamento']; ?>">
                                Editar
                            </button>
                            <form method="post" style="display:inline;margin-left:6px;" data-eliminar="true">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_sucursal" value="<?php echo $sucursal['id_sucursal']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" disabled>Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($sucursales)): ?>
                    <tr><td colspan="9" class="text-center">No hay sucursales registradas</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <script>
        // Mapa id -> nombre de departamentos disponible para el JS
        var DEPARTAMENTOS_MAP = <?php echo json_encode($departamentos_map, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/SistemaWebRestaurante/javascript/Sucursales.js"></script>
</body>
</html>
