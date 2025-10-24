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

// ========================== FUNCIONES CRUD ==========================

function crearSucursal() {
    $conn = conectar();

    $id_sucursal = $_POST['id_sucursal'] ?? '';
    $direccion = trim($_POST['direccion'] ?? '');
    $capacidad_empleados = $_POST['capacidad_empleados'] ?? 0;
    $correo_sucursal = trim($_POST['correo_sucursal'] ?? '');
    $telefono_sucursal = trim($_POST['telefono_sucursal'] ?? '');
    $hora_apertura = $_POST['hora_apertura'] ?? '';
    $hora_cierre = $_POST['hora_cierre'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? null;

    if ($direccion === '' || $correo_sucursal === '' || $telefono_sucursal === '') {
        $_SESSION['mensaje'] = 'Debe completar todos los campos requeridos.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: Sucursales.php');
        exit();
    }

    $sql = "INSERT INTO sucursales 
            (id_sucursal, direccion, capacidad_empleados, correo_sucursal, telefono_sucursal, hora_apertura, hora_cierre, id_departamento)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isissssi', $id_sucursal, $direccion, $capacidad_empleados, $correo_sucursal, $telefono_sucursal, $hora_apertura, $hora_cierre, $id_departamento);

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
    $direccion = trim($_POST['direccion'] ?? '');
    $capacidad_empleados = $_POST['capacidad_empleados'] ?? 0;
    $correo_sucursal = trim($_POST['correo_sucursal'] ?? '');
    $telefono_sucursal = trim($_POST['telefono_sucursal'] ?? '');
    $hora_apertura = $_POST['hora_apertura'] ?? '';
    $hora_cierre = $_POST['hora_cierre'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? null;

    $sql = "UPDATE sucursales 
            SET direccion = ?, capacidad_empleados = ?, correo_sucursal = ?, telefono_sucursal = ?, 
                hora_apertura = ?, hora_cierre = ?, id_departamento = ?
            WHERE id_sucursal = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sissssii', $direccion, $capacidad_empleados, $correo_sucursal, $telefono_sucursal, $hora_apertura, $hora_cierre, $id_departamento, $id_sucursal);

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Sucursales</title>

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
            <h2 class="card__title text-primary mb-4">Sucursales</h2>

            <form id="form-sucursal" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">

                <div class="col-md-3">
                    <label class="form-label" for="id_sucursal">ID Sucursal:</label>
                    <input type="number" class="form-control" id="id_sucursal" name="id_sucursal" required>
                </div>

                <div class="col-md-5">
                    <label class="form-label" for="direccion">Dirección:</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" required placeholder="Ej. 5ta avenida zona 1">
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="capacidad_empleados">Capacidad Empleados:</label>
                    <input type="number" class="form-control" id="capacidad_empleados" name="capacidad_empleados" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="correo_sucursal">Correo Sucursal:</label>
                    <input type="email" class="form-control" id="correo_sucursal" name="correo_sucursal" required placeholder="Ej. contacto@sucursal.com">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="telefono_sucursal">Teléfono:</label>
                    <input type="text" class="form-control" id="telefono_sucursal" name="telefono_sucursal" required placeholder="Ej. 5555-1234">
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="hora_apertura">Hora Apertura:</label>
                    <input type="time" class="form-control" id="hora_apertura" name="hora_apertura" required>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="hora_cierre">Hora Cierre:</label>
                    <input type="time" class="form-control" id="hora_cierre" name="hora_cierre" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="id_departamento">ID Departamento:</label>
                    <input type="number" class="form-control" id="id_departamento" name="id_departamento" required>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                    <button id="btn-guardar" type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>

            <h2 class="card__title mb-3 mt-5">Lista de Sucursales</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Dirección</th>
                            <th>Capacidad</th>
                            <th>Correo</th>
                            <th>Teléfono</th>
                            <th>Hora Apertura</th>
                            <th>Hora Cierre</th>
                            <th>ID Departamento</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sucursales as $sucursal): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sucursal['id_sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($sucursal['direccion']); ?></td>
                            <td><?php echo htmlspecialchars($sucursal['capacidad_empleados']); ?></td>
                            <td><?php echo htmlspecialchars($sucursal['correo_sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($sucursal['telefono_sucursal']); ?></td>
                            <td><?php echo htmlspecialchars($sucursal['hora_apertura']); ?></td>
                            <td><?php echo htmlspecialchars($sucursal['hora_cierre']); ?></td>
                            <td><?php echo htmlspecialchars($sucursal['id_departamento']); ?></td>
                            <td class="text-center">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_sucursal" value="<?php echo $sucursal['id_sucursal']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta sucursal?')">Eliminar</button>
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
    </main>
</body>
</html>
