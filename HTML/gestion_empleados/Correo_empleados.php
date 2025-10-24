<?php
session_start();
require_once '../conexion.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Manejo de formulario: insertar nuevo correo (POST)
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_correo = isset($_POST['id_correo']) ? intval($_POST['id_correo']) : 0; // opcional
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;

    if ($correo === '' || $id_empleado <= 0) {
        $mensaje = '<div class="alert alert-danger">Complete todos los campos correctamente.</div>';
    } else {
        $conn = conectar();
        // Insert condicional: si se proporciona id_correo, lo incluimos explícitamente
        if ($id_correo > 0) {
            $stmt = $conn->prepare("INSERT INTO correos_empleado (id_correo, id_empleado, direccion_correo) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('iis', $id_correo, $id_empleado, $correo);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO correos_empleado (id_empleado, direccion_correo) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param('is', $id_empleado, $correo);
            }
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Correo guardado correctamente.</div>';
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

// Obtener correos registrados
function obtenerCorreos() {
    $conn = conectar();
    $sql = "SELECT id_correo, id_empleado, direccion_correo FROM correos_empleado ORDER BY id_correo";
    $resultado = $conn->query($sql);
    $correos = [];

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $correos[] = $fila;
        }
    }

    desconectar($conn);
    return $correos;
}

$correos = obtenerCorreos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Correos de Empleados</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/gestion_empleados.css">
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">Gestión de Correos</h1>
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
            </ul>
        </div>
    </header>

    <main class="container my-4">
        <section class="card shadow p-4">
            <?php if (!empty($mensaje)) { echo $mensaje; } ?>
            <h2 class="card__title text-primary mb-4">Formulario de Correos</h2>

            <form class="row g-3" method="post" action="">
                <div class="col-md-3">
                    <label class="form-label" for="id_correo">ID Correo:</label>
                    <input type="number" class="form-control" id="id_correo" name="id_correo" placeholder="2011">
                   
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="correo">Correo:</label>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="ejemplo@dominio.com" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="id_empleado">ID Empleado:</label>
                    <input type="number" class="form-control" id="id_empleado" name="id_empleado" placeholder="Ej. 1" required>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="reset" class="btn btn-secondary">Limpiar</button>
                </div>
            </form>

            <h2 class="card__title mb-3 mt-5">Lista de Correos Registrados</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Correo</th>
                            <th>Correo</th>
                            <th>ID Empleado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($correos)): ?>
                            <?php foreach ($correos as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['id_correo']); ?></td>
                                <td><?php echo htmlspecialchars($c['direccion_correo']); ?></td>
                                <td><?php echo htmlspecialchars($c['id_empleado']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">No hay correos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
