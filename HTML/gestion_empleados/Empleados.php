<?php
// Ernesto David Samayoa Jocol 0901-22-3415
session_start();
require_once '../conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// ---------------------- CRUD PRINCIPAL ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $operacion = $_POST['operacion'] ?? '';
    switch ($operacion) {
        case 'crear':
            crearEmpleado();
            break;
        case 'actualizar':
            actualizarEmpleado();
            break;
        case 'eliminar':
            eliminarEmpleado();
            break;
    }
}

function crearEmpleado() {
    $conn = conectar();
    $dpi = $_POST['dpi'] ?? '';
    $nombre = $_POST['nombre_empleado'] ?? '';
    $apellido = $_POST['apellido_empleado'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? null;
    $id_puesto = $_POST['id_puesto'] ?? null;

    if ($id_departamento && preg_match('/^\s*(\d+)/', $id_departamento, $m)) $id_departamento = (int)$m[1];
    if ($id_puesto && preg_match('/^\s*(\d+)/', $id_puesto, $m)) $id_puesto = (int)$m[1];

    $sql = "INSERT INTO empleados (dpi, nombre_empleado, apellido_empleado, id_departamento, id_puesto)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssii', $dpi, $nombre, $apellido, $id_departamento, $id_puesto);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Empleado creado exitosamente' : 'Error al crear empleado: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Empleados.php');
    exit();
}

function actualizarEmpleado() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $dpi = $_POST['dpi'] ?? '';
    $nombre = $_POST['nombre_empleado'] ?? '';
    $apellido = $_POST['apellido_empleado'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? null;
    $id_puesto = $_POST['id_puesto'] ?? null;

    if ($id_departamento && preg_match('/^\s*(\d+)/', $id_departamento, $m)) $id_departamento = (int)$m[1];
    if ($id_puesto && preg_match('/^\s*(\d+)/', $id_puesto, $m)) $id_puesto = (int)$m[1];

    $sql = "UPDATE empleados SET dpi=?, nombre_empleado=?, apellido_empleado=?, id_departamento=?, id_puesto=? WHERE id_empleado=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssiii', $dpi, $nombre, $apellido, $id_departamento, $id_puesto, $id_empleado);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Empleado actualizado exitosamente' : 'Error al actualizar empleado: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Empleados.php');
    exit();
}

function eliminarEmpleado() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $sql = "DELETE FROM empleados WHERE id_empleado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_empleado);
    $stmt->execute();

    $_SESSION['mensaje'] = $stmt->affected_rows > 0 ? 'Empleado eliminado exitosamente' : 'Error al eliminar empleado: ' . $stmt->error;
    $_SESSION['tipo_mensaje'] = $stmt->affected_rows > 0 ? 'success' : 'error';

    $stmt->close();
    desconectar($conn);
    header('Location: Empleados.php');
    exit();
}

function obtenerEmpleados() {
    $conn = conectar();
    $sql = "SELECT * FROM empleados ORDER BY id_empleado";
    $resultado = $conn->query($sql);
    $data = [];
    while ($fila = $resultado->fetch_assoc()) {
        $data[] = $fila;
    }
    desconectar($conn);
    return $data;
}

// ---------------------- MAPEOS ----------------------
$empleados = obtenerEmpleados();
$conn = conectar();

// Departamentos
$departamentos_map = [];
$res = $conn->query("SELECT id_departamento, nombre_departamento FROM departamentos");
while ($row = $res->fetch_assoc()) {
    $departamentos_map[$row['id_departamento']] = $row['nombre_departamento'];
}

// Puestos
$puestos_map = [];
$res = $conn->query("SELECT id_puesto, puesto FROM puesto");
while ($row = $res->fetch_assoc()) {
    $puestos_map[$row['id_puesto']] = $row['puesto'];
}
desconectar($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
</head>
<body>
<header class="mb-4">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
        <h1 class="mb-0">Gestión de Empleados</h1>
        <ul class="nav nav-pills gap-2 mb-0">
            <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
        </ul>
    </div>
</header>

<main class="container my-4">
    <?php if (isset($_SESSION['mensaje'])): ?>
        <script>
            // Mensaje generado en servidor — lo mostramos con SweetAlert en el cliente si está disponible
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
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <section class="card shadow p-4">
        <h2 class="card__title text-primary mb-4">Formulario de Empleados</h2>

        <form id="form-empleado" method="post" class="row g-3">
            <input type="hidden" name="operacion" id="operacion" value="crear">
            <input type="hidden" name="id_empleado" id="id_empleado">

            <div class="col-md-3">
                <label class="form-label">DPI</label>
                <input type="text" class="form-control" name="dpi" id="dpi" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre_empleado" id="nombre_empleado" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Apellido</label>
                <input type="text" class="form-control" name="apellido_empleado" id="apellido_empleado" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">ID Departamento</label>
                <select class="form-select" name="id_departamento" id="id_departamento" required>
                    <option value="">-- Sin departamento --</option>
                    <?php foreach ($departamentos_map as $dep_id => $dep_name): ?>
                        <option value="<?= $dep_id; ?>"><?= $dep_id . ' - ' . htmlspecialchars($dep_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">ID Puesto</label>
                <select class="form-select" name="id_puesto" id="id_puesto" required>
                    <option value="">-- Sin puesto --</option>
                    <?php foreach ($puestos_map as $puesto_id => $puesto_nombre): ?>
                        <option value="<?= $puesto_id; ?>"><?= $puesto_id . ' - ' . htmlspecialchars($puesto_nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
            <h3 class="mb-0">Lista de Empleados</h3>
        </div>

        <div id="lista-empleados" class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>DPI</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Departamento</th>
                        <th>Puesto</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($empleados as $empleado): ?>
                    <tr>
                        <td><?= $empleado['id_empleado']; ?></td>
                        <td><?= htmlspecialchars($empleado['dpi']); ?></td>
                        <td><?= htmlspecialchars($empleado['nombre_empleado']); ?></td>
                        <td><?= htmlspecialchars($empleado['apellido_empleado']); ?></td>
                        <td><?= $empleado['id_departamento'] . ' - ' . ($departamentos_map[$empleado['id_departamento']] ?? ''); ?></td>
                        <td><?= $empleado['id_puesto'] . ' - ' . ($puestos_map[$empleado['id_puesto']] ?? ''); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-primary btn-sm editar-btn"
                                data-id="<?= $empleado['id_empleado']; ?>"
                                data-dpi="<?= htmlspecialchars($empleado['dpi']); ?>"
                                data-nombre="<?= htmlspecialchars($empleado['nombre_empleado']); ?>"
                                data-apellido="<?= htmlspecialchars($empleado['apellido_empleado']); ?>"
                                data-departamento="<?= $empleado['id_departamento']; ?>"
                                data-puesto="<?= $empleado['id_puesto']; ?>">Editar</button>
                            <form method="post" style="display:inline;margin-left:6px;" data-eliminar="true">
                                <input type="hidden" name="operacion" value="eliminar">
                                <input type="hidden" name="id_empleado" value="<?= $empleado['id_empleado']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($empleados)): ?>
                    <tr><td colspan="7" class="text-center">No hay empleados registrados</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/SistemaWebRestaurante/javascript/Empleados.js"></script>
</body>
</html>  