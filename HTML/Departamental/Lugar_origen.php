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
            crearDepartamento();
            break;
        case 'actualizar':
            actualizarDepartamento();
            break;
        case 'eliminar':
            eliminarDepartamento();
            break;
    }
}

// ========================== FUNCIONES CRUD ==========================

function crearDepartamento() {
    $conn = conectar();
    $id_departamento = $_POST['id_departamento'] ?? '';
    $nombre_departamento = trim($_POST['departamento'] ?? '');

    if ($nombre_departamento === '') {
        $_SESSION['mensaje'] = 'Debe ingresar el nombre del departamento.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Lugar_origen.php');
        exit();
    }

    $sql = "INSERT INTO departamentos (id_departamento, departamento) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $id_departamento, $nombre_departamento);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Departamento creado exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al crear el departamento: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Lugar_origen.php');
    exit();
}

function actualizarDepartamento() {
    $conn = conectar();
    $id_departamento = $_POST['id_departamento'] ?? '';
    $nombre_departamento = trim($_POST['departamento'] ?? '');

    $sql = "UPDATE departamentos SET departamento = ? WHERE id_departamento = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $nombre_departamento, $id_departamento);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Departamento actualizado correctamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al actualizar el departamento: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Lugar_origen.php');
    exit();
}

function eliminarDepartamento() {
    $conn = conectar();
    $id_departamento = $_POST['id_departamento'] ?? '';

    $sql = "DELETE FROM departamentos WHERE id_departamento = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_departamento);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Departamento eliminado exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar el departamento: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: Lugar_origen.php');
    exit();
}

function obtenerDepartamentos() {
    $conn = conectar();
    $sql = "SELECT * FROM departamentos ORDER BY id_departamento";
    $resultado = $conn->query($sql);
    $departamentos = [];

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $departamentos[] = $fila;
        }
    }

    desconectar($conn);
    return $departamentos;
}

$departamentos = obtenerDepartamentos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departamentos</title>

    <!-- Google Fonts y estilos -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/gestion_empleados.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">Departamental</h1>
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
            </ul>
        </div>
    </header>

    <main class="container my-4">
        <!-- Mensajes de sesión -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo ($_SESSION['tipo_mensaje'] === 'success') ? 'success' : 'danger'; ?>">
                <?php
                echo htmlspecialchars($_SESSION['mensaje']);
                unset($_SESSION['mensaje']);
                unset($_SESSION['tipo_mensaje']);
                ?>
            </div>
        <?php endif; ?>

        <section class="card shadow p-4">
            <h2 class="card__title text-primary mb-4">Departamentos</h2>

            <form id="form-departamento" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">

                <div class="col-md-3">
                    <label class="form-label" for="id_departamento">ID Departamento:</label>
                    <input type="number" class="form-control" id="id_departamento" name="id_departamento" required placeholder="Ej. 1">
                </div>

                <div class="col-md-5">
                    <label class="form-label" for="departamento">Nombre del Departamento:</label>
                    <input type="text" class="form-control" id="departamento" name="departamento" required placeholder="Ej. Recursos Humanos">
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                    <button id="btn-guardar" type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>

            <h2 class="card__title mb-3 mt-5">Lista de Departamentos</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Departamento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departamentos as $dep): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dep['id_departamento']); ?></td>
                            <td><?php echo htmlspecialchars($dep['departamento']); ?></td>
                            <td class="text-center">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_departamento" value="<?php echo $dep['id_departamento']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este departamento?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($departamentos)): ?>
                        <tr><td colspan="3" class="text-center">No hay departamentos registrados</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
