<?php
session_start();
require_once '../conexion.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}
 
// Manejo de formulario: insertar nuevo teléfono (POST)
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar campos esperados
    $id_telefono = isset($_POST['id_telefono']) ? intval($_POST['id_telefono']) : 0; // opcional
    $numero_telefono = isset($_POST['numero_telefono']) ? trim($_POST['numero_telefono']) : '';
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;

    if ($numero_telefono === '' || $id_empleado <= 0) {
        $mensaje = '<div class="alert alert-danger">Complete todos los campos correctamente.</div>';
    } else {
        $conn = conectar();
        // Insert condicional: si se proporciona id_telefono, lo incluimos explícitamente
        if ($id_telefono > 0) {
            $stmt = $conn->prepare("INSERT INTO telefonos_empleado (id_telefono, id_empleado, numero_telefono) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('iis', $id_telefono, $id_empleado, $numero_telefono);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO telefonos_empleado (id_empleado, numero_telefono) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param('is', $id_empleado, $numero_telefono);
            }
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Teléfono guardado correctamente.</div>';
            } else {
                $mensaje = '<div class="alert alert-danger">Error al guardar: ' . htmlspecialchars($stmt->error) . '</div>';
            }
            $stmt->close();
        } else {
            $mensaje = '<div class="alert alert-danger">Error en la consulta: ' . htmlspecialchars($conn->error) . '</div>';
        }
        desconectar($conn);
    }
}

function obtenerTelefonos() {
    $conn = conectar();
    // Nombre de tabla correcto: telefonos_empleado 
    $sql = "SELECT * FROM telefonos_empleado ORDER BY id_telefono";
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

$telefonos = obtenerTelefonos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Teléfonos de Empleados</title>

    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- CSS principal -->
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/gestion_empleados.css">
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

        <section class="card shadow p-4">
            <?php if (!empty($mensaje)) { echo $mensaje; } ?>
            <h2 class="card__title text-primary mb-4">Formulario de Teléfonos</h2>

            <form class="row g-3" method="post" action="">
                <div class="col-md-3">
                    <label class="form-label" for="id_telefono">ID Teléfono:</label>
                    <input type="number" class="form-control" id="id_telefono" name="id_telefono" placeholder="2223">
                    
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="numero_telefono">Número de Teléfono:</label>
                    <input type="text" class="form-control" id="numero_telefono" name="numero_telefono" placeholder="Ej. 5551234567" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="id_empleado">ID Empleado:</label>
                    <input type="number" class="form-control" id="id_empleado" name="id_empleado" placeholder="Ej. 1" required>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="reset" class="btn btn-secondary">Limpiar</button>
                </div>
            </form>

            <h2 class="card__title mb-3 mt-5">Lista de Teléfonos Registrados</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Teléfono</th>
                            <th>Número de Teléfono</th>
                            <th>ID Empleado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($telefonos)): ?>
                            <?php foreach ($telefonos as $tel): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tel['id_telefono']); ?></td>
                                <td><?php echo htmlspecialchars($tel['numero_telefono']); ?></td>
                                <td><?php echo htmlspecialchars($tel['id_empleado']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No hay teléfonos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
