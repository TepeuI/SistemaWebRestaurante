<?php
session_start();
require_once '../conexion.php';

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../login.php');
    exit();
}

// --- Manejo del formulario (INSERTAR) ---
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_departamento = !empty($_POST['id_departamento']) ? intval($_POST['id_departamento']) : null;
    $nombre_select = trim($_POST['nombre_select'] ?? '');
    $nombre_otro = trim($_POST['nombre_otro'] ?? '');
    $nombre = ($nombre_select === 'Otro') ? $nombre_otro : $nombre_select;

    if ($nombre === '') {
        $mensaje = '<div class="alert alert-danger">Seleccione o ingrese el nombre del departamento.</div>';
    } else {
        $conn = conectar();

        if ($id_departamento !== null) {
            // Si el usuario quiere insertar con ID específico
            $sql = "INSERT INTO departamentos (id_departamento, nombre) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('is', $id_departamento, $nombre);
        } else {
            // Si desea que el ID sea AUTO_INCREMENT
            $sql = "INSERT INTO departamentos (nombre) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $nombre);
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Departamento guardado correctamente.</div>';
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

// --- Obtener departamentos registrados ---
function obtenerDepartamentos() {
    $conn = conectar();
    $sql = "SELECT id_departamento, nombre FROM departamentos ORDER BY id_departamento";
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
    <title>Gestión de Departamentos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/bootstrap.min.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/diseñoModulos.css">
    <link rel="stylesheet" href="/SistemaWebRestaurante/css/gestion_empleados.css">
    <script>
        // Mostrar u ocultar el campo "Otro" dinámicamente
        document.addEventListener('DOMContentLoaded', () => {
            const select = document.getElementById('nombre_select');
            const divOtro = document.getElementById('div_nombre_otro');

            select.addEventListener('change', () => {
                if (select.value === 'Otro') {
                    divOtro.style.display = 'block';
                } else {
                    divOtro.style.display = 'none';
                    document.getElementById('nombre_otro').value = '';
                }
            });
        });
    </script>
</head>
<body>
    <header class="mb-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between py-3">
            <h1 class="mb-0">Gestión de Departamentos</h1>
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a href="../menu_empleados.php" class="nav-link">Regresar al Menú</a></li>
            </ul>
        </div>
    </header>

    <main class="container my-4">
        <section class="card shadow p-4">
            <?php if (!empty($mensaje)) echo $mensaje; ?>

            <h2 class="card__title text-primary mb-4">Formulario de Departamentos</h2>

            <form class="row g-3" method="post" action="">
                <div class="col-md-3">
                    <label class="form-label" for="id_departamento">ID Departamento (opcional):</label>
                    <input type="number" class="form-control" id="id_departamento" name="id_departamento" placeholder="Auto-Increment si se deja vacío">
                    <small class="text-muted">Dejar vacío para asignar automáticamente el ID.</small>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="nombre_select">Departamento:</label>
                    <select id="nombre_select" name="nombre_select" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <option value="Guatemala">Guatemala</option>
                        <option value="Zacapa">Zacapa</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div class="col-md-5" id="div_nombre_otro" style="display:none;">
                    <label class="form-label" for="nombre_otro">Nombre (Otro):</label>
                    <input type="text" class="form-control" id="nombre_otro" name="nombre_otro" placeholder="Nombre del departamento">
                </div>

                <div class="col-12 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="reset" class="btn btn-secondary">Limpiar</button>
                </div>
            </form>

            <h2 class="card__title mb-3 mt-5">Lista de Departamentos</h2>
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Departamento</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($departamentos)): ?>
                            <?php foreach ($departamentos as $d): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($d['id_departamento']); ?></td>
                                    <td><?php echo htmlspecialchars($d['nombre']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center">No hay departamentos registrados</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
