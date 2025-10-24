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
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $departamento = $_POST['departamento'] ?? null;
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $estado = $_POST['estado'] ?? 'ACTIVO';

    $sql = "INSERT INTO empleados (nombre, apellido, id_departamento, telefono, correo, estado) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssisss', $nombre, $apellido, $departamento, $telefono, $correo, $estado);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Empleado creado exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al crear empleado: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: control_empleados.php');
    exit();
}

function actualizarEmpleado() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $departamento = $_POST['departamento'] ?? null;
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $estado = $_POST['estado'] ?? '';

    $sql = "UPDATE empleados SET nombre = ?, apellido = ?, id_departamento = ?, telefono = ?, correo = ?, estado = ? WHERE id_empleado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssisssi', $nombre, $apellido, $departamento, $telefono, $correo, $estado, $id_empleado);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Empleado actualizado exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al actualizar empleado: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: control_empleados.php');
    exit();
}

function eliminarEmpleado() {
    $conn = conectar();
    $id_empleado = $_POST['id_empleado'] ?? '';

    $sql = "DELETE FROM empleados WHERE id_empleado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_empleado);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = 'Empleado eliminado exitosamente';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = 'Error al eliminar empleado: ' . $conn->error;
        $_SESSION['tipo_mensaje'] = 'error';
    }

    $stmt->close();
    desconectar($conn);
    header('Location: control_empleados.php');
    exit();
}

function obtenerEmpleados() {
    $conn = conectar();
    $sql = "SELECT * FROM empleados ORDER BY id_empleado";
    $resultado = $conn->query($sql);
    $empleados = [];

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $empleados[] = $fila;
        }
    }

    desconectar($conn);
    return $empleados;
}

$empleados = obtenerEmpleados();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Empleados</title>

    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- CSS desde la carpeta css/ (usar archivos separados) -->
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/gestion_empleados.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">Control de Empleados</h1>
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
            </ul>
        </div>
    </header>

    <main class="container my-4">
        <!-- Mensajes de sesión -->
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
            <h2 class="card__title text-primary mb-4">Empleados</h2>

            <form id="form-empleado" method="post" class="row g-3">
                <input type="hidden" id="operacion" name="operacion" value="crear">
                <input type="hidden" id="id_empleado" name="id_empleado" value="">

                <!-- ===== SECCIÓN 1: DATOS GENERALES DE EMPLEADOS ===== -->
                <div class="col-md-3">
                    <label class="form-label" for="id_empleado">ID:</label>
                    <input type="text" class="form-control" id="id_empleado" name="id_empleado" required placeholder="Ej. 123">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="nombre_empleado">Nombre:</label>
                    <input type="text" class="form-control" id="nombre_empleado" name="nombre_empleado" required placeholder="Ej. Juan">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="apellido_empleado">Apellido:</label>
                    <input type="text" class="form-control" id="apellido_empleado" name="apellido_empleado" required placeholder="Ej. Pérez">
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="fk_id_departamento_empleado">ID Departamento:</label>
                    <input type="number" class="form-control" id="fk_id_departamento_empleado" name="fk_id_departamento_empleado" required>
                </div>

                <!-- ===== SECCIÓN 2: TELEFONO DE EMPLEADOS ===== -->
                <h2 class="card__title text-primary mb-4">Teléfono de Empleados</h2>
                <div class="col-md-2">
                    
                    <label class="form-label" for="fk_id_telefono_empleado">Teléfono:</label>
                    <input type="text" class="form-control" id="fk_id_telefono_empleado" name="fk_id_telefono_empleadoo" placeholder="Ej. 5551234567">
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="correo">Correo:</label>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@dominio.com">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="estado">Estado:</label>
                    <select class="form-control" id="estado" name="estado" required>
                        <option value="ACTIVO">ACTIVO</option>
                        <option value="INACTIVO">INACTIVO</option>
                        <option value="SUSPENDIDO">SUSPENDIDO</option>
                    </select>
                </div>
            </form>

            <div class="d-flex gap-2 mt-4">
                <button id="btn-nuevo" type="button" class="btn btn-secondary">Nuevo</button>
                <button id="btn-guardar" type="button" class="btn btn-success">Guardar</button>
                <button id="btn-actualizar" type="button" class="btn btn-warning" style="display:none;">Actualizar</button>
                <button id="btn-cancelar" type="button" class="btn btn-danger" style="display:none;">Cancelar</button>
            </div>

            <h2 class="card__title mb-3 mt-5">Lista de Empleados</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered" id="tabla-empleados">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Departamento</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $empleado): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($empleado['id_empleado']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['id_departamento']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($empleado['correo']); ?></td>
                            <td>
                                <span class="badge <?php echo $empleado['estado'] == 'ACTIVO' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars($empleado['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-action editar-btn"
                                        data-id="<?php echo $empleado['id_empleado']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($empleado['nombre']); ?>"
                                        data-apellido="<?php echo htmlspecialchars($empleado['apellido']); ?>"
                                        data-departamento="<?php echo $empleado['id_departamento']; ?>"
                                        data-telefono="<?php echo htmlspecialchars($empleado['telefono']); ?>"
                                        data-correo="<?php echo htmlspecialchars($empleado['correo']); ?>"
                                        data-estado="<?php echo $empleado['estado']; ?>">
                                    Editar
                                </button>
                                <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este empleado?')">
                                    <input type="hidden" name="operacion" value="eliminar">
                                    <input type="hidden" name="id_empleado" value="<?php echo $empleado['id_empleado']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($empleados)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No hay empleados registrados</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="/SistemaWebRestaurante/javascript/Empleados.js"></script>
</body>
</html>
