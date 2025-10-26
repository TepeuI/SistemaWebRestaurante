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
            crearTelefono();
            break;
        case 'actualizar':
            actualizarTelefono();
            break;
        case 'eliminar':
            eliminarTelefono();
            break;
    }
}

// ---------------------- FUNCIONES CRUD ----------------------

function crearTelefono() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $numero = $_POST['numero_telefono'] ?? '';

    // Extraer ID si viene en formato "1 - Nombre Apellido"
    if (!empty($id_empleado) && preg_match('/^\s*(\d+)/', $id_empleado, $m)) {
        $id_empleado = intval($m[1]);
    } else {
        $id_empleado = null;
    }

    if (empty($id_empleado) || empty($numero)) {
        $_SESSION['mensaje'] = 'Debe seleccionar un empleado y proporcionar un número.';
        $_SESSION['tipo_mensaje'] = 'error';
    } else {
        $sql = "INSERT INTO telefono_empleados (id_empleado, numero_telefono) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $_SESSION['mensaje'] = 'Error en la consulta (crear): ' . $conn->error;
            $_SESSION['tipo_mensaje'] = 'error';
        } else {
            $stmt->bind_param('is', $id_empleado, $numero);
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = 'Teléfono registrado exitosamente';
                $_SESSION['tipo_mensaje'] = 'success';
            } else {
                $_SESSION['mensaje'] = 'Error al registrar teléfono: ' . $stmt->error;
                $_SESSION['tipo_mensaje'] = 'error';
            }
            $stmt->close();
        }
    }

    desconectar($conn);
    header('Location: Telefono_Empleados.php');
    exit();
}

function actualizarTelefono() {
    $conn = conectar();
    $id_telefono = $_POST['id_telefono'] ?? '';
    $id_empleado = $_POST['id_empleado'] ?? '';
    $numero = $_POST['numero_telefono'] ?? '';

    // Extraer ID si viene en formato "1 - Nombre"
    if (!empty($id_empleado) && preg_match('/^\s*(\d+)/', $id_empleado, $m)) {
        $id_empleado = intval($m[1]);
    } else {
        $id_empleado = null;
    }

    $sql = "UPDATE telefono_empleados 
            SET id_empleado = ?, numero_telefono = ?
            WHERE id_telefono = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['mensaje'] = 'Error en la consulta (actualizar): ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    } else {
        $stmt->bind_param('isi', $id_empleado, $numero, $id_telefono);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Teléfono actualizado exitosamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al actualizar teléfono: ' . $stmt->error;
            $_SESSION['tipo_mensaje'] = 'error';
        }
        $stmt->close();
    }

    desconectar($conn);
    header('Location: Telefono_Empleados.php');
    exit();
}

function eliminarTelefono() {
    $conn = conectar();
    $id_telefono = $_POST['id_telefono'] ?? '';

    $sql = "DELETE FROM telefono_empleados WHERE id_telefono = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['mensaje'] = 'Error en la consulta (eliminar): ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    } else {
        $stmt->bind_param('i', $id_telefono);
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = 'Teléfono eliminado exitosamente';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = 'Error al eliminar teléfono: ' . $stmt->error;
            $_SESSION['tipo_mensaje'] = 'error';
        }
        $stmt->close();
    }

    desconectar($conn);
    header('Location: Telefono_Empleados.php');
    exit();
}

function obtenerTelefonos() {
    $conn = conectar();
    $sql = "SELECT t.id_telefono, t.numero_telefono, e.id_empleado, e.nombre_empleado, e.apellido_empleado
            FROM telefono_empleados t
            INNER JOIN empleados e ON t.id_empleado = e.id_empleado
            ORDER BY t.id_telefono";
    $resultado = $conn->query($sql);
    $telefonos = [];

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $telefonos[] = $fila;
        }
    }

    desconectar($conn);
    return $telefonos;
}

// ---------------------- DATOS ADICIONALES ----------------------

$telefonos = obtenerTelefonos();

// Obtener mapa de empleados (id => nombre completo)
$conn = conectar();
$empleados_map = [];
$sql = "SELECT id_empleado, CONCAT(nombre_empleado, ' ', apellido_empleado) AS nombre_completo FROM empleados";
$resultado = $conn->query($sql);
if ($resultado && $resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $empleados_map[$fila['id_empleado']] = $fila['nombre_completo'];
    }
}
desconectar($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Teléfonos de Empleados</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Gestión de Teléfonos de Empleados</h1>
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
        <h2 class="card__title text-primary mb-4">Formulario de Teléfonos</h2>

        <form id="form-telefono" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_telefono" id="id_telefono">

            <div class="col-md-4">
                <label class="form-label">Empleado</label>
                <input type="text" class="form-control" name="id_empleado" id="id_empleado" list="empleados-list" inputmode="numeric" required>
                <datalist id="empleados-list">
                    <?php foreach ($empleados_map as $id => $nombre): ?>
                        <option value="<?php echo htmlspecialchars($id . ' - ' . $nombre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="col-md-4">
                <label class="form-label">Número de Teléfono</label>
                <input type="text" class="form-control" name="numero_telefono" id="numero_telefono" required>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Teléfonos</h3>
            <button id="btn-mostrar-lista" type="button" class="btn btn-info btn-sm">Mostrar lista</button>
        </div>

        <div id="lista-telefonos" class="table-responsive" style="display:none;">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID Teléfono</th>
                        <th>Empleado</th>
                        <th>Número</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($telefonos as $tel): ?>
                    <tr>
                        <td><?= $tel['id_telefono']; ?></td>
                        <td><?= htmlspecialchars($tel['id_empleado'] . ' - ' . $tel['nombre_empleado'] . ' ' . $tel['apellido_empleado']); ?></td>
                        <td><?= htmlspecialchars($tel['numero_telefono']); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-primary btn-sm editar-btn"
                                data-id="<?= $tel['id_telefono']; ?>"
                                data-empleado="<?= $tel['id_empleado']; ?>"
                                data-numero="<?= htmlspecialchars($tel['numero_telefono']); ?>">Editar</button>
                            <form method="post" style="display:inline;margin-left:6px;" data-eliminar="true">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_telefono" value="<?= $tel['id_telefono']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($telefonos)): ?>
                    <tr><td colspan="4" class="text-center">No hay teléfonos registrados</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    var EMPLEADOS_MAP = <?php echo json_encode($empleados_map, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Telefono_Empleados.js"></script>
</body>
</html>

